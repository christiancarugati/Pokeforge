<?php
$conn = new mysqli("localhost", "root", "", "poke_forge");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Funzione per inserire i tipi
function insertTipi($conn, $types) {
    $stmt = $conn->prepare("INSERT IGNORE INTO Tipi (nome, icona_url) VALUES (?, ?)");
    foreach ($types as $type) {
        $stmt->bind_param("ss", $type['name'], $type['url']);
        $stmt->execute();
    }
    $stmt->close();
}

// Funzione per inserire i Pokémon
function insertPokeEsistenti($conn, $pokemons) {
    $stmt = $conn->prepare("INSERT IGNORE INTO Poke_Esistenti (ID, nome, tipo1, tipo2, sprite_url) VALUES (?, ?, ?, ?, ?)");
    foreach ($pokemons as $pokemon) {
        $stmt->bind_param("issss", $pokemon['id'], $pokemon['name'], $pokemon['type1'], $pokemon['type2'], $pokemon['sprite']);
        $stmt->execute();
    }
    $stmt->close();
}

// Funzione per inserire le mosse
function insertMosse($conn, $moves) {
    $stmt = $conn->prepare("INSERT IGNORE INTO Mosse (nome, tipo, potenza, precisione, categoria) VALUES (?, ?, ?, ?, ?)");
    foreach ($moves as $move) {
        $stmt->bind_param("ssiis", $move['name'], $move['type'], $move['power'], $move['accuracy'], $move['category']);
        $stmt->execute();
    }
    $stmt->close();
}

// Funzione per ottenere i tipi dal sito PokeAPI
function getTypesFromApi($start = 0, $limit = 20) {
    $url = "https://pokeapi.co/api/v2/type?offset=$start&limit=$limit";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    $types = [];
    foreach ($data['results'] as $type) {
        $types[] = ['name' => $type['name'], 'url' => "https://pokeapi.co/media/type-icons/" . $type['name'] . ".png"];
    }
    return $types;
}

// Funzione per ottenere i Pokémon dal sito PokeAPI
function getPokemonsFromApi($start = 1, $limit = 50) {
    $pokemons = [];
    for ($i = $start; $i < $start + $limit; $i++) {
        $url = "https://pokeapi.co/api/v2/pokemon/$i/";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data) {
            $types = $data['types'];
            $type1 = $types[0]['type']['name'];
            $type2 = isset($types[1]) ? $types[1]['type']['name'] : null;
            $pokemons[] = [
                'id' => $data['id'],
                'name' => $data['name'],
                'type1' => $type1,
                'type2' => $type2,
                'sprite' => $data['sprites']['front_default']
            ];
        }
    }
    return $pokemons;
}

// Funzione per ottenere le mosse dal sito PokeAPI
function getMovesFromApi($start = 1, $limit = 50) {
    $moves = [];
    for ($i = $start; $i < $start + $limit; $i++) {
        $url = "https://pokeapi.co/api/v2/move/$i/";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data) {
            $moves[] = [
                'name' => $data['name'],
                'type' => $data['type']['name'],
                'power' => isset($data['power']) ? $data['power'] : null,
                'accuracy' => isset($data['accuracy']) ? $data['accuracy'] : null,
                'category' => $data['damage_class']['name']
            ];
        }
    }
    return $moves;
}

// Carica i dati
$types = getTypesFromApi(0, 20);  // Carica i primi 20 tipi
$pokemons = getPokemonsFromApi(999, 50);  // Carica i primi 50 Pokémon
$moves = getMovesFromApi(939, 50);  // Carica le prime 50 mosse (ID da 1 a 50)

// Inserisci i dati nel database
insertTipi($conn, $types);
insertPokeEsistenti($conn, $pokemons);
insertMosse($conn, $moves);

echo "Primi 50 Pokémon, 50 mosse e 20 tipi caricati con successo nel database.";

$conn->close();
?>

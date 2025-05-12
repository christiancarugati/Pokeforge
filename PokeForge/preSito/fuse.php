<?php
$conn = new mysqli("localhost", "root", "", "poke_forge");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/** Funzione per inserire la relazione tra Pokémon e mosse */
function insertPokemonMosse($conn, $pokemonId, $moveIds) {
    $stmt = $conn->prepare("INSERT IGNORE INTO Pokemon_Mosse (ID_Pokemon, ID_Mossa) VALUES (?, ?)");
    foreach ($moveIds as $moveId) {
        $stmt->bind_param("ii", $pokemonId, $moveId);
        $stmt->execute();
    }
    $stmt->close();
}

/** Funzione per ottenere le mosse di un Pokémon tramite la PokeAPI */
function getPokemonMoves($pokemonId) {
    $url = "https://pokeapi.co/api/v2/pokemon/$pokemonId/";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    $moveNames = [];
    if (isset($data['moves'])) {
        foreach ($data['moves'] as $move) {
            $moveNames[] = $move['move']['name'];  // otteniamo il nome della mossa
        }
    }
    return $moveNames;
}

/** Funzione per ottenere l'ID della mossa dalla tabella Mosse nel database */
function getMoveIdByName($conn, $moveName) {
    $stmt = $conn->prepare("SELECT ID FROM Mosse WHERE LOWER(nome) = LOWER(?)");
    $stmt->bind_param("s", $moveName);
    $stmt->execute();

    $moveId = null; // Inizializzazione PRIMA del bind_result
    $stmt->bind_result($moveId);

    if ($stmt->fetch()) {
        $stmt->close();
        return $moveId;
    } else {
        $stmt->close();
        echo "⚠ Mossa non trovata: $moveName<br>";
        return null;
    }
}


/** Funzione per associare le mosse ai Pokémon */
function associatePokemonMoves($conn) {
    $result = $conn->query("SELECT ID FROM Poke_Esistenti");

    while ($row = $result->fetch_assoc()) {
        $pokemonId = $row['ID'];

        // Ottieni i nomi delle mosse tramite API
        $moveNames = getPokemonMoves($pokemonId);

        $moveIds = [];
        foreach ($moveNames as $moveName) {
            $moveId = getMoveIdByName($conn, $moveName);
            if ($moveId !== null) {
                $moveIds[] = $moveId;
            }
        }

        // Inserisci le relazioni
        insertPokemonMosse($conn, $pokemonId, $moveIds);
    }
}

associatePokemonMoves($conn);

echo "Le mosse sono state correttamente collegate ai Pokémon nel database.";

$conn->close();
?>

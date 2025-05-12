<?php
set_time_limit(0);

$conn = new mysqli("localhost", "root", "", "poke_forge");
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Prendi l'intervallo da caricare via URL
$start = isset($_GET['start']) ? intval($_GET['start']) : 1;
$end = isset($_GET['end']) ? intval($_GET['end']) : $start + 99;

// Query per ottenere i Pokémon nell'intervallo
$query = "SELECT ID FROM Poke_Esistenti WHERE ID BETWEEN $start AND $end ORDER BY ID ASC";
$result = $conn->query($query);

if (!$result) {
    die("Errore nella query: " . $conn->error);
}

// Per ogni Pokémon, carica e inserisci le abilità
while ($row = $result->fetch_assoc()) {
    $pokemonId = $row['ID'];
    echo "Caricamento abilità per Pokémon ID: $pokemonId...<br>";

    $url = "https://pokeapi.co/api/v2/pokemon/$pokemonId";
    $response = file_get_contents($url);

    if ($response === FALSE) {
        echo "Errore nel recupero dati per ID: $pokemonId<br>";
        continue;
    }

    $data = json_decode($response, true);
    if (!isset($data['abilities'])) {
        echo "Nessuna abilità trovata per ID: $pokemonId<br>";
        continue;
    }

    foreach ($data['abilities'] as $abilityEntry) {
        $abilityName = $abilityEntry['ability']['name'];

        // Inserisci solo se non esiste già
        $stmt = $conn->prepare("INSERT IGNORE INTO Pokemon_Abilita (ID_Pokemon, nome_abilita) VALUES (?, ?)");
        $stmt->bind_param("is", $pokemonId, $abilityName);
        $stmt->execute();
        $stmt->close();
    }

    flush();
    ob_flush();
}

echo "<br><strong>Caricamento completato per i Pokémon da $start a $end.</strong>";

$conn->close();
?>

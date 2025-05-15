<?php

require_once("conn.php"); // Include il file di connessione al database

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$utente_id = $_SESSION['user_id'];
$team_id = $_POST['team_id'] ?? null;
$pokemons = $_POST['pokemon'] ?? [];

function cleanString($str) {
    return substr(trim($str), 0, 50);
}

if (empty($team_id)) {
    // Crea nuovo team
    $nome_team = "Nuovo Team";
    $stmt = $conn->prepare("INSERT INTO Team (nomeTeam, ID_Utente) VALUES (?, ?)");
    $stmt->bind_param("si", $nome_team, $utente_id);
    if (!$stmt->execute()) {
        die("Errore durante la creazione del team.");
    }
    $team_id = $stmt->insert_id;
    $stmt->close();
} else {
    // Verifica che il team appartenga all'utente
    $stmt = $conn->prepare("SELECT ID FROM Team WHERE ID = ? AND ID_Utente = ?");
    $stmt->bind_param("ii", $team_id, $utente_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        die("Accesso negato: questo team non ti appartiene.");
    }
    $stmt->close();

    // Elimina i Pokémon esistenti nel team (reset squadra)
    $stmt = $conn->prepare("DELETE FROM Poke_Team WHERE ID_Team = ?");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $stmt->close();
}

// Inserisci membri team
$stmt = $conn->prepare("INSERT INTO Poke_Team (ID_Team, ID_Pokemon, abilita, natura, oggetto, sprite, mossa1, mossa2, mossa3, mossa4) VALUES (?, ?, ?, ?, ?, '', ?, ?, ?, ?)");

foreach ($pokemons as $slot) {
    $id_pokemon = isset($slot['ID_Pokemon']) && is_numeric($slot['ID_Pokemon']) ? (int)$slot['ID_Pokemon'] : null;
    if (!$id_pokemon) {
        // Slot vuoto, salta
        continue;
    }
    $abilita = cleanString($slot['abilita'] ?? '');
    $natura = cleanString($slot['natura'] ?? '');
    $oggetto = cleanString($slot['oggetto'] ?? '');

    $mossa1 = isset($slot['mossa1']) && is_numeric($slot['mossa1']) ? (int)$slot['mossa1'] : null;
    $mossa2 = isset($slot['mossa2']) && is_numeric($slot['mossa2']) ? (int)$slot['mossa2'] : null;
    $mossa3 = isset($slot['mossa3']) && is_numeric($slot['mossa3']) ? (int)$slot['mossa3'] : null;
    $mossa4 = isset($slot['mossa4']) && is_numeric($slot['mossa4']) ? (int)$slot['mossa4'] : null;

    $stmt->bind_param("iisssiiii",
        $team_id,
        $id_pokemon,
        $abilita,
        $natura,
        $oggetto,
        $mossa1,
        $mossa2,
        $mossa3,
        $mossa4
    );
    if (!$stmt->execute()) {
        die("Errore durante il salvataggio del Pokémon nel team.");
    }
}

$stmt->close();

header("Location: index.php?msg=" . urlencode("Team salvato correttamente."));
exit;
?>

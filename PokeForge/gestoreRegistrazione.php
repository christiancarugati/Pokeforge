<?php
require_once("conn.php"); // Include il file di connessione al database

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Location: registrazione.php");
    exit;
}

$username = trim($_GET['username'] ?? '');
$email = trim($_GET['email'] ?? '');
$password = $_GET['password'] ?? '';
$password_confirm = $_GET['password_confirm'] ?? '';

function redirectWithMsg($msg) {
    header("Location: registrazione.php?msg=" . urlencode($msg));
    exit;
}

if (!$username || !$email || !$password || !$password_confirm) {
    redirectWithMsg("Compila tutti i campi.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithMsg("Email non valida.");
}
if ($password !== $password_confirm) {
    redirectWithMsg("Le password non coincidono.");
}
if (strlen($password) < 6) {
    redirectWithMsg("La password deve contenere almeno 6 caratteri.");
}


$stmt = $conn->prepare("SELECT ID FROM Utenti WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();
    redirectWithMsg("Email già registrata.");
}
$stmt->close();

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO Utenti (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $password_hash);
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    redirectWithMsg("Registrazione avvenuta con successo! Ora puoi effettuare il login.");
} else {
    $stmt->close();
    $conn->close();
    redirectWithMsg("Errore durante la registrazione.");
}

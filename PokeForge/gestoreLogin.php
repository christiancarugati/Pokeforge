<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Location: login.php");
    exit;
}

$email = trim($_GET['email'] ?? '');
$password = $_GET['password'] ?? '';

function redirectWithMsg($msg) {
    header("Location: login.php?msg=" . urlencode($msg));
    exit;
}

if (!$email || !$password) {
    redirectWithMsg("Compila entrambi i campi.");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithMsg("Email non valida.");
}

$conn = new mysqli("localhost", "root", "", "poke_forge");
if ($conn->connect_error) {
    redirectWithMsg("Errore di connessione al database.");
}

$stmt = $conn->prepare("SELECT ID, password, username FROM Utenti WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['ID'];
    $_SESSION['username'] = $user['username'];
    $stmt->close();
    $conn->close();
    header("Location: index.php");
    exit;
} else {
    $stmt->close();
    $conn->close();
    redirectWithMsg("Email o password errati.");
}

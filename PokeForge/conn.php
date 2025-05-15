<?php
$conn = new mysqli("localhost", "root", "", "gltfcrgb_wp663");
if ($conn->connect_error) {
    redirectWithMsg("Errore di connessione al database.");
}
?>
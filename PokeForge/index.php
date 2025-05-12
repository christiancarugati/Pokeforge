<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$conn = new mysqli("localhost", "root", "", "poke_forge");
if ($conn->connect_error) {
    die("Connection failed: ".$conn->connect_error);
}
$stmt = $conn->prepare("SELECT ID, nomeTeam FROM Team WHERE ID_Utente = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$teams = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1" />
<title>PokeForge - Pagina Principale</title>
<style>
body { font-family: Arial, sans-serif; background:#f0f0f0; display:flex; flex-direction: column; align-items:center; justify-content:center; height:100vh; margin:0;}
.container { background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px #ccc; width:300px; text-align:center;}
button, select { width: 100%; padding: 10px; margin: 10px 0; border-radius:5px; border:1px solid #ccc;}
button { background:#cc0000; color:white; border:none; cursor:pointer;}
button:hover { background:#a30000;}
</style>
</head>
<body>
<div class="container">
<h2>PokeForge</h2>
<h3>Benvenuto, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
<form method="get" action="team.php">
    <button type="submit">Crea Nuovo Team</button>
</form>
<h4>Oppure scegli un team esistente:</h4>
<form method="get" action="team.php">
    <select name="team_id">
        <option value="">Seleziona un team</option>
        <?php foreach ($teams as $team): ?>
            <option value="<?php echo $team['ID']; ?>"><?php echo htmlspecialchars($team['nomeTeam']); ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Usa Team Selezionato</button>
</form>
<form method="get" action="logout.php">
    <button type="submit">Logout</button>
</form>
</div>
</body>
</html>

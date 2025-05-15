<?php 
require_once("conn.php"); // Include il file di connessione al database

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// GESTIONE RINOMINA TEAM - MODIFICA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_team_id'], $_POST['new_name'])) {
    $rename_team_id = intval($_POST['rename_team_id']);
    $new_name = trim($_POST['new_name']);
    if ($new_name !== '') {
        $stmt = $conn->prepare("UPDATE Team SET nomeTeam = ? WHERE ID = ? AND ID_Utente = ?");
        $stmt->bind_param("sii", $new_name, $rename_team_id, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        // Redirect per evitare reinvio form con refresh
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

$stmt = $conn->prepare("SELECT ID, nomeTeam FROM Team WHERE ID_Utente = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$teams = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

// Recupero team selezionato (da GET) per mostrare form rinomina
$selected_team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1" />
<title>PokeForge - Pagina Principale</title>
<style>
body { font-family: Arial, sans-serif; background:#f0f0f0; display:flex; flex-direction: column; align-items:center; justify-content:center; height:100vh; margin:0;}
.container { background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px #ccc; width:300px; text-align:center;}
button, select, input[type=text] { width: 100%; padding: 10px; margin: 10px 0; border-radius:5px; border:1px solid #ccc;}
button { background:#cc0000; color:white; border:none; cursor:pointer;}
button:hover { background:#a30000;}
.rename-form { margin-top: 10px; text-align:left;}
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
<form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <select name="team_id" onchange="this.form.submit()">
        <option value="">Seleziona un team</option>
        <?php foreach ($teams as $team): ?>
            <option value="<?php echo $team['ID']; ?>" <?php if($team['ID'] === $selected_team_id) echo 'selected'; ?>>
                <?php echo htmlspecialchars($team['nomeTeam']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <noscript><button type="submit">Usa Team Selezionato</button></noscript>
</form>

<?php if ($selected_team_id > 0): ?>
    <form method="post" class="rename-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <input type="hidden" name="rename_team_id" value="<?php echo $selected_team_id; ?>" />
        <input type="text" name="new_name" placeholder="Nuovo nome team" required />
        <button type="submit">Rinomina Team</button>
    </form>
<?php endif; ?>

<form method="get" action="team.php" style="margin-top:20px;">
    <input type="hidden" name="team_id" value="<?php echo $selected_team_id; ?>" />
    <button type="submit" <?php if($selected_team_id === 0) echo 'disabled'; ?>>Usa Team Selezionato</button>
</form>

<form method="get" action="logout.php">
    <button type="submit">Logout</button>
</form>
</div>
</body>
</html>

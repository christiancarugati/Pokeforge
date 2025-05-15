<?php

session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$msg = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Registrazione - PokeForge</title>
<style>
/* Stili semplici */
body { font-family: Arial,sans-serif; background:#f0f0f0; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;}
form { background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px #ccc; width: 320px;}
input, button { width: 100%; padding:10px; margin:8px 0; box-sizing:border-box; }
button { background:#cc0000; color:#fff; border:none; cursor:pointer;}
button:hover { background:#a30000;}
.msg { color:red; text-align:center; margin-bottom:10px;}
</style>
</head>
<body>
<form method="get" action="gestoreRegistrazione.php" autocomplete="off" novalidate>
    <h2>Registrazione</h2>
    <?php if ($msg): ?>
        <div class="msg"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <input type="text" name="username" placeholder="Nome utente" required maxlength="50" autofocus />
    <input type="email" name="email" placeholder="Email" required maxlength="100" />
    <input type="password" name="password" placeholder="Password" required minlength="6" />
    <input type="password" name="password_confirm" placeholder="Conferma Password" required minlength="6" />
    <button type="submit">Registrati</button>
    <p style="text-align:center; margin-top:10px;">
        Hai già un account? <a href="login.php">Accedi qui</a>
    </p>
</form>
</body>
</html>

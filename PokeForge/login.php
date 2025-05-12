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
<title>Login - PokeForge</title>
<style>
body { font-family: Arial,sans-serif; background:#f0f0f0; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;}
form { background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px #ccc; width:320px;}
input, button { width: 100%; padding:10px; margin:8px 0; box-sizing:border-box; }
button { background:#cc0000; color:#fff; border:none; cursor:pointer;}
button:hover { background:#a30000;}
.msg { color:red; text-align:center; margin-bottom:10px;}
</style>
</head>
<body>
<form method="get" action="gestoreLogin.php" autocomplete="off" novalidate>
    <h2>Login</h2>
    <?php if ($msg): ?>
        <div class="msg"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <input type="email" name="email" placeholder="Email" required maxlength="100" autofocus />
    <input type="password" name="password" placeholder="Password" required minlength="6" />
    <button type="submit">Accedi</button>
    <p style="text-align:center; margin-top:10px;">
        Non hai un account? <a href="registrazione.php">Registrati qui</a>
    </p>
</form>
</body>
</html>

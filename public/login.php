<?php
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Pagnany\Kcp\Auth\LoginHandler;

// Simple login processing (for demonstration only, don't use in production)
$loginMessage = '';
$loginHandler = new LoginHandler();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $loginHandler->processLogin($username, $password);
    
    if ($result['success']) {
        header('Location: /index.php');
        exit;
    } else {
        $loginMessage = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/style.css">
    <title>KCP - Login</title>
</head>
<body>
    <header class="site-header">
        <h1 class="site-title"><a href="/index.php">Kegelclub Pegelbrüder</a></h1>
    </header> 
    <h2>Login</h2>
    
    <?php if ($loginMessage): ?>
        <p><?= htmlspecialchars($loginMessage) ?></p>
    <?php endif; ?>

    <!-- Simple login form -->
    <form method="POST" action="login.php">
        <label for="username">Benutzername:</label>
        <input type="text" id="username" name="username" required><br><br>
        
        <label for="password">Passwort:</label>
        <input type="password" id="password" name="password" required><br><br>
        
        <button type="submit">Login</button>
    </form>
</body>
</html>

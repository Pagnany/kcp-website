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
        header('Location: /');
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
        <h1 class="site-title"><a href="/">Kegelclub Pegelbrüder</a></h1>
    </header> 

    <h2>Login</h2>
    
    <?php if ($loginMessage): ?>
        <div class="error-message">
            <?= htmlspecialchars($loginMessage) ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" action="login" class="event-form">
            <div class="form-group">
                <label for="username">Benutzername:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Passwort:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="submit-button">Login</button>
            </div>
        </form>
    </div>
</body>
</html>

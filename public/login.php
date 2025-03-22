<?php
require __DIR__ . '/../vendor/autoload.php';  // Adjust path if needed

// Simple login processing (for demonstration only, don't use in production)
$loginMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Simulate a simple check (replace this with real validation in a production app)
    if ($username === 'admin' && $password === 'password') {
        $loginMessage = 'Login successful!';
    } else {
        $loginMessage = 'Invalid username or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    
    <!-- Display the login message if there's any -->
    <?php if ($loginMessage): ?>
        <p><?= htmlspecialchars($loginMessage) ?></p>
    <?php endif; ?>

    <!-- Simple login form -->
    <form method="POST" action="login.php">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>
        
        <button type="submit">Login</button>
    </form>
</body>
</html>

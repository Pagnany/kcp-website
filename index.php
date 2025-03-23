<?php

require __DIR__ . '/vendor/autoload.php';  // Ensure correct path

use Pagnany\Kcp\Test;

$test = new Test();
echo $test->sayHello();

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="public/style.css">
    <title>KCP - Home</title>
</head>
<body>
    <h2>Wilkommen beim Kegelclub Pegelbrüder</h2>

    <!-- Link to the login page -->
    <a href="public/login.php">Login</a>
</body>
</html>

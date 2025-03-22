<?php

require __DIR__ . '/vendor/autoload.php';  // Ensure correct path

use Pagnany\Testo\Test;

$test = new Test();
echo $test->sayHello();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KCP</title>
</head>
<body>
    <h2>Wilkommen beim Kegelclub Pegelbrüder</h2>

    <!-- Link to the login page -->
    <a href="public/login.php">Login</a>
</body>
</html>

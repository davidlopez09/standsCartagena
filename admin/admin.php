<?php
session_start();
require '../app/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if ($user && $password == $user['password']) {

        $_SESSION['admin'] = $user['usuario'];

        header("Location: cpanel.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login Admin</title>
</head>

<body>

    <h2>Panel Administrador</h2>

    <?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>

    <form method="POST">

        <label>Usuario</label>
        <input type="text" name="usuario" required>

        <br><br>

        <label>Contraseña</label>
        <input type="password" name="password" required>

        <br><br>

        <button type="submit">Ingresar</button>

    </form>

</body>

</html>
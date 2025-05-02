<?php
session_start(); // ¡Esto es importante!

$men = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $contrasena = $_POST["contrasena"];

    $conexion = new mysqli("localhost", "root", "", "civilnuevo");
    if ($conexion->connect_error) {
        die("Error en la conexion: " . $conexion->connect_error);
    }

    $stmt = $conexion->prepare("SELECT * FROM tbusuarios WHERE loginUsuario = ? AND contrasena= ?");
    $stmt->bind_param("ss", $usuario, $contrasena);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario_data = $resultado->fetch_assoc();
        $_SESSION['usuario'] = $usuario_data['loginUsuario'];
        $_SESSION['nombre'] = $usuario_data['nombre'];
        $_SESSION['rol'] = isset($usuario_data['rol'])?
            $usuario_data['rol'] : 'trabajador';

        header("Location: presupuesto.php"); // Redireccionamos si es correcto
        exit;
    } else {
        $men = "Usuario o contraseña incorrectos";
    }


    $stmt->close();
    $conexion->close();
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <style>
        body {
            background: #257fda;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: Georgia;
        }
        .formulario {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .formulario h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        }
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            outline: none;
        }
        .form-group input:focus {
            border-color: #0d6efd;
        }
        .form-group label {
            position: absolute;
            top: 10px;
            left: 10px;
            color: #999;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        .form-group input:focus + label,
        .form-group input:valid + label {
            top: -10px;
            left: 10px;
            font-size: 12px;
            background: white;
            padding: 0 5px;
            color: #0d6efd;
        }
        .btn-login {
            width: 100%;
            padding: 10px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-login:hover {
            background-color: #0b5ed7;
        }
        .links {
            margin-top: 1rem;
            text-align: center;
        }
        .alert {
            margin-top: 1rem;
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="formulario">
    <h1>Iniciar Sesión</h1>
    <form method="POST">
        <div class="form-group">
            <input type="text" name="usuario" required>
            <label>Usuario</label>
        </div>
        <div class="form-group">
            <input type="password" name="contrasena" required>
            <label>Contraseña</label>
        </div>
        <button type="submit" class="btn-login">Entrar</button>

        <?php if ($men): ?>
            <div class="alert">
                <p><?php echo $men; ?></p>
            </div>
        <?php endif; ?>
    </form>
</div>
</body>
</html>

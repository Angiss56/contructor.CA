<?php
session_start();

$conexion = new mysqli("localhost", "root", "", "civilnuevo");
if ($conexion->connect_error) {
    die("Error en la conexión: " . $conexion->connect_error);
}

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $presupuesto = $_POST["presupuesto"];
    //guardar el presupuesto en la sesion
    $_SESSION['presupuesto_maximo'] = $presupuesto;

    if ($presupuesto > 0) {
        // Guardar en la base de datos
        $sql = "INSERT INTO tbpresupuesto (presupuesto, fecha_creacion) 
                VALUES ($presupuesto, NOW())";
        $resultado = $conexion->query($sql);

        // Guardar en sesión
        $_SESSION['presupuesto_maximo'] = $presupuesto;
        $_SESSION['presupuesto_actual'] = 0; // Inicializamos el presupuesto gastado

        header("Location: productos.php");
        exit();
    } else {
        $error = "El presupuesto debe ser mayor a 0.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Establecer Presupuesto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .contenedor {
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            width: 400px;
            text-align: center;
        }
        input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        h2 {
            margin-bottom: 20px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="contenedor">
    <h2>Ingrese el presupuesto para la construcción</h2>

    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="number" name="presupuesto" id="presupuesto" step="0.01" min="0" required placeholder="Ingrese el monto en quetzales">
        <button type="submit">Continuar</button>
    </form>
</div>
</body>
</html>





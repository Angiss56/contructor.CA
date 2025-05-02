<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "civilnuevo");
if ($conexion->connect_error) {
    die("Error en la conexión: " . $conexion->connect_error);
}

// Obtener presupuesto máximo desde la sesión (0 significa sin presupuesto límite)
$presupuesto_maximo = isset($_SESSION['presupuesto_maximo']) ? $_SESSION['presupuesto_maximo'] : 0;
$total_actual = isset($_SESSION['presupuesto_actual']) ? $_SESSION['presupuesto_actual'] : 0;

// Procesar formulario POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $producto_id = intval($_POST['producto_id']);
    $cantidad = intval($_POST['cantidad']);

    // Validar que la cantidad sea positiva
    if ($cantidad <= 0) {
        $error = "La cantidad debe ser mayor que cero.";
    } else {
        // Obtener información del producto
        $consulta = $conexion->prepare("SELECT p.*, e.empresa FROM tbproducto p JOIN tbempresa e ON p.idempresa = e.idempresa WHERE p.idproducto = ?");
        $consulta->bind_param("i", $producto_id);
        $consulta->execute();
        $resultado = $consulta->get_result();

        if ($resultado->num_rows > 0) {
            $producto = $resultado->fetch_assoc();
            $precio_total = $producto['precio'] * $cantidad;

            // Validar si el presupuesto es suficiente
            if ($presupuesto_maximo > 0) {
                $presupuesto_restante = $presupuesto_maximo - $total_actual;

                if ($precio_total > $presupuesto_restante) {
                    $error = "No se puede agregar. El costo ($precio_total) excede el presupuesto restante ($presupuesto_restante).";
                } else {
                    // Actualizar inventario
                    $nuevo_inventario = $producto['inventario'] + $cantidad;
                    $actualizar = $conexion->prepare("UPDATE tbproducto SET inventario = ? WHERE idproducto = ?");
                    $actualizar->bind_param("ii", $nuevo_inventario, $producto_id);
                    $actualizar->execute();

                    // Registrar movimiento
                    $fecha = date("Y-m-d H:i:s");
                    $usuario = $_SESSION['usuario'];
                    $consulta_usuario = $conexion->prepare("SELECT IDusuarios FROM tbusuarios WHERE loginUsuario = ?");
                    $consulta_usuario->bind_param("s", $usuario);
                    $consulta_usuario->execute();
                    $resultado_usuario = $consulta_usuario->get_result();
                    $usuario_data = $resultado_usuario->fetch_assoc();
                    $idusuario = $usuario_data['IDusuarios'];

                    $insertar_mov = $conexion->prepare("INSERT INTO tbmovimientos (Entradas, Salidas, Fecha, idproducto, idusuario) VALUES (?, 0, ?, ?, ?)");
                    $insertar_mov->bind_param("isii", $cantidad, $fecha, $producto_id, $idusuario);
                    $insertar_mov->execute();

                    // Actualizar el presupuesto actual
                    $_SESSION['presupuesto_actual'] += $precio_total;

                    // Preparar mensaje de éxito
                    $mensaje = "Inventario actualizado correctamente";
                    $producto_nombre = htmlspecialchars($producto['descripcion']);
                    $empresa_nombre = htmlspecialchars($producto['empresa']);

                    // Verificar si estamos cerca del presupuesto (por ejemplo, 90%)
                    $porcentaje_usado = ($_SESSION['presupuesto_actual'] / $presupuesto_maximo) * 100;
                    if ($porcentaje_usado >= 90 && $porcentaje_usado < 100) {
                        $advertencia = "Advertencia: Has utilizado más del 90% de tu presupuesto.";
                    } elseif ($porcentaje_usado >= 100) {
                        $advertencia = "Has alcanzado o superado el presupuesto máximo establecido.";
                    }
                }
            } else {
                $error = "No se ha establecido un presupuesto máximo.";
            }
        } else {
            $error = "Producto no encontrado.";
        }
    }
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Inventario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .info-box {
            background: #e2e3e5;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
        }
        .btn-primary {
            background: #007bff;
        }
        .btn-success {
            background: #28a745;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Gestión de Inventario</h2>

    <?php if (isset($mensaje)): ?>
        <div class="alert success">
            <h3>Operación Exitosa</h3>
            <div class="info-box">
                <p><strong>Producto:</strong> <?php echo $producto_nombre; ?></p>
                <p><strong>Empresa:</strong> <?php echo $empresa_nombre; ?></p>
                <p><strong>Cantidad agregada:</strong> <?php echo $cantidad; ?></p>
            </div>
            <p><?php echo $mensaje; ?></p>

            <?php if (isset($advertencia)): ?>
                <div class="alert warning">
                    <p><?php echo $advertencia; ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <a href="historial_inventario.php" class="btn btn-primary">Ver Historial</a>
            <a href="productos.php" class="btn btn-success">Volver a Productos</a>
        </div>

    <?php elseif (isset($error)): ?>
        <div class="alert error">
            <h3>Error</h3>
            <p><?php echo $error; ?></p>
        </div>
        <a href="productos.php" class="btn btn-success">Volver a Productos</a>

    <?php else: ?>
        <div class="alert error">
            <h3>Acceso no autorizado</h3>
            <p>Debe acceder a esta página a través del formulario correspondiente.</p>
        </div>
        <a href="productos.php" class="btn btn-success">Ir a Productos</a>
    <?php endif; ?>

    <div style="margin-top: 20px; font-size: 0.9em; color: #666;">
        Sistema de Inventario - <?php echo date('d/m/Y H:i:s'); ?>
    </div>
</div>
</body>
</html>



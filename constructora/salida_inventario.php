<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Conectar a base de datos
$conexion = new mysqli("localhost", "root", "", "civilnuevo");
if ($conexion->connect_error) {
    die("Error en la conexión: " . $conexion->connect_error);
}

// Procesar envío
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $producto_id = intval($_POST['producto_id']);
    $cantidad = intval($_POST['cantidad']);

    // Consultamos el inventario del producto
    $consulta = $conexion->prepare("SELECT * FROM tbproducto WHERE idproducto = ?");
    $consulta->bind_param("i", $producto_id);
    $consulta->execute();
    $resultado = $consulta->get_result();

    if ($resultado->num_rows > 0) {
        $producto = $resultado->fetch_assoc();

        // Validar si la cantidad solicitada no es mayor al inventario disponible
        if ($cantidad <= 0) {
            $error = "La cantidad debe ser mayor que cero.";
        } elseif ($cantidad > $producto['inventario']) {
            $error = "No puedes retirar más de lo que tienes en inventario. Cantidad disponible: " . $producto['inventario'];
        } else {
            // Realizar la salida
            $nuevo_inventario = $producto['inventario'] - $cantidad;

            // Actualizar inventario
            $actualizar = $conexion->prepare("UPDATE tbproducto SET inventario = ? WHERE idproducto = ?");
            $actualizar->bind_param("ii", $nuevo_inventario, $producto_id);
            $actualizar->execute();

            // Obtener usuario
            $fecha = date("Y-m-d H:i:s");
            $usuario = $_SESSION['usuario'];
            $consulta_usuario = $conexion->prepare("SELECT IDusuarios FROM tbusuarios WHERE loginUsuario = ?");
            $consulta_usuario->bind_param("s", $usuario);
            $consulta_usuario->execute();
            $resultado_usuario = $consulta_usuario->get_result();
            $idusuario = $resultado_usuario->fetch_assoc()['IDusuarios'];

            // Registrar movimiento
            $mov = $conexion->prepare("INSERT INTO tbmovimientos (Entradas, Salidas, Fecha, idproducto, idusuario) VALUES (0, ?, ?, ?, ?)");
            $mov->bind_param("isii", $cantidad, $fecha, $producto_id, $idusuario);
            $mov->execute();

            $mensaje = "Salida registrada correctamente";
            $producto_nombre = htmlspecialchars($producto['descripcion']);
        }
    } else {
        $error = "Producto no encontrado.";
    }
}

// Obtener productos con inventario disponible
$productos = $conexion->query("SELECT idproducto, descripcion, inventario FROM tbproducto WHERE inventario > 0 ORDER BY descripcion");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Salida de Inventario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 25px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #333;
        }
        input[type="number"], select {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .btn {
            display: inline-block;
            padding: 10px 16px;
            background: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover {
            background: #1a252f;
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Salida de Inventario</h1>

    <?php if (isset($mensaje)): ?>
        <div class="alert success">
            <strong>¡Éxito!</strong> <?php echo $mensaje; ?><br>
            Producto: <strong><?php echo $producto_nombre; ?></strong><br>
            Cantidad retirada: <strong><?php echo $cantidad; ?></strong>
        </div>
        <a href="productos.php" class="btn">Volver a Productos</a>
        <a href="historial_inventario.php" class="btn">Ver Historial</a>

    <?php elseif (isset($error)): ?>
        <div class="alert error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="producto_id">Producto:</label>
            <select name="producto_id" id="producto_id" required>
                <option value="">Seleccione un producto</option>
                <?php while ($row = $productos->fetch_assoc()): ?>
                    <option value="<?php echo $row['idproducto']; ?>" data-max="<?php echo $row['inventario']; ?>">
                        <?php echo htmlspecialchars($row['descripcion']); ?> (Disponible: <?php echo $row['inventario']; ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="cantidad">Cantidad a retirar:</label>
            <input type="number" name="cantidad" id="cantidad" min="1" required>
        </div>

        <button type="submit" class="btn">Registrar Salida</button>
        <a href="productos.php" class="btn">Cancelar</a>
    </form>
</div>

<script>
    // Actualizar el valor máximo del campo cantidad al seleccionar un producto
    document.getElementById('producto_id').addEventListener('change', function () {
        var productoSelect = this;
        var cantidadInput = document.getElementById('cantidad');
        var selectedOption = productoSelect.options[productoSelect.selectedIndex];
        var maxCantidad = selectedOption.getAttribute('data-max');

        // Limitar la cantidad máxima según el inventario del producto seleccionado
        cantidadInput.setAttribute('max', maxCantidad);
        cantidadInput.value = ""; // Resetear el valor de cantidad cuando se cambia el producto
    });
</script>

</body>
</html>

<?php $conexion->close(); ?>


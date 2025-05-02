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

// Consulta para obtener el historial
$sql = "SELECT m.IDmovimientos, m.Entradas, m.Salidas, m.Fecha, 
               p.descripcion AS producto, 
               u.nombre AS usuario, 
               e.empresa
        FROM tbmovimientos m
        JOIN tbproducto p ON m.idproducto = p.idproducto
        JOIN tbusuarios u ON m.idusuario = u.IDusuarios
        JOIN tbempresa e ON p.idempresa = e.idempresa
        ORDER BY 
                case
                when m.Entradas> 0 THEN 1
                WHEN M.Salidas> 0 THEN 2
                ELSE 3
                END, 
            m.fecha DESC";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Inventario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 5px;
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #2c3e50;
            color: white;
            position: sticky;
            top: 0;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .entrada {
            color: #28a745;
            font-weight: bold;
        }
        .salida {
            color: #dc3545;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 10px 5px;
            text-decoration: none;
            border-radius: 4px;
            background: #2c3e50;
            color: white;
        }
        .btn:hover {
            background: #1a252f;
        }
        .filtros {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .total {
            font-weight: bold;
            margin-top: 20px;
            text-align: right;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Historial de Movimientos de Inventario</h1>

    <div class="filtros">
        <form method="get" action="">
            <label for="fecha">Filtrar por fecha:</label>
            <input type="date" id="fecha" name="fecha">
            <button type="submit" class="btn">Filtrar</button>
            <a href="historial_inventario.php" class="btn">Limpiar</a>
        </form>
    </div>

    <table>
        <thead>
        <tr>
            <th>Fecha y Hora</th>
            <th>Usuario</th>
            <th>Producto</th>
            <th>Empresa</th>
            <th>Entradas</th>
            <th>Salidas</th>
        </tr>
        </thead>
        <tbody>
        <?php
        if ($resultado->num_rows > 0) {
            while($movimiento = $resultado->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($movimiento['Fecha']) . "</td>";
                echo "<td>" . htmlspecialchars($movimiento['usuario']) . "</td>";
                echo "<td>" . htmlspecialchars($movimiento['producto']) . "</td>";
                echo "<td>" . htmlspecialchars($movimiento['empresa']) . "</td>";
                echo "<td class='entrada'>" . ($movimiento['Entradas'] > 0 ? "+" . $movimiento['Entradas'] : "-") . "</td>";
                echo "<td class='salida'>" . ($movimiento['Salidas'] > 0 ? "-" . $movimiento['Salidas'] : "-") . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No hay movimientos registrados</td></tr>";
        }
        ?>
        </tbody>
    </table>

    <div class="total">
        <a href="productos.php" class="btn">Volver a Productos</a>
    </div>
</div>
</body>
</html>

<?php
$conexion->close();
?>

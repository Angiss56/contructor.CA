<?php
session_start();
$logueado = isset($_SESSION['usuario']);

$cone = new mysqli("localhost", "root", "", "civilnuevo");
if ($cone->connect_error) {
    die("Error en la conexión: " . $cone->connect_error);
}

//obtener el presupuesto maximo desde la sesion
$presupuesto_maximo = isset($_SESSION['presupuesto_maximo']) ? $_SESSION['presupuesto_maximo'] : 0;

//Bucar productos
$busqueda = "";
$hayBusqueda = false;

if (isset($_GET['buscar']) && trim($_GET['buscar']) !== "") {
    $busqueda = $cone->real_escape_string($_GET['buscar']);
    $sqlBusqueda = "SELECT p.*, e.empresa 
                    FROM tbproducto p 
                    JOIN tbempresa e ON p.idempresa = e.idempresa 
                    WHERE p.descripcion LIKE '%$busqueda%'
                    and p.tipo is not null
                    ORDER BY p.tipo,p.precio,p.descripcion ASC";
    $resultadoBusqueda = $cone->query($sqlBusqueda);
    $hayBusqueda = true;
} else {
    // Si no hay búsqueda, cargar por empresa
    $epa = $cone->query("SELECT * FROM tbproducto WHERE idempresa = 1");
    $novex = $cone->query("SELECT * FROM tbproducto WHERE idempresa = 2");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos</title>
    <style>
        body {
            font-family: Arial;
            background: #f0f4f8;
            padding: 20px;
        }
        h2 {
            color: #257fda;
            border-bottom: 2px solid #257fda;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            background: white;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #257fda;
            color: white;
        }
        form {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        input[type="number"] {
            width: 60px;
            padding: 5px;
        }
        button {
            padding: 5px 10px;
            background: limegreen;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }
        button:hover {
            background: blue;
        }
        .btn-salida {
            display: inline-block;
            padding: 10px 20px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: background 0.3s;
            margin-top: 20px;
        }
        .btn-salida:hover {
            background: #bb2d3b;
        }
        .center {
            text-align: center;
        }
        .filtros {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="filtros">
    <form method="get" action="">
        <label for="buscar">Buscar producto:</label>
        <input type="text" name="buscar" id="buscar" value="<?= htmlspecialchars($busqueda); ?>" placeholder="Escriba el nombre del producto..." style="padding: 8px; width: 300px; border-radius: 4px; border: 1px solid #ccc;">
        <button type="submit" class="btn">Buscar</button>
        <a href="productos.php" class="btn">Limpiar</a>
    </form>
</div>

<?php if ($hayBusqueda): ?>
    <h2>Resultados de búsqueda:</h2>
    <table>
        <thead>
        <tr>
            <th>Código</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Inventario</th>
            <th>Empresa</th>
            <?php if ($logueado): ?><th>Agregar</th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php if ($resultadoBusqueda->num_rows > 0):
                $tipo = 0;
                $color = "white";
                ?>
            <?php while($row = $resultadoBusqueda->fetch_assoc()):
            if ($tipo != $row['tipo']) {
                switch ($row['tipo']) {
                    case 1: // Rojizos
                        $r = rand(180, 255);
                        $g = rand(0, 80);
                        $b = rand(0, 80);
                        break;

                    case 2: // Azulados
                        $r = rand(0, 80);
                        $g = rand(0, 80);
                        $b = rand(180, 255);
                        break;

                    case 3: // Verdes
                        $r = rand(0, 80);
                        $g = rand(180, 255);
                        $b = rand(0, 80);
                        break;

                    case 4: // Amarillos/Naranjas
                        $r = rand(200, 255);
                        $g = rand(150, 220);
                        $b = rand(0, 50);
                        break;

                    case 5: // Morados/Violetas
                        $r = rand(120, 200);
                        $g = rand(0, 80);
                        $b = rand(150, 255);
                        break;

                    default: // Por si acaso
                        $r = rand(100, 255);
                        $g = rand(100, 255);
                        $b = rand(100, 255);
                        break;
                }

                $color = "rgb($r, $g, $b)";
                $tipo = $row['tipo'];
            }

            ?>

                <tr style="background: <?php echo $color; ?> ">
                    <td><?= $row['codigo'] ?></td>
                    <td><?= $row['descripcion'] ?></td>
                    <td><?= $row['precio'] ?></td>
                    <td><?= $row['inventario'] ?></td>
                    <td><?= $row['empresa'] ?></td>
                    <?php if ($logueado): ?>
                        <td>
                            <?php
                            //verificar si el producto va a exceder el presupuesto
                            $nuevo_total= isset($_SESSION['total_producto']) ? $_SESSION['total_producto'] + $row['precio'] : $row['precio'];
                            $mensaje_alerta= " ";
                            if ($presupuesto_maximo>0 && $nuevo_total> $presupuesto_maximo) {
                                $mensaje_alerta = "!Advertenciaaaaa!, El total de los productos superara el presupuesto establecido";
                            }
                            ?>
                            <form action="agregar_inventario.php" method="POST">
                                <input type="hidden" name="producto_id" value="<?= $row['idproducto'] ?>">
                                <input type="number" name="cantidad" min="1" value="1" required>
                                <button type="submit">Agregar</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No se encontraron productos con esa descripción.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

<?php else: ?>
    <h2>Productos EPA</h2>
    <table>
        <thead>
        <tr>
            <th>Código</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Inventario</th>
            <?php if ($logueado): ?><th>Agregar</th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php while($row = $epa->fetch_assoc()): ?>
            <tr>
                <td><?= $row['codigo'] ?></td>
                <td><?= $row['descripcion'] ?></td>
                <td><?= $row['precio'] ?></td>
                <td><?= $row['inventario'] ?></td>
                <?php if ($logueado): ?>
                    <td>
                        <form action="agregar_inventario.php" method="POST">
                            <input type="hidden" name="producto_id" value="<?= $row['idproducto'] ?>">
                            <input type="number" name="cantidad" min="1" value="1" required>
                            <button type="submit">Agregar</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h2>Productos NOVEX</h2>
    <table>
        <thead>
        <tr>
            <th>Código</th>
            <th>Descripción</th>
            <th>Precio</th>
            <th>Inventario</th>
            <?php if ($logueado): ?><th>Agregar</th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php while($row = $novex->fetch_assoc()): ?>
            <tr>
                <td><?= $row['codigo'] ?></td>
                <td><?= $row['descripcion'] ?></td>
                <td><?= $row['precio'] ?></td>
                <td><?= $row['inventario'] ?></td>
                <?php if ($logueado): ?>
                    <td>
                        <form action="agregar_inventario.php" method="POST">
                            <input type="hidden" name="producto_id" value="<?= $row['idproducto'] ?>">
                            <input type="number" name="cantidad" min="1" value="1" required>
                            <button type="submit">Agregar</button>
                        </form>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if ($logueado): ?>
    <div class="center">
        <a href="salida_inventario.php" class="btn-salida">Registrar Salida de Productos</a>
    </div>
<?php endif; ?>

</body>
</html>

<?php
session_start();

if (!isset($_SESSION['empleado_id'])) {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "cinepoint";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$current_user_id = $_SESSION['empleado_id'];
$sql_current_user = "SELECT * FROM empleados WHERE id = '$current_user_id'";
$result_current_user = $conn->query($sql_current_user);
$current_user = $result_current_user->fetch_assoc();
$is_admin = ($current_user['rol'] == 'administrador');

if (!$is_admin) {
    header("Location: boletos.php");
    exit;
}

$sql_check_table = "SHOW COLUMNS FROM productos";
$result_check = $conn->query($sql_check_table);
$columns = [];
if ($result_check) {
    while ($row = $result_check->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}

if (empty($columns)) {
    $sql_create_table = "CREATE TABLE IF NOT EXISTS productos (
        id_producto INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT,
        precio DECIMAL(10,2) NOT NULL,
        categoria VARCHAR(50) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        estado VARCHAR(20) NOT NULL DEFAULT 'Activo'
    )";

    if ($conn->query($sql_create_table) === FALSE) {
        die("Error al crear la tabla productos: " . $conn->error);
    }
} else {
    if (!in_array('estado', $columns)) {
        $sql_add_column = "ALTER TABLE productos ADD COLUMN estado VARCHAR(20) NOT NULL DEFAULT 'Activo'";
        if ($conn->query($sql_add_column) === FALSE) {
            die("Error al agregar la columna estado: " . $conn->error);
        }
    }
}

$mensaje = "";
$tipo_mensaje = "";

if (isset($_POST['eliminar']) && isset($_POST['id_producto'])) {
    $id = $conn->real_escape_string($_POST['id_producto']);
    
    $sql = "DELETE FROM productos WHERE id_producto = '$id'";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Producto eliminado con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

if (isset($_POST['agregar'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $precio = $conn->real_escape_string($_POST['precio']);
    $categoria = $conn->real_escape_string($_POST['categoria']);
    $stock = $conn->real_escape_string($_POST['stock']);
    $estado = $conn->real_escape_string($_POST['estado']);
    
    $sql = "INSERT INTO productos (nombre, descripcion, precio, categoria, stock, estado, fecha_creacion, fecha_actualizacion) 
            VALUES ('$nombre', '$descripcion', '$precio', '$categoria', '$stock', '$estado', NOW(), NOW())";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Producto agregado con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al agregar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

if (isset($_POST['actualizar'])) {
    $id = $conn->real_escape_string($_POST['id_producto']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $descripcion = $conn->real_escape_string($_POST['descripcion']);
    $precio = $conn->real_escape_string($_POST['precio']);
    $categoria = $conn->real_escape_string($_POST['categoria']);
    $stock = $conn->real_escape_string($_POST['stock']);
    $estado = $conn->real_escape_string($_POST['estado']);
    
    $sql = "UPDATE productos SET 
            nombre = '$nombre', 
            descripcion = '$descripcion', 
            precio = '$precio', 
            categoria = '$categoria', 
            stock = '$stock', 
            estado = '$estado',
            fecha_actualizacion = NOW()
            WHERE id_producto = '$id'";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Producto actualizado con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

$sql = "SELECT * FROM productos ORDER BY categoria, nombre";
$result = $conn->query($sql);

if (isset($_GET['logout'])) {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CinePoint - Gestión de Productos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #141414;
            color: #ffffff;
        }
        
        .header {
            background-color: #000000;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #333;
        }
        
        .logo {
            color: #e50914;
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
        }
        
        .nav-links {
            display: flex;
        }
        
        .nav-links a {
            color: #ffffff;
            text-decoration: none;
            margin: 0 15px;
            padding: 5px;
        }
        
        .nav-links a:hover {
            color: #e50914;
        }
        
        .nav-links a.active {
            border-bottom: 2px solid #e50914;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-icon {
            background-color: #e50914;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .dropdown {
            position: absolute;
            right: 0;
            top: 45px;
            background-color: #333;
            border-radius: 4px;
            width: 150px;
            display: none;
            z-index: 100;
        }
        
        .dropdown.show {
            display: block;
        }
        
        .dropdown a {
            display: block;
            padding: 10px 15px;
            color: white;
            text-decoration: none;
        }
        
        .dropdown a:hover {
            background-color: #444;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn {
            background-color: #e50914;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        
        .btn:hover {
            background-color: #f40612;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #4CAF50;
            color: white;
        }
        
        .alert-error {
            background-color: #f44336;
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #222;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        
        th {
            background-color: #333;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .edit-btn {
            background-color: #2196F3;
            color: white;
        }
        
        .edit-btn:hover {
            background-color: #0b7dda;
        }
        
        .delete-btn {
            background-color: #f44336;
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #d32f2f;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: #333;
            margin: 5% auto;
            padding: 20px;
            width: 90%;
            max-width: 500px;
            border-radius: 5px;
            position: relative;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 10px;
        }
        
        .close:hover {
            color: white;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            background-color: #444;
            border: 1px solid #555;
            color: white;
            border-radius: 4px;
        }
        
        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }
        
        .footer {
            text-align: center;
            padding: 15px;
            margin-top: 30px;
            border-top: 1px solid #333;
            color: #999;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-activo {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-inactivo {
            background-color: #F44336;
            color: white;
        }
        
        .status-agotado {
            background-color: #FF9800;
            color: white;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
            }
            
            .nav-links {
                margin-top: 15px;
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .nav-links a {
                margin: 5px;
            }
            
            .user-menu {
                position: absolute;
                top: 15px;
                right: 15px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <i class="fas fa-film"></i>
            <span>CinePoint</span>
        </div>
        
        <nav class="nav-links">
            <?php if ($is_admin): ?>
            <a href="index.php">
                <i class="fas fa-film"></i> Películas
            </a>
            <a href="salas.php">
                <i class="fas fa-door-open"></i> Salas
            </a>
            <a href="funciones.php">
                <i class="fas fa-calendar-alt"></i> Funciones
            </a>
            <?php endif; ?>
            <a href="boletos.php">
                <i class="fas fa-ticket-alt"></i> Boletos
            </a>
            <?php if ($is_admin): ?>
            <a href="productos.php" class="active">
                <i class="fas fa-shopping-cart"></i> Productos
            </a>
            <a href="empleados.php">
                <i class="fas fa-users"></i> Empleados
            </a>
            <?php endif; ?>
        </nav>
        
        <div class="user-menu">
            <div class="user-icon" id="userIcon">
                <i class="fas fa-user"></i>
            </div>
            <div class="dropdown" id="userDropdown">
                <a href="?logout=1">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="page-header">
            <h1>Productos</h1>
            <button class="btn" id="btnAgregar">
                <i class="fas fa-plus"></i> Agregar Producto
            </button>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $estado_class = '';
                        $estado = isset($row['estado']) && !empty($row['estado']) ? $row['estado'] : 'Activo';
                        
                        // Manejar categoría vacía
                        $categoria_display = isset($row['categoria']) && !empty(trim($row['categoria'])) ? $row['categoria'] : 'Sin categoría';
                        
                        switch(strtolower($estado)) {
                            case 'activo':
                                $estado_class = 'status-activo';
                                break;
                            case 'inactivo':
                                $estado_class = 'status-inactivo';
                                break;
                            case 'agotado':
                                $estado_class = 'status-agotado';
                                break;
                            default:
                                $estado_class = '';
                        }
                        
                        echo "<tr>";
                        echo "<td>{$row['id_producto']}</td>";
                        echo "<td>{$row['nombre']}</td>";
                        echo "<td>{$categoria_display}</td>";
                        echo "<td>$" . number_format($row['precio'], 2) . "</td>";
                        echo "<td>{$row['stock']}</td>";
                        echo "<td><span class='status-badge {$estado_class}'>{$estado}</span></td>";
                        echo "<td class='actions'>";
                        echo "<button class='action-btn edit-btn' onclick='editarProducto(" . json_encode($row) . ")' title='Editar'><i class='fas fa-edit'></i></button>";
                        echo "<button class='action-btn delete-btn' onclick='confirmarEliminar({$row['id_producto']}, \"{$row['nombre']}\")' title='Eliminar'><i class='fas fa-trash'></i></button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' style='text-align:center;padding:20px;'>No hay productos registrados</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div id="modalAgregar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalAgregar')">&times;</span>
            <h2>Agregar Nuevo Producto</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="precio">Precio</label>
                        <input type="number" id="precio" name="precio" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="categoria">Categoría</label>
                        <select id="categoria" name="categoria" class="form-control" required>
                            <option value="Comida">Comida</option>
                            <option value="Bebida">Bebida</option>
                            <option value="Combo">Combo</option>
                            <option value="Merchandising">Merchandising</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="stock">Stock</label>
                        <input type="number" id="stock" name="stock" class="form-control" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" class="form-control" required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                            <option value="Agotado">Agotado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModal('modalAgregar')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" name="agregar" class="btn">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
            <h2>Editar Producto</h2>
            <form method="POST" action="">
                <input type="hidden" id="edit_id_producto" name="id_producto">
                <div class="form-group">
                    <label for="edit_nombre">Nombre</label>
                    <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_descripcion">Descripción</label>
                    <textarea id="edit_descripcion" name="descripcion" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_precio">Precio</label>
                        <input type="number" id="edit_precio" name="precio" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_categoria">Categoría</label>
                        <select id="edit_categoria" name="categoria" class="form-control" required>
                            <option value="Comida">Comida</option>
                            <option value="Bebida">Bebida</option>
                            <option value="Combo">Combo</option>
                            <option value="Merchandising">Merchandising</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_stock">Stock</label>
                        <input type="number" id="edit_stock" name="stock" class="form-control" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_estado">Estado</label>
                        <select id="edit_estado" name="estado" class="form-control" required>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                            <option value="Agotado">Agotado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModal('modalEditar')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" name="actualizar" class="btn">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="modalEliminar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalEliminar')">&times;</span>
            <h2>Confirmar Eliminación</h2>
            <p>¿Estás seguro que deseas eliminar el producto <strong id="nombreProducto"></strong>?</p>
            <form method="POST" action="">
                <input type="hidden" id="delete_id" name="id_producto">
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModal('modalEliminar')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" name="eliminar" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
    
    <footer class="footer">
        <p>CinePoint &copy; <?php echo date("Y"); ?></p>
    </footer>
    
    <script>
        document.getElementById('userIcon').addEventListener('click', function() {
            document.getElementById('userDropdown').classList.toggle('show');
        });
        
        window.addEventListener('click', function(event) {
            if (!event.target.matches('.user-icon') && !event.target.matches('.user-icon *')) {
                var dropdown = document.getElementById('userDropdown');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });
        
        document.getElementById('btnAgregar').addEventListener('click', function() {
            document.getElementById('modalAgregar').style.display = 'block';
        });
        
        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function editarProducto(producto) {
            document.getElementById('edit_id_producto').value = producto.id_producto;
            document.getElementById('edit_nombre').value = producto.nombre;
            document.getElementById('edit_descripcion').value = producto.descripcion || '';
            document.getElementById('edit_precio').value = producto.precio;
            
            var categoriaSelect = document.getElementById('edit_categoria');
            var categoriaProducto = producto.categoria && producto.categoria.trim() !== '' ? producto.categoria.trim() : 'Comida';
            
            var encontrado = false;
            for (var i = 0; i < categoriaSelect.options.length; i++) {
                if (categoriaSelect.options[i].value === categoriaProducto) {
                    categoriaSelect.selectedIndex = i;
                    encontrado = true;
                    break;
                }
            }
            
            if (!encontrado) {
                categoriaSelect.selectedIndex = 0;
            }
            
            document.getElementById('edit_stock').value = producto.stock;
            
            var estadoSelect = document.getElementById('edit_estado');
            var estado = producto.estado && producto.estado.trim() !== '' ? producto.estado.trim() : 'Activo';
            
            var encontradoEstado = false;
            for (var i = 0; i < estadoSelect.options.length; i++) {
                if (estadoSelect.options[i].value === estado) {
                    estadoSelect.selectedIndex = i;
                    encontradoEstado = true;
                    break;
                }
            }
            
            if (!encontradoEstado) {
                estadoSelect.selectedIndex = 0;
            }
            
            document.getElementById('modalEditar').style.display = 'block';
        }
        
        function confirmarEliminar(id, nombre) {
            document.getElementById('delete_id').value = id;
            document.getElementById('nombreProducto').textContent = nombre;
            document.getElementById('modalEliminar').style.display = 'block';
        }
        
        window.addEventListener('click', function(event) {
            var modales = document.getElementsByClassName('modal');
            for (var i = 0; i < modales.length; i++) {
                if (event.target == modales[i]) {
                    modales[i].style.display = 'none';
                }
            }
        });
        
        setTimeout(function() {
            var alertas = document.getElementsByClassName('alert');
            for (var i = 0; i < alertas.length; i++) {
                alertas[i].style.display = 'none';
            }
        }, 3000);
    </script>
</body>
</html>
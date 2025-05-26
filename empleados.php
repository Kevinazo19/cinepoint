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

// Verificar el rol del usuario actual
$current_user_id = $_SESSION['empleado_id'];
$sql_current_user = "SELECT * FROM empleados WHERE id = '$current_user_id'";
$result_current_user = $conn->query($sql_current_user);
$current_user = $result_current_user->fetch_assoc();
$is_admin = ($current_user['rol'] == 'administrador');

// Si no es administrador, redirigir a la página de boletos
if (!$is_admin) {
    header("Location: boletos.php");
    exit;
}

$sql_check_table = "SHOW COLUMNS FROM empleados";
$result_check = $conn->query($sql_check_table);
$columns = [];
if ($result_check) {
    while ($row = $result_check->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}

if (empty($columns)) {
    $sql_create_table = "CREATE TABLE IF NOT EXISTS empleados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        correo VARCHAR(100) NOT NULL UNIQUE,
        contraseña VARCHAR(255) NOT NULL,
        rol ENUM('administrador', 'empleado') NOT NULL DEFAULT 'empleado'
    )";

    if ($conn->query($sql_create_table) === FALSE) {
        die("Error al crear la tabla empleados: " . $conn->error);
    }
} else {
    if (!in_array('rol', $columns)) {
        $sql_add_column = "ALTER TABLE empleados ADD COLUMN rol ENUM('administrador', 'empleado') NOT NULL DEFAULT 'empleado'";
        if ($conn->query($sql_add_column) === FALSE) {
            die("Error al agregar la columna rol: " . $conn->error);
        }
    }
}

$mensaje = "";
$tipo_mensaje = "";

if (isset($_POST['eliminar']) && isset($_POST['empleado_id'])) {
    $id = $conn->real_escape_string($_POST['empleado_id']);

    if ($id == $_SESSION['empleado_id']) {
        $mensaje = "No puedes eliminar tu propia cuenta";
        $tipo_mensaje = "error";
    } else {
        $sql = "DELETE FROM empleados WHERE id = '$id'";
        
        if ($conn->query($sql) === TRUE) {
            $mensaje = "Empleado eliminado con éxito";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar: " . $conn->error;
            $tipo_mensaje = "error";
        }
    }
}

if (isset($_POST['agregar'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $correo = $conn->real_escape_string($_POST['correo']);
    $password = $conn->real_escape_string($_POST['password']); // Sin encriptar
    $rol = $conn->real_escape_string($_POST['rol']);

    $check_email = "SELECT * FROM empleados WHERE correo = '$correo'";
    $result_email = $conn->query($check_email);

    if ($result_email->num_rows > 0) {
        $mensaje = "El correo ya está registrado";
        $tipo_mensaje = "error";
    } else {
        $sql = "INSERT INTO empleados (nombre, correo, contraseña, rol) 
                VALUES ('$nombre', '$correo', '$password', '$rol')";
        
        if ($conn->query($sql) === TRUE) {
            $mensaje = "Empleado agregado con éxito";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al agregar: " . $conn->error;
            $tipo_mensaje = "error";
        }
    }
}

if (isset($_POST['actualizar'])) {
    $id = $conn->real_escape_string($_POST['empleado_id']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $correo = $conn->real_escape_string($_POST['correo']);
    $rol = $conn->real_escape_string($_POST['rol']);

    $check_email = "SELECT * FROM empleados WHERE correo = '$correo' AND id != '$id'";
    $result_email = $conn->query($check_email);

    if ($result_email->num_rows > 0) {
        $mensaje = "El correo ya está registrado por otro empleado";
        $tipo_mensaje = "error";
    } else {
        if (!empty($_POST['password'])) {
            $password = $conn->real_escape_string($_POST['password']); // Sin encriptar
            $sql = "UPDATE empleados SET 
                    nombre = '$nombre', 
                    correo = '$correo', 
                    contraseña = '$password', 
                    rol = '$rol'
                    WHERE id = '$id'";
        } else {
            $sql = "UPDATE empleados SET 
                    nombre = '$nombre', 
                    correo = '$correo', 
                    rol = '$rol'
                    WHERE id = '$id'";
        }
        
        if ($conn->query($sql) === TRUE) {
            $mensaje = "Empleado actualizado con éxito";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar: " . $conn->error;
            $tipo_mensaje = "error";
        }
    }
}

if (isset($_POST['cambiar_rol'])) {
    $id = $conn->real_escape_string($_POST['empleado_id']);
    $nuevo_rol = $conn->real_escape_string($_POST['nuevo_rol']);

    $sql = "UPDATE empleados SET rol = '$nuevo_rol' WHERE id = '$id'";

    if ($conn->query($sql) === TRUE) {
        $mensaje = "Rol actualizado con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar rol: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

$sql = "SELECT * FROM empleados ORDER BY nombre";
$result = $conn->query($sql);

// Mejorar el cierre de sesión para PHP 8.0.12
if (isset($_GET['logout'])) {
    // Eliminamos todas las variables de sesión
    $_SESSION = array();
    
    // Si se desea destruir la cookie de sesión también
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finalmente, destruir la sesión
    session_destroy();
    
    // Redirigir al login
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CinePoint - Gestión de Empleados</title>
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
    
    .btn-success {
        background-color: #28a745;
    }
    
    .btn-success:hover {
        background-color: #218838;
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
    
    .role-btn {
        background-color: #ff9800;
        color: white;
    }
    
    .role-btn:hover {
        background-color: #e68a00;
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
    
    .role-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .role-administrador {
        background-color: #ff9800;
        color: white;
    }
    
    .role-empleado {
        background-color: #2196F3;
        color: white;
    }
    
    .employee-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .employee-card {
        background-color: #222;
        border-radius: 8px;
        padding: 15px;
        transition: transform 0.3s;
    }
    
    .employee-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .employee-info {
        padding: 10px 0;
    }
    
    .employee-name {
        font-size: 18px;
        margin-bottom: 5px;
    }
    
    .employee-email {
        color: #999;
        font-size: 14px;
        margin-bottom: 10px;
    }
    
    .employee-role {
        font-size: 14px;
        margin-bottom: 10px;
    }
    
    .employee-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 15px;
    }
    
    .view-mode-toggle {
        display: flex;
        gap: 10px;
        margin-left: auto;
    }
    
    .view-mode-toggle button {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
        padding: 5px;
    }
    
    .view-mode-toggle button.active {
        color: #e50914;
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
        
        .employee-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
        <a href="index.php">
            <i class="fas fa-film"></i> Películas
        </a>
        <a href="salas.php">
            <i class="fas fa-door-open"></i> Salas
        </a>
        <a href="funciones.php">
            <i class="fas fa-calendar-alt"></i> Funciones
        </a>
        <a href="boletos.php">
            <i class="fas fa-ticket-alt"></i> Boletos
        </a>
        <a href="productos.php">
            <i class="fas fa-shopping-cart"></i> Productos
        </a>
        <a href="empleados.php" class="active">
            <i class="fas fa-users"></i> Empleados
        </a>
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
        <h1>Gestión de Empleados</h1>
        <div class="actions-container">
            <div class="view-mode-toggle">
                <button id="gridViewBtn" class="active" title="Vista de cuadrícula">
                    <i class="fas fa-th"></i>
                </button>
                <button id="listViewBtn" title="Vista de lista">
                    <i class="fas fa-list"></i>
                </button>
            </div>
            <button class="btn" id="btnAgregar">
                <i class="fas fa-plus"></i> Agregar Empleado
            </button>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>
    
    <table id="tabla_empleados" style="display: none;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $rol_class = '';
                    $rol = isset($row['rol']) ? $row['rol'] : 'empleado';
                    
                    switch(strtolower($rol)) {
                        case 'administrador':
                            $rol_class = 'role-administrador';
                            break;
                        case 'empleado':
                            $rol_class = 'role-empleado';
                            break;
                        default:
                            $rol_class = '';
                    }
                    
                    echo "<tr data-nombre='{$row['nombre']}' data-rol='{$rol}'>";
                    echo "<td>{$row['id']}</td>";
                    echo "<td>{$row['nombre']}</td>";
                    echo "<td>{$row['correo']}</td>";
                    echo "<td><span class='role-badge {$rol_class}'>{$rol}</span></td>";
                    echo "<td class='actions'>";
                    
                    if ($rol == 'empleado') {
                        echo "<form method='POST' action='' style='display:inline;'>";
                        echo "<input type='hidden' name='empleado_id' value='{$row['id']}'>";
                        echo "<input type='hidden' name='nuevo_rol' value='administrador'>";
                        echo "<button type='submit' name='cambiar_rol' class='action-btn role-btn' title='Hacer Administrador'><i class='fas fa-user-shield'></i></button>";
                        echo "</form>";
                    } else {
                        echo "<form method='POST' action='' style='display:inline;'>";
                        echo "<input type='hidden' name='empleado_id' value='{$row['id']}'>";
                        echo "<input type='hidden' name='nuevo_rol' value='empleado'>";
                        echo "<button type='submit' name='cambiar_rol' class='action-btn role-btn' title='Hacer Empleado'><i class='fas fa-user'></i></button>";
                        echo "</form>";
                    }
                    
                    echo "<button class='action-btn edit-btn' onclick='editarEmpleado(" . json_encode($row) . ")' title='Editar'><i class='fas fa-edit'></i></button>";
                    
                    if ($row['id'] != $_SESSION['empleado_id']) {
                        echo "<button class='action-btn delete-btn' onclick='confirmarEliminar({$row['id']}, \"{$row['nombre']}\")' title='Eliminar'><i class='fas fa-trash'></i></button>";
                    }
                    
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='text-align:center;padding:20px;'>No hay empleados registrados</td></tr>";
            }
            ?>
        </tbody>
    </table>
    
    <div id="grid_empleados" class="employee-grid">
        <?php
        if ($result && $result->num_rows > 0) {
            $result->data_seek(0);
            
            while ($row = $result->fetch_assoc()) {
                $rol_class = '';
                $rol = isset($row['rol']) ? $row['rol'] : 'empleado';
                
                switch(strtolower($rol)) {
                    case 'administrador':
                        $rol_class = 'role-administrador';
                        break;
                    case 'empleado':
                        $rol_class = 'role-empleado';
                        break;
                    default:
                        $rol_class = '';
                }
                
                echo "<div class='employee-card' data-nombre='{$row['nombre']}' data-rol='{$rol}'>";
                echo "<div class='employee-info'>";
                echo "<h3 class='employee-name'>{$row['nombre']}</h3>";
                echo "<div class='employee-email'>{$row['correo']}</div>";
                echo "<div class='employee-role'><span class='role-badge {$rol_class}'>{$rol}</span></div>";
                echo "<div class='employee-actions'>";
                
                if ($rol == 'empleado') {
                    echo "<form method='POST' action='' style='display:inline;'>";
                    echo "<input type='hidden' name='empleado_id' value='{$row['id']}'>";
                    echo "<input type='hidden' name='nuevo_rol' value='administrador'>";
                    echo "<button type='submit' name='cambiar_rol' class='action-btn role-btn' title='Hacer Administrador'><i class='fas fa-user-shield'></i></button>";
                    echo "</form>";
                } else {
                    echo "<form method='POST' action='' style='display:inline;'>";
                    echo "<input type='hidden' name='empleado_id' value='{$row['id']}'>";
                    echo "<input type='hidden' name='nuevo_rol' value='empleado'>";
                    echo "<button type='submit' name='cambiar_rol' class='action-btn role-btn' title='Hacer Empleado'><i class='fas fa-user'></i></button>";
                    echo "</form>";
                }
                
                echo "<button class='action-btn edit-btn' onclick='editarEmpleado(" . json_encode($row) . ")' title='Editar'><i class='fas fa-edit'></i></button>";
                
                if ($row['id'] != $_SESSION['empleado_id']) {
                    echo "<button class='action-btn delete-btn' onclick='confirmarEliminar({$row['id']}, \"{$row['nombre']}\")' title='Eliminar'><i class='fas fa-trash'></i></button>";
                }
                
                echo "</div>";
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<div style='grid-column: 1 / -1; text-align: center; padding: 20px;'>No hay empleados registrados</div>";
        }
        ?>
    </div>
</div>

<div id="modalAgregar" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModal('modalAgregar')">&times;</span>
        <h2>Agregar Nuevo Empleado</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="correo">Correo</label>
                <input type="email" id="correo" name="correo" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="rol">Rol</label>
                <select id="rol" name="rol" class="form-control" required>
                    <option value="empleado">Empleado</option>
                    <option value="administrador">Administrador</option>
                </select>
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
        <h2>Editar Empleado</h2>
        <form method="POST" action="">
            <input type="hidden" id="edit_empleado_id" name="empleado_id">
            <div class="form-group">
                <label for="edit_nombre">Nombre</label>
                <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_correo">Correo</label>
                <input type="email" id="edit_correo" name="correo" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_password">Contraseña (dejar en blanco para mantener la actual)</label>
                <input type="password" id="edit_password" name="password" class="form-control">
            </div>
            <div class="form-group">
                <label for="edit_rol">Rol</label>
                <select id="edit_rol" name="rol" class="form-control" required>
                    <option value="empleado">Empleado</option>
                    <option value="administrador">Administrador</option>
                </select>
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
        <p>¿Estás seguro que deseas eliminar al empleado <strong id="nombreEmpleado"></strong>?</p>
        <form method="POST" action="">
            <input type="hidden" id="delete_id" name="empleado_id">
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
    
    function editarEmpleado(empleado) {
        document.getElementById('edit_empleado_id').value = empleado.id;
        document.getElementById('edit_nombre').value = empleado.nombre;
        document.getElementById('edit_correo').value = empleado.correo;
        
        var rolSelect = document.getElementById('edit_rol');
        for (var i = 0; i < rolSelect.options.length; i++) {
            if (rolSelect.options[i].value === empleado.rol) {
                rolSelect.selectedIndex = i;
                break;
            }
        }
        
        document.getElementById('modalEditar').style.display = 'block';
    }
    
    function confirmarEliminar(id, nombre) {
        document.getElementById('delete_id').value = id;
        document.getElementById('nombreEmpleado').textContent = nombre;
        document.getElementById('modalEliminar').style.display = 'block';
    }
    
    document.getElementById('gridViewBtn').addEventListener('click', function() {
        document.getElementById('grid_empleados').style.display = 'grid';
        document.getElementById('tabla_empleados').style.display = 'none';
        document.getElementById('gridViewBtn').classList.add('active');
        document.getElementById('listViewBtn').classList.remove('active');
    });
    
    document.getElementById('listViewBtn').addEventListener('click', function() {
        document.getElementById('grid_empleados').style.display = 'none';
        document.getElementById('tabla_empleados').style.display = 'table';
        document.getElementById('gridViewBtn').classList.remove('active');
        document.getElementById('listViewBtn').classList.add('active');
    });
    
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
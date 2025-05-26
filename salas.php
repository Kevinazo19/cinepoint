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

$mensaje = "";
$tipo_mensaje = "";

if (isset($_POST['eliminar']) && isset($_POST['id_sala'])) {
    $id = $conn->real_escape_string($_POST['id_sala']);
    
    $conn->begin_transaction();
    
    try {
        $get_functions = "SELECT id_funcion FROM funciones WHERE id_sala = '$id'";
        $functions_result = $conn->query($get_functions);
        
        if ($functions_result && $functions_result->num_rows > 0) {
            $function_ids = [];
            while ($row = $functions_result->fetch_assoc()) {
                $function_ids[] = $row['id_funcion'];
            }
            
            if (!empty($function_ids)) {
                $function_ids_str = implode(',', $function_ids);
                
                $delete_tickets = "DELETE FROM boletos WHERE id_funcion IN ($function_ids_str)";
                $conn->query($delete_tickets);
            }
        }
        
        $delete_functions = "DELETE FROM funciones WHERE id_sala = '$id'";
        $conn->query($delete_functions);
        
        $delete_sala = "DELETE FROM salas WHERE id_sala = '$id'";
        $conn->query($delete_sala);
        
        $conn->commit();
        
        $mensaje = "Sala eliminada con éxito";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $conn->rollback();
        $mensaje = "Error al eliminar: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

if (isset($_POST['agregar'])) {
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $capacidad = $conn->real_escape_string($_POST['capacidad']);
    $tipo = $conn->real_escape_string($_POST['tipo']);
    $estado = $conn->real_escape_string($_POST['estado']);
    
    $sql = "INSERT INTO salas (nombre, capacidad, tipo, estado) 
            VALUES ('$nombre', '$capacidad', '$tipo', '$estado')";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Sala agregada con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al agregar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

if (isset($_POST['actualizar'])) {
    $id = $conn->real_escape_string($_POST['id_sala']);
    $nombre = $conn->real_escape_string($_POST['nombre']);
    $capacidad = $conn->real_escape_string($_POST['capacidad']);
    $tipo = $conn->real_escape_string($_POST['tipo']);
    $estado = $conn->real_escape_string($_POST['estado']);
    
    $sql = "UPDATE salas SET 
            nombre = '$nombre', 
            capacidad = '$capacidad', 
            tipo = '$tipo', 
            estado = '$estado' 
            WHERE id_sala = '$id'";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Sala actualizada con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

$sql = "SELECT * FROM salas";
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
    <title>CinePoint - Gestión de Salas</title>
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
        
        .actions button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            margin-right: 10px;
        }
        
        .edit-btn {
            color: #2196F3;
        }
        
        .delete-btn {
            color: #f44336;
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
        
        .status-activa {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-mantenimiento {
            background-color: #FFC107;
            color: black;
        }
        
        .status-fuera-de-servicio {
            background-color: #F44336;
            color: white;
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
            <a href="salas.php" class="active">
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
            <a href="productos.php">
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
            <h1>Salas</h1>
            <button class="btn" id="btnAgregar">
                <i class="fas fa-plus"></i> Agregar
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
                    <th>Capacidad</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $id_field = isset($row['id_sala']) ? 'id_sala' : 'id';
                    
                        $estado_display = !empty($row['estado']) ? $row['estado'] : 'Activa';
                        
                        // Mejorar la detección del estado para el CSS
                        $estado_lower = strtolower(trim($row['estado']));
                        $estado_class = '';
                        if (strpos($estado_lower, 'activ') !== false) {
                            $estado_class = 'status-activa';
                        } elseif (strpos($estado_lower, 'mantenimiento') !== false) {
                            $estado_class = 'status-mantenimiento';
                        } elseif (strpos($estado_lower, 'fuera') !== false || strpos($estado_lower, 'servicio') !== false) {
                            $estado_class = 'status-fuera-de-servicio';
                        }
                        
                        echo "<tr>";
                        echo "<td>{$row[$id_field]}</td>";
                        echo "<td>{$row['nombre']}</td>";
                        echo "<td>{$row['capacidad']} asientos</td>";
                        echo "<td>{$row['tipo']}</td>";
                        echo "<td><span class='status-badge {$estado_class}'>{$estado_display}</span></td>";
                        echo "<td class='actions'>";
                        echo "<button class='edit-btn' onclick='editarSala(" . json_encode($row) . ")'><i class='fas fa-edit'></i></button>";
                        echo "<button class='delete-btn' onclick='confirmarEliminar({$row[$id_field]}, \"{$row['nombre']}\")'><i class='fas fa-trash'></i></button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center;padding:20px;'>No hay salas registradas</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <div id="modalAgregar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalAgregar')">&times;</span>
            <h2>Agregar Nueva Sala</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Ej: Sala 1">
                </div>
                <div class="form-group">
                    <label for="capacidad">Capacidad (asientos)</label>
                    <input type="number" id="capacidad" name="capacidad" class="form-control" required min="1">
                </div>
                <div class="form-group">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo" class="form-control" required>
                        <option value="Regular">Regular</option>
                        <option value="VIP">VIP</option>
                        <option value="3D">3D</option>
                        <option value="IMAX">IMAX</option>
                        <option value="4DX">4DX</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" class="form-control" required>
                        <option value="Activa">Activa</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                        <option value="Fuera de servicio">Fuera de servicio</option>
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
            <h2>Editar Sala</h2>
            <form method="POST" action="">
                <input type="hidden" id="edit_id_sala" name="id_sala">
                <div class="form-group">
                    <label for="edit_nombre">Nombre</label>
                    <input type="text" id="edit_nombre" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_capacidad">Capacidad (asientos)</label>
                    <input type="number" id="edit_capacidad" name="capacidad" class="form-control" required min="1">
                </div>
                <div class="form-group">
                    <label for="edit_tipo">Tipo</label>
                    <select id="edit_tipo" name="tipo" class="form-control" required>
                        <option value="Regular">Regular</option>
                        <option value="VIP">VIP</option>
                        <option value="3D">3D</option>
                        <option value="IMAX">IMAX</option>
                        <option value="4DX">4DX</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_estado">Estado</label>
                    <select id="edit_estado" name="estado" class="form-control" required>
                        <option value="Activa">Activa</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                        <option value="Fuera de servicio">Fuera de servicio</option>
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
            <p>¿Estás seguro que deseas eliminar la sala <strong id="nombreSala"></strong>?</p>
            <p class="alert alert-error" style="margin-top: 10px;">
                <i class="fas fa-exclamation-triangle"></i> Advertencia: Esta acción eliminará también todas las funciones y boletos asociados a esta sala.
            </p>
            <form method="POST" action="">
                <input type="hidden" id="delete_id" name="id_sala">
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
        
        function editarSala(sala) {
            var idField = sala.id_sala !== undefined ? 'id_sala' : 'id';
            
            document.getElementById('edit_id_sala').value = sala[idField];
            document.getElementById('edit_nombre').value = sala.nombre;
            document.getElementById('edit_capacidad').value = sala.capacidad;
            
            // Seleccionar tipo
            var tipoSelect = document.getElementById('edit_tipo');
            for (var i = 0; i < tipoSelect.options.length; i++) {
                if (tipoSelect.options[i].value === sala.tipo) {
                    tipoSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Manejar estado - buscar coincidencia exacta con los valores del ENUM
            var estadoSelect = document.getElementById('edit_estado');
            var estadoSala = sala.estado && sala.estado.trim() !== '' ? sala.estado.trim() : 'Activa';
            
            // Buscar coincidencia exacta
            var encontrado = false;
            for (var i = 0; i < estadoSelect.options.length; i++) {
                if (estadoSelect.options[i].value === estadoSala) {
                    estadoSelect.selectedIndex = i;
                    encontrado = true;
                    break;
                }
            }
            
            // Si no se encontró coincidencia exacta, usar "Activa" por defecto
            if (!encontrado) {
                estadoSelect.selectedIndex = 0; // "Activa" es la primera opción
            }
            
            document.getElementById('modalEditar').style.display = 'block';
        }
        
        function confirmarEliminar(id, nombre) {
            document.getElementById('delete_id').value = id;
            document.getElementById('nombreSala').textContent = nombre;
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
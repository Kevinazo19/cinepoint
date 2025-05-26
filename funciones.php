<?php
// Iniciar sesión
session_start();

if (!isset($_SESSION['empleado_id'])) {
    header("Location: login.php");
    exit;
}

// Conexión a la base de datos
$host = "localhost";
$user = "root";
$pass = "";
$db   = "cinepoint";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Crear la tabla funciones si no existe
$sql_create_table = "CREATE TABLE IF NOT EXISTS funciones (
    id_funcion INT AUTO_INCREMENT PRIMARY KEY,
    id_pelicula INT NOT NULL,
    id_sala INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    precio_estudiante DECIMAL(10,2) NOT NULL,
    precio_general DECIMAL(10,2) NOT NULL,
    formato VARCHAR(10) NOT NULL,
    estado VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_pelicula) REFERENCES peliculas(id_pelicula),
    FOREIGN KEY (id_sala) REFERENCES salas(id_sala)
)";

if ($conn->query($sql_create_table) === FALSE) {
    die("Error al crear la tabla funciones: " . $conn->error);
}

// Procesar operaciones CRUD
$mensaje = "";
$tipo_mensaje = "";

// ELIMINAR función
if (isset($_POST['eliminar']) && isset($_POST['id_funcion'])) {
    $id = $conn->real_escape_string($_POST['id_funcion']);
    
    // Eliminar la función
    $sql = "DELETE FROM funciones WHERE id_funcion = '$id'";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Función eliminada con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

// AGREGAR nueva función
if (isset($_POST['agregar'])) {
    $id_pelicula = $conn->real_escape_string($_POST['id_pelicula']);
    $id_sala = $conn->real_escape_string($_POST['id_sala']);
    $fecha = $conn->real_escape_string($_POST['fecha']);
    $hora_inicio = $conn->real_escape_string($_POST['hora_inicio']);
    $hora_fin = $conn->real_escape_string($_POST['hora_fin']);
    $precio_estudiante = $conn->real_escape_string($_POST['precio_estudiante']);
    $precio_general = $conn->real_escape_string($_POST['precio_general']);
    $formato = $conn->real_escape_string($_POST['formato']);
    $estado = $conn->real_escape_string($_POST['estado']);
    
    $sql = "INSERT INTO funciones (id_pelicula, id_sala, fecha, hora_inicio, hora_fin, precio_estudiante, precio_general, formato, estado) 
            VALUES ('$id_pelicula', '$id_sala', '$fecha', '$hora_inicio', '$hora_fin', '$precio_estudiante', '$precio_general', '$formato', '$estado')";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Función agregada con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al agregar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

// ACTUALIZAR función
if (isset($_POST['actualizar'])) {
    $id = $conn->real_escape_string($_POST['id_funcion']);
    $id_pelicula = $conn->real_escape_string($_POST['id_pelicula']);
    $id_sala = $conn->real_escape_string($_POST['id_sala']);
    $fecha = $conn->real_escape_string($_POST['fecha']);
    $hora_inicio = $conn->real_escape_string($_POST['hora_inicio']);
    $hora_fin = $conn->real_escape_string($_POST['hora_fin']);
    $precio_estudiante = $conn->real_escape_string($_POST['precio_estudiante']);
    $precio_general = $conn->real_escape_string($_POST['precio_general']);
    $formato = $conn->real_escape_string($_POST['formato']);
    $estado = $conn->real_escape_string($_POST['estado']);
    
    $sql = "UPDATE funciones SET 
            id_pelicula = '$id_pelicula', 
            id_sala = '$id_sala', 
            fecha = '$fecha', 
            hora_inicio = '$hora_inicio', 
            hora_fin = '$hora_fin', 
            precio_estudiante = '$precio_estudiante', 
            precio_general = '$precio_general', 
            formato = '$formato', 
            estado = '$estado' 
            WHERE id_funcion = '$id'";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Función actualizada con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

// Obtener todas las funciones con información de películas y salas
$sql = "SELECT f.*, p.titulo as pelicula_titulo, s.nombre as sala_nombre 
        FROM funciones f 
        LEFT JOIN peliculas p ON f.id_pelicula = p.id_pelicula 
        LEFT JOIN salas s ON f.id_sala = s.id_sala 
        ORDER BY f.fecha DESC, f.hora_inicio ASC";
$result = $conn->query($sql);

// Obtener todas las películas para el formulario
$sql_peliculas = "SELECT id_pelicula, titulo FROM peliculas ORDER BY titulo";
$result_peliculas = $conn->query($sql_peliculas);

// Obtener todas las salas para el formulario
$sql_salas = "SELECT id_sala, nombre FROM salas ORDER BY nombre";
$result_salas = $conn->query($sql_salas);

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_unset();
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
    <title>CinePoint - Gestión de Funciones</title>
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
        
        .status-programada {
            background-color: #2196F3;
            color: white;
        }
        
        .status-en-curso {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-finalizada {
            background-color: #9E9E9E;
            color: white;
        }
        
        .status-cancelada {
            background-color: #F44336;
            color: white;
        }
        
        .formato-badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            background-color: #333;
            color: white;
            margin-left: 5px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .precio-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .precio-container .form-group {
            flex: 1;
        }
        
        .precio-label {
            font-size: 12px;
            color: #aaa;
            margin-bottom: 3px;
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
    <!-- Header con navegación -->
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
            <a href="funciones.php" class="active">
                <i class="fas fa-calendar-alt"></i> Funciones
            </a>
            <a href="boletos.php">
                <i class="fas fa-ticket-alt"></i> Boletos
            </a>
            <a href="productos.php">
                <i class="fas fa-shopping-cart"></i> Productos
            </a>
            <a href="empleados.php">
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
        <!-- Encabezado de página -->
        <div class="page-header">
            <h1>Funciones</h1>
            <button class="btn" id="btnAgregar">
                <i class="fas fa-plus"></i> Agregar
            </button>
        </div>
        
        <!-- Mensajes de alerta -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <!-- Tabla de funciones -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Película</th>
                    <th>Sala</th>
                    <th>Fecha</th>
                    <th>Horario</th>
                    <th>Precios</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $id_field = isset($row['id_funcion']) ? 'id_funcion' : 'id';
                        
                        // Determinar la clase de estado para el badge
                        $estado_class = '';
                        switch(strtolower($row['estado'])) {
                            case 'programada':
                                $estado_class = 'status-programada';
                                break;
                            case 'en curso':
                                $estado_class = 'status-en-curso';
                                break;
                            case 'finalizada':
                                $estado_class = 'status-finalizada';
                                break;
                            case 'cancelada':
                                $estado_class = 'status-cancelada';
                                break;
                            default:
                                $estado_class = '';
                        }
                        
                        // Formatear fecha
                        $fecha = date('d/m/Y', strtotime($row['fecha']));
                        
                        echo "<tr>";
                        echo "<td>{$row[$id_field]}</td>";
                        echo "<td>{$row['pelicula_titulo']} <span class='formato-badge'>{$row['formato']}</span></td>";
                        echo "<td>{$row['sala_nombre']}</td>";
                        echo "<td>{$fecha}</td>";
                        echo "<td>{$row['hora_inicio']} - {$row['hora_fin']}</td>";
                        echo "<td>General: $" . number_format($row['precio_general'], 2) . "<br>Estudiante: $" . number_format($row['precio_estudiante'], 2) . "</td>";
                        echo "<td><span class='status-badge {$estado_class}'>{$row['estado']}</span></td>";
                        echo "<td class='actions'>";
                        echo "<button class='edit-btn' onclick='editarFuncion(" . json_encode($row) . ")'><i class='fas fa-edit'></i></button>";
                        echo "<button class='delete-btn' onclick='confirmarEliminar({$row[$id_field]}, \"{$row['pelicula_titulo']}\", \"{$fecha}\", \"{$row['hora_inicio']}\")'><i class='fas fa-trash'></i></button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center;padding:20px;'>No hay funciones registradas</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal para agregar función -->
    <div id="modalAgregar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalAgregar')">&times;</span>
            <h2>Agregar Nueva Función</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="id_pelicula">Película</label>
                    <select id="id_pelicula" name="id_pelicula" class="form-control" required>
                        <option value="">Seleccione una película</option>
                        <?php
                        if ($result_peliculas && $result_peliculas->num_rows > 0) {
                            while ($pelicula = $result_peliculas->fetch_assoc()) {
                                echo "<option value='{$pelicula['id_pelicula']}'>{$pelicula['titulo']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id_sala">Sala</label>
                    <select id="id_sala" name="id_sala" class="form-control" required>
                        <option value="">Seleccione una sala</option>
                        <?php
                        if ($result_salas && $result_salas->num_rows > 0) {
                            while ($sala = $result_salas->fetch_assoc()) {
                                echo "<option value='{$sala['id_sala']}'>{$sala['nombre']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="formato">Formato</label>
                        <select id="formato" name="formato" class="form-control" required>
                            <option value="2D">2D</option>
                            <option value="3D">3D</option>
                            <option value="IMAX">IMAX</option>
                            <option value="4DX">4DX</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="hora_inicio">Hora de inicio</label>
                        <input type="time" id="hora_inicio" name="hora_inicio" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="hora_fin">Hora de fin</label>
                        <input type="time" id="hora_fin" name="hora_fin" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Precios</label>
                        <div class="precio-container">
                            <div class="form-group">
                                <div class="precio-label">General</div>
                                <input type="number" id="precio_general" name="precio_general" class="form-control" step="0.01" min="0" required placeholder="$">
                            </div>
                            <div class="form-group">
                                <div class="precio-label">Estudiante</div>
                                <input type="number" id="precio_estudiante" name="precio_estudiante" class="form-control" step="0.01" min="0" required placeholder="$">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" class="form-control" required>
                            <option value="Programada">Programada</option>
                            <option value="En curso">En curso</option>
                            <option value="Finalizada">Finalizada</option>
                            <option value="Cancelada">Cancelada</option>
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
    
    <!-- Modal para editar función -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
            <h2>Editar Función</h2>
            <form method="POST" action="">
                <input type="hidden" id="edit_id_funcion" name="id_funcion">
                <div class="form-group">
                    <label for="edit_id_pelicula">Película</label>
                    <select id="edit_id_pelicula" name="id_pelicula" class="form-control" required>
                        <?php
                        // Reiniciar el puntero del resultado
                        $result_peliculas->data_seek(0);
                        if ($result_peliculas && $result_peliculas->num_rows > 0) {
                            while ($pelicula = $result_peliculas->fetch_assoc()) {
                                echo "<option value='{$pelicula['id_pelicula']}'>{$pelicula['titulo']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_id_sala">Sala</label>
                    <select id="edit_id_sala" name="id_sala" class="form-control" required>
                        <?php
                        // Reiniciar el puntero del resultado
                        $result_salas->data_seek(0);
                        if ($result_salas && $result_salas->num_rows > 0) {
                            while ($sala = $result_salas->fetch_assoc()) {
                                echo "<option value='{$sala['id_sala']}'>{$sala['nombre']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_fecha">Fecha</label>
                        <input type="date" id="edit_fecha" name="fecha" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_formato">Formato</label>
                        <select id="edit_formato" name="formato" class="form-control" required>
                            <option value="2D">2D</option>
                            <option value="3D">3D</option>
                            <option value="IMAX">IMAX</option>
                            <option value="4DX">4DX</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_hora_inicio">Hora de inicio</label>
                        <input type="time" id="edit_hora_inicio" name="hora_inicio" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_hora_fin">Hora de fin</label>
                        <input type="time" id="edit_hora_fin" name="hora_fin" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Precios</label>
                        <div class="precio-container">
                            <div class="form-group">
                                <div class="precio-label">General</div>
                                <input type="number" id="edit_precio_general" name="precio_general" class="form-control" step="0.01" min="0" required placeholder="$">
                            </div>
                            <div class="form-group">
                                <div class="precio-label">Estudiante</div>
                                <input type="number" id="edit_precio_estudiante" name="precio_estudiante" class="form-control" step="0.01" min="0" required placeholder="$">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_estado">Estado</label>
                        <select id="edit_estado" name="estado" class="form-control" required>
                            <option value="Programada">Programada</option>
                            <option value="En curso">En curso</option>
                            <option value="Finalizada">Finalizada</option>
                            <option value="Cancelada">Cancelada</option>
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
    
    <!-- Modal para confirmar eliminación -->
    <div id="modalEliminar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalEliminar')">&times;</span>
            <h2>Confirmar Eliminación</h2>
            <p>¿Estás seguro que deseas eliminar la función de <strong id="nombrePelicula"></strong>?</p>
            <p>Fecha: <span id="fechaFuncion"></span> | Hora: <span id="horaFuncion"></span></p>
            <form method="POST" action="">
                <input type="hidden" id="delete_id" name="id_funcion">
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
        // Mostrar/ocultar menú desplegable del perfil
        document.getElementById('userIcon').addEventListener('click', function() {
            document.getElementById('userDropdown').classList.toggle('show');
        });
        
        // Cerrar el menú desplegable al hacer clic fuera de él
        window.addEventListener('click', function(event) {
            if (!event.target.matches('.user-icon') && !event.target.matches('.user-icon *')) {
                var dropdown = document.getElementById('userDropdown');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });
        
        // Establecer fecha actual por defecto
        document.addEventListener('DOMContentLoaded', function() {
            var today = new Date().toISOString().split('T')[0];
            document.getElementById('fecha').value = today;
        });
        
        // Abrir modal para agregar función
        document.getElementById('btnAgregar').addEventListener('click', function() {
            document.getElementById('modalAgregar').style.display = 'block';
        });
        
        // Función para cerrar modales
        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Función para editar función
        function editarFuncion(funcion) {
            // Determinar qué campo de ID usar
            var idField = funcion.id_funcion !== undefined ? 'id_funcion' : 'id';
            
            document.getElementById('edit_id_funcion').value = funcion[idField];
            
            // Seleccionar la película correcta
            var peliculaSelect = document.getElementById('edit_id_pelicula');
            for (var i = 0; i < peliculaSelect.options.length; i++) {
                if (peliculaSelect.options[i].value == funcion.id_pelicula) {
                    peliculaSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Seleccionar la sala correcta
            var salaSelect = document.getElementById('edit_id_sala');
            for (var i = 0; i < salaSelect.options.length; i++) {
                if (salaSelect.options[i].value == funcion.id_sala) {
                    salaSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Formatear la fecha para el input date
            var fecha = funcion.fecha;
            if (fecha.includes('/')) {
                var partes = fecha.split('/');
                fecha = partes[2] + '-' + partes[1] + '-' + partes[0];
            }
            document.getElementById('edit_fecha').value = fecha;
            
            document.getElementById('edit_hora_inicio').value = funcion.hora_inicio;
            document.getElementById('edit_hora_fin').value = funcion.hora_fin;
            document.getElementById('edit_precio_general').value = funcion.precio_general;
            document.getElementById('edit_precio_estudiante').value = funcion.precio_estudiante;
            
            // Seleccionar el formato correcto
            var formatoSelect = document.getElementById('edit_formato');
            for (var i = 0; i < formatoSelect.options.length; i++) {
                if (formatoSelect.options[i].value === funcion.formato) {
                    formatoSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Seleccionar el estado correcto
            var estadoSelect = document.getElementById('edit_estado');
            for (var i = 0; i < estadoSelect.options.length; i++) {
                if (estadoSelect.options[i].value === funcion.estado) {
                    estadoSelect.selectedIndex = i;
                    break;
                }
            }
            
            document.getElementById('modalEditar').style.display = 'block';
        }
        
        // Función para confirmar eliminación
        function confirmarEliminar(id, pelicula, fecha, hora) {
            document.getElementById('delete_id').value = id;
            document.getElementById('nombrePelicula').textContent = pelicula;
            document.getElementById('fechaFuncion').textContent = fecha;
            document.getElementById('horaFuncion').textContent = hora;
            document.getElementById('modalEliminar').style.display = 'block';
        }
        
        // Cerrar modales al hacer clic fuera de ellos
        window.addEventListener('click', function(event) {
            var modales = document.getElementsByClassName('modal');
            for (var i = 0; i < modales.length; i++) {
                if (event.target == modales[i]) {
                    modales[i].style.display = 'none';
                }
            }
        });
        
        // Ocultar mensajes de alerta después de 3 segundos
        setTimeout(function() {
            var alertas = document.getElementsByClassName('alert');
            for (var i = 0; i < alertas.length; i++) {
                alertas[i].style.display = 'none';
            }
        }, 3000);
    </script>
</body>
</html>
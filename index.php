<?php
// Iniciar sesión
session_start();

// Verificar si el usuario está logueado
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

// Procesar operaciones CRUD
$mensaje = "";
$tipo_mensaje = "";

// ELIMINAR película
if (isset($_POST['eliminar']) && isset($_POST['id_pelicula'])) {
    $id = $conn->real_escape_string($_POST['id_pelicula']);
    
    // Comenzar una transacción para asegurarnos que todo se ejecuta correctamente
    $conn->begin_transaction();
    
    try {
        // 1. Primero obtener todas las funciones asociadas a esta película
        $get_functions = "SELECT id_funcion FROM funciones WHERE id_pelicula = '$id'";
        $functions_result = $conn->query($get_functions);
        
        if ($functions_result && $functions_result->num_rows > 0) {
            // Crear un array con los IDs de las funciones
            $function_ids = [];
            while ($row = $functions_result->fetch_assoc()) {
                $function_ids[] = $row['id_funcion'];
            }
            
            // Convertir el array a una cadena para usar en la consulta IN
            if (!empty($function_ids)) {
                $function_ids_str = implode(',', $function_ids);
                
                // 2. Eliminar todos los boletos asociados a estas funciones
                $delete_tickets = "DELETE FROM boletos WHERE id_funcion IN ($function_ids_str)";
                $conn->query($delete_tickets);
            }
        }
        
        // 3. Eliminar todas las funciones asociadas a esta película
        $delete_functions = "DELETE FROM funciones WHERE id_pelicula = '$id'";
        $conn->query($delete_functions);
        
        // 4. Finalmente eliminar la película
        $delete_movie = "DELETE FROM peliculas WHERE id_pelicula = '$id'";
        $conn->query($delete_movie);
        
        // Si llegamos aquí, todo fue bien - confirmar la transacción
        $conn->commit();
        
        $mensaje = "Película y todos sus datos asociados han sido eliminados con éxito";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        // Si ocurre algún error, deshacer los cambios
        $conn->rollback();
        $mensaje = "Error al eliminar: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// AGREGAR nueva película
if (isset($_POST['agregar'])) {
    $titulo = $conn->real_escape_string($_POST['titulo']);
    $director = $conn->real_escape_string($_POST['director']);
    $genero = $conn->real_escape_string($_POST['genero']);
    $duracion = $conn->real_escape_string($_POST['duracion']);
    $clasificacion = $conn->real_escape_string($_POST['clasificacion']);
    $sinopsis = $conn->real_escape_string($_POST['sinopsis']);
    $poster_url = $conn->real_escape_string($_POST['poster_url']);
    
    $sql = "INSERT INTO peliculas (titulo, director, genero, duracion, clasificacion, sinopsis, poster_url) 
            VALUES ('$titulo', '$director', '$genero', '$duracion', '$clasificacion', '$sinopsis', '$poster_url')";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Película agregada con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al agregar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

// ACTUALIZAR película
if (isset($_POST['actualizar'])) {
    $id = $conn->real_escape_string($_POST['id_pelicula']);
    $titulo = $conn->real_escape_string($_POST['titulo']);
    $director = $conn->real_escape_string($_POST['director']);
    $genero = $conn->real_escape_string($_POST['genero']);
    $duracion = $conn->real_escape_string($_POST['duracion']);
    $clasificacion = $conn->real_escape_string($_POST['clasificacion']);
    $sinopsis = $conn->real_escape_string($_POST['sinopsis']);
    $poster_url = $conn->real_escape_string($_POST['poster_url']);
    
    $sql = "UPDATE peliculas SET 
            titulo = '$titulo', 
            director = '$director', 
            genero = '$genero', 
            duracion = '$duracion', 
            clasificacion = '$clasificacion', 
            sinopsis = '$sinopsis', 
            poster_url = '$poster_url' 
            WHERE id_pelicula = '$id'";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Película actualizada con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

// Obtener todas las películas
$sql = "SELECT * FROM peliculas";
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
    <title>CinePoint - Gestión de Películas</title>
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
        
        .poster-thumbnail {
            width: 50px;
            height: 75px;
            object-fit: cover;
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
    <!-- Header con navegación -->
    <header class="header">
        <div class="logo">
            <i class="fas fa-film"></i>
            <span>CinePoint</span>
        </div>
        
        <nav class="nav-links">
            <a href="index.php" class="active">
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
            <h1>Películas</h1>
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
        
        <!-- Tabla de películas -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Poster</th>
                    <th>Título</th>
                    <th>Director</th>
                    <th>Género</th>
                    <th>Duración</th>
                    <th>Clasificación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $poster_url = !empty($row['poster_url']) ? $row['poster_url'] : 'https://via.placeholder.com/50x75?text=No+Imagen';
                        $id_field = isset($row['id_pelicula']) ? 'id_pelicula' : 'id';
                        
                        echo "<tr>";
                        echo "<td>{$row[$id_field]}</td>";
                        echo "<td><img src='{$poster_url}' alt='{$row['titulo']}' class='poster-thumbnail'></td>";
                        echo "<td>{$row['titulo']}</td>";
                        echo "<td>{$row['director']}</td>";
                        echo "<td>{$row['genero']}</td>";
                        echo "<td>{$row['duracion']} min</td>";
                        echo "<td>{$row['clasificacion']}</td>";
                        echo "<td class='actions'>";
                        echo "<button class='action-btn edit-btn' onclick='editarPelicula(" . json_encode($row) . ")' title='Editar'><i class='fas fa-edit'></i></button>";
                        echo "<button class='action-btn delete-btn' onclick='confirmarEliminar({$row[$id_field]}, \"{$row['titulo']}\")' title='Eliminar'><i class='fas fa-trash'></i></button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center;padding:20px;'>No hay películas registradas</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal para agregar película -->
    <div id="modalAgregar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalAgregar')">&times;</span>
            <h2>Agregar Nueva Película</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="titulo">Título</label>
                    <input type="text" id="titulo" name="titulo" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="director">Director</label>
                    <input type="text" id="director" name="director" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="genero">Género</label>
                    <input type="text" id="genero" name="genero" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="duracion">Duración (minutos)</label>
                    <input type="number" id="duracion" name="duracion" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="clasificacion">Clasificación</label>
                    <input type="text" id="clasificacion" name="clasificacion" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="sinopsis">Sinopsis</label>
                    <textarea id="sinopsis" name="sinopsis" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="poster_url">URL del Poster</label>
                    <input type="text" id="poster_url" name="poster_url" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModal('modalAgregar')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" name="agregar" class="btn">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para editar película -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
            <h2>Editar Película</h2>
            <form method="POST" action="">
                <input type="hidden" id="edit_id_pelicula" name="id_pelicula">
                <div class="form-group">
                    <label for="edit_titulo">Título</label>
                    <input type="text" id="edit_titulo" name="titulo" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_director">Director</label>
                    <input type="text" id="edit_director" name="director" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_genero">Género</label>
                    <input type="text" id="edit_genero" name="genero" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_duracion">Duración (minutos)</label>
                    <input type="number" id="edit_duracion" name="duracion" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_clasificacion">Clasificación</label>
                    <input type="text" id="edit_clasificacion" name="clasificacion" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_sinopsis">Sinopsis</label>
                    <textarea id="edit_sinopsis" name="sinopsis" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_poster_url">URL del Poster</label>
                    <input type="text" id="edit_poster_url" name="poster_url" class="form-control">
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
            <p>¿Estás seguro que deseas eliminar la película <strong id="nombrePelicula"></strong>?</p>
            <p class="alert alert-error" style="margin-top: 10px;">
                <i class="fas fa-exclamation-triangle"></i> Advertencia: Esta acción eliminará también todas las funciones y boletos asociados a esta película.
            </p>
            <form method="POST" action="">
                <input type="hidden" id="delete_id" name="id_pelicula">
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
        
        // Abrir modal para agregar película
        document.getElementById('btnAgregar').addEventListener('click', function() {
            document.getElementById('modalAgregar').style.display = 'block';
        });
        
        // Función para cerrar modales
        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Función para editar película
        function editarPelicula(pelicula) {
            // Determinar qué campo de ID usar
            var idField = pelicula.id_pelicula !== undefined ? 'id_pelicula' : 'id';
            
            document.getElementById('edit_id_pelicula').value = pelicula[idField];
            document.getElementById('edit_titulo').value = pelicula.titulo;
            document.getElementById('edit_director').value = pelicula.director;
            document.getElementById('edit_genero').value = pelicula.genero;
            document.getElementById('edit_duracion').value = pelicula.duracion;
            document.getElementById('edit_clasificacion').value = pelicula.clasificacion;
            document.getElementById('edit_sinopsis').value = pelicula.sinopsis;
            document.getElementById('edit_poster_url').value = pelicula.poster_url || '';
            
            document.getElementById('modalEditar').style.display = 'block';
        }
        
        // Función para confirmar eliminación
        function confirmarEliminar(id, titulo) {
            document.getElementById('delete_id').value = id;
            document.getElementById('nombrePelicula').textContent = titulo;
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
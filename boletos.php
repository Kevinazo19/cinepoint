<?php
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

// Verificar el rol del usuario actual
$current_user_id = $_SESSION['empleado_id'];
$sql_current_user = "SELECT * FROM empleados WHERE id = '$current_user_id'";
$result_current_user = $conn->query($sql_current_user);
$current_user = $result_current_user->fetch_assoc();
$is_admin = ($current_user['rol'] == 'administrador');

// Verificar la estructura actual de la tabla boletos
$sql_check_table = "SHOW COLUMNS FROM boletos";
$result_check = $conn->query($sql_check_table);
$columns = [];
if ($result_check) {
    while ($row = $result_check->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}

// Si la tabla no existe, crearla con la estructura correcta
if (empty($columns)) {
    $sql_create_table = "CREATE TABLE IF NOT EXISTS boletos (
        id_boleto INT AUTO_INCREMENT PRIMARY KEY,
        id_funcion INT NOT NULL,
        tipo_boleto VARCHAR(20) NOT NULL,
        precio DECIMAL(10,2) NOT NULL,
        numero_asiento VARCHAR(10) NOT NULL,
        fecha_compra DATETIME NOT NULL,
        estado VARCHAR(20) NOT NULL,
        metodo_pago VARCHAR(50) NOT NULL,
        FOREIGN KEY (id_funcion) REFERENCES funciones(id_funcion)
    )";

    if ($conn->query($sql_create_table) === FALSE) {
        die("Error al crear la tabla boletos: " . $conn->error);
    }
}

// Procesar operaciones CRUD
$mensaje = "";
$tipo_mensaje = "";

// ELIMINAR boleto
if (isset($_POST['eliminar']) && isset($_POST['id_boleto'])) {
    $id = $conn->real_escape_string($_POST['id_boleto']);
    
    // Eliminar el boleto
    $sql = "DELETE FROM boletos WHERE id_boleto = '$id'";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Boleto eliminado con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al eliminar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

// AGREGAR nuevo boleto
if (isset($_POST['agregar'])) {
    $id_funcion = $conn->real_escape_string($_POST['id_funcion']);
    $tipo_boleto = $conn->real_escape_string($_POST['tipo_boleto']);
    $precio = $conn->real_escape_string($_POST['precio']);
    $numero_asiento = $conn->real_escape_string($_POST['numero_asiento']);
    $fecha_compra = date('Y-m-d H:i:s'); // Fecha actual
    $estado = $conn->real_escape_string($_POST['estado']);
    $metodo_pago = $conn->real_escape_string($_POST['metodo_pago']);
    
    $sql = "INSERT INTO boletos (id_funcion, tipo_boleto, precio, numero_asiento, fecha_compra, estado, metodo_pago) 
            VALUES ('$id_funcion', '$tipo_boleto', '$precio', '$numero_asiento', '$fecha_compra', '$estado', '$metodo_pago')";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Boleto agregado con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al agregar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

// ACTUALIZAR boleto
if (isset($_POST['actualizar'])) {
    $id = $conn->real_escape_string($_POST['id_boleto']);
    $id_funcion = $conn->real_escape_string($_POST['id_funcion']);
    $tipo_boleto = $conn->real_escape_string($_POST['tipo_boleto']);
    $precio = $conn->real_escape_string($_POST['precio']);
    $numero_asiento = $conn->real_escape_string($_POST['numero_asiento']);
    $estado = $conn->real_escape_string($_POST['estado']);
    $metodo_pago = $conn->real_escape_string($_POST['metodo_pago']);
    
    $sql = "UPDATE boletos SET 
            id_funcion = '$id_funcion', 
            tipo_boleto = '$tipo_boleto', 
            precio = '$precio', 
            numero_asiento = '$numero_asiento', 
            estado = '$estado',
            metodo_pago = '$metodo_pago'
            WHERE id_boleto = '$id'";
    
    if ($conn->query($sql) === TRUE) {
        $mensaje = "Boleto actualizado con éxito";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar: " . $conn->error;
        $tipo_mensaje = "error";
    }
}

// Obtener todos los boletos con información de funciones, películas y salas
$sql = "SELECT b.*, 
        f.fecha as funcion_fecha, f.hora_inicio, f.hora_fin, 
        p.titulo as pelicula_titulo, 
        s.nombre as sala_nombre 
        FROM boletos b 
        LEFT JOIN funciones f ON b.id_funcion = f.id_funcion 
        LEFT JOIN peliculas p ON f.id_pelicula = p.id_pelicula 
        LEFT JOIN salas s ON f.id_sala = s.id_sala 
        ORDER BY b.fecha_compra DESC";
$result = $conn->query($sql);

// Obtener todas las funciones para el formulario, incluyendo películas nuevas
$sql_funciones = "SELECT f.id_funcion, p.titulo, f.fecha, f.hora_inicio, s.nombre as sala_nombre,
                  p.id_pelicula  -- Incluir id_pelicula en la consulta
                 FROM funciones f 
                 INNER JOIN peliculas p ON f.id_pelicula = p.id_pelicula 
                 INNER JOIN salas s ON f.id_sala = s.id_sala 
                 WHERE (f.estado = 'Programada' OR f.estado = 'En curso')
                 ORDER BY p.id_pelicula DESC, f.fecha DESC, f.hora_inicio ASC";
$result_funciones = $conn->query($sql_funciones);

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
    <title>CinePoint - Gestión de Boletos</title>
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
        
        .status-pagado {
            background-color: #4CAF50;
            color: white;
        }
        
        .status-reservado {
            background-color: #2196F3;
            color: white;
        }
        
        .status-cancelado {
            background-color: #F44336;
            color: white;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .filters {
            background-color: #222;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .filters-title {
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: bold;
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
            <a href="boletos.php" class="active">
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
        <!-- Encabezado de página -->
        <div class="page-header">
            <h1>Boletos</h1>
            <button class="btn" id="btnAgregar">
                <i class="fas fa-plus"></i> Vender Boleto
            </button>
        </div>
        
        <!-- Mensajes de alerta -->
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <!-- Filtros -->
        <div class="filters">
            <div class="filters-title">
                <i class="fas fa-filter"></i> Filtros
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="filtro_pelicula">Película</label>
                    <input type="text" id="filtro_pelicula" class="form-control" placeholder="Buscar por película...">
                </div>
                <div class="form-group">
                    <label for="filtro_estado">Estado</label>
                    <select id="filtro_estado" class="form-control">
                        <option value="">Todos</option>
                        <option value="Pagado">Pagado</option>
                        <option value="Reservado">Reservado</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filtro_fecha">Fecha</label>
                    <input type="date" id="filtro_fecha" class="form-control">
                </div>
            </div>
        </div>
        
        <!-- Tabla de boletos -->
        <table id="tabla_boletos">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Película</th>
                    <th>Sala</th>
                    <th>Función</th>
                    <th>Asiento</th>
                    <th>Tipo</th>
                    <th>Precio</th>
                    <th>Método Pago</th>
                    <th>Fecha Compra</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Determinar la clase de estado para el badge
                        $estado_class = '';
                        switch(strtolower($row['estado'])) {
                            case 'pagado':
                                $estado_class = 'status-pagado';
                                break;
                            case 'reservado':
                                $estado_class = 'status-reservado';
                                break;
                            case 'cancelado':
                                $estado_class = 'status-cancelado';
                                break;
                            default:
                                $estado_class = '';
                        }
                        
                        // Formatear fechas
                        $fecha_funcion = date('d/m/Y', strtotime($row['funcion_fecha']));
                        $fecha_compra = date('d/m/Y H:i', strtotime($row['fecha_compra']));
                        
                        echo "<tr data-pelicula='{$row['pelicula_titulo']}' data-estado='{$row['estado']}' data-fecha='{$row['funcion_fecha']}'>";
                        echo "<td>{$row['id_boleto']}</td>";
                        echo "<td>{$row['pelicula_titulo']}</td>";
                        echo "<td>{$row['sala_nombre']}</td>";
                        echo "<td>{$fecha_funcion} {$row['hora_inicio']}</td>";
                        echo "<td>{$row['numero_asiento']}</td>";
                        echo "<td>{$row['tipo_boleto']}</td>";
                        echo "<td>$" . number_format($row['precio'], 2) . "</td>";
                        echo "<td>{$row['metodo_pago']}</td>";
                        echo "<td>{$fecha_compra}</td>";
                        echo "<td><span class='status-badge {$estado_class}'>{$row['estado']}</span></td>";
                        echo "<td class='actions'>";
                        echo "<button class='edit-btn' onclick='editarBoleto(" . json_encode($row) . ")'><i class='fas fa-edit'></i></button>";
                        echo "<button class='delete-btn' onclick='confirmarEliminar({$row['id_boleto']}, \"{$row['pelicula_titulo']}\", \"{$row['numero_asiento']}\")'><i class='fas fa-trash'></i></button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='11' style='text-align:center;padding:20px;'>No hay boletos registrados</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Modal para agregar boleto -->
    <div id="modalAgregar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalAgregar')">&times;</span>
            <h2>Vender Nuevo Boleto</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="id_funcion">Función</label>
                    <select id="id_funcion" name="id_funcion" class="form-control" required onchange="actualizarPrecio()">
                        <option value="">Seleccione una función</option>
                        <?php
                        if ($result_funciones && $result_funciones->num_rows > 0) {
                            while ($funcion = $result_funciones->fetch_assoc()) {
                                $fecha_formateada = date('d/m/Y', strtotime($funcion['fecha']));
                                echo "<option value='{$funcion['id_funcion']}' data-precio-general='0' data-precio-estudiante='0'>{$funcion['titulo']} - {$fecha_formateada} {$funcion['hora_inicio']} - Sala {$funcion['sala_nombre']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo_boleto">Tipo de Boleto</label>
                        <select id="tipo_boleto" name="tipo_boleto" class="form-control" required onchange="actualizarPrecio()">
                            <option value="General">General</option>
                            <option value="Estudiante">Estudiante</option>
                            <option value="Tercera Edad">Tercera Edad</option>
                            <option value="Niño">Niño</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="numero_asiento">Asiento</label>
                        <input type="text" id="numero_asiento" name="numero_asiento" class="form-control" required placeholder="Ej: A12">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="precio">Precio</label>
                        <input type="number" id="precio" name="precio" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" class="form-control" required>
                            <option value="Pagado">Pagado</option>
                            <option value="Reservado">Reservado</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="metodo_pago">Método de Pago</label>
                    <select id="metodo_pago" name="metodo_pago" class="form-control" required>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                        <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Pendiente">Pendiente</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModal('modalAgregar')" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" name="agregar" class="btn">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para editar boleto -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalEditar')">&times;</span>
            <h2>Editar Boleto</h2>
            <form method="POST" action="">
                <input type="hidden" id="edit_id_boleto" name="id_boleto">
                <div class="form-group">
                    <label for="edit_id_funcion">Función</label>
                    <select id="edit_id_funcion" name="id_funcion" class="form-control" required>
                        <?php
                        // Reiniciar el puntero del resultado
                        $result_funciones->data_seek(0);
                        if ($result_funciones && $result_funciones->num_rows > 0) {
                            while ($funcion = $result_funciones->fetch_assoc()) {
                                $fecha_formateada = date('d/m/Y', strtotime($funcion['fecha']));
                                echo "<option value='{$funcion['id_funcion']}'>{$funcion['titulo']} - {$fecha_formateada} {$funcion['hora_inicio']} - Sala {$funcion['sala_nombre']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_tipo_boleto">Tipo de Boleto</label>
                        <select id="edit_tipo_boleto" name="tipo_boleto" class="form-control" required>
                            <option value="General">General</option>
                            <option value="Estudiante">Estudiante</option>
                            <option value="Tercera Edad">Tercera Edad</option>
                            <option value="Niño">Niño</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_numero_asiento">Asiento</label>
                        <input type="text" id="edit_numero_asiento" name="numero_asiento" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_precio">Precio</label>
                        <input type="number" id="edit_precio" name="precio" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_estado">Estado</label>
                        <select id="edit_estado" name="estado" class="form-control" required>
                            <option value="Pagado">Pagado</option>
                            <option value="Reservado">Reservado</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_metodo_pago">Método de Pago</label>
                    <select id="edit_metodo_pago" name="metodo_pago" class="form-control" required>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                        <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                        <option value="Transferencia">Transferencia</option>
                        <option value="Pendiente">Pendiente</option>
                    </select>
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
            <p>¿Estás seguro que deseas eliminar el boleto para <strong id="nombrePelicula"></strong>?</p>
            <p>Asiento: <span id="asientoBoleto"></span></p>
            <form method="POST" action="">
                <input type="hidden" id="delete_id" name="id_boleto">
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
        
        // Abrir modal para agregar boleto
        document.getElementById('btnAgregar').addEventListener('click', function() {
            document.getElementById('modalAgregar').style.display = 'block';
        });
        
        // Función para cerrar modales
        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Función para editar boleto
        function editarBoleto(boleto) {
            document.getElementById('edit_id_boleto').value = boleto.id_boleto;
            
            // Seleccionar la función correcta
            var funcionSelect = document.getElementById('edit_id_funcion');
            for (var i = 0; i < funcionSelect.options.length; i++) {
                if (funcionSelect.options[i].value == boleto.id_funcion) {
                    funcionSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Seleccionar el tipo de boleto correcto
            var tipoSelect = document.getElementById('edit_tipo_boleto');
            for (var i = 0; i < tipoSelect.options.length; i++) {
                if (tipoSelect.options[i].value === boleto.tipo_boleto) {
                    tipoSelect.selectedIndex = i;
                    break;
                }
            }
            
            document.getElementById('edit_numero_asiento').value = boleto.numero_asiento;
            document.getElementById('edit_precio').value = boleto.precio;
            
            // Seleccionar el estado correcto
            var estadoSelect = document.getElementById('edit_estado');
            for (var i = 0; i < estadoSelect.options.length; i++) {
                if (estadoSelect.options[i].value === boleto.estado) {
                    estadoSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Seleccionar el método de pago correcto
            var metodoPagoSelect = document.getElementById('edit_metodo_pago');
            for (var i = 0; i < metodoPagoSelect.options.length; i++) {
                if (metodoPagoSelect.options[i].value === boleto.metodo_pago) {
                    metodoPagoSelect.selectedIndex = i;
                    break;
                }
            }
            
            document.getElementById('modalEditar').style.display = 'block';
        }
        
        // Función para confirmar eliminación
        function confirmarEliminar(id, pelicula, asiento) {
            document.getElementById('delete_id').value = id;
            document.getElementById('nombrePelicula').textContent = pelicula;
            document.getElementById('asientoBoleto').textContent = asiento;
            document.getElementById('modalEliminar').style.display = 'block';
        }
        
        // Función para actualizar precio según tipo de boleto
        function actualizarPrecio() {
            var funcionSelect = document.getElementById('id_funcion');
            var tipoBoleto = document.getElementById('tipo_boleto').value;
            var precioInput = document.getElementById('precio');
            
            // Aquí se podría implementar una lógica para obtener el precio según la función y tipo de boleto
            // Por ahora, establecemos un precio predeterminado según el tipo
            var precio = 0;
            
            switch(tipoBoleto) {
                case 'General':
                    precio = 120.00;
                    break;
                case 'Estudiante':
                    precio = 80.00;
                    break;
                case 'Tercera Edad':
                    precio = 70.00;
                    break;
                case 'Niño':
                    precio = 60.00;
                    break;
                default:
                    precio = 100.00;
            }
            
            precioInput.value = precio;
        }
        
        // Filtros para la tabla
        document.getElementById('filtro_pelicula').addEventListener('keyup', filtrarTabla);
        document.getElementById('filtro_estado').addEventListener('change', filtrarTabla);
        document.getElementById('filtro_fecha').addEventListener('change', filtrarTabla);
        
        function filtrarTabla() {
            var filtroPelicula = document.getElementById('filtro_pelicula').value.toLowerCase();
            var filtroEstado = document.getElementById('filtro_estado').value;
            var filtroFecha = document.getElementById('filtro_fecha').value;
            
            var filas = document.getElementById('tabla_boletos').getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (var i = 0; i < filas.length; i++) {
                var fila = filas[i];
                var pelicula = fila.getAttribute('data-pelicula').toLowerCase();
                var estado = fila.getAttribute('data-estado');
                var fecha = fila.getAttribute('data-fecha');
                
                var mostrarPelicula = pelicula.indexOf(filtroPelicula) > -1;
                var mostrarEstado = filtroEstado === '' || estado === filtroEstado;
                var mostrarFecha = filtroFecha === '' || fecha === filtroFecha;
                
                if (mostrarPelicula && mostrarEstado && mostrarFecha) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            }
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
        
        // Establecer precio por defecto al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            actualizarPrecio();
        });
    </script>
</body>
</html>
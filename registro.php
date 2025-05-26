<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "cinepoint";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre     = $conn->real_escape_string($_POST['nombre'] ?? '');
    $correo     = $conn->real_escape_string($_POST['correo'] ?? '');
    $contrasena = $conn->real_escape_string($_POST['contraseña'] ?? '');

    if ($nombre && $correo && $contrasena) {
        $check = $conn->query("SELECT id FROM empleados WHERE correo = '$correo'");
        if ($check && $check->num_rows > 0) {
            $mensaje = "El correo \"$correo\" ya está registrado.";
        } else {
            // Simplificada la consulta SQL para usar solo las columnas que existen
            $sql = "INSERT INTO empleados (nombre, correo, contraseña) 
                    VALUES ('$nombre', '$correo', '$contrasena')";
            
            if ($conn->query($sql) === TRUE) {
                $empleado_id = $conn->insert_id;
                $_SESSION['empleado_id'] = $empleado_id;
                $_SESSION['empleado_nombre'] = $nombre;
                header("Location: index.php");
                exit;
            } else {
                $mensaje = "Error al registrar: " . $conn->error;
            }
        }
    } else {
        $mensaje = "Por favor completa todos los campos.";
    }
}
$conn->close();

$movie_posters = [
    'https://image.tmdb.org/t/p/w500/rktDFPbfHfUbArZ6OOOKsXcv0Bm.jpg',
    'https://image.tmdb.org/t/p/w500/8kSerJrhrJWKLk1LViesGcnrUPE.jpg',
    'https://image.tmdb.org/t/p/w500/qJ2tW6WMUDux911r6m7haRef0WH.jpg',
    'https://image.tmdb.org/t/p/w500/velWPhVMQeQKcxggNEU8YmIo52R.jpg',
    'https://image.tmdb.org/t/p/w500/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg',
    'https://image.tmdb.org/t/p/w500/q6y0Go1tsGEsmtFryDOJo3dEmqu.jpg',
    'https://image.tmdb.org/t/p/w500/saHP97rTPS5eLmrLQEcANmKrsFl.jpg',
    'https://image.tmdb.org/t/p/w500/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg',
    'https://image.tmdb.org/t/p/w500/9xjZS2rlVxm8SFx8kPC3aIGCOYQ.jpg',
    'https://image.tmdb.org/t/p/w500/sF1U4EUQS8YHUYjNl3pMGNIQyr0.jpg',
    'https://image.tmdb.org/t/p/w500/vUUqzWa2LnHIVqkaKVlVGkVcZIW.jpg',
    'https://image.tmdb.org/t/p/w500/pci1ArYW7oJ2eyTo2NMYEKHHiCP.jpg',
    'https://image.tmdb.org/t/p/w500/1g0dhYtq4irTY1GPXvft6k4YLjm.jpg',
    'https://image.tmdb.org/t/p/w500/6FfCtAuVAW8XJjZ7eWeLibRLWTw.jpg',
    'https://image.tmdb.org/t/p/w500/lPsD10PP4rgUGiGR4CCXA6iY0QQ.jpg',
    'https://image.tmdb.org/t/p/w500/rEm96ib0sPiZBADNKBHKBv5bve9.jpg',
    'https://image.tmdb.org/t/p/w500/hEjK9A9BkNXejFW4tfacVAEHtkn.jpg',
    'https://image.tmdb.org/t/p/w500/5KCVkau1HEl7ZzfPsKAPM0sMiKc.jpg',
    'https://image.tmdb.org/t/p/w500/3bhkrj58Vtu7enYsRolD1fZdja1.jpg',
    'https://image.tmdb.org/t/p/w500/wuMc08IPKEatf9rnMNXvIDxqP4W.jpg'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro - CinePoint Admin</title>
  <style>
    * { 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body, html { 
        height: 100%; 
        width: 100%;
        overflow-x: hidden;
        position: relative;
        background-color: #000;
    }

    .fondo-peliculas {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: 1;
    }
    
    .galeria-peliculas {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
    }
    
    .fila-posters {
        display: flex;
        animation: deslizar 60s linear infinite;
        width: 300%;
        margin-bottom: 10px;
    }
    
    .poster {
        flex: 0 0 auto;
        width: 180px;
        height: 260px;
        margin: 10px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        transition: transform 0.3s;
        background-size: cover;
        background-position: center;
        background-color: #333;
    }
    
    .poster:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(255,255,255,0.3);
        z-index: 10;
    }
    
    @keyframes deslizar { 
        0% { transform: translateX(0); } 
        100% { transform: translateX(-50%); }
    }
    
    .capa-oscura {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 2;
    }

    .contenedor {
        position: relative;
        z-index: 3;
        max-width: 450px;
        margin: 60px auto;
        background: rgba(15, 15, 15, 0.9);
        padding: 40px;
        border-radius: 15px;
        color: #fff;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0,0,0,0.6);
        backdrop-filter: blur(5px);
    }
    
    .logo {
        width: 180px;
        margin-bottom: 20px;
    }
    
    .encabezado h1 { 
        font-size: 36px; 
        color: #e50914; 
        margin-bottom: 10px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.5);
    }
    
    .encabezado p { 
        color: #ccc; 
        margin-bottom: 30px;
        font-size: 16px;
    }

    form { 
        text-align: left; 
    }
    
    .grupo-formulario { 
        margin-bottom: 25px; 
    }
    
    .grupo-formulario label {
        display: block;
        margin-bottom: 8px;
        color: #ccc;
        font-size: 14px;
        font-weight: 500;
    }
    
    .control-formulario {
        width: 100%;
        padding: 14px 18px;
        border-radius: 6px;
        border: 1px solid #444;
        background: rgba(255,255,255,0.1);
        color: #fff;
        font-size: 16px;
        transition: all 0.3s;
    }
    
    .control-formulario:focus {
        border-color: #e50914;
        background: rgba(255,255,255,0.2);
        outline: none;
        box-shadow: 0 0 0 2px rgba(229, 9, 20, 0.25);
    }
    
    .boton-enviar {
        width: 100%;
        padding: 15px;
        background: #e50914;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .boton-enviar:hover { 
        background: #b50710;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    
    .boton-enviar:active {
        transform: translateY(0);
        box-shadow: none;
    }
    
    .pie-pagina { 
        margin-top: 30px; 
        color: #777; 
        font-size: 14px;
    }
    
    .mensaje {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 6px;
        font-weight: 500;
    }
    
    .mensaje.exito {
        background: rgba(40, 167, 69, 0.2);
        border: 1px solid #28a745;
        color: #28a745;
    }
    
    .mensaje.error {
        background: rgba(220, 53, 69, 0.2);
        border: 1px solid #dc3545;
        color: #dc3545;
    }
    
    .enlace-login {
        margin-top: 20px;
        font-size: 14px;
        color: #aaa;
    }
    
    .enlace-login a {
        color: #e50914;
        text-decoration: none;
    }
    
    .enlace-login a:hover {
        text-decoration: underline;
    }
    
    @media (max-width: 768px) {
        .contenedor {
            margin: 40px 20px;
            padding: 30px;
        }
        
        .encabezado h1 {
            font-size: 28px;
        }
    }
  </style>
</head>
<body>
  <div class="fondo-peliculas">
    <div class="galeria-peliculas">
      <div class="fila-posters">
        <?php 
        for($i=0; $i<20; $i++): 
            $poster_index = $i % count($movie_posters);
            $poster_url = $movie_posters[$poster_index];
        ?>
          <div class="poster" style="background-image: url('<?= $poster_url ?>');"></div>
        <?php endfor; ?>
        
        <?php for($i=0; $i<20; $i++): 
            $poster_index = $i % count($movie_posters);
            $poster_url = $movie_posters[$poster_index];
        ?>
          <div class="poster" style="background-image: url('<?= $poster_url ?>');"></div>
        <?php endfor; ?>
      </div>
      
      <div class="fila-posters" style="animation-direction: reverse;">
        <?php 
        for($i=count($movie_posters)-1; $i>=0; $i--): 
            $poster_url = $movie_posters[$i];
        ?>
          <div class="poster" style="background-image: url('<?= $poster_url ?>');"></div>
        <?php endfor; ?>
        
        <?php for($i=count($movie_posters)-1; $i>=0; $i--): 
            $poster_url = $movie_posters[$i];
        ?>
          <div class="poster" style="background-image: url('<?= $poster_url ?>');"></div>
        <?php endfor; ?>
      </div>
      
      <div class="fila-posters" style="animation-duration: 80s;">
        <?php 
        $mixed_posters = $movie_posters;
        shuffle($mixed_posters);
        foreach($mixed_posters as $poster_url): 
        ?>
          <div class="poster" style="background-image: url('<?= $poster_url ?>');"></div>
        <?php endforeach; ?>
        
        <?php foreach($mixed_posters as $poster_url): ?>
          <div class="poster" style="background-image: url('<?= $poster_url ?>');"></div>
        <?php endforeach; ?>
      </div>
      
      <div class="fila-posters" style="animation-direction: reverse; animation-duration: 70s;">
        <?php 
        shuffle($mixed_posters);
        foreach($mixed_posters as $poster_url): 
        ?>
          <div class="poster" style="background-image: url('<?= $poster_url ?>');"></div>
        <?php endforeach; ?>
        
        <?php foreach($mixed_posters as $poster_url): ?>
          <div class="poster" style="background-image: url('<?= $poster_url ?>');"></div>
        <?php endforeach; ?>
      </div>
    </div>
    
    <div class="capa-oscura"></div>
  </div>

  <div class="contenedor">
    <div class="encabezado">
      <h1>CinePoint</h1>
      <p>Registro de Empleados</p>
    </div>
    
    <?php if ($mensaje): ?>
      <div class="mensaje <?= strpos($mensaje, 'exitoso') !== false ? 'exito' : 'error' ?>">
        <?= $mensaje ?>
      </div>
    <?php endif; ?>
    
    <form action="registro.php" method="POST">
      <div class="grupo-formulario">
        <label for="nombre">Nombre completo</label>
        <input id="nombre" name="nombre" type="text" class="control-formulario" placeholder="Ingresa tu nombre" required>
      </div>
      <div class="grupo-formulario">
        <label for="correo">Correo electrónico</label>
        <input id="correo" name="correo" type="email" class="control-formulario" placeholder="Ingresa tu correo" required>
      </div>
      <div class="grupo-formulario">
        <label for="contrasena">Contraseña</label>
        <input id="contrasena" name="contraseña" type="password" class="control-formulario" placeholder="Crea una contraseña" required>
      </div>
      <button type="submit" class="boton-enviar">Registrarme</button>
    </form>
    
    <div class="enlace-login">
      <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>
    
    <div class="pie-pagina">
      <p>© <?= date("Y") ?> CinePoint - Panel Administrativo</p>
    </div>
  </div>
  
  <script>
    function checkImageLoaded(imgElement) {
      imgElement.onerror = function() {
        const colors = [
          'linear-gradient(45deg, #FF5252, #FF1744)',
          'linear-gradient(45deg, #536DFE, #3D5AFE)',
          'linear-gradient(45deg, #FFC107, #FFAB00)',
          'linear-gradient(45deg, #4CAF50, #388E3C)',
          'linear-gradient(45deg, #9C27B0, #7B1FA2)',
          'linear-gradient(45deg, #2196F3, #1976D2)',
          'linear-gradient(45deg, #F44336, #D32F2F)',
          'linear-gradient(45deg, #009688, #00796B)'
        ];
        const randomColor = colors[Math.floor(Math.random() * colors.length)];
        imgElement.style.backgroundImage = randomColor;
      };
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      const posters = document.querySelectorAll('.poster');
      posters.forEach(function(poster) {
        checkImageLoaded(poster);
      });
    });
  </script>
</body>
</html>
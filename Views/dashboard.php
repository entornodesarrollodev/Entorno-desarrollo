<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Principal - ITCA-FEPADE</title>
  <style>
    :root { --itca-red: #a8201a; --itca-red-dark: #841813; --bg-main: #f5f6f8; }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { background-color: var(--bg-main); color: #333; }
    header { background-color: var(--itca-red); color: white; display: flex; justify-content: space-between; align-items: center; padding: 1rem 2rem; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    header h2 { font-size: 1.25rem; }
    .btn-logout { background-color: #ffffff; color: var(--itca-red); border: none; padding: 0.5rem 1rem; border-radius: 4px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s; }
    .btn-logout:hover { background-color: #f3dede; }
    .main-container { padding: 2.5rem; max-width: 1000px; margin: 0 auto; }
    .welcome-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .welcome-card h3 { color: var(--itca-red); margin-bottom: 0.5rem; }
  </style>
</head>
<body>
  <header>
    <h2>ITCA-FEPADE - Panel de Gestión</h2>
    <!-- Tarea #6: Botón de Cerrar Sesión -->
    <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
  </header>
  <main class="main-container">
    <div class="welcome-card">
      <h3>Bienvenido al Sistema</h3>
      <p>Has iniciado sesión correctamente en el entorno de desarrollo académico.</p>
    </div>
  </main>
</body>
</html>
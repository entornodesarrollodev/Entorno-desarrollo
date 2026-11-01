<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Proyectos - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="/dashboard.php">🏠 Inicio</a></li>
      <li><a href="estudiantes_listado.php">🎓 Estudiantes</a></li>
      <li><a href="proyectos.php" class="active">📁 Proyectos</a></li>
      <li><a href="horas_bandeja.php">📋 Registros</a></li>
    </ul>
  </aside>
  <div class="main-wrapper">
    <header class="topbar"><span>📁 Gestión de proyectos</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area">
      <div class="breadcrumb">🏠 Inicio &gt; Proyectos</div>
      <div class="page-header">
        <div><h2>Gestión de Proyectos</h2><p style="color:var(--text-muted);font-size:.85rem;">Consulta, crea, actualiza y controla el estado de los proyectos.</p></div>
        <a href="cronograma_nuevo.php" class="btn-red">+ Nueva actividad</a>
      </div>
      <div id="project-management"></div>
    </main>
  </div>
</body>
</html>

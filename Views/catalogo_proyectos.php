<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catálogo de Proyectos - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="/dashboard.php">🏠 Inicio</a></li>
      <li><a href="catalogo_proyectos.php" class="active">📁 Proyectos disponibles</a></li>
      <li><a href="estudiantes_resumen_horas.php">⏱️ Mi resumen</a></li>
      <li><a href="horas_registrar.php">📋 Registrar horas</a></li>
    </ul>
  </aside>
  <div class="main-wrapper">
    <header class="topbar"><span>📁 Catálogo de proyectos</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area">
      <div class="breadcrumb">🏠 Inicio &gt; Catálogo</div>
      <h2 style="margin-top:.5rem;">Proyectos disponibles</h2>
      <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:1.5rem;">Consulta los proyectos activos y su modalidad.</p>
      <p>¿Te interesa participar? Envía una solicitud para el proyecto desde <a href="/mis_solicitudes.php?nueva=1">Mis solicitudes</a>. La participación requiere aprobación del personal autorizado.</p>
      <div id="project-catalog"></div>
    </main>
  </div>
</body>
</html>

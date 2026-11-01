<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel del estudiante - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand">
      <h2>ITCA-FEPADE</h2>
      <p>Portal académico</p>
    </div>
    <ul class="sidebar-nav">
      <li><a href="dashboard.php" class="active">🏠 <span>Inicio</span></a></li>
      <li><a href="catalogo_proyectos.php">📁 <span>Proyectos disponibles</span></a></li>
      <li><a href="estudiantes_resumen_horas.php">⏱️ <span>Mi resumen de horas</span></a></li>
      <li><a href="horas_registrar.php">📝 <span>Registrar horas</span></a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar">
      <span>Panel del estudiante</span>
      <a href="logout.php" class="btn-logout">Cerrar sesión</a>
    </header>

    <main class="content-area">
      <section class="dashboard-hero">
        <h1>Bienvenido al Sistema</h1>
        <p>Consulta tus proyectos, revisa tus actividades y registra las horas realizadas desde un solo lugar.</p>
      </section>

      <section>
        <div class="page-header">
          <div>
            <h2>Accesos rápidos</h2>
            <p class="page-description">Continúa con tus actividades académicas.</p>
          </div>
        </div>
        <div class="dashboard-links">
          <a class="dashboard-link" href="catalogo_proyectos.php"><span class="link-icon">📁</span><span><strong>Proyectos disponibles</strong><small>Explora el catálogo activo</small></span></a>
          <a class="dashboard-link" href="estudiantes_resumen_horas.php"><span class="link-icon">⏱️</span><span><strong>Mi resumen de horas</strong><small>Consulta horas aprobadas</small></span></a>
          <a class="dashboard-link" href="horas_registrar.php"><span class="link-icon">📝</span><span><strong>Registrar horas</strong><small>Envía un nuevo registro</small></span></a>
        </div>
      </section>
    </main>
  </div>
</body>
</html>

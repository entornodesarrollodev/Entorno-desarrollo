<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de administración - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand">
      <h2>ITCA-FEPADE</h2>
      <p>Sistema de Proyección Social</p>
    </div>
    <ul class="sidebar-nav"></ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>Panel de administración</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area">
      <div class="breadcrumb">Inicio / Administración</div>
      <div class="page-header">
        <div>
          <h1>Resumen institucional</h1>
          <p class="page-description">Gestiona participantes, proyectos y actividad del sistema desde un solo lugar.</p>
        </div>
      </div>

      <section class="stats-grid" aria-label="Resumen del sistema">
        <article class="stat-card">
          <h2>Estudiantes registrados</h2>
          <div class="number">0</div>
        </article>
        <article class="stat-card">
          <h2>Personal activo</h2>
          <div class="number">0</div>
        </article>
        <article class="stat-card">
          <h2>Roles del sistema</h2>
          <div class="number">0</div>
        </article>
        <div id="registration-request-summary"></div>
      </section>
    </main>
  </div>
</body>
</html>

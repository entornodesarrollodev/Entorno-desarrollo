<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resumen de horas - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA-FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav"></ul>
  </aside>
  <div class="main-wrapper">
    <header class="topbar"><span>Resumen de horas</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area">
      <div class="breadcrumb"><a href="/dashboard.php">Inicio</a> / Horas / Resumen</div>
      <div class="page-header">
        <div><h1>Mi resumen de horas</h1><p class="page-description">Consulta las horas aprobadas, pendientes y rechazadas por proyecto y periodo.</p></div>
        <a href="/horas_registrar.php" class="btn-red">Registrar horas</a>
      </div>
      <form method="GET" action="/estudiantes_resumen_horas.php" class="form-card filter-card">
        <div class="filter-grid">
          <?php // Estos filtros mantienen el periodo consultado sin insertar texto sin escapar. ?>
          <div class="form-group"><label for="hours-from">Desde</label><input id="hours-from" type="date" name="desde" value="<?= htmlspecialchars((string)($_GET['desde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
          <div class="form-group"><label for="hours-to">Hasta</label><input id="hours-to" type="date" name="hasta" value="<?= htmlspecialchars((string)($_GET['hasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        </div>
        <div class="form-actions"><button type="submit" class="btn-red">Filtrar periodo</button><a class="btn-gray" href="/estudiantes_resumen_horas.php">Limpiar</a></div>
      </form>
      <section id="hours-summary">
        <div class="stats-grid">
          <article class="stat-card"><h2>Horas aprobadas</h2><div class="number" id="approved-hours">0.00</div></article>
          <article class="stat-card"><h2>Horas pendientes</h2><div class="number" id="pending-hours">0.00</div></article>
          <article class="stat-card"><h2>Horas rechazadas</h2><div class="number" id="rejected-hours">0.00</div></article>
        </div>
        <section class="table-container">
          <table>
            <thead><tr><th>Proyecto</th><th>Periodo</th><th>Aprobadas</th><th>Pendientes</th><th>Rechazadas</th></tr></thead>
            <tbody><tr><td colspan="5" class="empty-state">Cargando resumen...</td></tr></tbody>
          </table>
        </section>
      </section>
    </main>
  </div>
</body>
</html>

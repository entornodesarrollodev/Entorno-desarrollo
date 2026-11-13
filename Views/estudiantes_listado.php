<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de expedientes - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA-FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav"></ul>
  </aside>
  <div class="main-wrapper">
    <header class="topbar"><span>Gestión de expedientes</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area">
      <div class="breadcrumb">Inicio / Expedientes</div>
      <div class="page-header">
        <div>
          <h1>Expedientes</h1>
          <p class="page-description">Busca y filtra expedientes por carrera, año académico, modalidad y estado.</p>
        </div>
        <a href="estudiantes_nuevo.php" class="btn-red" data-permission="estudiantes.crear">+ Nuevo estudiante</a>
      </div>
      <form id="student-filter-form" method="GET" action="/estudiantes_listado.php" class="form-card filter-card">
        <div class="filter-grid">
          <div class="form-group">
            <label for="student-search">Buscar expediente</label>
            <?php // Este valor conserva la búsqueda y se escapa antes de imprimirla. ?>
            <input id="student-search" name="q" type="search" placeholder="Nombre, carnet o identificador" value="<?= htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="form-group"><label for="career-filter">Carrera</label><select id="career-filter" name="carrera"><option value="">Todas las carreras</option></select></div>
          <div class="form-group"><label for="year-filter">Año académico</label><select id="year-filter" name="anio_academico"><option value="">Todos</option><option value="1">1</option><option value="2">2</option></select></div>
          <div class="form-group"><label for="mode-filter">Modalidad</label><select id="mode-filter" name="modalidad_participacion"><option value="">Todas</option><option>Voluntariado</option><option>Horas sociales</option><option>Práctica profesional</option></select></div>
          <div class="form-group"><label for="status-filter">Estado</label><select id="status-filter" name="estado"><option value="Activo">Activos</option><option value="Inactivo">Inactivos</option><option value="todos">Todos</option></select></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-red">Aplicar filtros</button>
          <a class="btn-gray" href="/estudiantes_listado.php">Limpiar filtros</a>
        </div>
      </form>
      <section class="table-container" aria-label="Resultados de búsqueda">
        <table>
          <thead><tr><th>ID</th><th>Nombre completo</th><th>Carnet</th><th>Carrera</th><th>Año</th><th>Modalidad</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody><tr><td colspan="8" class="empty-state">Cargando expedientes...</td></tr></tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>

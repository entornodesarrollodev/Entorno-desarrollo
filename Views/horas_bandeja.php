<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Validación de horas - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA-FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav"></ul>
  </aside>
  <div class="main-wrapper">
    <header class="topbar"><span>Validación de horas</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area">
      <div class="breadcrumb"><a href="/dashboard_admin.php">Inicio</a> / Horas / Pendientes</div>
      <div class="page-header">
        <div><h1>Registros pendientes</h1><p class="page-description">Revisa la evidencia antes de aprobar. El rechazo requiere un motivo.</p></div>
      </div>
      <section class="table-container">
        <table>
          <thead><tr><th>Estudiante</th><th>Proyecto y actividad</th><th>Fecha</th><th>Horas</th><th>Descripción</th><th>Reportado por</th><th>Acciones</th></tr></thead>
          <tbody><tr><td colspan="7" class="empty-state">Cargando registros...</td></tr></tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>

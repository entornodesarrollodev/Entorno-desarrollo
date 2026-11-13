<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registrar horas - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA-FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav"></ul>
  </aside>
  <div class="main-wrapper">
    <header class="topbar"><span>Registro de horas</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area">
      <div class="breadcrumb"><a href="/dashboard.php">Inicio</a> / Horas / Nuevo registro</div>
      <div class="page-header">
        <div><h1>Registrar horas de actividad</h1><p class="page-description">Envía las horas realizadas en una actividad asignada para su validación.</p></div>
      </div>
      <form action="/horas_registrar.php" method="POST" class="form-card">
        <div class="form-grid">
          <div class="form-group full"><label for="activity">Actividad *</label><select id="activity" name="actividad_id" required><option value="">Selecciona una actividad</option></select></div>
          <div class="form-group"><label for="activity-date">Fecha realizada *</label><input id="activity-date" type="date" name="fecha_actividad" required></div>
          <div class="form-group"><label for="hours">Horas realizadas *</label><input id="hours" type="number" name="horas" min="0.01" max="8" step="0.01" required></div>
          <div class="form-group full"><label for="hours-description">Descripción del trabajo realizado *</label><textarea id="hours-description" name="descripcion" rows="4" maxlength="1000" required></textarea></div>
          <div class="form-group full">
            <label class="checkbox-label"><input type="checkbox" name="confirmar_duplicado" value="1"> Si aparece un duplicado, confirmo que deseo enviar este registro para la misma actividad y fecha.</label>
          </div>
        </div>
        <div class="form-actions form-actions-end">
          <a href="/estudiantes_resumen_horas.php" class="btn-gray">Cancelar</a>
          <button type="submit" class="btn-red">Enviar para validación</button>
        </div>
      </form>
    </main>
  </div>
</body>
</html>

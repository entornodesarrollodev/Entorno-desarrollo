<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar Horas de Actividad - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="#">🏠 Inicio</a></li>
      <li><a href="estudiantes_listado.php">🎓 Estudiantes</a></li>
      <li><a href="#" class="active">📋 Registros de Horas</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Juan Carlos Pérez (Estudiante)</span></header>
    
    <main class="content-area">
      <div class="breadcrumb">🏠 Inicio > Registros > Nuevo Registro</div>
      <h2 style="margin-top: 0.5rem;">Registrar Horas de Actividad</h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Ingrese las horas realizadas en la actividad del proyecto asignado.</p>

      <form action="#" method="POST" class="form-card">
        <h4 style="margin-bottom: 1rem; color: var(--itca-red);">📋 Detalle de Horas</h4>
        
        <div class="form-grid">
          <div class="form-group">
            <label>Actividad del Cronograma *</label>
            <select name="actividad_id" required>
              <option value="">Seleccione la actividad...</option>
              <option value="1">Taller de Capacitación Básica</option>
              <option value="2">Soporte Técnico en Centro Escolar</option>
            </select>
          </div>
          <div class="form-group">
            <label>Número de Horas Realizadas *</label>
            <input type="number" name="horas" min="1" max="12" required placeholder="Ej: 4">
          </div>
          <div class="form-group" style="grid-column: span 2;">
            <label>Descripción / Evidencia del trabajo realizado *</label>
            <textarea name="descripcion" rows="3" required placeholder="Describa brevemente las tareas ejecutadas..." style="width: 100%; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 6px;"></textarea>
          </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
          <a href="estudiantes_resumen_horas.php" style="padding: 0.6rem 1.2rem; text-decoration: none; color: var(--text-muted); border: 1px solid var(--border-color); border-radius: 6px;">Cancelar</a>
          <button type="submit" style="background: var(--itca-red); color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer;">💾 Enviar para Validación</button>
        </div>
      </form>
    </main>
  </div>
</body>
</html>
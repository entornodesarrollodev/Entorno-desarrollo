<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Asignar Estudiantes a Proyecto - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="#">🏠 Inicio</a></li>
      <li><a href="estudiantes_listado.php">🎓 Estudiantes</a></li>
      <li><a href="#" class="active">📁 Proyectos</a></li>
      <li><a href="#">📋 Registros</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Brayan Vásquez (Administrador)</span></header>
    
    <main class="content-area">
      <div class="breadcrumb">🏠 Inicio > Proyectos > Asignar Estudiantes</div>
      <h2 style="margin-top: 0.5rem;">Asignación de Estudiantes</h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Seleccione el proyecto y busque a los estudiantes activos para su participación.</p>

      <form action="#" method="POST" class="form-card">
        <h4 style="margin-bottom: 1rem; color: var(--itca-red);">📁 Datos de la Asignación</h4>
        
        <div class="form-grid">
          <div class="form-group">
            <label>Proyecto Destino *</label>
            <select name="proyecto_id" required>
              <option value="">Seleccione un proyecto...</option>
              <option value="1">Alfabetización Comunitaria - Fase II</option>
              <option value="2">Apoyo Informático Escolar</option>
            </select>
          </div>
          <div class="form-group">
            <label>Buscar Estudiante (Carnet o Nombre) *</label>
            <input type="text" name="busqueda_estudiante" required placeholder="Ej: 202400123 o Juan Carlos">
          </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
          <a href="proyectos_listado.php" style="padding: 0.6rem 1.2rem; text-decoration: none; color: var(--text-muted); border: 1px solid var(--border-color); border-radius: 6px;">Cancelar</a>
          <button type="submit" style="background: var(--itca-red); color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer;">💾 Asignar Estudiante</button>
        </div>
      </form>
    </main>
  </div>
</body>
</html>
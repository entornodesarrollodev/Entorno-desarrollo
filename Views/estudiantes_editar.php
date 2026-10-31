<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Estudiante - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="#">🏠 Inicio</a></li>
      <li><a href="estudiantes_listado.php" class="active">🎓 Estudiantes</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Brayan Vásquez (Administrador)</span></header>

    <main class="content-area">
      <div class="breadcrumb">🏠 Inicio > Estudiantes > Editar</div>
      <h2 style="margin-top: 0.5rem;">Editar Estudiante</h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Modifique la información del estudiante.</p>

      <form action="#" method="POST" class="form-card">
        <div class="form-grid">
          <div class="form-group">
            <label>Nombres *</label>
            <input type="text" name="nombres" value="Juan Carlos" required>
          </div>
          <div class="form-group">
            <label>Apellidos *</label>
            <input type="text" name="apellidos" value="Pérez López" required>
          </div>
          <div class="form-group">
            <label>Carnet *</label>
            <input type="text" name="carnet" value="202400123" required>
          </div>
          <div class="form-group">
            <label>DUI</label>
            <input type="text" name="dui" value="12345678-9">
          </div>
          <div class="form-group">
            <label>Correo institucional *</label>
            <input type="email" name="correo" value="juan.perez@itca.edu.sv" required>
          </div>
          <div class="form-group">
            <label>Teléfono</label>
            <input type="text" name="telefono" value="7654-3210">
          </div>
          <div class="form-group">
            <label>Estado *</label>
            <select name="estado" required>
              <option value="Activo" selected>Activo</option>
              <option value="Inactivo">Inactivo</option>
            </select>
          </div>
          <div class="form-group full">
            <label>Dirección</label>
            <textarea name="direccion" rows="2">Santa Ana, El Salvador</textarea>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
          <a href="estudiantes_listado.php" class="btn-gray">Cancelar</a>
          <button type="submit" class="btn-red">🔄 Actualizar Estudiante</button>
        </div>
      </form>
    </main>
  </div>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo Estudiante - ITCA-FEPADE</title>
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
      <div class="breadcrumb">🏠 Inicio > Estudiantes > Nuevo</div>
      <h2 style="margin-top: 0.5rem;">Nuevo Estudiante</h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Ingrese la información del estudiante.</p>

      <form action="#" method="POST" class="form-card">
        <h4 style="margin-bottom: 1rem; color: var(--itca-red);">👤 Datos del estudiante</h4>
        <div class="form-grid">
          <div class="form-group">
            <label>Nombres *</label>
            <input type="text" name="nombres" required placeholder="Ingrese los nombres">
          </div>
          <div class="form-group">
            <label>Apellidos *</label>
            <input type="text" name="apellidos" required placeholder="Ingrese los apellidos">
          </div>
          <div class="form-group">
            <label>Carnet *</label>
            <input type="text" name="carnet" required placeholder="Ingrese el número de carnet">
          </div>
          <div class="form-group">
            <label>DUI</label>
            <input type="text" name="dui" placeholder="Ingrese el DUI (opcional)">
          </div>
          <div class="form-group">
            <label>Fecha de nacimiento</label>
            <input type="date" name="fecha_nacimiento">
          </div>
          <div class="form-group">
            <label>Género</label>
            <select name="genero">
              <option value="">Seleccione...</option>
              <option value="M">Masculino</option>
              <option value="F">Femenino</option>
            </select>
          </div>
          <div class="form-group">
            <label>Correo institucional *</label>
            <input type="email" name="correo" required placeholder="ejemplo@itca.edu.sv">
          </div>
          <div class="form-group">
            <label>Teléfono</label>
            <input type="text" name="telefono" placeholder="XXXX-XXXX">
          </div>
          <div class="form-group">
            <label>Carrera *</label>
            <select name="carrera" required>
              <option value="">Seleccione la carrera</option>
              <option value="software">Ing. en Desarrollo de Software</option>
              <option value="sistemas">Ing. en Sistemas Informáticos</option>
            </select>
          </div>
          <div class="form-group">
            <label>Estado *</label>
            <select name="estado" required>
              <option value="Activo">Activo</option>
              <option value="Inactivo">Inactivo</option>
            </select>
          </div>
          <div class="form-group full">
            <label>Dirección</label>
            <textarea name="direccion" rows="2" placeholder="Ingrese la dirección de residencia (opcional)"></textarea>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
          <a href="estudiantes_listado.php" class="btn-gray">Cancelar</a>
          <button type="submit" class="btn-red">💾 Guardar Estudiante</button>
        </div>
      </form>
    </main>
  </div>
</body>
</html>
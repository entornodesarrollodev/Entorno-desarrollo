<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar estudiante - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA-FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav"></ul>
  </aside>
  <div class="main-wrapper">
    <header class="topbar"><span>Editar expediente</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area">
      <div class="breadcrumb"><a href="/estudiantes_listado.php">Estudiantes</a> / Editar expediente</div>
      <div class="page-header">
        <div><h1>Actualizar expediente</h1><p class="page-description">Actualiza los datos permitidos y guarda los cambios.</p></div>
      </div>
      <form action="/estudiantes_editar.php" method="POST" class="form-card">
        <?php // Este bloque muestra el formulario completo solo al personal autorizado. ?>
        <?php if ($canManageStudents): ?>
        <div class="form-grid">
          <div class="form-group"><label for="student-first-name">Nombres *</label><input id="student-first-name" type="text" name="nombres" required></div>
          <div class="form-group"><label for="student-last-name">Apellidos *</label><input id="student-last-name" type="text" name="apellidos" required></div>
          <div class="form-group"><label for="student-carnet">Carnet *</label><input id="student-carnet" type="text" name="carnet" required></div>
          <div class="form-group"><label for="student-email">Correo institucional *</label><input id="student-email" type="email" name="correo" required></div>
          <div class="form-group"><label for="student-phone">Teléfono</label><input id="student-phone" type="tel" name="telefono"></div>
          <div class="form-group"><label for="student-career">Carrera *</label><input id="student-career" type="text" name="carrera" required></div>
          <div class="form-group"><label for="student-year">Año académico *</label><select id="student-year" name="anio_academico" required><option value="1">1</option><option value="2">2</option></select></div>
          <div class="form-group"><label for="student-mode">Modalidad de participación *</label><select id="student-mode" name="modalidad_participacion" required><option>Voluntariado</option><option>Horas sociales</option><option>Práctica profesional</option></select></div>
          <div class="form-group"><label for="student-dui">DUI</label><input id="student-dui" type="text" name="dui"></div>
          <div class="form-group"><label for="student-birth-date">Fecha de nacimiento</label><input id="student-birth-date" type="date" name="fecha_nacimiento"></div>
          <div class="form-group"><label for="student-gender">Género</label><select id="student-gender" name="genero"><option value="">Prefiero no indicar</option><option value="M">Masculino</option><option value="F">Femenino</option></select></div>
          <div class="form-group"><label for="student-status">Estado del expediente</label><select id="student-status" name="estado"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select></div>
          <div class="form-group full"><label for="student-address">Dirección</label><textarea id="student-address" name="direccion" rows="2"></textarea></div>
        </div>
        <?php else: ?>
        <p class="alert">Como estudiante puedes actualizar únicamente tus datos de contacto. Los datos académicos y de identificación requieren autorización institucional.</p>
        <div class="form-grid">
          <div class="form-group"><label for="student-email">Correo institucional *</label><input id="student-email" type="email" name="correo" required></div>
          <div class="form-group"><label for="student-phone">Teléfono</label><input id="student-phone" type="tel" name="telefono"></div>
          <div class="form-group full"><label for="student-address">Dirección</label><textarea id="student-address" name="direccion" rows="2"></textarea></div>
        </div>
        <?php endif; ?>
        <div class="form-actions form-actions-end">
          <?php // Este enlace devuelve al listado institucional o al panel del estudiante. ?>
          <a href="<?= $canManageStudents ? '/estudiantes_listado.php' : '/dashboard.php' ?>" class="btn-gray">Cancelar</a>
          <button type="submit" class="btn-red">Guardar cambios</button>
        </div>
      </form>
    </main>
  </div>
</body>
</html>

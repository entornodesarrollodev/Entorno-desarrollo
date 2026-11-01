<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Asignar estudiantes - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA-FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav"></ul>
  </aside>
  <div class="main-wrapper">
    <header class="topbar"><span>Asignaciones de proyecto</span><a href="logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area">
      <div class="breadcrumb"><a href="/proyectos.php">Proyectos</a> / Asignar estudiantes</div>
      <div class="page-header">
        <div><h1>Asignar estudiantes</h1><p class="page-description">Vincula estudiantes activos; puedes seleccionar varios y definir una función común.</p></div>
      </div>
      <form method="GET" action="/proyectos_asignar.php" class="form-card filter-card">
        <input type="hidden" name="proyecto_id" value="<?= htmlspecialchars((string)($_GET['proyecto_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group"><label for="student-search">Buscar estudiantes activos</label><input id="student-search" name="q" value="<?= htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre, carnet o correo"></div>
        <div class="form-actions"><button class="btn-gray" type="submit">Buscar</button></div>
      </form>
      <form action="/proyectos_asignar.php" method="POST" class="form-card">
        <div class="form-grid">
          <div class="form-group full"><label for="target-project">Proyecto *</label><select id="target-project" name="proyecto_id" required><option value="">Selecciona un proyecto activo</option></select></div>
          <div class="form-group full"><label for="active-students">Estudiantes *</label><select id="active-students" name="estudiante_ids[]" multiple size="8" required></select><small>Usa Ctrl (Windows) o Cmd (Mac) para seleccionar varios.</small></div>
          <div class="form-group"><label for="assignment-function">Rol o función *</label><input id="assignment-function" name="funcion" maxlength="120" value="Participante" required></div>
        </div>
        <div class="form-actions form-actions-end"><a href="/proyectos.php" class="btn-gray">Cancelar</a><button type="submit" class="btn-red">Asignar estudiantes</button></div>
      </form>
    </main>
  </div>
</body>
</html>

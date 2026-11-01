<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Expediente del estudiante - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA-FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav"></ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>Expediente del estudiante</span><a href="/logout.php" class="btn-logout">Cerrar sesión</a></header>
    <main class="content-area student-record">
      <div class="breadcrumb"><a href="/dashboard.php">Inicio</a> / <a href="/estudiantes_listado.php">Estudiantes</a> / Expediente</div>

      <section class="form-card student-record-header">
        <div class="student-record-identity">
          <div class="student-record-avatar" aria-hidden="true">👤</div>
          <div>
            <h1>{{student_name}} <span class="badge {{status_class}}">{{status}}</span></h1>
            <p class="page-description">{{career}} · Carnet: {{carnet}}</p>
          </div>
        </div>
        <div class="student-record-actions">{{actions}}</div>
      </section>

      <section class="student-record-grid" aria-label="Datos del estudiante">
        <article class="form-card">
          <h2 class="section-title">Datos personales</h2>
          <dl class="student-data-list">
            <div><dt>Identificador</dt><dd>{{student_id}}</dd></div>
            <div><dt>Nombres</dt><dd>{{first_names}}</dd></div>
            <div><dt>Apellidos</dt><dd>{{last_names}}</dd></div>
            <div><dt>Carnet</dt><dd>{{personal_carnet}}</dd></div>
            <div><dt>DUI</dt><dd>{{dui}}</dd></div>
            <div><dt>Correo</dt><dd>{{email}}</dd></div>
            <div><dt>Teléfono</dt><dd>{{phone}}</dd></div>
            <div><dt>Dirección</dt><dd>{{address}}</dd></div>
          </dl>
        </article>
        <article class="form-card">
          <h2 class="section-title">Información académica</h2>
          <dl class="student-data-list">
            <div><dt>Carrera</dt><dd>{{academic_career}}</dd></div>
            <div><dt>Año académico</dt><dd>{{academic_year}}</dd></div>
            <div><dt>Modalidad</dt><dd>{{participation_mode}}</dd></div>
            <div><dt>Estado</dt><dd><span class="badge {{status_class}}">{{status}}</span></dd></div>
            <div><dt>Fecha de registro</dt><dd>{{created_at}}</dd></div>
          </dl>
        </article>
      </section>

      <section class="form-card student-record-section">
        <h2 class="section-title">Proyectos relacionados</h2>
        <div class="table-container">
          <table>
            <thead><tr><th>Código</th><th>Proyecto</th><th>Modalidad</th><th>Estado</th><th>Asignación</th></tr></thead>
            <tbody>{{projects}}</tbody>
          </table>
        </div>
      </section>

      <section class="form-card student-record-section">
        <div class="page-header">
          <div><h2 class="section-title">Horas y actividades</h2><p class="page-description">El total oficial incluye únicamente las horas aprobadas.</p></div>
        </div>
        {{hour_totals}}
        <div class="table-container">
          <table>
            <thead><tr><th>Proyecto</th><th>Actividad</th><th>Horas</th><th>Estado</th><th>Registro</th></tr></thead>
            <tbody>{{hours}}</tbody>
          </table>
        </div>
      </section>

      <section class="form-card student-record-section">
        <h2 class="section-title">Evaluaciones</h2>
        <div class="table-container">
          <table>
            <thead><tr><th>Proyecto</th><th>Calificación</th><th>Comentarios</th><th>Evaluador</th><th>Fecha</th></tr></thead>
            <tbody>{{evaluations}}</tbody>
          </table>
        </div>
      </section>

      <section class="form-card student-record-section">
        <h2 class="section-title">Documentos</h2>
        <div class="table-container">
          <table>
            <thead><tr><th>Documento</th><th>Tipo</th><th>Fecha</th><th>Subido por</th></tr></thead>
            <tbody>{{documents}}</tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>
</html>

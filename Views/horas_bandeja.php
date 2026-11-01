<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Bandeja de Validación de Horas - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="#">🏠 Inicio</a></li>
      <li><a href="#" class="active">📋 Validación de Horas</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Licda. Martínez (Coordinadora)</span></header>
    
    <main class="content-area">
      <div class="breadcrumb">🏠 Inicio > Registros > Validación Pendiente</div>
      <h2 style="margin-top: 0.5rem;">Bandeja de Horas Pendientes</h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Revise y apruebe o rechace las horas reportadas por los estudiantes.</p>

      <div class="form-card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
          <thead style="background: var(--bg-main); border-bottom: 1px solid var(--border-color);">
            <tr>
              <th style="padding: 1rem;">Carnet</th>
              <th style="padding: 1rem;">Estudiante</th>
              <th style="padding: 1rem;">Actividad</th>
              <th style="padding: 1rem;">Horas</th>
              <th style="padding: 1rem;">Estado</th>
              <th style="padding: 1rem; text-align: center;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 1rem;">202400123</td>
              <td style="padding: 1rem; font-weight: 500;">Juan Carlos Pérez</td>
              <td style="padding: 1rem;">Taller de Computación</td>
              <td style="padding: 1rem;">4 hrs</td>
              <td style="padding: 1rem;"><span style="background: #fef3c7; color: #d97706; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">Pendiente</span></td>
              <td style="padding: 1rem; text-align: center;">
                <button style="background: #16a34a; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">✔️ Aprobar</button>
                <button style="background: #dc2626; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; margin-left: 0.4rem;">❌ Rechazar</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
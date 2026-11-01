<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Mi Resumen de Horas - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="#">🏠 Inicio</a></li>
      <li><a href="#" class="active">⏱️ Mi Resumen de Horas</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Juan Carlos Pérez (Estudiante)</span></header>
    
    <main class="content-area">
      <div class="breadcrumb">🏠 Inicio > Horas Sociales > Resumen</div>
      <h2 style="margin-top: 0.5rem;">Mi Resumen de Horas Sociales</h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Consulte el balance general y el desglose de horas aprobadas por proyecto.</p>

      <!-- Tarjetas de Estadísticas -->
      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="form-card" style="padding: 1.2rem; text-align: center; margin: 0;">
          <span style="color: var(--text-muted); font-size: 0.85rem;">Total Aprobadas</span>
          <h3 style="color: #16a34a; font-size: 1.8rem; margin-top: 0.3rem;">32 hrs</h3>
        </div>
        <div class="form-card" style="padding: 1.2rem; text-align: center; margin: 0;">
          <span style="color: var(--text-muted); font-size: 0.85rem;">Pendientes de Validación</span>
          <h3 style="color: #d97706; font-size: 1.8rem; margin-top: 0.3rem;">4 hrs</h3>
        </div>
        <div class="form-card" style="padding: 1.2rem; text-align: center; margin: 0;">
          <span style="color: var(--text-muted); font-size: 0.85rem;">Meta Requerida</span>
          <h3 style="color: var(--itca-red); font-size: 1.8rem; margin-top: 0.3rem;">500 hrs</h3>
        </div>
      </div>

      <!-- Tabla de Desglose por Proyecto -->
      <div class="form-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem; background: var(--bg-main); border-bottom: 1px solid var(--border-color); font-weight: 600;">
          📁 Desglose por Proyecto y Período
        </div>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
          <thead style="background: #f1f5f9; border-bottom: 1px solid var(--border-color);">
            <tr>
              <th style="padding: 1rem;">Proyecto</th>
              <th style="padding: 1rem;">Período</th>
              <th style="padding: 1rem;">Horas Aprobadas</th>
              <th style="padding: 1rem;">Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 1rem; font-weight: 500;">Alfabetización Comunitaria - Fase II</td>
              <td style="padding: 1rem;">Ciclo I - 2026</td>
              <td style="padding: 1rem;">32 hrs</td>
              <td style="padding: 1rem;"><span style="background: #dcfce7; color: #16a34a; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">En Proceso</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
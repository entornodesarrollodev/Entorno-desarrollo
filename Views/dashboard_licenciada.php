<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel Principal Licenciada - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
  <style>
    .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .kpi-card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 1.2rem; display: flex; align-items: center; gap: 1rem; }
    .kpi-icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
    .donut-chart { width: 140px; height: 140px; border-radius: 50%; background: conic-gradient(var(--itca-red) 0% 72%, #f59e0b 72% 89%, #9ca3af 89% 100%); display: flex; align-items: center; justify-content: center; }
    .donut-hole { width: 92px; height: 92px; background: #fff; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    .bar-chart { display: flex; align-items: flex-end; justify-content: space-around; height: 150px; padding-top: 1rem; border-bottom: 1px solid var(--border-color); }
    .bar-col { display: flex; flex-direction: column; align-items: center; gap: 0.4rem; width: 32px; }
    .bar-fill { width: 100%; background: var(--itca-red); border-radius: 4px 4px 0 0; }
    .event-row { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border-color); }
    .event-row:last-child { border-bottom: none; }
    .date-box { background: #fee2e2; color: var(--itca-red); border-radius: 6px; padding: 0.35rem 0.6rem; text-align: center; font-weight: 700; font-size: 0.75rem; min-width: 48px; }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="dashboard_licenciada.php" class="active">🏠 Panel principal</a></li>
      <li><a href="estudiantes_listado.php">🎓 Estudiantes</a></li>
      <li><a href="proyectos_asignar.php">📂 Proyectos</a></li>
      <li><a href="consulta_respuestas.php">📊 Respuestas</a></li>
      <li><a href="calendario_actividades.php">📅 Calendario</a></li>
      <li><a href="diplomas_generar.php">🏅 Diplomas</a></li>
      <li><a href="cartas_participacion.php">📄 Cartas de participación</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Licenciada</span></header>

    <main class="content-area">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
          <h2>🎛️ Panel principal</h2>
          <p style="color: var(--text-muted); font-size: 0.85rem;">Bienvenida, Licenciada. Aquí puedes consultar el estado general de la Proyección Social.</p>
        </div>
        <span style="background: #fff; border: 1px solid var(--border-color); padding: 0.5rem 0.9rem; border-radius: 6px; font-size: 0.85rem; color: #374151;">📅 Miércoles, 11 de noviembre de 2026</span>
      </div>

      <!-- KPIs -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-icon" style="background: #fee2e2; color: var(--itca-red);">👥</div>
          <div>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Estudiantes</span>
            <h3 style="font-size: 1.6rem; margin: 0.1rem 0;">128</h3>
            <span style="font-size: 0.75rem; color: #16a34a;">^ +12% respecto al mes anterior</span>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon" style="background: #e0f2fe; color: #0284c7;">📂</div>
          <div>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Proyectos</span>
            <h3 style="font-size: 1.6rem; margin: 0.1rem 0;">18</h3>
            <span style="font-size: 0.75rem; color: #16a34a;">^ +3 proyectos nuevos</span>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon" style="background: #fef3c7; color: #d97706;">📅</div>
          <div>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Actividades</span>
            <h3 style="font-size: 1.6rem; margin: 0.1rem 0;">47</h3>
            <span style="font-size: 0.75rem; color: #16a34a;">^ +8 actividades esta semana</span>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon" style="background: #ede9fe; color: #7c3aed;">📋</div>
          <div>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Respuestas</span>
            <h3 style="font-size: 1.6rem; margin: 0.1rem 0;">96</h3>
            <span style="font-size: 0.75rem; color: #16a34a;">^ +20% de participación</span>
          </div>
        </div>
      </div>

      <!-- Gráficas -->
      <div class="charts-grid">
        <div class="form-card" style="margin: 0;">
          <h4 style="color: var(--itca-red); margin-bottom: 1.2rem;">📊 Avance de proyectos</h4>
          <div style="display: flex; align-items: center; justify-content: space-around;">
            <div class="donut-chart">
              <div class="donut-hole">
                <strong style="font-size: 1.3rem;">72%</strong>
                <span style="font-size: 0.65rem; color: var(--text-muted);">De avance<br>general</span>
              </div>
            </div>
            <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.7rem;">
              <div><span style="display:inline-block; width:12px; height:12px; background:var(--itca-red); border-radius:2px; margin-right:6px;"></span> En ejecución <strong>13 (72%)</strong></div>
              <div><span style="display:inline-block; width:12px; height:12px; background:#f59e0b; border-radius:2px; margin-right:6px;"></span> Pendientes <strong>3 (17%)</strong></div>
              <div><span style="display:inline-block; width:12px; height:12px; background:#9ca3af; border-radius:2px; margin-right:6px;"></span> Finalizados <strong>2 (11%)</strong></div>
            </div>
          </div>
        </div>

        <div class="form-card" style="margin: 0;">
          <h4 style="color: var(--itca-red); margin-bottom: 1rem;">📈 Actividades por mes</h4>
          <div class="bar-chart">
            <div class="bar-col"><div class="bar-fill" style="height: 35px;"></div><span style="font-size: 0.75rem;">Jun</span></div>
            <div class="bar-col"><div class="bar-fill" style="height: 55px;"></div><span style="font-size: 0.75rem;">Jul</span></div>
            <div class="bar-col"><div class="bar-fill" style="height: 68px;"></div><span style="font-size: 0.75rem;">Ago</span></div>
            <div class="bar-col"><div class="bar-fill" style="height: 82px;"></div><span style="font-size: 0.75rem;">Sep</span></div>
            <div class="bar-col"><div class="bar-fill" style="height: 98px;"></div><span style="font-size: 0.75rem;">Oct</span></div>
            <div class="bar-col"><div class="bar-fill" style="height: 115px;"></div><span style="font-size: 0.75rem;">Nov</span></div>
          </div>
        </div>
      </div>

      <!-- Actividades Próximas y Notificaciones -->
      <div class="charts-grid" style="margin-bottom: 0;">
        <div class="form-card" style="margin: 0;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 0.8rem;">
            <h4 style="color: var(--itca-red);">📅 Actividades próximas</h4>
            <a href="calendario_actividades.php" style="font-size: 0.8rem; color: var(--itca-red); text-decoration: none;">Ver calendario →</a>
          </div>
          <div class="event-row">
            <div style="display: flex; gap: 0.8rem; align-items: center;">
              <div class="date-box">NOV<br><span style="font-size: 1rem;">12</span></div>
              <div><strong>Reunión de seguimiento</strong><br><span style="font-size: 0.8rem; color: var(--text-muted);">📍 Sala 2, ITCA-FEPADE</span></div>
            </div>
            <span style="font-size: 0.8rem; color: var(--text-muted);">🕙 10:00 a. m.</span>
          </div>
          <div class="event-row">
            <div style="display: flex; gap: 0.8rem; align-items: center;">
              <div class="date-box">NOV<br><span style="font-size: 1rem;">14</span></div>
              <div><strong>Jornada de limpieza</strong><br><span style="font-size: 0.8rem; color: var(--text-muted);">📍 Parque Central, Santa Tecla</span></div>
            </div>
            <span style="font-size: 0.8rem; color: var(--text-muted);">🕗 08:00 a. m.</span>
          </div>
          <div class="event-row">
            <div style="display: flex; gap: 0.8rem; align-items: center;">
              <div class="date-box">NOV<br><span style="font-size: 1rem;">15</span></div>
              <div><strong>Entrega de evidencias</strong><br><span style="font-size: 0.8rem; color: var(--text-muted);">💻 Plataforma del sistema</span></div>
            </div>
            <span style="font-size: 0.8rem; color: var(--text-muted);">🕛 11:59 p. m.</span>
          </div>
        </div>

        <div class="form-card" style="margin: 0;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 0.8rem;">
            <h4 style="color: var(--itca-red);">🔔 Notificaciones recientes</h4>
            <a href="crear_plantilla.php" style="font-size: 0.8rem; color: var(--itca-red); text-decoration: none;">Ver todas →</a>
          </div>
          <div class="event-row">
            <div><strong>Nueva actividad registrada</strong><br><span style="font-size: 0.8rem; color: var(--text-muted);">Se ha creado la actividad "Charla comunitaria".</span></div>
            <span style="font-size: 0.75rem; color: var(--text-muted);">Hace 5 min</span>
          </div>
          <div class="event-row">
            <div><strong>Respuesta recibida</strong><br><span style="font-size: 0.8rem; color: var(--text-muted);">El estudiante Carlos Mejía ha completado el formulario.</span></div>
            <span style="font-size: 0.75rem; color: var(--text-muted);">Hace 18 min</span>
          </div>
          <div class="event-row">
            <div><strong>Proyecto actualizado</strong><br><span style="font-size: 0.8rem; color: var(--text-muted);">Se han realizado cambios en el proyecto "Apoyo Comunitario".</span></div>
            <span style="font-size: 0.75rem; color: var(--text-muted);">Hace 35 min</span>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
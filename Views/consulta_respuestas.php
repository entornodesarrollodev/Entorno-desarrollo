<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Consulta de Respuestas - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
  <style>
    .filter-bar { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; align-items: end; margin-bottom: 1.5rem; }
    .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    .kpi-card { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem; display: flex; align-items: center; gap: 0.9rem; }
    .tabs { display: flex; gap: 1.5rem; border-bottom: 2px solid var(--border-color); margin-bottom: 1.5rem; }
    .tab { padding: 0.6rem 0.2rem; font-weight: 600; font-size: 0.9rem; color: var(--text-muted); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -2px; }
    .tab.active { color: var(--itca-red); border-bottom-color: var(--itca-red); }
    .charts-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
    .donut-resp { width: 130px; height: 130px; border-radius: 50%; background: conic-gradient(#22c55e 0% 56%, #3b82f6 56% 84%, #eab308 84% 96%, #ef4444 96% 100%); display: flex; align-items: center; justify-content: center; }
    .donut-hole { width: 82px; height: 82px; background: #fff; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .v-bars { display: flex; align-items: flex-end; justify-content: space-around; height: 140px; border-bottom: 1px solid var(--border-color); padding-top: 1rem; }
    .v-bar-item { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; font-size: 0.78rem; }
    .v-bar { width: 38px; border-radius: 4px 4px 0 0; }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="dashboard_licenciada.php">🏠 Inicio</a></li>
      <li><a href="estudiantes_listado.php">🎓 Estudiantes</a></li>
      <li><a href="proyectos_asignar.php">📂 Proyectos</a></li>
      <li><a href="consulta_respuestas.php" class="active">📊 Respuestas</a></li>
      <li><a href="calendario_actividades.php">📅 Calendario</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Coordinador</span></header>

    <main class="content-area">
      <div class="breadcrumb">Cuestionarios > Respuestas</div>
      <h2 style="margin-top: 0.4rem;">📋 Consulta de respuestas</h2>
      <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.2rem;">Visualiza y analiza las respuestas de los cuestionarios aplicados a estudiantes.</p>

      <!-- Filtros Superiores -->
      <div class="form-card filter-bar">
        <div class="form-group">
          <label>Cuestionario</label>
          <select style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-color); border-radius: 6px;"><option>Evaluación de la actividad</option></select>
        </div>
        <div class="form-group">
          <label>Proyecto</label>
          <select style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-color); border-radius: 6px;"><option>Apoyo Comunitario en la Zona Norte</option></select>
        </div>
        <div class="form-group">
          <label>Actividad</label>
          <select style="width: 100%; padding: 0.55rem; border: 1px solid var(--border-color); border-radius: 6px;"><option>Jornada de limpieza</option></select>
        </div>
        <button type="button" style="background: var(--itca-red); color: #fff; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: 600; cursor: pointer;">🌪️ Aplicar filtros</button>
      </div>

      <!-- KPIs -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div style="font-size: 1.5rem; background: #fee2e2; color: var(--itca-red); padding: 0.5rem; border-radius: 8px;">👥</div>
          <div><strong style="font-size: 1.3rem;">25</strong><br><span style="font-size: 0.78rem; color: var(--text-muted);">Respuestas registradas</span></div>
        </div>
        <div class="kpi-card">
          <div style="font-size: 1.5rem; background: #ede9fe; color: #7c3aed; padding: 0.5rem; border-radius: 8px;">📝</div>
          <div><strong style="font-size: 1.3rem;">10</strong><br><span style="font-size: 0.78rem; color: var(--text-muted);">Preguntas</span></div>
        </div>
        <div class="kpi-card">
          <div style="font-size: 1.5rem; background: #dcfce7; color: #16a34a; padding: 0.5rem; border-radius: 8px;">✔️</div>
          <div><strong style="font-size: 1.3rem;">100 %</strong><br><span style="font-size: 0.78rem; color: var(--text-muted);">Tasa de respuesta</span></div>
        </div>
        <div class="kpi-card">
          <div style="font-size: 1.5rem; background: #e0f2fe; color: #0284c7; padding: 0.5rem; border-radius: 8px;">📅</div>
          <div><strong style="font-size: 1.2rem;">15 mar. 2025</strong><br><span style="font-size: 0.78rem; color: var(--text-muted);">Fecha de aplicación</span></div>
        </div>
      </div>

      <!-- Pestañas -->
      <div class="tabs">
        <a href="#" class="tab active">📊 Resumen general</a>
        <a href="#" class="tab">👥 Respuestas por estudiante</a>
      </div>

      <!-- Gráficos -->
      <div class="charts-grid">
        <div class="form-card" style="margin: 0;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h4 style="color: var(--itca-red);">📋 Resultados del cuestionario</h4>
            <select style="padding: 0.35rem 0.6rem; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.8rem;"><option>Pregunta 1</option></select>
          </div>
          <p style="font-weight: 600; font-size: 0.9rem; margin-bottom: 1rem;">1. ¿Cómo califica la organización de la actividad?</p>
          <div style="display: flex; align-items: center; justify-content: space-around;">
            <div class="donut-resp">
              <div class="donut-hole"><strong style="font-size: 1.2rem;">25</strong><span style="font-size: 0.7rem; color: var(--text-muted);">Respuestas</span></div>
            </div>
            <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.5rem; width: 55%;">
              <div style="display:flex; justify-content:space-between;"><span>🟢 Excelente</span><strong>56 % (14)</strong></div>
              <div style="display:flex; justify-content:space-between;"><span>🔵 Buena</span><strong>28 % (7)</strong></div>
              <div style="display:flex; justify-content:space-between;"><span>🟡 Regular</span><strong>12 % (3)</strong></div>
              <div style="display:flex; justify-content:space-between;"><span>🔴 Deficiente</span><strong>4 % (1)</strong></div>
            </div>
          </div>
        </div>

        <div class="form-card" style="margin: 0;">
          <h4 style="color: var(--itca-red); margin-bottom: 1rem;">📊 Gráfico general</h4>
          <div class="v-bars">
            <div class="v-bar-item"><strong>14</strong><div class="v-bar" style="height: 95px; background: #22c55e;"></div><span>Excelente</span></div>
            <div class="v-bar-item"><strong>7</strong><div class="v-bar" style="height: 50px; background: #3b82f6;"></div><span>Buena</span></div>
            <div class="v-bar-item"><strong>3</strong><div class="v-bar" style="height: 25px; background: #eab308;"></div><span>Regular</span></div>
            <div class="v-bar-item"><strong>1</strong><div class="v-bar" style="height: 10px; background: #ef4444;"></div><span>Deficiente</span></div>
          </div>
        </div>
      </div>

      <!-- Tabla Detalle de Respuestas -->
      <div class="form-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem; background: var(--bg-main); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
          <strong>📄 Detalle de respuestas</strong>
          <button type="button" style="background: #fff; border: 1px solid var(--border-color); padding: 0.45rem 0.9rem; border-radius: 6px; font-size: 0.82rem; font-weight: 600; cursor: pointer;">📥 Exportar (Excel)</button>
        </div>
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
          <thead style="background: #f8fafc; border-bottom: 1px solid var(--border-color);">
            <tr>
              <th style="padding: 0.8rem 1rem;">#</th>
              <th style="padding: 0.8rem 1rem;">Estudiante</th>
              <th style="padding: 0.8rem 1rem;">Carné</th>
              <th style="padding: 0.8rem 1rem;">Fecha de respuesta</th>
              <th style="padding: 0.8rem 1rem;">Calificación general</th>
              <th style="padding: 0.8rem 1rem; text-align: center;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 0.8rem 1rem;">1</td>
              <td style="padding: 0.8rem 1rem; font-weight: 500;">Carlos Andrés Ramírez López</td>
              <td style="padding: 0.8rem 1rem;">202410123</td>
              <td style="padding: 0.8rem 1rem;">15 mar. 2025, 09:14 a. m.</td>
              <td style="padding: 0.8rem 1rem;"><span style="background: #dcfce7; color: #16a34a; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">Excelente</span></td>
              <td style="padding: 0.8rem 1rem; text-align: center;"><button style="background:#fff; border:1px solid var(--border-color); padding:0.3rem 0.6rem; border-radius:4px; font-size:0.8rem; cursor:pointer;">👁️ Ver respuestas</button></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 0.8rem 1rem;">2</td>
              <td style="padding: 0.8rem 1rem; font-weight: 500;">María Fernanda Gómez Torres</td>
              <td style="padding: 0.8rem 1rem;">202410145</td>
              <td style="padding: 0.8rem 1rem;">15 mar. 2025, 09:27 a. m.</td>
              <td style="padding: 0.8rem 1rem;"><span style="background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">Buena</span></td>
              <td style="padding: 0.8rem 1rem; text-align: center;"><button style="background:#fff; border:1px solid var(--border-color); padding:0.3rem 0.6rem; border-radius:4px; font-size:0.8rem; cursor:pointer;">👁️ Ver respuestas</button></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 0.8rem 1rem;">3</td>
              <td style="padding: 0.8rem 1rem; font-weight: 500;">José Eduardo Martínez Rivera</td>
              <td style="padding: 0.8rem 1rem;">202410167</td>
              <td style="padding: 0.8rem 1rem;">15 mar. 2025, 09:35 a. m.</td>
              <td style="padding: 0.8rem 1rem;"><span style="background: #dcfce7; color: #16a34a; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">Excelente</span></td>
              <td style="padding: 0.8rem 1rem; text-align: center;"><button style="background:#fff; border:1px solid var(--border-color); padding:0.3rem 0.6rem; border-radius:4px; font-size:0.8rem; cursor:pointer;">👁️ Ver respuestas</button></td>
            </tr>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td style="padding: 0.8rem 1rem;">4</td>
              <td style="padding: 0.8rem 1rem; font-weight: 500;">Ana Sofía Molina Castillo</td>
              <td style="padding: 0.8rem 1rem;">202410189</td>
              <td style="padding: 0.8rem 1rem;">15 mar. 2025, 09:42 a. m.</td>
              <td style="padding: 0.8rem 1rem;"><span style="background: #fef3c7; color: #b45309; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">Regular</span></td>
              <td style="padding: 0.8rem 1rem; text-align: center;"><button style="background:#fff; border:1px solid var(--border-color); padding:0.3rem 0.6rem; border-radius:4px; font-size:0.8rem; cursor:pointer;">👁️ Ver respuestas</button></td>
            </tr>
            <tr>
              <td style="padding: 0.8rem 1rem;">5</td>
              <td style="padding: 0.8rem 1rem; font-weight: 500;">Daniel Alejandro Santos Vega</td>
              <td style="padding: 0.8rem 1rem;">202410201</td>
              <td style="padding: 0.8rem 1rem;">15 mar. 2025, 09:50 a. m.</td>
              <td style="padding: 0.8rem 1rem;"><span style="background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">Buena</span></td>
              <td style="padding: 0.8rem 1rem; text-align: center;"><button style="background:#fff; border:1px solid var(--border-color); padding:0.3rem 0.6rem; border-radius:4px; font-size:0.8rem; cursor:pointer;">👁️ Ver respuestas</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
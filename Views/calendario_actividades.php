<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Calendario de Actividades - ITCA-FEPADE</title>
  <link rel="stylesheet" href="css/estudiantes.css">
  <style>
    .cal-layout { display: grid; grid-template-columns: 2.3fr 1fr; gap: 1.5rem; }
    .cal-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .cal-table th { background: #fdf2f2; color: var(--itca-red); padding: 0.6rem; font-size: 0.82rem; border: 1px solid var(--border-color); }
    .cal-table td { height: 86px; vertical-align: top; padding: 0.4rem; border: 1px solid var(--border-color); font-size: 0.8rem; background: #fff; }
    .cal-table td.muted { color: #9ca3af; background: #f9fafb; }
    .cal-ev { display: block; margin-top: 0.3rem; padding: 0.2rem 0.35rem; border-radius: 4px; font-size: 0.7rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600; }
    .ev-green { background: #dcfce7; color: #166534; }
    .ev-blue { background: #dbeafe; color: #1e40af; }
    .ev-purple { background: #f3e8ff; color: #6b21a8; }
    .ev-yellow { background: #fef3c7; color: #92400e; }
    .ev-red { background: #fee2e2; color: #991b1b; }
    .day-active { display: inline-flex; width: 24px; height: 24px; border-radius: 50%; background: var(--itca-red); color: #fff; align-items: center; justify-content: center; font-weight: 700; }
    .legend-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.8rem; margin-top: 0.6rem; }
    .dot { display: inline-block; width: 12px; height: 12px; border-radius: 3px; margin-right: 6px; vertical-align: middle; }
  </style>
</head>
<body>
  <aside class="sidebar">
    <div class="sidebar-brand"><h2>ITCA FEPADE</h2><p>Sistema de Proyección Social</p></div>
    <ul class="sidebar-nav">
      <li><a href="dashboard_licenciada.php">🏠 Inicio</a></li>
      <li><a href="estudiantes_listado.php">🎓 Estudiantes</a></li>
      <li><a href="proyectos_asignar.php">📂 Proyectos</a></li>
      <li><a href="consulta_respuestas.php">📊 Respuestas</a></li>
      <li><a href="calendario_actividades.php" class="active">📅 Calendario</a></li>
    </ul>
  </aside>

  <div class="main-wrapper">
    <header class="topbar"><span>👤 Coordinador</span></header>

    <main class="content-area">
      <div class="breadcrumb">Actividades > Calendario</div>
      <div style="display: flex; justify-content: space-between; align-items: center; margin: 0.4rem 0 1.2rem;">
        <div>
          <h2>📅 Calendario de actividades</h2>
          <p style="color: var(--text-muted); font-size: 0.85rem;">Consulta y gestiona las actividades, proyectos y eventos del sistema de proyección social.</p>
        </div>
        <a href="cronograma_nuevo.php" style="background: var(--itca-red); color: #fff; padding: 0.6rem 1.1rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.88rem;">+ Nueva actividad</a>
      </div>

      <!-- Barra de Controles del Calendario -->
      <div class="form-card" style="padding: 0.8rem 1rem; margin-bottom: 1.2rem; display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; gap: 0.5rem; align-items: center;">
          <button style="padding: 0.4rem 0.7rem; border: 1px solid var(--border-color); background: #fff; border-radius: 6px; cursor: pointer;">&lt;</button>
          <select style="padding: 0.45rem 0.9rem; border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600;">
            <option>📅 Marzo de 2025</option>
          </select>
          <button style="padding: 0.4rem 0.7rem; border: 1px solid var(--border-color); background: #fff; border-radius: 6px; cursor: pointer;">&gt;</button>
        </div>
        <div style="display: flex; gap: 0.4rem;">
          <button style="background: var(--itca-red); color: #fff; border: none; padding: 0.45rem 1rem; border-radius: 6px; font-weight: 600;">Mes</button>
          <button style="background: #fff; border: 1px solid var(--border-color); padding: 0.45rem 1rem; border-radius: 6px;">Semana</button>
          <button style="background: #fff; border: 1px solid var(--border-color); padding: 0.45rem 1rem; border-radius: 6px;">Día</button>
        </div>
      </div>

      <div class="cal-layout">
        <!-- Cuadrícula Mensual -->
        <div class="form-card" style="padding: 0.8rem; margin: 0;">
          <table class="cal-table">
            <thead>
              <tr><th>Dom</th><th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th></tr>
            </thead>
            <tbody>
              <tr>
                <td class="muted">23</td><td class="muted">24</td><td class="muted">25</td><td class="muted">26</td><td class="muted">27</td><td class="muted">28</td><td>1</td>
              </tr>
              <tr>
                <td>2</td>
                <td>3<span class="cal-ev ev-green">Reunión de coor...</span></td>
                <td>4</td>
                <td>5<span class="cal-ev ev-blue">Asignación de e...</span></td>
                <td>6</td>
                <td>7<span class="cal-ev ev-purple">Jornada de lim...</span></td>
                <td>8</td>
              </tr>
              <tr>
                <td>9</td><td>10</td>
                <td>11<span class="cal-ev ev-yellow">Entrega de info...</span></td>
                <td>12</td><td>13</td><td>14</td><td>15</td>
              </tr>
              <tr>
                <td>16</td>
                <td>17<span class="cal-ev ev-blue">Evaluación de ...</span></td>
                <td>18</td>
                <td style="background: #fff5f5;"><span class="day-active">19</span><span class="cal-ev ev-red">Avance de proy...</span></td>
                <td>20</td>
                <td>21<span class="cal-ev ev-green">Actividad comu...</span></td>
                <td>22</td>
              </tr>
              <tr>
                <td>23</td><td>24</td><td>25</td>
                <td>26<span class="cal-ev ev-purple">Cierre de activi...</span></td>
                <td>27</td><td>28</td><td>29</td>
              </tr>
              <tr>
                <td>30</td><td>31</td><td class="muted">1</td><td class="muted">2</td><td class="muted">3</td><td class="muted">4</td><td class="muted">5</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Panel Lateral de Filtros y Detalle -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
          <div class="form-card" style="margin: 0;">
            <h4 style="color: var(--itca-red); margin-bottom: 0.8rem;">🌪️ Filtros</h4>
            <div class="form-group" style="margin-bottom: 0.7rem;">
              <label>Tipo de evento</label>
              <select style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px;"><option>Todos los eventos</option></select>
            </div>
            <div class="form-group" style="margin-bottom: 0.7rem;">
              <label>Proyecto</label>
              <select style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px;"><option>Todos los proyectos</option></select>
            </div>
            <div class="form-group">
              <label>Estado</label>
              <select style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 6px;"><option>Todos</option></select>
            </div>
          </div>

          <div class="form-card" style="margin: 0;">
            <h4 style="color: var(--itca-red); margin-bottom: 0.8rem;">📅 Actividades del 19 de marzo de 2025</h4>
            <div style="background: #f9fafb; border: 1px solid var(--border-color); border-radius: 6px; padding: 0.8rem;">
              <span style="font-size: 0.78rem; color: var(--text-muted);">🕘 09:00 a. m. - 11:00 a. m.</span>
              <div style="font-weight: 700; margin: 0.25rem 0;">Avance de proyecto</div>
              <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.6rem;">Proyecto: Apoyo Comunitario en la Zona Norte</div>
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="background: #dcfce7; color: #16a34a; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">Programada</span>
                <button style="background: #fff; border: 1px solid var(--border-color); padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.78rem; cursor: pointer;">👁️ Ver detalle</button>
              </div>
            </div>
          </div>

          <div class="form-card" style="margin: 0;">
            <h4 style="color: var(--itca-red);">Leyenda de eventos</h4>
            <div class="legend-grid">
              <div><span class="dot" style="background:#4ade80;"></span>Reunión</div>
              <div><span class="dot" style="background:var(--itca-red);"></span>Proyecto</div>
              <div><span class="dot" style="background:#60a5fa;"></span>Asignación</div>
              <div><span class="dot" style="background:#86efac;"></span>Actividad</div>
              <div><span class="dot" style="background:#facc15;"></span>Entrega</div>
              <div><span class="dot" style="background:#f472b6;"></span>Evento general</div>
              <div><span class="dot" style="background:#c084fc;"></span>Evaluación</div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
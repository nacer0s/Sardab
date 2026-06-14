<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sardab — Analytics Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/app/assets/css/base.css" />
  <link rel="stylesheet" href="/app/assets/css/layout.css" />
  <link rel="stylesheet" href="/app/assets/css/components.css" />
  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />


  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <nav class="nav-bar" id="nav-bar">
    <a href="/" class="nav-logo"><img src="/app/assets/svg/logo.svg" width="18" height="18" alt="" class="nav-logo-img" /> Sardab</a>
    <div class="nav-links">
      <a href="/app" class="nav-link">Hub</a>
    </div>
    <a href="/app" class="btn btn-ghost btn-sm"><i class="fa-solid fa-chevron-left btn-icon"></i> Back to Hub</a>
  </nav>

  <main class="page">
    <div class="page-header">
      <span class="badge-accent">• ACTIVITY</span>
      <h1>Your <span class="text-gradient">Usage Overview</span></h1>
      <p>Your monthly minutes spent on calls and meetings.</p>
    </div>

    <!-- Stats Counters Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1.5rem; max-width: 600px; margin: 0 auto 3rem auto; text-align: center;">
      <div>
        <div id="stat-voice" style="font-size: 3rem; font-weight: 800; color: #fff; line-height: 1;">0</div>
        <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-text-muted); margin-top: 0.5rem;"><i class="fa-solid fa-phone" style="margin-right: 4px;"></i> Voice Mins</div>
      </div>
      <div style="border-left: 1px solid rgba(255,255,255,0.1); border-right: 1px solid rgba(255,255,255,0.1);">
        <div id="stat-video" style="font-size: 3rem; font-weight: 800; color: #fff; line-height: 1;">0</div>
        <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-text-muted); margin-top: 0.5rem;"><i class="fa-solid fa-video" style="margin-right: 4px;"></i> Video Mins</div>
      </div>
      <div>
        <div id="stat-meeting" style="font-size: 3rem; font-weight: 800; color: #fff; line-height: 1;">0</div>
        <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-text-muted); margin-top: 0.5rem;"><i class="fa-solid fa-people-group" style="margin-right: 4px;"></i> Meeting Mins</div>
      </div>
    </div>

    <!-- Charts Container Grid -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem; max-width: 900px; margin: 0 auto; width: 100%;">
      
      <div class="glass-panel" style="padding: 2rem; border-radius: 1rem; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); position: relative;">
        
        <!-- Filter Buttons inside the card -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;">
          <button class="btn btn-sm btn-primary" id="btn-all" onclick="updateChartFilter('all')">All</button>
          <button class="btn btn-sm btn-ghost" id="btn-calls" onclick="updateChartFilter('calls')">Calls</button>
          <button class="btn btn-sm btn-ghost" id="btn-meetings" onclick="updateChartFilter('meetings')">Meetings</button>
        </div>

        <!-- Chart wrapper for size control -->
        <div style="position: relative; max-height: 450px; max-width: 900px; width: 100%;">
          <canvas id="sardabStatsChart"></canvas>
        </div>
      </div>

      <!-- Teammates Charts Placeholder -->
      <div style="border: 2px dashed rgba(255,255,255,0.05); border-radius: 1rem; padding: 3rem; text-align: center; color: var(--color-text-muted);">
        <i class="fa-solid fa-plus-circle" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
        <span>Teammates analytics charts will be integrated here</span>
      </div>

      <section class="card" style="padding:var(--space-6);">
        <h3 class="feat-card-title" style="margin-bottom:var(--space-4);">Activity</h3>
        <div id="activity-heatmap" class="activity-heatmap"></div>
        <div class="heatmap-legend">
          <span>Less</span>
          <span class="heatmap-cell level-0"></span>
          <span class="heatmap-cell level-1"></span>
          <span class="heatmap-cell level-2"></span>
          <span class="heatmap-cell level-3"></span>
          <span class="heatmap-cell level-4"></span>
          <span>More</span>
        </div>
      </section>


      

    </div>
  </main>

  <footer class="page-footer">
    <span>Sardab &mdash; Zero-Knowledge Communication Platform</span>
  </footer>

  <!-- Script that handles the chart logic -->
   
  <script src="/app/assets/js/stats.js"></script>
  <script src="/app/assets/js/activity-heatmap.js"></script>
  
  
</body>
</html>
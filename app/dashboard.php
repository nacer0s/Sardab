<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sardab — Activity</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Project CSS (chemins existants du projet) -->
  <link rel="stylesheet" href="/app/assets/css/base.css" />
  <link rel="stylesheet" href="/app/assets/css/layout.css" />
  <link rel="stylesheet" href="/app/assets/css/components.css" />

  <!-- CSS dédié à la heatmap (à créer dans le projet) -->
  <link rel="stylesheet" href="/app/assets/css/activity.css" />

  <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>

  <nav class="nav-bar" id="nav-bar">
    <a href="/app" class="nav-logo"><img src="/app/assets/svg/logo.svg" width="18" height="18" alt="" class="nav-logo-img" /> Sardab</a>
    <div class="nav-links">
      <a href="/app" class="nav-link">Dashboard</a>
    </div>
  </nav>

  <main class="section-block">
    <section class="card" style="padding:var(--space-6);max-width:900px;margin:0 auto;">
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
  </main>

  <!-- JS dédié à la heatmap (à créer dans le projet) -->
  <script src="/app/assets/js/activityg.js"></script>

</body>
</html>
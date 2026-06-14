// Renders a GitHub-style contribution heatmap.
// "activityData" = { "YYYY-MM-DD": count } where count = number of calls/meets that day.

function renderActivityHeatmap(containerId, activityData) {
  const container = document.getElementById(containerId);
  if (!container) return;

  container.innerHTML = '';

  const today = new Date();
  const oneYearAgo = new Date();
  oneYearAgo.setDate(today.getDate() - 364);

  // Align start to a Sunday so columns line up like GitHub
  const startDate = new Date(oneYearAgo);
  startDate.setDate(startDate.getDate() - startDate.getDay());

  const fragment = document.createDocumentFragment();
  const cursor = new Date(startDate);

  while (cursor <= today) {
    const key = cursor.toISOString().slice(0, 10);
    const count = activityData[key] || 0;

    const cell = document.createElement('div');
    cell.classList.add('heatmap-cell', `level-${getLevel(count)}`);
    cell.title = `${key}: ${count} session${count === 1 ? '' : 's'}`;

    fragment.appendChild(cell);
    cursor.setDate(cursor.getDate() + 1);
  }

  container.appendChild(fragment);
}

function getLevel(count) {
  if (count <= 0) return 0;
  if (count === 1) return 1;
  if (count === 2) return 2;
  if (count === 3) return 3;
  return 4;
}

// Example local-storage based tracker for calls/meets
function recordActivity(storageKey = 'sardab_activity') {
  const today = new Date().toISOString().slice(0, 10);
  const data = JSON.parse(localStorage.getItem(storageKey) || '{}');
  data[today] = (data[today] || 0) + 1;
  localStorage.setItem(storageKey, JSON.stringify(data));
}


// Enregistre les minutes par type (voice / video / meeting)
function recordSessionMinutes(type, minutes) {
  minutes = minutes || 1;
  var key = 'sardab_minutes_' + type;
  var total = parseInt(localStorage.getItem(key) || '0', 10);
  localStorage.setItem(key, total + minutes);
}

// Enregistre les minutes par type (voice / video / meeting)
function recordSessionMinutes(type, minutes) {
  minutes = minutes || 1;
  var key = 'sardab_minutes_' + type;
  var total = parseInt(localStorage.getItem(key) || '0', 10);
  localStorage.setItem(key, total + minutes);
}


// On page load
document.addEventListener('DOMContentLoaded', () => {
  const data = JSON.parse(localStorage.getItem('sardab_activity') || '{}');
  renderActivityHeatmap('activity-heatmap', data);
});
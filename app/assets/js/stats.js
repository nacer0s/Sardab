document.addEventListener("DOMContentLoaded", function () {
    var canvas = document.getElementById('sardabStatsChart');
    if (!canvas) return;

    // 1. Fetch data from localStorage
    var year = new Date().getFullYear();
    var stored = JSON.parse(localStorage.getItem('sardab_stats') || '{}');

    var voiceData = new Array(12).fill(0);
    var videoData = new Array(12).fill(0);
    var meetingData = new Array(12).fill(0);

    var totalVoice = 0, totalVideo = 0, totalMeeting = 0;


    for (var m = 0; m < 12; m++) {
        var key = year + '-' + String(m + 1).padStart(2, '0');
        if (stored[key]) {
            var vMins = Math.round((stored[key].voice || 0) / 60);
            var vidMins = Math.round((stored[key].video || 0) / 60);
            var mMins = Math.round((stored[key].meeting || 0) / 60);

            voiceData[m] = vMins;
            videoData[m] = vidMins;
            meetingData[m] = mMins;


            var currentMonthKey = year + '-' + String(new Date().getMonth() + 1).padStart(2, '0');
            if (key === currentMonthKey) {
                totalVoice += vMins;
                totalVideo += vidMins;
                totalMeeting += mMins;
            }
        }
    }

    if(document.getElementById('stat-voice')) document.getElementById('stat-voice').innerText = totalVoice;
    if(document.getElementById('stat-video')) document.getElementById('stat-video').innerText = totalVideo;
    if(document.getElementById('stat-meeting')) document.getElementById('stat-meeting').innerText = totalMeeting;

    var ctx = canvas.getContext('2d');
    window.sardabChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Voice Calls',
                    data: voiceData,
                    backgroundColor: 'rgba(255, 255, 255, 0.8)',
                    borderColor: '#ffffff',
                    borderWidth: 1,
                    borderRadius: 4,
                    categoryPercentage: 0.8,
                    barPercentage: 0.9
                },
                {
                    label: 'Video Calls',
                    data: videoData,
                    backgroundColor: 'rgba(46, 213, 115, 0.8)',
                    borderColor: '#2ed573',
                    borderWidth: 1,
                    borderRadius: 4,
                    categoryPercentage: 0.8,
                    barPercentage: 0.9
                },
                {
                    label: 'Meetings',
                    data: meetingData,
                    backgroundColor: 'rgba(255, 165, 0, 0.8)',
                    borderColor: '#ffa500',
                    borderWidth: 1,
                    borderRadius: 4,
                    categoryPercentage: 0.8,
                    barPercentage: 0.9
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: true, labels: { color: '#a0a0a0', font: { family: 'Inter' } } },
                tooltip: { backgroundColor: '#1a1a1a', titleFont: { family: 'Inter' }, bodyFont: { family: 'Inter' } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#a0a0a0', font: { family: 'Inter' } } },
                y: { 
                    grid: { color: 'rgba(255, 255, 255, 0.05)' }, 
                    ticks: { 
                        color: '#a0a0a0', 
                        font: { family: 'Inter' },
                        callback: function(value) { return value + ' min'; }
                    },
                    suggestedMin: 0
                }
            }
        }
    });
});


window.updateChartFilter = function(filter) {
    if (!window.sardabChart) return;
    
    var buttons = ['btn-all', 'btn-calls', 'btn-meetings'];
    buttons.forEach(function(id) {
        var btn = document.getElementById(id);
        if(btn) {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-ghost');
        }
    });

    var activeBtn = document.getElementById('btn-' + filter);
    if(activeBtn) {
        activeBtn.classList.remove('btn-ghost');
        activeBtn.classList.add('btn-primary');
    }

    if (filter === 'all') {
        window.sardabChart.setDatasetVisibility(0, true);
        window.sardabChart.setDatasetVisibility(1, true);
        window.sardabChart.setDatasetVisibility(2, true);
    } else if (filter === 'calls') {
        window.sardabChart.setDatasetVisibility(0, true);
        window.sardabChart.setDatasetVisibility(1, true);
        window.sardabChart.setDatasetVisibility(2, false);
    } else if (filter === 'meetings') {
        window.sardabChart.setDatasetVisibility(0, false);
        window.sardabChart.setDatasetVisibility(1, false);
        window.sardabChart.setDatasetVisibility(2, true);
    }
    window.sardabChart.update();
};
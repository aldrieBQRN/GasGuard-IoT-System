<?php 
include 'layout_head.php'; 
include 'sidebar.php'; 

// --- Load Config ---
$config = json_decode(file_get_contents('config.json'), true);
// Use null coalescing operator (??) to set defaults if keys are missing
$serverRefresh = $config['refresh_rate'] ?? 1000;
$serverSound = $config['sound_enabled'] ?? true;
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h6 class="text-muted text-uppercase mb-1">Live Overview</h6>
            <h2 class="m-0 text-dark">System Dashboard</h2>
        </div>
        <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-white border shadow-sm">
            <span class="badge bg-success rounded-circle p-1" style="width: 8px; height: 8px;" id="liveFeedDot"> </span>
            <span class="text-success fw-bold" style="font-size: 0.9rem;" id="liveFeedText">Live Feed</span>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card glow-blue">
                <small class="text-muted text-uppercase fw-bold">Gas Level</small>
                <div class="stat-value grad-blue" id="dispPercent">0%</div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="stat-card" id="cardStatus">
                <small class="text-muted text-uppercase fw-bold">Condition</small>
                <div class="stat-value grad-green" id="dispStatus">SAFE</div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted text-uppercase fw-bold">Sensor Voltage</small>
                <div class="stat-value text-dark" id="dispTemp">0 V</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <small class="text-muted text-uppercase fw-bold">WiFi Signal</small>
                <div class="stat-value text-dark" id="dispHum">Unknown</div>
            </div>
        </div>
    </div>

    <div class="stat-card mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="m-0 text-dark">Gas Quality Trends</h5>
            <small class="text-muted">Auto-refreshing every <span id="lblRefresh">2</span>s</small>
        </div>
        <div id="mainChart"></div>
    </div>
</div>

<script>
    // --- LOAD SETTINGS FROM PHP (Server-Side) ---
    const userRefresh = <?php echo $serverRefresh; ?>;
    const isSoundOn   = <?php echo $serverSound ? 'true' : 'false'; ?>;
    
    // Fixed Threshold
    const userThreshold = 2000;
    
    // 1 SECOND THRESHOLD (Matches Index)
    const DISCONNECT_THRESHOLD_S = 60;

    document.getElementById('lblRefresh').innerText = (userRefresh / 1000);

    // --- CHART CONFIGURATION ---
    var options = {
        series: [{ name: 'Gas Raw', data: [] }],
        chart: { 
            type: 'area', 
            height: 350, 
            width: '100%', // Force full width
            toolbar: { show: false }, 
            fontFamily: 'Outfit, sans-serif',
            animations: {
                enabled: true,
                easing: 'linear',
                dynamicAnimation: { speed: 1000 }
            }
        },
        colors: ['#3b82f6'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        xaxis: { 
            categories: [], 
            labels: { style: { colors: '#64748b' } }, 
            axisBorder: { show: false }, 
            axisTicks: { show: false } 
        },
        yaxis: { labels: { style: { colors: '#64748b' } } },
        grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
        theme: { mode: 'light' },

        // --- RESPONSIVE RULES ---
        responsive: [
            {
                breakpoint: 768, // Tablet & Mobile
                options: {
                    chart: {
                        height: 300 // Slightly smaller height
                    },
                    xaxis: {
                        labels: { show: false } // Hide X-axis labels to prevent clutter on small screens
                    }
                }
            },
            {
                breakpoint: 480, // Small Phones
                options: {
                    chart: {
                        height: 200 // Much smaller height
                    }
                }
            }
        ]
    };

    var chart = new ApexCharts(document.querySelector("#mainChart"), options);
    chart.render();

    function fetchData() {
        fetch('get-data.php').then(r => r.json()).then(data => {
            const s = document.getElementById('dispStatus');
            const card = document.getElementById('cardStatus');
            const liveFeedDot = document.getElementById('liveFeedDot');
            const liveFeedText = document.getElementById('liveFeedText');

            if(!data.latest) {
                s.innerText = "NO DATA";
                return;
            }
            
            const secondsAgo = parseInt(data.latest.seconds_ago);
            const rawGas = parseInt(data.latest.gas_raw);
            
            // --- UPDATED VALUES ---
            document.getElementById('dispPercent').innerText = data.latest.gas_percent + "%";
            
            // 1. Voltage Logic (Using new DB column 'sensor_voltage')
            let voltage = parseFloat(data.latest.sensor_voltage).toFixed(2);
            document.getElementById('dispTemp').innerText = voltage + " V";

            // 2. WiFi Signal Logic (Using new DB column 'wifi_signal')
            let rssi = parseInt(data.latest.wifi_signal);
            let rssiEl = document.getElementById('dispHum');
            
            let signalText = "Weak";
            let signalClass = "text-danger"; // Red by default

            if (rssi > -60) {
                signalText = "Strong";
                signalClass = "text-success"; // Green
            } else if (rssi > -75) {
                signalText = "Fair";
                signalClass = "text-warning"; // Yellow/Orange
            }

            rssiEl.innerText = signalText;
            rssiEl.className = "stat-value " + signalClass;

            // 1. CHECK CONNECTIVITY
            if (secondsAgo > DISCONNECT_THRESHOLD_S) {
                s.innerText = "OFFLINE"; 
                s.className = "stat-value text-muted"; 
                card.className = "stat-card"; 
                chart.updateOptions({ colors: ['#64748b'] }); 
                liveFeedDot.className = "badge bg-danger rounded-circle p-1";
                liveFeedText.className = "text-danger fw-bold";
                liveFeedText.innerText = "OFFLINE";
                return;
            }
            
            // 2. LIVE MONITORING
            liveFeedDot.className = "badge bg-success rounded-circle p-1";
            liveFeedText.className = "text-success fw-bold";
            liveFeedText.innerText = "Live Feed";

            if(rawGas > userThreshold) { 
                s.innerText = "CRITICAL"; 
                s.className = "stat-value grad-red"; 
                card.className = "stat-card glow-red";
                chart.updateOptions({ colors: ['#ef4444'] }); 
                
                if(isSoundOn) playAlertSound();
            } else { 
                s.innerText = "SAFE"; 
                s.className = "stat-value grad-green"; 
                card.className = "stat-card glow-green";
                chart.updateOptions({ colors: ['#3b82f6'] }); 
            }

            chart.updateSeries([{ data: data.chart.data }]);
            chart.updateOptions({ xaxis: { categories: data.chart.labels } });
        });
    }

    function playAlertSound() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.type = 'sawtooth'; 
        osc.frequency.setValueAtTime(600, ctx.currentTime);
        osc.frequency.linearRampToValueAtTime(1200, ctx.currentTime + 0.4);
        gain.gain.setValueAtTime(0.1, ctx.currentTime); 
        gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.8); 
        osc.start(); setTimeout(() => osc.stop(), 800);
    }

    // Use the PHP-injected refresh rate
    setInterval(fetchData, userRefresh);
    fetchData();
</script>
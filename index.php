<?php 
// 1. Load Global Settings (Only for Sound)
$configFile = 'config.json';
$serverSound = true; // Default on

if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if(isset($config['sound_enabled'])) {
        $serverSound = $config['sound_enabled'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GasGuard Public Monitor</title> 
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background-color: #f8fafc; font-family: 'Outfit', sans-serif; color: #1e293b; }
        
        /* Container Logic */
        .container-hero { max-width: 900px; margin: 0 auto; padding: 40px 20px; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; }
        
        /* Main Status Card */
        .hero-card { background: white; border-radius: 24px; padding: 60px 40px; text-align: center; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; margin-bottom: 30px; transition: all 0.3s ease; }
        .status-big { font-size: 5rem; font-weight: 800; letter-spacing: -2px; line-height: 1; margin: 20px 0; }
        .hero-icon { font-size: 4rem; margin-bottom: 15px; display: inline-block; }
        
        /* Small Cards */
        .live-card { background: white; padding: 30px; border-radius: 20px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); text-align: center; height: 100%; transition: transform 0.2s; }
        .live-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
        .live-val { font-size: 2.5rem; font-weight: 700; color: #3b82f6; margin: 10px 0; }
        .live-label { color: #64748b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700; }
        
        /* Status Colors */
        .safe-mode { border-color: #10b981; background: linear-gradient(to bottom, #ffffff, #f0fdfa); }
        .safe-text { background: linear-gradient(45deg, #059669, #10b981); -webkit-background-clip: text; background-clip: text; color: transparent; }
        
        .danger-mode { border-color: #ef4444; background: linear-gradient(to bottom, #ffffff, #fef2f2); animation: pulseRed 2s infinite; }
        .danger-text { background: linear-gradient(45deg, #dc2626, #ef4444); -webkit-background-clip: text; background-clip: text; color: transparent; }
        
        @keyframes pulseRed { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.2); } 70% { box-shadow: 0 0 0 30px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        
        /* Overlay */
        #clickOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
        
        .text-secondary { color: #94a3b8 !important; }

        /* --- RESPONSIVE DESIGN --- */
        @media (max-width: 768px) {
            .container-hero { padding: 20px 15px; justify-content: flex-start; }
            .hero-card { padding: 40px 20px; border-radius: 20px; }
            .status-big { font-size: 3.5rem; margin: 15px 0; }
            .hero-icon { font-size: 3rem; margin-bottom: 10px; }
            .live-card { padding: 20px; margin-bottom: 0; }
            .live-val { font-size: 2rem; }
            .header-flex { flex-direction: column; text-align: center; gap: 15px; }
            .header-flex .btn { width: 100%; justify-content: center; }
            .header-title-group { justify-content: center; }
        }
    </style>
</head>
<body>

    <div id="clickOverlay" onclick="startAudioSystem()">
        <div class="text-center text-white px-3">
            <div class="mb-4">
                <div class="bg-white text-dark rounded-circle d-inline-flex align-items-center justify-content-center shadow-lg" style="width: 80px; height: 80px;">
                    <i class="bi bi-hand-index-thumb-fill fs-1"></i>
                </div>
            </div>
            <h3 class="fw-bold">Tap to Enable Monitoring</h3>
            <p class="opacity-75">Browser security requires interaction to play audio alerts.</p>
        </div>
    </div>

    <div class="container-hero">
        
        <div class="d-flex justify-content-between align-items-center mb-4 header-flex">
            <div class="d-flex align-items-center gap-3 header-title-group">
                <div class="rounded-3 d-flex align-items-center justify-content-center shadow-sm" 
                     style="width: 50px; height: 50px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white;">
                    <i class="bi bi-shield-check fs-3"></i>
                </div>
                <div class="text-start">
                    <h4 class="m-0 fw-bold lh-1">GasGuard</h4> 
                    <small class="text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">Public Monitor</small>
                </div>
            </div>
            
            <div>
                <a href="dashboard.php" class="btn btn-white border shadow-sm rounded-pill px-4 fw-bold text-dark d-flex align-items-center gap-2">
                    Dashboard <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="hero-card safe-mode" id="heroBanner">
            <div id="heroIcon" class="hero-icon text-success"><i class="bi bi-shield-check"></i></div>
            <div class="text-muted text-uppercase fw-bold" style="font-size: 0.8rem; letter-spacing: 2px;">Current Status</div>
            <div class="status-big safe-text" id="dispStatus">SAFE</div>
            <p class="text-muted mb-0" style="font-size: 1.1rem;">System is monitoring normally.</p>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="live-card">
                    <div class="live-label">Gas Level</div>
                    <div class="live-val text-primary" id="dispPercent">0%</div>
                    <div class="badge bg-light text-muted border px-3 py-2 rounded-pill fw-normal">
                        Raw: <span id="dispRaw">0</span>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-4">
                <div class="live-card">
                    <div class="live-label">Sensor Voltage</div>
                    <div class="live-val text-warning" id="dispTemp">0 V</div>
                    <small class="text-muted">Health Check</small>
                </div>
            </div>

            <div class="col-6 col-md-4">
                <div class="live-card">
                    <div class="live-label">WiFi Signal</div>
                    <div class="live-val text-info" id="dispHum">Unknown</div>
                    <small class="text-muted">Connectivity</small>
                </div>
            </div>
        </div>

    </div>
    
<script>
    // --- AUDIO SYSTEM ---
    let audioCtx = null;
    const isSoundOn = <?php echo $serverSound ? 'true' : 'false'; ?>;

    function startAudioSystem() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        document.getElementById('clickOverlay').style.display = 'none';
        playTone(0, 0.1); 
    }

    function playSiren() {
        if (!isSoundOn) return; 

        if (!audioCtx) startAudioSystem(); 
        
        const now = audioCtx.currentTime;
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        
        osc.type = 'sawtooth'; 
        
        osc.frequency.setValueAtTime(600, now);
        osc.frequency.linearRampToValueAtTime(1200, now + 0.3);
        osc.frequency.linearRampToValueAtTime(600, now + 0.6);

        gain.gain.setValueAtTime(0.1, now);
        gain.gain.linearRampToValueAtTime(0.01, now + 0.6);

        osc.start(now);
        osc.stop(now + 0.6);
    }

    function playTone(freq, dur) {
        const osc = audioCtx.createOscillator();
        const g = audioCtx.createGain();
        osc.connect(g); g.connect(audioCtx.destination);
        osc.frequency.value = freq;
        g.gain.value = 0.001; 
        osc.start(); osc.stop(audioCtx.currentTime + dur);
    }

    // --- DATA FETCHING ---
    const userThreshold = 2000;
    const DISCONNECT_THRESHOLD_S = 60; 

    // Fixed to 1000ms (1 second)
    setInterval(() => {
        fetch('get-data.php').then(r => r.json()).then(data => {
            const statusText = document.getElementById('dispStatus');
            const heroBanner = document.getElementById('heroBanner');
            const heroIcon = document.getElementById('heroIcon');

            const dispPercent = document.getElementById('dispPercent');
            const dispTemp = document.getElementById('dispTemp');
            const dispHum = document.getElementById('dispHum');

            if(!data.latest) {
                statusText.innerText = "NO DATA"; 
                statusText.className = "status-big text-secondary";
                heroBanner.className = "hero-card"; 
                heroIcon.innerHTML = '<i class="bi bi-x-octagon-fill"></i>';
                heroIcon.className = "hero-icon text-secondary";
                dispPercent.innerText = '—'; dispTemp.innerText = '—'; dispHum.innerText = '—';
                return;
            }
            
            const secondsAgo = parseInt(data.latest.seconds_ago);
            const rawGas = parseInt(data.latest.gas_raw);

            // --- UPDATE VALUES ---

            // 1. Voltage (from 'sensor_voltage' column)
            let voltage = parseFloat(data.latest.sensor_voltage).toFixed(2);
            dispTemp.innerText = voltage + " V";

            // 2. WiFi Signal Logic (from 'wifi_signal' column)
            // Shows "Strong/Fair/Weak" instead of number
            let rssi = parseInt(data.latest.wifi_signal);
            let signalText = "Weak";
            
            dispHum.classList.remove('text-info', 'text-secondary', 'text-success', 'text-warning', 'text-danger');

            if (rssi > -60) {
                signalText = "Strong";
                dispHum.classList.add('text-success'); // Green
            } else if (rssi > -75) {
                signalText = "Fair";
                dispHum.classList.add('text-warning'); // Orange
            } else {
                dispHum.classList.add('text-danger');  // Red
            }

            dispHum.innerText = signalText;

            // --- OFFLINE CHECK ---
            if (secondsAgo > DISCONNECT_THRESHOLD_S) {
                statusText.innerText = "OFFLINE";
                statusText.className = "status-big text-secondary"; 
                heroBanner.className = "hero-card"; 
                heroIcon.innerHTML = '<i class="bi bi-dash-circle-fill"></i>';
                heroIcon.className = "hero-icon text-secondary";

                dispPercent.classList.remove('text-primary'); dispPercent.classList.add('text-secondary');
                dispTemp.classList.remove('text-warning'); dispTemp.classList.add('text-secondary');
                dispHum.classList.remove('text-success', 'text-warning', 'text-danger'); dispHum.classList.add('text-secondary');
                
                dispPercent.innerText = data.latest.gas_percent + "%";
                document.getElementById('dispRaw').innerText = data.latest.gas_raw;
                return; 
            }

            // --- ONLINE CHECK ---
            dispPercent.classList.remove('text-secondary'); dispPercent.classList.add('text-primary');
            dispTemp.classList.remove('text-secondary'); dispTemp.classList.add('text-warning');

            dispPercent.innerText = data.latest.gas_percent + "%";
            document.getElementById('dispRaw').innerText = data.latest.gas_raw;

            if(rawGas > userThreshold) { 
                statusText.innerText = "CRITICAL"; 
                statusText.className = "status-big danger-text";
                heroBanner.className = "hero-card danger-mode";
                heroIcon.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>';
                heroIcon.className = "hero-icon text-danger";
                
                playSiren();
            } else { 
                statusText.innerText = "SAFE"; 
                statusText.className = "status-big safe-text";
                heroBanner.className = "hero-card safe-mode";
                heroIcon.innerHTML = '<i class="bi bi-shield-check"></i>';
                heroIcon.className = "hero-icon text-success";
            }
        });
    }, 1000); 
</script>

</body>
</html>
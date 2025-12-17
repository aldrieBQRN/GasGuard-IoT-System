<?php include 'layout_head.php'; ?>
<?php include 'sidebar.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="main-content">
    
    <div class="mb-5">
        <h6 class="text-muted text-uppercase mb-1">Configuration</h6>
        <h2 class="m-0 text-dark">System Settings</h2>
    </div>

    <div class="row g-4">
        
        <div class="col-md-6">
            <div class="stat-card mb-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-primary bg-opacity-10 p-2 rounded text-primary">
                        <i class="bi bi-envelope-paper fs-4"></i>
                    </div>
                    <h5 class="m-0 text-dark">Email Alerts</h5>
                </div>
                
                <form onsubmit="saveServerSettings(event)">
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold small text-uppercase">Receiver Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="alertEmail" class="form-control bg-light border-0 py-2" placeholder="name@example.com" required>
                        </div>
                        <div class="form-text">GasGuard will send critical warnings to this address.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Save Email</button>
                </form>
            </div>

            <div class="stat-card border-danger border-opacity-25" style="background: #fef2f2;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-danger bg-opacity-10 p-2 rounded text-danger">
                        <i class="bi bi-exclamation-triangle fs-4"></i>
                    </div>
                    <h5 class="text-danger m-0">Danger Zone</h5>
                </div>
                <p class="text-muted small mb-3">Permanently delete all sensor logs. This action cannot be undone.</p>
                <button class="btn btn-danger w-100" onclick="clearDB()">Clear Database</button>
            </div>
        </div>

        <div class="col-md-6">
            <div class="stat-card mb-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-success bg-opacity-10 p-2 rounded text-success">
                        <i class="bi bi-speedometer2 fs-4"></i>
                    </div>
                    <h5 class="m-0 text-dark">System Performance</h5>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted fw-bold small text-uppercase">Dashboard Refresh Rate</label>
                    <select class="form-select bg-light border-0 py-2" id="refreshRate">
                        <option value="1000">Fast (1 Second)</option>
                        <option value="2000">Normal (2 Seconds)</option>
                        <option value="5000">Slow (5 Seconds)</option>
                    </select>
                    <div class="form-text">Controls how often the dashboard fetches new sensor data.</div>
                </div>
                
                <button class="btn btn-success w-100 py-2" onclick="saveDisplaySettings()">Update Refresh Rate</button>
            </div>

            <div class="stat-card">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-warning bg-opacity-10 p-2 rounded text-warning">
                        <i class="bi bi-volume-up fs-4"></i>
                    </div>
                    <h5 class="m-0 text-dark">Audio Feedback</h5>
                </div>

                <div class="d-flex justify-content-between align-items-center p-3 rounded bg-light mb-3">
                    <div>
                        <span class="d-block fw-bold text-dark">Siren Alarm</span>
                        <small class="text-muted">Global setting for dashboard sound</small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="soundToggle" style="width: 3em; height: 1.5em; cursor: pointer;">
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary flex-grow-1" onclick="testSound()">Test Sound 🔊</button>
                    <button class="btn btn-warning flex-grow-1 text-white" onclick="saveAudioSettings()">Save Audio</button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // --- 1. LOAD SETTINGS FROM SERVER ---
    document.addEventListener("DOMContentLoaded", function() {
        fetch('api-settings.php').then(r => r.json()).then(data => {
            if(data.email) document.getElementById('alertEmail').value = data.email;
            if(data.refresh_rate) document.getElementById('refreshRate').value = data.refresh_rate;
            
            // Convert to boolean for checkbox
            const isSoundOn = (data.sound_enabled === true || data.sound_enabled === "true");
            document.getElementById('soundToggle').checked = isSoundOn;
        });
    });

    // --- 2. SAVE FUNCTIONS ---
    
    function saveServerSettings(e) {
        e.preventDefault();
        const email = document.getElementById('alertEmail').value;
        sendToApi({ email: email });
    }

    function saveAudioSettings() {
        const sound = document.getElementById('soundToggle').checked;
        sendToApi({ sound_enabled: sound });
    }

    function saveDisplaySettings() {
        const refresh = document.getElementById('refreshRate').value;
        sendToApi({ refresh_rate: refresh });
    }

    // Helper function to send data
    function sendToApi(dataObj) {
        fetch('api-settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dataObj)
        }).then(r => r.json()).then(res => {
            Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: res.message,
                showConfirmButton: false,
                timer: 1500
            });
        });
    }

    function clearDB() {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will wipe all data!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('clear-data.php').then(() => {
                    Swal.fire('Deleted!', 'Database cleared.', 'success');
                });
            }
        })
    }

    // --- 3. SIREN TEST ---
    function testSound() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
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
</script>
</body>
</html>
<?php $current_page = basename($_SERVER['PHP_SELF']); ?>

<div class="sidebar">
    <div class="d-flex align-items-center gap-3 mb-5 px-2">
        <div class="rounded-3 d-flex align-items-center justify-content-center" 
             style="width: 40px; height: 40px; background: linear-gradient(135deg, #ef4444, #dc2626);">
            <i class="bi bi-shield-check text-white fs-5"></i>
        </div>
        <div>
            <div class="fw-bold text-dark" style="font-size: 1.2rem; letter-spacing: 0.5px;">GasGuard</div>
            <div class="text-muted" style="font-size: 0.75rem;">Safety System</div>
        </div>
    </div>
    
    <nav class="nav flex-column gap-2">
        <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="bi bi-grid-fill"></i> <span>Overview</span>
        </a>
        <a href="analytics.php" class="nav-link <?php echo ($current_page == 'analytics.php') ? 'active' : ''; ?>">
            <i class="bi bi-bar-chart-fill"></i> <span>History</span>
        </a>
        <a href="settings.php" class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
            <i class="bi bi-gear-fill"></i> <span>Settings</span>
        </a>

        <hr class="my-2 border-secondary opacity-25">

        <a href="index.php" class="nav-link text-danger">
            <i class="bi bi-box-arrow-left"></i> <span>Public View</span>
        </a>
    </nav>
    
    <div style="position: absolute; bottom: 30px; left: 25px; right: 25px;">
        <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
            <small class="text-muted d-block mb-2 text-uppercase" style="font-size: 0.7rem;">Connection</small>
            <div class="d-flex align-items-center gap-2">
                <span class="position-relative d-flex h-2 w-2">
                  <span id="connDotPulse" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                  <span id="connDotSolid" class="relative inline-flex rounded-full h-2 w-2 bg-success"></span>
                </span>
                <span id="connStatusText" class="fw-bold text-dark small">Checking...</span>
            </div>
        </div>
    </div>
</div>

<script>
    const CONNECTION_TIMEOUT_SECONDS = 60; 

    function updateConnectionStatus() {
        fetch('get-data.php')
            .then(response => response.json())
            .then(data => {
                const pulseDot = document.getElementById('connDotPulse');
                const solidDot = document.getElementById('connDotSolid');
                const statusText = document.getElementById('connStatusText');

                // Use the seconds_ago calculated by the server
                const secondsAgo = parseInt(data.latest.seconds_ago);

                if (data.latest && !isNaN(secondsAgo)) {
                    if (secondsAgo <= CONNECTION_TIMEOUT_SECONDS) {
                        // Status is ACTIVE (Green)
                        statusText.innerText = "System Active";
                        statusText.classList.remove('text-danger', 'text-muted');
                        statusText.classList.add('text-dark');
                        
                        pulseDot.classList.remove('bg-danger');
                        pulseDot.classList.add('bg-success');
                        solidDot.classList.remove('bg-danger');
                        solidDot.classList.add('bg-success');
                        
                        // Start/Resume pulsing animation
                        pulseDot.classList.add('animate-ping');

                    } else {
                        // Status is DISCONNECTED (Red)
                        const mins = Math.floor(secondsAgo / 60);
                        let timeText = "";
                        if (secondsAgo < 3600) {
                            // Less than 1 hour, show minutes
                            const mins = Math.floor(secondsAgo / 60);
                            timeText = `${mins}m ago`;
                        } else if (secondsAgo < 86400) {
                            // Less than 1 day, show hours
                            const hours = Math.floor(secondsAgo / 3600);
                            timeText = `${hours}h ago`;
                        } else {
                            // 1 day or more, show days
                            const days = Math.floor(secondsAgo / 86400);
                            timeText = `${days}d ago`;
                        }

                        statusText.innerText = `Disconnected (${timeText})`;
                        statusText.classList.remove('text-dark', 'animate-ping');
                        statusText.classList.add('text-danger');
                        
                        pulseDot.classList.remove('bg-success', 'animate-ping');
                        pulseDot.classList.add('bg-danger');
                        solidDot.classList.remove('bg-success');
                        solidDot.classList.add('bg-danger');
                    }
                } else {
                    // No data found or bad server calculation
                    statusText.innerText = "No Data Found";
                    statusText.classList.add('text-muted');
                    pulseDot.classList.remove('bg-success', 'animate-ping');
                    solidDot.classList.remove('bg-success');
                    pulseDot.classList.add('bg-danger');
                    solidDot.classList.add('bg-danger');
                }
            })
            .catch(error => {
                // Network error
                document.getElementById('connStatusText').innerText = "No Data Found";
                document.getElementById('connStatusText').classList.add('text-danger');
            });
    }

    // Run immediately and then every 1 seconds
    updateConnectionStatus();
    setInterval(updateConnectionStatus, 1000); 
</script>
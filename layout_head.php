<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GasGuard Pro</title> <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        /* ... (Existing CSS Styles) ... */
        :root { 
            --bg-app: #f1f5f9; --card-bg: #ffffff; --text-main: #1e293b; --text-muted: #64748b; 
            --accent: #3b82f6; --danger: #ef4444; --success: #10b981; --warning: #f59e0b;
            --sidebar-width: 280px;
        }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg-app); color: var(--text-main); min-height: 100vh; display: flex; overflow-x: hidden; }
        h1, h2, h3, h4, h5, h6 { color: var(--text-main) !important; font-weight: 700; }
        .text-muted { color: var(--text-muted) !important; }
        .sidebar { width: var(--sidebar-width); background: var(--card-bg); height: 100vh; position: fixed; left: 0; top: 0; border-right: 1px solid rgba(0,0,0,0.05); padding: 25px; z-index: 1050; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 5px 0 30px rgba(0,0,0,0.03); }
        .main-content { margin-left: var(--sidebar-width); padding: 40px; width: 100%; transition: margin 0.3s; max-width: 100vw; }
        .stat-card { background: var(--card-bg); border: 1px solid rgba(0,0,0,0.05); border-radius: 20px; padding: 24px; position: relative; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border-color: var(--accent); }
        .glow-blue { border-left: 5px solid var(--accent); } .glow-red { border-left: 5px solid var(--danger); animation: pulseRed 2s infinite; background: #fef2f2; } .glow-green { border-left: 5px solid var(--success); }
        
        /* CLEANED GRADIENT CLASSES TO REMOVE WARNINGS */
        .grad-blue { 
            background: linear-gradient(45deg, #2563eb, #3b82f6); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            /* Added standard versions */
            background-clip: text;
            color: transparent;
        } 
        .grad-green { 
            background: linear-gradient(45deg, #059669, #10b981); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            /* Added standard versions */
            background-clip: text;
            color: transparent;
        } 
        .grad-red { 
            background: linear-gradient(45deg, #dc2626, #ef4444); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            /* Added standard versions */
            background-clip: text;
            color: transparent;
        }
        
        .stat-value { font-size: 2.5rem; font-weight: 700; letter-spacing: -1px; margin-top: 10px; }
        .nav-link { color: var(--text-muted); padding: 14px 18px; margin-bottom: 8px; border-radius: 12px; text-decoration: none; display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .nav-link:hover { background: #f8fafc; color: var(--accent) !important; }
        .nav-link.active { background: #eff6ff; color: var(--accent) !important; border-left: 4px solid var(--accent); }
        .custom-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .custom-table th { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; padding: 10px; }
        .custom-table td { background: white; padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: var(--text-main); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .custom-table tr td:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .custom-table tr td:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
        @keyframes pulseRed { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        
        /* --- NEW: HIDE BUTTON WHEN SIDEBAR IS OPEN --- */
        .sidebar-open .menu-toggle-btn {
            display: none !important;
        }

        @media (max-width: 991px) { 
            .sidebar { transform: translateX(-100%); width: 260px; } 
            .sidebar.show { transform: translateX(0); } 
            .main-content { margin-left: 0; padding: 20px; padding-top: 80px; } 
            .stat-value { font-size: 2rem; } 
            .menu-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040; backdrop-filter: blur(3px); } 
            .menu-overlay.show { display: block; } 
        }
    </style>
</head>
<body>
<button class="btn btn-white shadow-sm d-lg-none position-fixed top-0 start-0 m-3 p-2 rounded-circle menu-toggle-btn" style="z-index: 1100; width: 45px; height: 45px;" onclick="toggleSidebar()">
    <i class="bi bi-list fs-4 text-dark"></i>
</button>
<div class="menu-overlay" onclick="toggleSidebar()"></div>
<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.menu-overlay');
        const body = document.body;
        
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        
        // Add or remove a class on the body to trigger the CSS rule
        body.classList.toggle('sidebar-open', sidebar.classList.contains('show'));
    }
</script>
<?php include 'layout_head.php'; ?>
<?php include 'sidebar.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<style>
    /* Desktop Default Styles */
    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px; /* Space between rows */
        min-width: 600px; /* Force table to have width so scrolling works */
    }
    
    /* --- MOBILE OPTIMIZATIONS (Max-width 768px) --- */
    @media (max-width: 768px) {
        .main-content {
            padding: 15px !important; 
            padding-top: 80px !important; /* Restore space for the menu button */
        }

        .stat-card {
            padding: 15px !important; 
        }

        /* Tighter Row Spacing */
        .custom-table {
            border-spacing: 0 5px;
        }

        /* Compact Cells */
        .custom-table th, 
        .custom-table td {
            padding: 10px 8px !important; /* Smaller padding */
            font-size: 0.85rem; /* Smaller text */
        }

        /* Adjust Badge Size */
        .badge {
            padding: 4px 8px !important;
            font-size: 0.7rem !important;
        }

        /* Adjust Header/Filters Layout */
        .d-flex.flex-column.flex-md-row {
            gap: 10px !important;
        }
        
        .form-control, .form-select, .btn {
            font-size: 0.9rem;
        }
    }
</style>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h6 class="text-muted text-uppercase mb-1">System Logs</h6>
            <h2 class="m-0 text-dark">Data History</h2>
        </div>
        <div id="liveBadge" class="d-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-success bg-opacity-10 border border-success border-opacity-25 d-none">
            <span class="rounded-circle" style="width: 8px; height: 8px;" id="liveDot"></span>
            <span class="fw-bold small" id="liveText"></span>
        </div>
    </div>

    <div class="d-flex flex-column flex-md-row gap-3 mb-4 align-items-center">
        
        <div class="d-flex gap-2 w-100 w-md-auto">
            <input type="text" id="dateRangeFilter" class="form-control border-0 shadow-sm py-2 flatpickr-range" 
                   placeholder="Select Date Range" style="min-width: 220px;">
            
            <select id="statusFilter" class="form-select border-0 shadow-sm py-2" 
                    style="min-width: 140px;">
                <option value="All">All Status</option>
                <option value="SAFE" class="text-success">SAFE Only</option>
                <option value="DANGER" class="text-danger">DANGER Only</option>
            </select>
        </div>

        <div class="d-flex gap-2 w-100">
            <div class="position-relative flex-grow-1">
                <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="searchInput" class="form-control border-0 bg-white shadow-sm ps-5 py-2" 
                       placeholder="Search..." onkeyup="searchLogs()">
            </div>
            
            <button type="button" class="btn btn-secondary d-flex align-items-center justify-content-center gap-2 px-3 shadow-sm" onclick="clearFilters()">
                <i class="bi bi-x-circle"></i> 
            </button>
                        
            <button type="button" class="btn btn-success d-flex align-items-center justify-content-center gap-2 px-3 shadow-sm" onclick="exportToExcel()">
                <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-lg-inline">Export</span>
            </button>
        </div>
    </div>

    <div class="stat-card p-0 overflow-hidden shadow-sm">
        <div class="table-responsive">
            <table class="custom-table mb-0">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th class="ps-4 py-3 text-nowrap">Timestamp</th>
                        <th class="py-3 text-nowrap">Device Name</th>
                        <th class="py-3 text-nowrap">Gas Level</th>
                        <th class="py-3 text-nowrap">Voltage</th> <th class="py-3 text-nowrap">WiFi</th>    <th class="py-3 text-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody id="historyBody"></tbody>
            </table>
        </div>
        
        <div id="noData" class="text-center p-5 d-none">
            <h5 class="text-muted">No records found</h5>
            <small class="text-muted opacity-75">Try clearing your filters</small>
        </div>

        <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light flex-wrap gap-2">
            <small class="text-muted" id="pageInfo">Loading...</small>
            <div class="btn-group">
                <button class="btn btn-sm btn-white border" id="btnPrev" onclick="changePage(-1)">Prev</button>
                <button class="btn btn-sm btn-white border" id="btnNext" onclick="changePage(1)">Next</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentPage = 1; 
    let totalPages = 1; 
    let searchTimer;
    const DISCONNECT_THRESHOLD_S = 60; 
    let flatpickrInstance; 

    // --- INITIALIZATION & EVENT HANDLERS ---
    document.addEventListener('DOMContentLoaded', function() {
        flatpickrInstance = flatpickr("#dateRangeFilter", {
            mode: "range",
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) { 
                if (selectedDates.length === 2 || dateStr.length === 0) { 
                     loadHistory(1);
                }
            }
        });
        
        document.getElementById('statusFilter').addEventListener('change', function() {
            loadHistory(1);
        });

        loadHistory(1);
    });

    setInterval(() => {
        if (currentPage === 1 && !document.getElementById('searchInput').matches(':focus')) {
            loadHistory(1, true); 
        }
    }, 1000);

    function searchLogs() { 
        clearTimeout(searchTimer); 
        searchTimer = setTimeout(() => { loadHistory(1); }, 400); 
    }

    function changePage(d) { 
        let n = currentPage + d; 
        if(n > 0 && n <= totalPages) loadHistory(n); 
    }
    
    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = 'All';
        
        if (flatpickrInstance) {
            flatpickrInstance.clear();
        } else {
            document.getElementById('dateRangeFilter').value = '';
        }
        
        loadHistory(1);
    }

    function updateLiveBadge(latestData) {
        const liveBadge = document.getElementById('liveBadge');
        const liveDot = document.getElementById('liveDot');
        const liveText = document.getElementById('liveText');
        
        // Always show badge
        liveBadge.classList.remove('d-none');
        
        if (!latestData || !latestData.seconds_ago) {
            liveDot.className = 'rounded-circle bg-secondary';
            liveText.className = 'text-muted fw-bold small';
            liveText.innerText = `NO DATA`; 
            liveBadge.className = 'd-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-light border border-secondary border-opacity-25';
            return;
        }

        const secondsAgo = parseInt(latestData.seconds_ago);

        if (secondsAgo <= DISCONNECT_THRESHOLD_S) {
            liveDot.className = 'rounded-circle bg-success';
            liveText.className = 'text-success fw-bold small';
            liveText.innerText = 'LIVE';
            liveBadge.className = 'd-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-success bg-opacity-10 border border-success border-opacity-25';
        } else {
            liveDot.className = 'rounded-circle bg-danger';
            liveText.className = 'text-danger fw-bold small';
            liveText.innerText = `OFFLINE`; 
            liveBadge.className = 'd-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-danger bg-opacity-10 border border-danger border-opacity-25';
        }
    }

    function loadHistory(page, silent = false) {
        const query = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        const dateRange = document.getElementById('dateRangeFilter').value;
        
        fetch('get-data.php')
            .then(r => r.json())
            .then(connData => {
                updateLiveBadge(connData.latest);
            })
            .catch(() => {
                updateLiveBadge(null);
            });

        fetch(`get-history.php?page=${page}&search=${query}&status=${status}&date_range=${dateRange}`)
            .then(r => r.json())
            .then(res => {
                currentPage = res.pagination.current_page; 
                totalPages = res.pagination.total_pages;

                document.getElementById('pageInfo').innerText = `Page ${currentPage} of ${totalPages} (${res.pagination.total_records} logs)`;
                document.getElementById('btnPrev').disabled = (currentPage === 1);
                document.getElementById('btnNext').disabled = (currentPage === totalPages || totalPages === 0);

                let rowsHtml = "";
                
                if(res.data.length === 0) {
                    document.getElementById('noData').classList.remove('d-none');
                    document.getElementById('historyBody').innerHTML = "";
                } else {
                    document.getElementById('noData').classList.add('d-none');
                    
                    res.data.forEach(row => {
                        let isDanger = row.status === 'DANGER';
                        
                        let badgeStyle = isDanger 
                            ? 'background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2;' 
                            : 'background: #ecfdf5; color: #10b981; border: 1px solid #d1fae5;';
                        
                        let icon = isDanger 
                            ? '<i class="bi bi-exclamation-triangle-fill me-1"></i>' 
                            : '<i class="bi bi-check-circle-fill me-1"></i>';
                        
                        // --- UPDATED WIFI LOGIC FOR TABLE ---
                        // Reads from 'wifi_signal' DB column
                        let rssi = parseInt(row.wifi_signal);
                        let wifiText = "Weak";
                        let wifiClass = "text-danger";

                        if(rssi > -60) {
                            wifiText = "Strong";
                            wifiClass = "text-success";
                        } else if(rssi > -75) {
                            wifiText = "Fair";
                            wifiClass = "text-warning";
                        }

                        // Voltage from 'sensor_voltage'
                        let voltVal = parseFloat(row.sensor_voltage).toFixed(2);

                        rowsHtml += `
                            <tr>
                                <td class="ps-4 text-muted font-monospace text-nowrap">${row.reading_time}</td>
                                <td class="fw-bold text-dark text-nowrap">${row.device_name}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1 d-none d-md-flex" style="height: 4px; width: 60px;">
                                            <div class="progress-bar ${isDanger ? 'bg-danger' : 'bg-primary'}" style="width: ${row.gas_percent}%"></div>
                                        </div>
                                        <small class="text-dark fw-bold">${row.gas_percent}%</small>
                                    </div>
                                </td>
                                <td class="text-nowrap">
                                    <span class="text-dark fw-bold">${voltVal} V</span> 
                                </td>
                                <td class="text-nowrap">
                                    <span class="${wifiClass} fw-bold">${wifiText}</span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 fw-normal" style="${badgeStyle}">
                                        ${icon} ${row.status}
                                    </span>
                                </td>
                            </tr>`;
                    });
                    
                    const tbody = document.getElementById('historyBody');
                    if (tbody.innerHTML !== rowsHtml) {
                        tbody.innerHTML = rowsHtml;
                    }
                }
            });
    }

    // --- ENHANCED EXPORT TO EXCEL ---
    function exportToExcel() {
        const tbody = document.getElementById("historyBody");
        
        if(tbody.rows.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Data',
                text: 'The table is empty. Please load some data before exporting.',
                confirmButtonColor: '#ffc107'
            });
            return;
        }

        let data = [];
        
        // --- 1. TITLE & METADATA ROWS ---
        data.push(["GASGUARD PRO - ANALYTICS REPORT"]); 
        data.push(["Export Date:", new Date().toLocaleString()]); 
        data.push([]); 
        
        // --- 2. UPDATED COLUMN HEADERS ---
        data.push(["Timestamp", "Device Name", "Gas Level (%)", "Voltage (V)", "WiFi Status", "Status"]);

        // --- 3. DATA ROWS ---
        Array.from(tbody.rows).forEach(row => {
            const cols = row.querySelectorAll("td");
            
            // Build Row
            let rowData = [
                cols[0].innerText.trim(), // Timestamp
                cols[1].innerText.trim(), // Device Name
                parseFloat(cols[2].innerText.replace('%', '').trim()) || 0, // Gas
                parseFloat(cols[3].innerText.replace('V', '').trim()) || 0, // Voltage (Numeric)
                cols[4].innerText.trim(), // WiFi Text (Strong/Fair/Weak)
                cols[5].innerText.trim()  // Status
            ];
            data.push(rowData);
        });

        // --- 4. CREATE WORKSHEET ---
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(data);

        // --- 5. FORMATTING (Merge Title) ---
        if(!ws['!merges']) ws['!merges'] = [];
        // Merge A1:F1
        ws['!merges'].push({ s: {r:0, c:0}, e: {r:0, c:5} }); 

        // Set Column Widths
        ws['!cols'] = [
            {wch: 22}, // Timestamp
            {wch: 18}, // Device
            {wch: 12}, // Gas
            {wch: 15}, // Voltage
            {wch: 15}, // WiFi
            {wch: 12}  // Status
        ];

        XLSX.utils.book_append_sheet(wb, ws, "System Logs");

        // --- 6. DOWNLOAD ---
        XLSX.writeFile(wb, "GasGuard_Report_" + new Date().toISOString().slice(0,10) + ".xlsx");

        Swal.fire({
            icon: 'success',
            title: 'Export Successful',
            text: 'Your formatted .xlsx report is ready.',
            showConfirmButton: false,
            timer: 1500
        });
    }
</script>
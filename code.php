<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Climate Change Impact Assessment - Zambia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        #map { height: 500px; }
        body { background-color: #f8f9fa; }
        h2, h4 { color: #2c3e50; }
        #map { border: 2px solid #2c3e50; margin-bottom: 20px; }
        canvas { background: #ffffff; padding: 10px; border: 1px solid #ddd; }
        #stats .card { border: 1px solid #ced4da; background-color: #fefefe; }
        #stats .card p { font-weight: bold; color: #2c3e50; }
        button { margin-right: 10px; }
        .legend {
            background: white;
            padding: 10px;
            border: 1px solid #ccc;
            line-height: 1.5;
        }
        .legend span {
            display: inline-block;
            width: 15px;
            height: 15px;
            margin-right: 5px;
            vertical-align: middle;
        }
        #sidePanel .card {
            height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        .list-group-item {
            padding: 0.75rem 1.25rem;
        }
        .badge {
            font-size: 0.9em;
            min-width: 70px;
        }
        @media print {
            button, select, form { display: none; }
            #map { height: 300px; }
            #sidePanel { display: none; }
        }
    </style>
</head>
<body>
<div class="container my-4">
    <h2 class="text-center mb-4">Climate Change Impact Assessment - Zambia</h2>

    <div class="row mb-3">
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="yearFilter" class="form-label">Filter by Year:</label>
                <select id="yearFilter" class="form-select">
                    <option value="">All Years</option>
                </select>
            </div>
        
            <div class="col-md-3">
                <label for="startDate" class="form-label">Start Date:</label>
                <input type="date" id="startDate" class="form-control">
            </div>
        
            <div class="col-md-3">
                <label for="endDate" class="form-label">End Date:</label>
                <input type="date" id="endDate" class="form-control">
            </div>
        
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" onclick="updateMapAndCharts()">Apply Filter</button>
            </div>
        </div>

        <div class="col-md-8">
            <form action="upload.php" method="post" enctype="multipart/form-data">
                <label class="form-label">Upload Climate Data (CSV):</label>
                <input type="file" name="file" class="form-control" accept=".csv" required>
                <button type="submit" class="btn btn-primary mt-2">Upload</button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div id="map"></div>
            <div id="legend" class="legend mt-3">
                <div><span style="background:red;"></span> Very High Temperature (&gt; 35°C)</div>
                <div><span style="background:green;"></span> Very Heavy Rainfall (&gt; 200mm)</div>
                <div><span style="background:blue;"></span> Normal Data</div>
            </div>
        </div>
        <div class="col-md-4">
            <div id="sidePanel">
                <div class="card mb-3">
                    <div class="card-header bg-danger text-white">
                        <i class="bi bi-thermometer-sun"></i> Top 5 High Temperature Areas
                    </div>
                    <ul id="highTempsList" class="list-group list-group-flush"></ul>
                </div>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-cloud-rain-heavy"></i> Top 5 High Rainfall Areas
                    </div>
                    <ul id="highRainfallList" class="list-group list-group-flush"></ul>
                </div>
            </div>
        </div>
    </div>

    <h4 class="mt-4">Climate Data Trends</h4>
    <canvas id="climateChart"></canvas>

    <div class="row my-4" id="stats">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Average Temperature (°C)</h5>
                    <p id="avgTemp" class="display-6">-</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Max Temperature (°C)</h5>
                    <p id="maxTemp" class="display-6">-</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Rainfall (mm)</h5>
                    <p id="totalRainfall" class="display-6">-</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Year with Highest Temp</h5>
                    <p id="yearMaxTemp" class="display-6">-</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button class="btn btn-success" onclick="exportToCSV()">Export to CSV</button>
        <button class="btn btn-danger" onclick="window.print()">Export to PDF</button>
    </div>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let climateData = [];
    let map = L.map('map').setView([-14.5, 28.5], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    let markersLayer = L.layerGroup().addTo(map);

    async function loadClimateData() {
        const response = await fetch('fetch_data.php');
        climateData = await response.json();
        setupYearFilter(climateData);
        updateMapAndCharts();
    }

    function setupYearFilter(data) {
        const yearSelect = document.getElementById('yearFilter');
        yearSelect.innerHTML = '<option value="">All Years</option>';
        const years = [...new Set(data.map(d => new Date(d.date).getFullYear()))].sort();
        years.forEach(year => {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            yearSelect.appendChild(option);
        });
        yearSelect.addEventListener('change', updateMapAndCharts);
    }

    function updateMapAndCharts() {
        const selectedYear = document.getElementById('yearFilter').value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
    
        let filteredData = climateData;
    
        if (selectedYear) {
            filteredData = filteredData.filter(d => new Date(d.date).getFullYear() == selectedYear);
        }
        if (startDate) {
            filteredData = filteredData.filter(d => new Date(d.date) >= new Date(startDate));
        }
        if (endDate) {
            filteredData = filteredData.filter(d => new Date(d.date) <= new Date(endDate));
        }
    
        updateMap(filteredData);
        updateChartsAndStats(filteredData);
    }

    function updateMap(data) {
        markersLayer.clearLayers();
        data.forEach(entry => {
            let temp = Number(entry.temperature);
            let rain = Number(entry.rainfall);
            let color = 'blue';

            if (temp > 35 && rain > 200) {
                color = 'purple';
            } else if (temp > 35) {
                color = 'red';
            } else if (rain > 200) {
                color = 'green';
            }

            L.circleMarker([entry.latitude, entry.longitude], {
                radius: 6,
                color: color,
                fillOpacity: 0.8
            }).bindPopup(`
                <strong>${entry.region}</strong><br>
                Date: ${entry.date}<br>
                Temp: ${temp}°C<br>
                Rainfall: ${rain}mm
            `).addTo(markersLayer);
        });
    }

    function updateChartsAndStats(data) {
        const temps = data.map(entry => Number(entry.temperature));
        const rainfalls = data.map(entry => Number(entry.rainfall));
        const dates = data.map(entry => entry.date);

        const avgTemp = temps.length ? (temps.reduce((a, b) => a + b, 0) / temps.length).toFixed(2) : '-';
        const maxTemp = temps.length ? Math.max(...temps) : '-';
        const totalRainfall = rainfalls.length ? rainfalls.reduce((sum, val) => sum + val, 0).toFixed(2) : '-';
        const yearMaxTemp = data.find(entry => Number(entry.temperature) == maxTemp)?.date?.split('-')[0] ?? '-';

        document.getElementById('avgTemp').innerText = avgTemp;
        document.getElementById('maxTemp').innerText = maxTemp;
        document.getElementById('totalRainfall').innerText = totalRainfall;
        document.getElementById('yearMaxTemp').innerText = yearMaxTemp;

        // Update side panel lists
        const regionStats = {};
        data.forEach(entry => {
            const region = entry.region;
            if (!regionStats[region]) {
                regionStats[region] = {
                    maxTemp: Number(entry.temperature),
                    totalRainfall: Number(entry.rainfall),
                    count: 1
                };
            } else {
                regionStats[region].maxTemp = Math.max(regionStats[region].maxTemp, Number(entry.temperature));
                regionStats[region].totalRainfall += Number(entry.rainfall);
                regionStats[region].count++;
            }
        });

        const regionsArray = Object.entries(regionStats).map(([name, stats]) => ({
            name,
            maxTemp: stats.maxTemp,
            totalRainfall: stats.totalRainfall,
            avgTemp: stats.maxTemp / stats.count
        }));

        const topTemps = [...regionsArray].sort((a, b) => b.maxTemp - a.maxTemp).slice(0, 5);
        const topRainfall = [...regionsArray].sort((a, b) => b.totalRainfall - a.totalRainfall).slice(0, 5);

        const tempsList = document.getElementById('highTempsList');
        tempsList.innerHTML = topTemps.map(region => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                ${region.name}
                <span class="badge bg-danger rounded-pill">${region.maxTemp.toFixed(1)}°C</span>
            </li>
        `).join('');

        const rainfallList = document.getElementById('highRainfallList');
        rainfallList.innerHTML = topRainfall.map(region => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
                ${region.name}
                <span class="badge bg-primary rounded-pill">${region.totalRainfall.toFixed(1)}mm</span>
            </li>
        `).join('');

        climateChart.data.labels = dates;
        climateChart.data.datasets[0].data = temps;
        climateChart.data.datasets[1].data = rainfalls;
        climateChart.update();
    }

    function exportToCSV() {
        const selectedYear = document.getElementById('yearFilter').value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
    
        let exportData = climateData;
    
        if (selectedYear) {
            exportData = exportData.filter(d => new Date(d.date).getFullYear() == selectedYear);
        }
        if (startDate) {
            exportData = exportData.filter(d => new Date(d.date) >= new Date(startDate));
        }
        if (endDate) {
            exportData = exportData.filter(d => new Date(d.date) <= new Date(endDate));
        }
    
        let csv = 'Region,Latitude,Longitude,Date,Temperature,Rainfall\n';
        exportData.forEach(row => {
            csv += `${row.region},${row.latitude},${row.longitude},${row.date},${row.temperature},${row.rainfall}\n`;
        });
    
        const blob = new Blob([csv], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `climate_data_filtered.csv`;
        link.click();
    }

    const ctx = document.getElementById('climateChart').getContext('2d');
    const climateChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'Temperature (°C)', data: [], borderColor: 'red', fill: false },
                { label: 'Rainfall (mm)', data: [], borderColor: 'blue', fill: false }
            ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    window.onload = loadClimateData;
</script>
</body>
</html>
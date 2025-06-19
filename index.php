<?php
require 'db_config.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Climate Change Impact Assessment - Zambia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body>
    <div class="container my-4">
    <nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <span class="navbar-brand">Climate Assessment System</span>
        <div class="d-flex">
            <span class="text-light me-3">Welcome, <?php echo $_SESSION['username']; ?></span>
            <a href="logout.php" class="btn btn-outline-light">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>
        
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
    <div><span style="background:red;"></span> Top 5 Temperature</div>
    <div><span style="background:blue;"></span> Top 5 Rainfall</div>
    <div><span style="background:purple;"></span> Top 5 in Both</div>
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

        <h4 class="mt-4">AI Climate Analysis</h4>
        <div class="card">
            <div class="card-body">
                <div id="aiPrediction" class="text-muted">Loading predictions...</div>
                <button class="btn btn-warning mt-3" onclick="generatePrediction()">
                    <i class="bi bi-robot"></i> Generate New Analysis
                </button>
            </div>
        </div>

        <h4 class="mt-4">Climate Data Trends
            <select id="chartInterval" class="form-select form-select-sm d-inline-block w-auto ms-2" onchange="updateChartsAndStats(getFilteredData())">
                <option value="day">Daily</option>
                <option value="month">Monthly</option>
                <option value="half-year">6 Months</option>
                <option value="year">Yearly</option>
            </select>
        </h4> 

        <div class="row">
            <div class="col-md-6">
                <canvas id="temperatureChart"></canvas>
            </div>
            <div class="col-md-6">
                <canvas id="rainfallChart"></canvas>
            </div>
        </div>

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

        // Initialize charts
        const tempCtx = document.getElementById('temperatureChart').getContext('2d');
        const rainfallCtx = document.getElementById('rainfallChart').getContext('2d');

        function aggregateData(data, interval) {
            const aggregated = { temp: {}, rainfall: {} };
            
            data.forEach(entry => {
                const date = new Date(entry.date);
                let key;
                
                switch(interval) {
                    case 'month':
                        key = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}`;
                        break;
                    case 'half-year':
                        const half = date.getMonth() < 6 ? 'H1' : 'H2';
                        key = `${date.getFullYear()}-${half}`;
                        break;
                    case 'year':
                        key = date.getFullYear();
                        break;
                    default: // day
                        key = entry.date;
                }
                
                if (!aggregated.temp[key]) {
                    aggregated.temp[key] = { sum: 0, count: 0 };
                    aggregated.rainfall[key] = 0;
                }
                
                aggregated.temp[key].sum += Number(entry.temperature);
                aggregated.temp[key].count++;
                aggregated.rainfall[key] += Number(entry.rainfall);
            });

            const tempData = Object.entries(aggregated.temp).map(([key, val]) => ({
                key,
                value: val.sum / val.count
            }));

            const rainfallData = Object.entries(aggregated.rainfall).map(([key, val]) => ({
                key,
                value: val
            }));

            const sorter = (a, b) => a.key.localeCompare(b.key);
    return {
        labels: tempData.sort(sorter).map(d => d.key),
        tempValues: tempData.sort(sorter).map(d => d.value),
        rainfallValues: rainfallData.sort(sorter).map(d => d.value)
    };
}



        const temperatureChart = new Chart(tempCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Temperature (°C)',
                    data: [],
                    borderColor: 'red',
                    fill: false,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: false } }
            }
        });

        const rainfallChart = new Chart(rainfallCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Rainfall (mm)',
                    data: [],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'blue',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });

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

        
        async function generatePrediction() {
    try {
        const aiPredictionElement = document.getElementById('aiPrediction');
        aiPredictionElement.innerHTML = '<div class="spinner-border text-warning" role="status"></div> Analyzing infrastructure impacts...';

        const filteredData = getFilteredData();
        if (filteredData.length === 0) {
            aiPredictionElement.innerHTML = '⚠️ No data available for selected filters';
            return;
        }

        const analysis = analyzeClimateData(filteredData);
        
        const response = await fetch('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyBTSA_bJ3AN4izELy8G8BAAJ4jj0hEL72Q', {
            method: 'POST',
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                contents: [{
                    parts: [{
                        text: `Analyze Zambia climate data and return JSON with:
                        {
                            "infrastructure_risk_areas": [
                                {
                                    "name": string,
                                    "coordinates": [number, number],
                                    "risk_factors": string[],
                                    "confidence": number
                                }
                            ],
                            "adoption_strategies": string[],
                            "projections": {
                                "temperature": {"min": number, "max": number},
                                "rainfall": {"min": number, "max": number}
                            }
                        }
                        Requirements:
                        - Exactly 5 infrastructure risk areas based on the data filtered
                        - Confidence scores 85-100% 
                        - Include specific risk factors per area
                        - Base on data: ${analysis.summary}`
                    }]
                }]
            })
        });

        if (!response.ok) throw new Error('API request failed');
        
        const data = await response.json();
        const responseText = data.candidates[0].content.parts[0].text;
        
        try {
            const cleanedResponse = responseText
                .replace(/```json/g, '')
                .replace(/```/g, '')
                .trim();
            
            const jsonResponse = JSON.parse(cleanedResponse);
            aiPredictionElement.innerHTML = formatInfrastructurePrediction(jsonResponse);
        } catch (e) {
            console.error('JSON parse error:', e);
            aiPredictionElement.innerHTML = `⚠️ Error parsing response. Raw output:<br>${responseText}`;
        }

    } catch (error) {
        console.error('Prediction error:', error);
        document.getElementById('aiPrediction').innerHTML = 
            '⚠️ Error generating prediction. Please try again later.';
    }
}
        
function formatInfrastructurePrediction(json) {
    // Validate response structure
    const isValid = json.infrastructure_risk_areas?.length === 5 && 
                   json.infrastructure_risk_areas.every(area => 
                       area.name && 
                       area.coordinates?.length === 2 &&
                       area.risk_factors?.length &&
                       typeof area.confidence === 'number'
                   );

    if (!isValid) {
        return `<div class="alert alert-danger mt-3">
            <i class="bi bi-x-circle-fill me-2"></i>
            Invalid prediction format received from API
        </div>`;
    }

    return `
        <div class="prediction-results mt-3">
            <h4 class="text-dark mb-4 pb-2 border-bottom">
                <i class="bi bi-building-exclamation me-2"></i>
                Infrastructure Risk Hotspots
            </h4>
            
            <div class="row g-3">
                ${json.infrastructure_risk_areas.map(area => `
                    <div class="col-md-6">
                        <div class="card h-100 border-light shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="card-title text-dark mb-1">
                                            <i class="bi bi-geo-alt text-primary me-2"></i>
                                            ${area.name}
                                        </h5>
                                        <small class="text-muted">
                                            Coordinates: ${area.coordinates.join(', ')}
                                        </small>
                                    </div>
                                    <span class="badge bg-warning bg-opacity-25 text-dark fs-7">
                                        ${Math.max(area.confidence, 85).toFixed(1)}%
                                    </span>
                                </div>

                                <div class="risk-factors mt-3">
                                    <span class="text-muted small d-block mb-2">
                                        Key Risk Factors:
                                    </span>
                                    <div class="d-flex flex-wrap gap-2">
                                        ${area.risk_factors.map(factor => `
                                            <div class="risk-factor-pill">
                                                <i class="bi bi-exclamation-circle me-1"></i>
                                                ${factor}
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}
        

        function getFilteredData() {
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

            return filteredData;
        }
        
        // Helper function to analyze data trends
        function analyzeClimateData(data) {
            const temps = data.map(d => Number(d.temperature));
            const rainfalls = data.map(d => Number(d.rainfall));
            const regions = [...new Set(data.map(d => d.region))];
            
            const tempVariance = calculateVariance(temps);
            const rainVariance = calculateVariance(rainfalls);
            const tempAvg = temps.reduce((a, b) => a + b, 0) / temps.length;
            const rainAvg = rainfalls.reduce((a, b) => a + b, 0) / rainfalls.length;

            return {
                summary: `Data Analysis:
                    - Time Period: ${data[0].date} to ${data[data.length-1].date}
                    - Regions: ${regions.join(', ')}
                    - Avg Temperature: ${tempAvg.toFixed(1)}°C (Variance: ${tempVariance.toFixed(1)})
                    - Avg Rainfall: ${rainAvg.toFixed(1)}mm (Variance: ${rainVariance.toFixed(1)})
                    - Confidence Levels:
                      * Temperature: ${tempVariance > 5 ? 'Low' : 'High'}
                      * Rainfall: ${rainVariance > 50 ? 'Low' : 'High'}
                    - Infrastructure Risks:
                      * Roads: ${rainAvg > 150 ? 'High flood risk' : 'Moderate risk'}
                      * Buildings: ${tempAvg > 35 ? 'Thermal stress likely' : 'Stable conditions'}`,
                stats: {
                    tempVariance,
                    rainVariance,
                    tempAvg,
                    rainAvg
                }
            };
        }

        function calculateVariance(values) {
            const mean = values.reduce((a, b) => a + b) / values.length;
            return values.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / values.length;
        }
        
        // Format the prediction text with proper HTML
        function formatPredictionText(text) {
            return text
                .replace(/## (.*?):/g, '<h5 class="mt-3">$1</h5>')
                .replace(/(\d+\.\s)/g, '<strong>$1</strong>')
                .replace(/- (.*?):/g, '<div class="prediction-item"><span class="prediction-label">$1:</span>')
                .replace(/\n/g, '</div>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        }
        
        // Initialize prediction on page load
        window.onload = () => { loadClimateData(); generatePrediction(); };
        function updateMap(data) {
    markersLayer.clearLayers();
    
    // Aggregate data by region
    const regionData = {};
    data.forEach(entry => {
        const region = entry.region;
        if (!regionData[region]) {
            regionData[region] = {
                maxTemp: Number(entry.temperature),
                totalRainfall: Number(entry.rainfall),
                lat: Number(entry.latitude),
                lon: Number(entry.longitude),
                count: 1
            };
        } else {
            regionData[region].maxTemp = Math.max(regionData[region].maxTemp, Number(entry.temperature));
            regionData[region].totalRainfall += Number(entry.rainfall);
            regionData[region].count++;
        }
    });

    // Convert to array and get top 5 regions
    const regionsArray = Object.entries(regionData).map(([name, stats]) => ({
        name,
        maxTemp: stats.maxTemp,
        totalRainfall: stats.totalRainfall,
        lat: stats.lat,
        lon: stats.lon
    }));

    // Get top 5 for each category
    const topTemps = [...regionsArray].sort((a, b) => b.maxTemp - a.maxTemp).slice(0, 5);
    const topRainfall = [...regionsArray].sort((a, b) => b.totalRainfall - a.totalRainfall).slice(0, 5);

    // Create sets for quick lookup
    const tempRegions = new Set(topTemps.map(r => r.name));
    const rainRegions = new Set(topRainfall.map(r => r.name));

    // Combine and deduplicate regions
    const allRegions = [...topTemps, ...topRainfall];
    const uniqueRegions = Array.from(
        new Map(allRegions.map(r => [r.name, r])).values()
    );

    // Create markers for each relevant region
    uniqueRegions.forEach(region => {
        const isTemp = tempRegions.has(region.name);
        const isRain = rainRegions.has(region.name);
        
        let color = 'blue';
        if (isTemp && isRain) {
            color = 'purple';
        } else if (isTemp) {
            color = 'red';
        } else if (isRain) {
            color = 'blue';
        }

        L.circleMarker([region.lat, region.lon], {
            radius: 8,
            color: color,
            fillColor: color,
            fillOpacity: 0.8,
            weight: 1
        }).bindPopup(`
            <strong>${region.name}</strong><br>
            Max Temperature: ${region.maxTemp.toFixed(1)}°C<br>
            Total Rainfall: ${region.totalRainfall.toFixed(1)}mm
        `).addTo(markersLayer);
    });
}

        function updateChartsAndStats(data) {
            const interval = document.getElementById('chartInterval').value;
    const { labels, tempValues, rainfallValues } = aggregateData(data, interval);

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

            // Update charts
            temperatureChart.data.labels = labels;
    temperatureChart.data.datasets[0].data = tempValues;
    temperatureChart.options.scales.x.title = { display: true, text: 'Time Period' };
    temperatureChart.update();

    rainfallChart.data.labels = labels;
    rainfallChart.data.datasets[0].data = rainfallValues;
    rainfallChart.options.scales.x.title = { display: true, text: 'Time Period' };
    rainfallChart.update();
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

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);

            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'climate_data_export.csv');
            link.style.display = 'none';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        window.onload = loadClimateData;
    </script>
</body>

</html>
import { CONFIG } from './config.js';
import { initializeMap, updateMap } from './modules/map.js';
import { initializeCharts, updateCharts } from './modules/charts.js';
import { generatePrediction } from './modules/predictions.js';
import { setupFilters, exportToCSV, formatPredictionText } from './modules/utilities.js';

// Initialize app
let climateData = [];
const { map, markersLayer } = initializeMap();
const charts = initializeCharts();

// Event listeners
document.getElementById('exportCSV').addEventListener('click', () => exportToCSV(climateData));
document.getElementById('generatePrediction').addEventListener('click', async () => {
    document.getElementById('aiPrediction').innerHTML = await generatePrediction(climateData);
});

async function loadClimateData() {
    const response = await fetch('fetch_data.php');
    climateData = await response.json();
    setupFilters(climateData);
    updateMapAndCharts();
}

function updateMapAndCharts() {
    // Filter logic
    updateMap(markersLayer, filteredData);
    updateCharts(charts, filteredData);
}

// Initial load
window.addEventListener('DOMContentLoaded', async () => {
    await loadClimateData();
    await generatePrediction(climateData);
});
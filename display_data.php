<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szenzor Dashboard – Smart Alert</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --bg: #121212; --card: #1e1e1e; --text: #e0e0e0; 
            --primary: #4caf50; --accent: #2196f3; --danger: #ff5252; 
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        .dashboard { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        
        .card { 
            background: var(--card); padding: 20px; border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.5); transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        /* Riasztási stílus */
        .card.alert { border-color: var(--danger); background: #2d1a1a; box-shadow: 0 0 20px rgba(255, 82, 82, 0.2); }
        
        .full-width { grid-column: 1 / -1; }
        h1, h2 { margin-top: 0; color: var(--primary); }
        .status-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        
        .alert-banner { 
            display: none; background: var(--danger); color: white; 
            padding: 10px; border-radius: 8px; margin-bottom: 20px; 
            text-align: center; font-weight: bold; animation: pulse 2s infinite;
        }

        .value { font-size: 2.5em; font-weight: bold; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
        .critical { color: var(--danger) !important; font-weight: bold; }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
    </style>
</head>
<body>

<div id="master-alert" class="alert-banner">FIGYELEM: KRITIKUS ÉRTÉK MÉRHETŐ!</div>

<div class="dashboard">
    <div class="card full-width">
        <div class="status-bar">
            <h1>Szenzor Állomás</h1>
            <span id="update-timer" style="color: #888;">Frissítés: -</span>
        </div>
    </div>

    <div id="temp-card" class="card">
        <h2>Hőmérséklet</h2>
        <div class="value" id="temp-display">-- °C</div>
        <canvas id="tempChart"></canvas>
    </div>

    <div id="humi-card" class="card">
        <h2>Páratartalom</h2>
        <div class="value" id="humi-display">-- %</div>
        <canvas id="humiChart"></canvas>
    </div>

    <div class="card full-width">
        <h2>Mérési napló</h2>
        <table>
            <thead><tr><th>Időpont</th><th>Hőmérséklet</th><th>Páratartalom</th></tr></thead>
            <tbody id="table-body"></tbody>
        </table>
    </div>
</div>

<script>
    // --- BEÁLLÍTÁSOK ---
    const THRESHOLDS = {
        tempMax: 30.0, // Efelett riaszt a hőmérséklet
        humiMax: 80.0  // Efelett riaszt a páratartalom
    };

    let charts = {};

    function initCharts() {
        const cfg = (color) => ({
            type: 'line',
            data: { labels: [], datasets: [{ data: [], borderColor: color, backgroundColor: color + '22', fill: true, tension: 0.4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: false }, scales: { x: { display: false }, y: { grid: { color: '#333' } } } }
        });

        charts.temp = new Chart(document.getElementById('tempChart'), cfg('#ff5252'));
        charts.humi = new Chart(document.getElementById('humiChart'), cfg('#2196f3'));
    }

    async function refresh() {
        try {
            const response = await fetch('insert_data_apikey.php');
            const data = await response.json();
            if (!data.length) return;

            const latest = data[data.length - 1];
            const isTempAlert = latest.temperature > THRESHOLDS.tempMax;
            const isHumiAlert = latest.humidity > THRESHOLDS.humiMax;

            // UI Frissítése
            document.getElementById('temp-display').innerText = `${latest.temperature} °C`;
            document.getElementById('humi-display').innerText = `${latest.humidity} %`;
            document.getElementById('update-timer').innerText = `Frissítve: ${new Date().toLocaleTimeString()}`;

            // Riasztási logika vizuálisan
            document.getElementById('temp-card').classList.toggle('alert', isTempAlert);
            document.getElementById('humi-card').classList.toggle('alert', isHumiAlert);
            document.getElementById('master-alert').style.display = (isTempAlert || isHumiAlert) ? 'block' : 'none';

            // Grafikonok frissítése
            const labels = data.map(d => d.created_at);
            charts.temp.data.labels = labels;
            charts.temp.data.datasets[0].data = data.map(d => d.temperature);
            charts.temp.update();

            charts.humi.data.labels = labels;
            charts.humi.data.datasets[0].data = data.map(d => d.humidity);
            charts.humi.update();

            // Táblázat generálása
            document.getElementById('table-body').innerHTML = [...data].reverse().slice(0, 8).map(d => `
                <tr>
                    <td>${d.created_at}</td>
                    <td class="${d.temperature > THRESHOLDS.tempMax ? 'critical' : ''}">${d.temperature} °C</td>
                    <td class="${d.humidity > THRESHOLDS.humiMax ? 'critical' : ''}">${d.humidity} %</td>
                </tr>
            `).join('');

        } catch (e) { console.error("Hiba:", e); }
    }

    initCharts();
    refresh();
    setInterval(refresh, 3000);
</script>
</body>
</html>

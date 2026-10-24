import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend,
    Filler,
);

Chart.defaults.font.family = "'Instrument Sans', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = '#667490';

const LIGHT_THEME = {
    brand: '#1f41f0',
    brandSoft: 'rgba(31, 65, 240, 0.12)',
    accent: '#f98c1b',
    tick: '#667490',
    palette: ['#1f41f0', '#f98c1b', '#12b981', '#8b5cf6', '#0ea5e9', '#f43f5e', '#14b8a6', '#eab308'],
};

const DARK_THEME = {
    brand: '#60a5fa',
    brandSoft: 'rgba(96, 165, 250, 0.16)',
    accent: '#f98c1b',
    tick: '#94a3b8',
    palette: ['#60a5fa', '#f98c1b', '#34d399', '#a78bfa', '#38bdf8', '#fb7185', '#2dd4bf', '#facc15'],
};

let THEME = LIGHT_THEME;

/**
 * Adapte couleurs, graduations et légendes au thème de la page.
 * À appeler avant la création des graphiques (défaut : clair).
 */
function setChartTheme(dark) {
    THEME = dark ? DARK_THEME : LIGHT_THEME;
    Chart.defaults.color = THEME.tick;
}

/**
 * Graphique d'évolution des ventes (ligne).
 */
function salesEvolutionChart(canvas, labels, revenue, tickets) {
    if (!canvas) return;

    return new Chart(canvas, {
        data: {
            labels,
            datasets: [
                {
                    type: 'line',
                    label: 'Revenu',
                    data: revenue,
                    borderColor: THEME.brand,
                    backgroundColor: THEME.brandSoft,
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: THEME.brand,
                    yAxisID: 'y',
                },
                {
                    type: 'line',
                    label: 'Tickets vendus',
                    data: tickets,
                    borderColor: THEME.accent,
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5, 4],
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: THEME.accent,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 },
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) =>
                            ctx.dataset.label === 'Revenu'
                                ? ` Revenu : ${new Intl.NumberFormat('fr-FR').format(ctx.parsed.y)} FCFA`
                                : ` Tickets : ${ctx.parsed.y}`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 16 } },
                y: {
                    position: 'left',
                    grid: { color: 'rgba(140, 152, 175, 0.15)' },
                    ticks: { callback: (v) => shortNumber(v) },
                },
                y1: {
                    position: 'right',
                    grid: { display: false },
                    ticks: { precision: 0, color: THEME.accent },
                },
            },
        },
    });
}

/** Barres horizontales (top événements, revenus par événement). */
function horizontalBarChart(canvas, labels, values, color) {
    if (!canvas) return;

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: color || THEME.palette,
                    borderRadius: 6,
                    borderSkipped: false,
                    barThickness: 18,
                },
            ],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${new Intl.NumberFormat('fr-FR').format(ctx.parsed.x)} FCFA`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { color: 'rgba(140, 152, 175, 0.15)' },
                    ticks: { callback: (v) => shortNumber(v) },
                },
                y: { grid: { display: false } },
            },
        },
    });
}

/** Barres verticales groupées (tickets par événement). */
function groupedBarChart(canvas, labels, datasets) {
    if (!canvas) return;

    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: datasets.map((d, i) => ({
                label: d.label,
                data: d.data,
                backgroundColor: d.color || THEME.palette[i % THEME.palette.length],
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 34,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 12 } },
                y: { grid: { color: 'rgba(140, 152, 175, 0.15)' }, ticks: { precision: 0 } },
            },
        },
    });
}

function shortNumber(value) {
    const n = Number(value) || 0;

    if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1).replace('.0', '')} M`;
    if (n >= 1_000) return `${(n / 1_000).toFixed(0)} k`;

    return new Intl.NumberFormat('fr-FR').format(n);
}

window.shortNumber = shortNumber;
window.setChartTheme = setChartTheme;
window.salesEvolutionChart = salesEvolutionChart;
window.horizontalBarChart = horizontalBarChart;
window.groupedBarChart = groupedBarChart;
window.CHART_PALETTE = LIGHT_THEME.palette;
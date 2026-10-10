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

const BRAND = '#1f41f0';
const BRAND_SOFT = 'rgba(31, 65, 240, 0.12)';
const ACCENT = '#f98c1b';
const PALETTE = ['#1f41f0', '#f98c1b', '#12b981', '#8b5cf6', '#0ea5e9', '#f43f5e', '#14b8a6', '#eab308'];

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
                    borderColor: BRAND,
                    backgroundColor: BRAND_SOFT,
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: BRAND,
                    yAxisID: 'y',
                },
                {
                    type: 'line',
                    label: 'Tickets vendus',
                    data: tickets,
                    borderColor: ACCENT,
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5, 4],
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointBackgroundColor: ACCENT,
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
                    ticks: { precision: 0, color: ACCENT },
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
                    backgroundColor: color || PALETTE,
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
                backgroundColor: d.color || PALETTE[i % PALETTE.length],
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
window.salesEvolutionChart = salesEvolutionChart;
window.horizontalBarChart = horizontalBarChart;
window.groupedBarChart = groupedBarChart;
window.CHART_PALETTE = PALETTE;
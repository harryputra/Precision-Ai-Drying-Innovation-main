import './bootstrap';
import Alpine from 'alpinejs';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import ApexCharts from 'apexcharts';

// Alpine.js
window.Alpine = Alpine;
Alpine.start();

// ApexCharts global
window.ApexCharts = ApexCharts;

// Laravel Echo + Reverb
// Konfigurasi dibaca RUNTIME dari meta tag (di-inject layout Blade) —
// bukan build-time VITE_* — agar satu build jalan di domain mana pun.
// Fallback: origin halaman (WS diproksikan Apache di path /app, same-origin).
window.Pusher = Pusher;

const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content || '';
const pageSecure = window.location.protocol === 'https:';
const reverbKey = meta('reverb-key') || import.meta.env.VITE_REVERB_APP_KEY;
const reverbScheme = meta('reverb-scheme') || (pageSecure ? 'https' : 'http');
const reverbHost = meta('reverb-host') || window.location.hostname;
const reverbPort = Number(meta('reverb-port') || window.location.port || (pageSecure ? 443 : 80));

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: reverbKey,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

// Default ApexCharts dark theme config
window.apexDarkConfig = {
    theme: { mode: 'dark' },
    chart: {
        background: 'transparent',
        foreColor: '#64748b',
        fontFamily: 'Instrument Sans, ui-sans-serif, system-ui, sans-serif',
        toolbar: { show: false },
        zoom: { enabled: false },
        animations: { enabled: true, easing: 'easeinout', speed: 600 },
    },
    grid: {
        borderColor: 'rgba(255,255,255,0.05)',
        strokeDashArray: 4,
    },
    tooltip: {
        theme: 'dark',
        style: { fontSize: '12px' },
    },
    stroke: { curve: 'smooth', width: 2 },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.3,
            opacityTo: 0.0,
            stops: [0, 90, 100],
        },
    },
    xaxis: {
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: '#475569', fontSize: '11px' } },
    },
    yaxis: {
        labels: { style: { colors: '#475569', fontSize: '11px' } },
    },
    legend: {
        labels: { colors: '#64748b' },
        fontSize: '12px',
    },
    colors: ['#f97316', '#10b981', '#3b82f6', '#a855f7'],
    markers: { size: 0, hover: { size: 4 } },
};

// Helper: create sensor chart
window.createSensorChart = function(selector, series, categories) {
    const options = {
        ...window.apexDarkConfig,
        chart: { ...window.apexDarkConfig.chart, type: 'area', height: 280 },
        series: series,
        xaxis: { ...window.apexDarkConfig.xaxis, categories: categories },
    };
    const chart = new ApexCharts(document.querySelector(selector), options);
    chart.render();
    return chart;
};

// Helper: create radial gauge chart (light-friendly)
window.createGaugeChart = function(selector, value, label, color) {
    const el = document.querySelector(selector);
    if (!el) return null;
    const options = {
        chart: {
            type: 'radialBar',
            height: 130,
            background: 'transparent',
            fontFamily: 'Inter, ui-sans-serif',
            toolbar: { show: false },
            animations: { enabled: true, easing: 'easeinout', speed: 800 },
            sparkline: { enabled: false },
        },
        series: [value],
        plotOptions: {
            radialBar: {
                startAngle: -135,
                endAngle: 135,
                hollow: {
                    size: '58%',
                    background: 'transparent',
                },
                track: {
                    background: '#e8edf5',
                    strokeWidth: '100%',
                    margin: 4,
                    dropShadow: { enabled: false },
                },
                dataLabels: {
                    show: true,
                    name: {
                        show: false,
                    },
                    value: {
                        show: true,
                        fontSize: '18px',
                        fontWeight: 700,
                        color: color || '#1e293b',
                        offsetY: 6,
                        formatter: function(val) { return Math.round(val) + '%'; },
                    },
                },
            },
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'horizontal',
                shadeIntensity: 0.3,
                gradientToColors: [color || '#f97316'],
                inverseColors: false,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100],
            },
            colors: [color || '#f97316'],
        },
        stroke: { lineCap: 'round' },
        labels: [label || ''],
        theme: { mode: 'light' },
        grid: { padding: { top: -10, bottom: -10, left: -10, right: -10 } },
    };
    const chart = new ApexCharts(el, options);
    chart.render();
    return chart;
};

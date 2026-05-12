@extends('layouts.app')

@section('content')
<style>
@media print {
    .no-print, nav, aside, header, footer, form, button { display: none !important; }
    a:not(.no-print-link) { display: none !important; }
    .no-print-link { color: black !important; text-decoration: none !important; cursor: default !important; }
    body { background: white !important; color: black !important; margin: 1cm !important; padding: 0 !important; }
    .shadow, .rounded-lg { box-shadow: none !important; border: none !important; }
    table { width: 100% !important; border: 1px solid #000 !important; font-size: 8pt !important; border-collapse: collapse !important; }
    th, td { border: 1px solid #000 !important; padding: 4px !important; }
    h2 { text-align: center !important; font-size: 18pt !important; margin-bottom: 20px !important; }
}
</style>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl rounded-2xl p-4 border-t border-r border-b border-gray-200 dark:border-gray-600 border-l-4 border-l-blue-500">
        <div class="text-sm text-gray-500 dark:text-gray-400 font-bold uppercase">💻 Total de Equipos</div>
        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalEquipos ?? 0 }}</div>
    </div>
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl rounded-2xl p-4 border-t border-r border-b border-gray-200 dark:border-gray-600 border-l-4 border-l-emerald-500">
        <div class="text-sm text-gray-500 dark:text-gray-400 font-bold uppercase">🔧 Mantenimientos</div>
        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalMantenimientos ?? 0 }}</div>
    </div>
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl rounded-2xl p-4 border-t border-r border-b border-gray-200 dark:border-gray-600 border-l-4 border-l-amber-500">
        <div class="text-sm text-gray-500 dark:text-gray-400 font-bold uppercase">⏳ Pendientes</div>
        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['pendientes'] ?? 0 }}</div>
    </div>
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl rounded-2xl p-4 border-t border-r border-b border-gray-200 dark:border-gray-600 border-l-4 border-l-violet-500">
        <div class="text-sm text-gray-500 dark:text-gray-400 font-bold uppercase">✅ Terminados</div>
        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['terminados'] ?? 0 }}</div>
    </div>
</div>

<!-- Carrusel de Gráficos -->
<h3 class="text-lg font-bold mb-3 text-gray-700 dark:text-gray-300 flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
    Análisis Visual de Rendimiento
</h3>
<div class="mb-8 relative overflow-hidden rounded-2xl shadow-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 group" id="statsCarouselContainer">
    
    <!-- Indicadores -->
    <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-3 z-10 flex-wrap px-2" id="carouselIndicators">
        <button type="button" class="w-3 h-3 rounded-full bg-blue-600 dark:bg-blue-500 transition-all duration-500 ring-2 ring-white dark:ring-gray-800"></button>
        <button type="button" class="w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600 transition-all duration-500 ring-2 ring-white dark:ring-gray-800"></button>
        <button type="button" class="w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600 transition-all duration-500 ring-2 ring-white dark:ring-gray-800"></button>
        <button type="button" class="w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600 transition-all duration-500 ring-2 ring-white dark:ring-gray-800"></button>
    </div>

    <!-- Contenedor Deslizante -->
    <div class="flex transition-transform duration-1000 ease-in-out" id="carouselTrack" style="width: 400%;">
        
        <!-- Slide 1: Gráfico de Barras (Tendencia 7 Días) -->
        <div class="w-1/4 p-6 flex flex-col bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900" style="height: 420px;">
            <div class="flex justify-between items-center mb-6 px-4">
                <div>
                    <h4 class="text-xl font-black text-gray-800 dark:text-white tracking-tight">Crecimiento Semanal</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Comparativa de ingresos de equipos vs órdenes creadas</p>
                </div>
                <span class="text-xs font-bold px-3 py-1.5 bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 rounded-full shadow-sm">Últimos 7 Días</span>
            </div>
            <div class="w-full flex-grow relative px-2 pb-6">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <!-- Slide 2: Gráfico Circular (Distribución de Órdenes) -->
        <div class="w-1/4 p-6 flex flex-col bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900" style="height: 420px;">
            <div class="flex justify-between items-center mb-2 px-4">
                <div>
                    <h4 class="text-xl font-black text-gray-800 dark:text-white tracking-tight">Distribución Global</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Estado actual de todos los mantenimientos históricos</p>
                </div>
                <span class="text-xs font-bold px-3 py-1.5 bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300 rounded-full shadow-sm">Tiempo Real</span>
            </div>
            <div class="w-full flex-grow relative flex justify-center items-center pb-8">
                <!-- Wrapper para forzar tamaño pequeño -->
                <div style="height: 250px; width: 250px; position: relative;">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Slide 3: Ingresos por día (últimos 7) + total acumulado -->
        <div class="w-1/4 p-6 flex flex-col bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900" style="height: 420px;">
            <div class="px-4 mb-4 space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h4 class="text-xl font-black text-gray-800 dark:text-white tracking-tight">Ingresos por día</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Órdenes terminadas: suma de costo por fecha de salida</p>
                    </div>
                    <span class="text-xs font-bold px-3 py-1.5 bg-teal-100 text-teal-800 dark:bg-teal-900/50 dark:text-teal-200 rounded-full shadow-sm shrink-0">7 días</span>
                </div>
                <div class="flex flex-wrap gap-6 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 tracking-wide">Total acumulado</p>
                        <p class="text-lg font-black text-teal-600 dark:text-teal-400">${{ $totalCostoFormateado ?? '0.00' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-gray-500 dark:text-gray-400 tracking-wide">Hoy</p>
                        <p class="text-lg font-black text-blue-600 dark:text-blue-400">${{ $totalCostoDiaFormateado ?? '0.00' }}</p>
                    </div>
                </div>
            </div>
            <div class="w-full flex-grow relative px-2 pb-8">
                <canvas id="ingresosChart"></canvas>
            </div>
        </div>

        <!-- Slide 4: Ranking de Técnicos -->
        <div class="w-1/4 p-6 flex flex-col bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900" style="height: 420px;">
            <div class="flex justify-between items-center mb-6 px-4">
                <div>
                    <h4 class="text-xl font-black text-gray-800 dark:text-white tracking-tight">Top Técnicos</h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Técnicos con mayor cantidad de equipos terminados</p>
                </div>
                <span class="text-xs font-bold px-3 py-1.5 bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300 rounded-full shadow-sm">Global</span>
            </div>
            <div class="w-full flex-grow relative px-2 pb-6">
                <canvas id="topTechChart"></canvas>
            </div>
        </div>

    </div>

    <!-- Controles -->
    <button id="btnPrev" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/10 dark:bg-gray-700/10 hover:bg-white/40 dark:hover:bg-gray-700/40 text-gray-800 dark:text-white w-10 h-10 rounded-full flex items-center justify-center shadow backdrop-blur-md transition-all duration-300 z-10 opacity-0 group-hover:opacity-100 hover:scale-110"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg></button>
    <button id="btnNext" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/10 dark:bg-gray-700/10 hover:bg-white/40 dark:hover:bg-gray-700/40 text-gray-800 dark:text-white w-10 h-10 rounded-full flex items-center justify-center shadow backdrop-blur-md transition-all duration-300 z-10 opacity-0 group-hover:opacity-100 hover:scale-110"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transform hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></button>
</div>

<div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow-xl border border-gray-200 dark:border-gray-600 rounded-2xl p-6">
    <h3 class="text-xl font-bold mb-4 text-gray-800 dark:text-white">Mantenimientos Recientes</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-200 dark:bg-gray-700 text-xs uppercase">
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Orden</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Equipo</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Costo</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Estado</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Entrada</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Días</th>
                    <th class="p-3 text-center border border-gray-300 dark:border-gray-500">Salida</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @foreach($recentMant ?? [] as $m)
                @php
                    $fechaEntrada = \Carbon\Carbon::parse($m->fecha_entrada)->startOfDay();
                    $fechaFin = $m->fecha_salida 
                        ? \Carbon\Carbon::parse($m->fecha_salida)->startOfDay() 
                        : \Carbon\Carbon::now()->startOfDay();
                    $diasTranscurridos = $fechaEntrada->diffInDays($fechaFin);
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="p-3 text-center font-bold whitespace-nowrap border border-gray-300 dark:border-gray-500">
                        <a href="{{ route('mantenimientos.index') }}#mantenimiento-{{ $m->id }}" class="text-blue-600 hover:text-blue-800 hover:underline no-print-link">
                            {{ $m->id_orden }}
                        </a>
                    </td>
                    
                    {{-- Celda de Equipo: Nombre al lado de Marca/Modelo --}}
                    <td class="p-3 text-center border border-gray-300 dark:border-gray-500">
                        <div class="flex items-baseline justify-center gap-1">
                            <span class="font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $m->equipo->nombre ?? '-' }}
                            </span>
                            <span class="font-bold text-[13px] text-gray-400 italic whitespace-nowrap">
                                ({{ $m->equipo->marca ?? '' }} {{ $m->equipo->modelo ?? '' }})
                            </span>
                            <span class="font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                                {{ $m->equipo->serie ?? '' }}
                            </span>
                        </div>
                    </td>

                    <td class="p-3 text-center font-bold text-green-600 border border-gray-300 dark:border-gray-500">
                        ${{ number_format($m->costo, 2) }}
                    </td>

                    <td class="p-3 text-center border border-gray-300 dark:border-gray-500">
                        <span class="px-2 py-1 rounded-md text-[11px] font-bold backdrop-blur-sm border {{ $m->estado == 'terminado' ? 'bg-green-500/20 text-green-700 dark:text-green-400 border-green-500/30' : 'bg-yellow-500/20 text-yellow-700 dark:text-yellow-400 border-yellow-500/30' }}">
                            {{ strtoupper($m->estado) }}
                        </span>
                    </td>

                    <td class="p-3 text-center whitespace-nowrap border border-gray-300 dark:border-gray-500">{{ $fechaEntrada->format('d/m/Y') }}</td>
                    
                    <td class="p-3 text-center font-bold border border-gray-300 dark:border-gray-500">
                        <span class="{{ !$m->fecha_salida && $diasTranscurridos > 3 ? 'text-red-500' : 'text-gray-600 dark:text-gray-400' }}">
                            {{ $diasTranscurridos }}
                        </span>
                    </td>

                    <td class="p-3 text-center whitespace-nowrap border border-gray-300 dark:border-gray-500 {{ $m->fecha_salida ? 'font-semibold text-gray-800 dark:text-gray-200' : 'text-gray-400 italic' }}">
                        {{ $m->fecha_salida ? \Carbon\Carbon::parse($m->fecha_salida)->format('d/m/Y') : 'En proceso' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4 text-right">
        <a href="{{ route('mantenimientos.reportes') }}" class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-500/30 hover:bg-blue-500/40 backdrop-blur-sm rounded-xl px-4 py-2 font-semibold transition-all shadow-sm hover:shadow-blue-500/20">
            📈 Ver reporte detallado →
        </a>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- LÓGICA DEL CARRUSEL ---
        const track = document.getElementById('carouselTrack');
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        const indicators = document.getElementById('carouselIndicators').children;
        const totalSlides = 4;
        let currentSlide = 0;
        let autoPlayInterval;

        function updateSlide() {
            track.style.transform = `translateX(-${currentSlide * 25}%)`;
            for (let i = 0; i < totalSlides; i++) {
                const ind = indicators[i];
                ind.classList.remove('w-8', 'h-3', 'bg-blue-600', 'dark:bg-blue-500', 'bg-gray-300', 'dark:bg-gray-600');
                if (i === currentSlide) {
                    ind.classList.add('w-8', 'h-3', 'bg-blue-600', 'dark:bg-blue-500');
                } else {
                    ind.classList.add('w-3', 'h-3', 'bg-gray-300', 'dark:bg-gray-600');
                }
            }
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateSlide();
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateSlide();
        }

        function startAutoPlay() {
            autoPlayInterval = setInterval(nextSlide, 15000); // Demora de 15 SEGUNDOS
        }

        function resetAutoPlay() {
            clearInterval(autoPlayInterval);
            startAutoPlay();
        }

        if (btnNext) btnNext.addEventListener('click', () => { nextSlide(); resetAutoPlay(); });
        if (btnPrev) btnPrev.addEventListener('click', () => { prevSlide(); resetAutoPlay(); });

        Array.from(indicators).forEach((ind, index) => {
            ind.addEventListener('click', () => {
                currentSlide = index;
                updateSlide();
                resetAutoPlay();
            });
        });

        startAutoPlay();
        updateSlide();

        // --- LÓGICA DE GRÁFICOS (CHART.JS) MEJORADA ---
        Chart.defaults.font.family = "'Inter', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
        Chart.defaults.color = '#6B7280'; // gray-500
        
        const chartData = @json($chartData);
        const stats = @json($stats);
        const ingresosData = (chartData && chartData.ingresos) ? chartData.ingresos : [];

        function formatMoneyEs(value) {
            const n = Number(value) || 0;
            return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Configurar gradientes para el gráfico de barras
        const canvasBar = document.getElementById('barChart');
        if (canvasBar) {
            const ctxBar = canvasBar.getContext('2d');
            const gradientBlue = ctxBar.createLinearGradient(0, 0, 0, 400);
            gradientBlue.addColorStop(0, 'rgba(59, 130, 246, 0.9)'); // solid blue
            gradientBlue.addColorStop(1, 'rgba(59, 130, 246, 0.1)'); // transparent blue
            
            const gradientGreen = ctxBar.createLinearGradient(0, 0, 0, 400);
            gradientGreen.addColorStop(0, 'rgba(16, 185, 129, 0.9)'); // solid green
            gradientGreen.addColorStop(1, 'rgba(16, 185, 129, 0.1)'); // transparent green

            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [
                        {
                            label: 'Equipos Registrados',
                            data: chartData.equipos,
                            backgroundColor: gradientBlue,
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 2,
                            borderRadius: 6, // bordes muy redondeados
                            borderSkipped: false,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'Mantenimientos Creados',
                            data: chartData.mantenimientos,
                            backgroundColor: gradientGreen,
                            borderColor: 'rgb(16, 185, 129)',
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: { 
                        legend: { 
                            position: 'top',
                            labels: { usePointStyle: true, padding: 20, font: { weight: 'bold' } }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.9)',
                            titleFont: { size: 14 },
                            bodyFont: { size: 13 },
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: true,
                            usePointStyle: true
                        }
                    },
                    scales: { 
                        y: { 
                            beginAtZero: true, 
                            ticks: { precision: 0, padding: 10 },
                            grid: { borderDash: [4, 4], color: 'rgba(156, 163, 175, 0.2)', drawBorder: false }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { weight: 'bold' }, padding: 10 }
                        }
                    }
                }
            });
        }

        // Plugin para poner texto en el centro de la Dona
        const centerTextPlugin = {
            id: 'centerText',
            beforeDraw: function(chart) {
                if (chart.config.type !== 'doughnut') return;
                var width = chart.width,
                    height = chart.height,
                    ctx = chart.ctx;

                ctx.restore();
                var fontSize = (height / 120).toFixed(2);
                ctx.font = "bold " + fontSize + "em sans-serif";
                ctx.textBaseline = "middle";
                ctx.fillStyle = document.documentElement.classList.contains('dark') ? '#E5E7EB' : '#1F2937';

                var total = chart.config.data.datasets[0].data.reduce((a, b) => a + b, 0);
                var text = total.toString(),
                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                    textY = height / 2 - 10;

                ctx.fillText(text, textX, textY);

                ctx.font = "600 " + (fontSize / 2.5).toFixed(2) + "em sans-serif";
                ctx.fillStyle = '#6B7280';
                var text2 = "Órdenes Totales",
                    text2X = Math.round((width - ctx.measureText(text2).width) / 2),
                    text2Y = height / 2 + 15;
                
                ctx.fillText(text2, text2X, text2Y);
                ctx.save();
            }
        };

        // Slide 2: Gráfico Circular (Doughnut) mejorado
        const canvasPie = document.getElementById('pieChart');
        if (canvasPie) {
            const ctxPie = canvasPie.getContext('2d');
            
            // Gradientes para la dona translucidos
            const gradTerminado = ctxPie.createLinearGradient(0, 0, 0, 400);
            gradTerminado.addColorStop(0, 'rgba(16, 185, 129, 0.7)');
            gradTerminado.addColorStop(1, 'rgba(16, 185, 129, 0.1)');
            
            const gradPendiente = ctxPie.createLinearGradient(0, 0, 0, 400);
            gradPendiente.addColorStop(0, 'rgba(245, 158, 11, 0.7)');
            gradPendiente.addColorStop(1, 'rgba(245, 158, 11, 0.1)');

            new Chart(ctxPie, {
                type: 'doughnut',
                plugins: [centerTextPlugin],
                data: {
                    labels: ['Terminados', 'Pendientes'],
                    datasets: [{
                        data: [
                            {{ $stats['terminados'] ?? 0 }},
                            {{ $stats['pendientes'] ?? 0 }}
                        ],
                        backgroundColor: [gradTerminado, gradPendiente],
                        hoverBackgroundColor: ['rgba(16, 185, 129, 1)', 'rgba(245, 158, 11, 1)'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    layout: { padding: 10 },
                    plugins: { 
                        legend: { 
                            position: 'bottom',
                            labels: { usePointStyle: true, padding: 15, font: { weight: 'bold', size: 12 } }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.9)',
                            bodyFont: { size: 14, weight: 'bold' },
                            padding: 12,
                            cornerRadius: 8,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) { label += ': '; }
                                    if (context.parsed !== null) { label += context.parsed + ' órdenes'; }
                                    return label;
                                }
                            }
                        }
                    },
                    cutout: '70%',
                    animation: { animateScale: true, animateRotate: true }
                }
            });
        }

        const canvasIngresos = document.getElementById('ingresosChart');
        if (canvasIngresos) {
            const ctxIng = canvasIngresos.getContext('2d');
            const gradientTeal = ctxIng.createLinearGradient(0, 0, 0, 400);
            gradientTeal.addColorStop(0, 'rgba(20, 184, 166, 0.85)');
            gradientTeal.addColorStop(1, 'rgba(20, 184, 166, 0.12)');

            new Chart(ctxIng, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Ingresos del día',
                        data: ingresosData,
                        backgroundColor: gradientTeal,
                        borderColor: 'rgb(13, 148, 136)',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
                        barPercentage: 0.55,
                        categoryPercentage: 0.85
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.9)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(ctx) {
                                    return formatMoneyEs(ctx.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(v) { return '$' + Number(v).toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); }
                            },
                            grid: { borderDash: [4, 4], color: 'rgba(156, 163, 175, 0.2)', drawBorder: false }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { weight: 'bold' }, padding: 8 }
                        }
                    }
                }
            });
        }

        // Slide 4: Ranking de Técnicos (Gráfico de Barras Horizontales)
        const canvasTopTech = document.getElementById('topTechChart');
        if (canvasTopTech) {
            const ctxTopTech = canvasTopTech.getContext('2d');
            const gradientPurple = ctxTopTech.createLinearGradient(400, 0, 0, 0);
            gradientPurple.addColorStop(0, 'rgba(139, 92, 246, 0.7)'); // solid purple
            gradientPurple.addColorStop(1, 'rgba(139, 92, 246, 0.1)'); // transparent purple
            
            const gradientBlue = ctxTopTech.createLinearGradient(400, 0, 0, 0);
            gradientBlue.addColorStop(0, 'rgba(59, 130, 246, 0.7)'); // solid blue
            gradientBlue.addColorStop(1, 'rgba(59, 130, 246, 0.1)'); // transparent blue

            new Chart(ctxTopTech, {
                type: 'bar',
                data: {
                    labels: chartData.topTecnicosLabels,
                    datasets: [
                        {
                            label: 'Equipos Recibidos',
                            data: chartData.topTecnicosRecibidosData,
                            backgroundColor: gradientBlue,
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false
                        },
                        {
                            label: 'Equipos Terminados',
                            data: chartData.topTecnicosData,
                            backgroundColor: gradientPurple,
                            borderColor: 'rgb(139, 92, 246)',
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false
                        }
                    ]
                },
                options: {
                    indexAxis: 'y', // Hace que el gráfico de barras sea horizontal
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { 
                            position: 'top',
                            labels: { usePointStyle: true, padding: 15, font: { weight: 'bold' } }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(17, 24, 39, 0.9)',
                            bodyFont: { size: 14 },
                            padding: 12,
                            cornerRadius: 8,
                            usePointStyle: true
                        }
                    },
                    scales: { 
                        x: { 
                            beginAtZero: true, 
                            ticks: { precision: 0 },
                            grid: { borderDash: [4, 4], color: 'rgba(156, 163, 175, 0.2)', drawBorder: false }
                        },
                        y: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { weight: 'bold' } }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection

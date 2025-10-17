/**
 * Chart Loader - Carregador de Gráficos para o Simulador Financeiro
 * Responsável por carregar e gerenciar gráficos usando Chart.js
 */

class ChartLoader {
    constructor() {
        this.charts = new Map();
        this.chartJsLoaded = false;
        this.init();
    }

    init() {
        this.loadChartJS();
    }

    /**
     * Carrega a biblioteca Chart.js dinamicamente
     */
    loadChartJS() {
        if (typeof Chart !== 'undefined') {
            this.chartJsLoaded = true;
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            // Verifica se já está carregando
            if (document.querySelector('script[src*="chart.js"]')) {
                const checkLoaded = setInterval(() => {
                    if (typeof Chart !== 'undefined') {
                        clearInterval(checkLoaded);
                        this.chartJsLoaded = true;
                        resolve();
                    }
                }, 100);
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = () => {
                this.chartJsLoaded = true;
                resolve();
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Cria um gráfico de pizza para distribuição de parcelas
     * @param {string} canvasId - ID do elemento canvas
     * @param {Object} data - Dados do gráfico
     * @returns {Chart} Instância do gráfico
     */
    createPieChart(canvasId, data) {
        return this.loadChartJS().then(() => {
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            if (this.charts.has(canvasId)) {
                this.charts.get(canvasId).destroy();
            }

            const chart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        data: data.values || [],
                        backgroundColor: data.colors || [
                            '#4CAF50', '#2196F3', '#FF9800', 
                            '#E91E63', '#9C27B0', '#607D8B'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${this.formatCurrency(value)} (${percentage}%)`;
                                }.bind(this)
                            }
                        }
                    }
                }
            });

            this.charts.set(canvasId, chart);
            return chart;
        });
    }

    /**
     * Cria um gráfico de barras para comparação de modalidades
     * @param {string} canvasId - ID do elemento canvas
     * @param {Object} data - Dados do gráfico
     * @returns {Chart} Instância do gráfico
     */
    createBarChart(canvasId, data) {
        return this.loadChartJS().then(() => {
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            if (this.charts.has(canvasId)) {
                this.charts.get(canvasId).destroy();
            }

            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels || [],
                    datasets: data.datasets || []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return this.formatCurrency(value);
                                }.bind(this)
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${this.formatCurrency(context.parsed.y)}`;
                                }.bind(this)
                            }
                        }
                    }
                }
            });

            this.charts.set(canvasId, chart);
            return chart;
        });
    }

    /**
     * Cria um gráfico de linha para evolução do saldo devedor
     * @param {string} canvasId - ID do elemento canvas
     * @param {Object} data - Dados do gráfico
     * @returns {Chart} Instância do gráfico
     */
    createLineChart(canvasId, data) {
        return this.loadChartJS().then(() => {
            const ctx = document.getElementById(canvasId).getContext('2d');
            
            if (this.charts.has(canvasId)) {
                this.charts.get(canvasId).destroy();
            }

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels || [],
                    datasets: data.datasets || []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return this.formatCurrency(value);
                                }.bind(this)
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${this.formatCurrency(context.parsed.y)}`;
                                }.bind(this)
                            }
                        }
                    }
                }
            });

            this.charts.set(canvasId, chart);
            return chart;
        });
    }

    /**
     * Formata valor como moeda
     * @param {number} value - Valor a ser formatado
     * @returns {string} Valor formatado
     */
    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    /**
     * Atualiza dados de um gráfico existente
     * @param {string} canvasId - ID do elemento canvas
     * @param {Object} newData - Novos dados
     */
    updateChart(canvasId, newData) {
        const chart = this.charts.get(canvasId);
        if (chart) {
            chart.data = newData;
            chart.update();
        }
    }

    /**
     * Destroi um gráfico específico
     * @param {string} canvasId - ID do elemento canvas
     */
    destroyChart(canvasId) {
        const chart = this.charts.get(canvasId);
        if (chart) {
            chart.destroy();
            this.charts.delete(canvasId);
        }
    }

    /**
     * Destroi todos os gráficos
     */
    destroyAllCharts() {
        this.charts.forEach((chart, canvasId) => {
            chart.destroy();
            this.charts.delete(canvasId);
        });
    }

    /**
     * Exporta gráfico como imagem
     * @param {string} canvasId - ID do elemento canvas
     * @param {string} filename - Nome do arquivo
     */
    exportChartAsImage(canvasId, filename = 'grafico') {
        const chart = this.charts.get(canvasId);
        if (chart) {
            const link = document.createElement('a');
            link.download = `${filename}.png`;
            link.href = chart.toBase64Image();
            link.click();
        }
    }

    /**
     * Cria gráfico de comparação entre modalidades
     * @param {string} canvasId - ID do elemento canvas
     * @param {Array} modalidades - Lista de modalidades
     * @returns {Chart} Instância do gráfico
     */
    createComparisonChart(canvasId, modalidades) {
        const datasets = [
            {
                label: 'Total com Juros',
                data: modalidades.map(m => m.total_com_juros),
                backgroundColor: '#4CAF50',
                borderColor: '#4CAF50',
                borderWidth: 1
            },
            {
                label: 'Valor Financiado',
                data: modalidades.map(m => m.valor_financiado),
                backgroundColor: '#2196F3',
                borderColor: '#2196F3',
                borderWidth: 1
            },
            {
                label: 'Juros Total',
                data: modalidades.map(m => m.total_juros),
                backgroundColor: '#FF9800',
                borderColor: '#FF9800',
                borderWidth: 1
            }
        ];

        return this.createBarChart(canvasId, {
            labels: modalidades.map(m => m.nome),
            datasets: datasets
        });
    }
}

// Instância global do ChartLoader
window.simuladorChartLoader = new ChartLoader();

// Inicialização quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    console.log('ChartLoader inicializado');
});

// Export para módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ChartLoader;
}

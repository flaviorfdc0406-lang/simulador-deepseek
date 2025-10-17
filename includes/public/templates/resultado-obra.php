<?php
if (!defined('ABSPATH')) {
    exit;
}

$resumo = $data['resumo'];
$parcelas_obra = $data['parcelas_obra'];
$parcelas_amortizacao = $data['parcelas_amortizacao'];
?>

<div class="resultado-obra">
    <div class="resultado-header">
        <h2>Resultado da Simulação - Período de Obras</h2>
        <div class="resultado-actions">
            <button type="button" class="btn-print" onclick="window.print()">
                📄 Imprimir
            </button>
            <button type="button" class="btn-download" onclick="exportarPDF()">
                📥 Salvar PDF
            </button>
        </div>
    </div>

    <!-- Resumo Principal -->
    <div class="resumo-principal">
        <div class="resumo-grid">
            <div class="resumo-card">
                <div class="resumo-icon">🏗️</div>
                <div class="resumo-content">
                    <h3>Período de Obra</h3>
                    <div class="resumo-valor"><?php echo $resumo['num_parcelas_obra']; ?> parcelas</div>
                    <div class="resumo-detalhe"><?php echo esc_html($parcelas_obra[0]['periodicidade']); ?></div>
                </div>
            </div>
            
            <div class="resumo-card">
                <div class="resumo-icon">💰</div>
                <div class="resumo-content">
                    <h3>Total Período Obra</h3>
                    <div class="resumo-valor">R$ <?php echo number_format($resumo['total_periodo_obra'], 2, ',', '.'); ?></div>
                    <div class="resumo-detalhe">Apenas juros</div>
                </div>
            </div>
            
            <div class="resumo-card">
                <div class="resumo-icon">📅</div>
                <div class="resumo-content">
                    <h3>Período Amortização</h3>
                    <div class="resumo-valor"><?php echo $resumo['num_parcelas_amortizacao']; ?> parcelas</div>
                    <div class="resumo-detalhe">Mensais</div>
                </div>
            </div>
            
            <div class="resumo-card">
                <div class="resumo-icon">🏠</div>
                <div class="resumo-content">
                    <h3>Total Geral</h3>
                    <div class="resumo-valor">R$ <?php echo number_format($resumo['total_geral'], 2, ',', '.'); ?></div>
                    <div class="resumo-detalhe">CET: <?php echo number_format($resumo['cet'], 2, ',', '.'); ?>%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="graficos-section">
        <h3>Visualização do Financiamento</h3>
        <div class="graficos-grid">
            <div class="grafico-container">
                <canvas id="grafico-evolucao-saldo" width="400" height="200"></canvas>
            </div>
            <div class="grafico-container">
                <canvas id="grafico-composicao" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabela de Parcelas -->
    <div class="parcelas-section">
        <h3>Detalhamento das Parcelas</h3>
        
        <div class="periodo-tabs">
            <button class="tab-button active" data-tab="obra">Período de Obra (<?php echo count($parcelas_obra); ?> parcelas)</button>
            <button class="tab-button" data-tab="amortizacao">Período de Amortização (<?php echo count($parcelas_amortizacao); ?> parcelas)</button>
        </div>
        
        <div class="tab-content">
            <div id="tab-obra" class="tab-pane active">
                <div class="table-container">
                    <table class="parcelas-table">
                        <thead>
                            <tr>
                                <th>Parcela</th>
                                <th>Vencimento</th>
                                <th>Valor (R$)</th>
                                <th>Juros (R$)</th>
                                <th>Amortização</th>
                                <th>Saldo Devedor (R$)</th>
                                <th>Periodicidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parcelas_obra as $parcela): ?>
                            <tr>
                                <td><?php echo $parcela['numero']; ?></td>
                                <td><?php echo esc_html($parcela['data_vencimento']); ?></td>
                                <td>R$ <?php echo number_format($parcela['valor_parcela'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($parcela['juros'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($parcela['amortizacao'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($parcela['saldo_devedor'], 2, ',', '.'); ?></td>
                                <td><?php echo esc_html($parcela['periodicidade']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="tab-amortizacao" class="tab-pane">
                <div class="table-container">
                    <table class="parcelas-table">
                        <thead>
                            <tr>
                                <th>Parcela</th>
                                <th>Vencimento</th>
                                <th>Valor (R$)</th>
                                <th>Juros (R$)</th>
                                <th>Amortização (R$)</th>
                                <th>Saldo Devedor (R$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parcelas_amortizacao as $parcela): ?>
                            <tr>
                                <td><?php echo $parcela['numero']; ?></td>
                                <td><?php echo esc_html($parcela['data_vencimento']); ?></td>
                                <td>R$ <?php echo number_format($parcela['valor_parcela'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($parcela['juros'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($parcela['amortizacao'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($parcela['saldo_devedor'], 2, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparação -->
    <div class="comparacao-section">
        <h3>Comparação: Com vs Sem Período de Obra</h3>
        <div class="comparacao-grid">
            <div class="comparacao-card">
                <h4>Com Período de Obra</h4>
                <div class="comparacao-valor">R$ <?php echo number_format($resumo['total_geral'], 2, ',', '.'); ?></div>
                <div class="comparacao-detalhe">Total a pagar</div>
                <div class="comparacao-parcelas"><?php echo count($parcelas_obra) + count($parcelas_amortizacao); ?> parcelas</div>
            </div>
            
            <div class="comparacao-card diferenca">
                <h4>Economia/Incremento</h4>
                <div class="comparacao-valor">+R$ <?php echo number_format($resumo['total_periodo_obra'], 2, ',', '.'); ?></div>
                <div class="comparacao-detalhe">Custo do período de obra</div>
            </div>
        </div>
    </div>

    <!-- Ações -->
    <div class="acoes-section">
        <button type="button" class="btn-primary" onclick="salvarSimulacao()">
            💾 Salvar Esta Simulação
        </button>
        <button type="button" class="btn-secondary" onclick="novaSimulacao()">
            🔄 Nova Simulação
        </button>
        <button type="button" class="btn-success" onclick="solicitarProposta()">
            📋 Solicitar Proposta
        </button>
    </div>
</div>

<script>
function exportarPDF() {
    // Implementar geração de PDF
    alert('Funcionalidade de exportação PDF em desenvolvimento');
}

function salvarSimulacao() {
    // Implementar salvamento
    alert('Simulação salva com sucesso!');
}

function novaSimulacao() {
    location.reload();
}

function solicitarProposta() {
    // Implementar solicitação de proposta
    alert('Redirecionando para solicitação de proposta...');
}

// Controle das abas
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        // Remove classe active de todos
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
        
        // Adiciona classe active ao clicado
        this.classList.add('active');
        const tabId = this.getAttribute('data-tab');
        document.getElementById(`tab-${tabId}`).classList.add('active');
    });
});

// Inicializar gráficos
document.addEventListener('DOMContentLoaded', function() {
    if (typeof simuladorChartLoader !== 'undefined') {
        const graficos = <?php echo json_encode($data['graficos']); ?>;
        
        // Gráfico de evolução do saldo devedor
        simuladorChartLoader.createLineChart('grafico-evolucao-saldo', {
            labels: graficos.labels,
            datasets: [{
                label: 'Saldo Devedor',
                data: graficos.saldo_devedor,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                fill: true,
                tension: 0.4
            }]
        });
        
        // Gráfico de composição (últimas 12 parcelas)
        const ultimasParcelas = graficos.labels.slice(-12);
        const ultimosJuros = graficos.juros.slice(-12);
        const ultimasAmortizacoes = graficos.amortizacao.slice(-12);
        
        simuladorChartLoader.createBarChart('grafico-composicao', {
            labels: ultimasParcelas,
            datasets: [
                {
                    label: 'Juros',
                    data: ultimosJuros,
                    backgroundColor: '#e74c3c'
                },
                {
                    label: 'Amortização',
                    data: ultimasAmortizacoes,
                    backgroundColor: '#2ecc71'
                }
            ]
        });
    }
});
</script>

<style>
.resultado-obra {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.resultado-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: #34495e;
    color: white;
}

.resultado-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.resultado-actions {
    display: flex;
    gap: 1rem;
}

.resumo-principal {
    padding: 2rem;
    background: #f8f9fa;
}

.resumo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.resumo-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba

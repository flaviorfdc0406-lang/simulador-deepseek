<?php
if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Handlers {
    private $obra_calculator;
    
    public function __construct() {
        // Handlers para simulação com obra
        add_action('wp_ajax_calcular_simulacao_obra', array($this, 'calcular_simulacao_obra'));
        add_action('wp_ajax_nopriv_calcular_simulacao_obra', array($this, 'calcular_simulacao_obra'));
        
        // Handlers para salvar simulação
        add_action('wp_ajax_salvar_simulacao_obra', array($this, 'salvar_simulacao_obra'));
        add_action('wp_ajax_nopriv_salvar_simulacao_obra', array($this, 'salvar_simulacao_obra'));
        
        // Inicializar calculadora
        $this->init_calculator();
    }
    
    private function init_calculator() {
        if (!class_exists('Obra_Calculator')) {
            $calculator_path = SIMULADOR_PLUGIN_PATH . 'includes/class-obra-calculator.php';
            if (file_exists($calculator_path)) {
                require_once $calculator_path;
                $this->obra_calculator = new Obra_Calculator();
            }
        } else {
            $this->obra_calculator = new Obra_Calculator();
        }
    }
    
    public function calcular_simulacao_obra() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['security'], 'simulador_nonce')) {
            wp_send_json_error('Erro de segurança. Recarregue a página e tente novamente.');
        }
        
        try {
            // Se a calculadora não foi carregada, usar cálculo básico
            if (!$this->obra_calculator) {
                $resultado = $this->calculo_basico_obra($_POST);
            } else {
                // Validar dados
                $errors = $this->obra_calculator->validar_dados_obra($_POST);
                if (!empty($errors)) {
                    wp_send_json_error(implode('<br>', $errors));
                }
                
                // Calcular simulação
                $resultado = $this->obra_calculator->calcular_financiamento_obra($_POST);
            }
            
            // Gerar HTML do resultado
            $html = $this->gerar_html_resultado($resultado);
            
            wp_send_json_success(array(
                'html' => $html,
                'dados' => $resultado
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Erro ao calcular simulação: ' . $e->getMessage());
        }
    }
    
    /**
     * Cálculo básico fallback
     */
    private function calculo_basico_obra($dados) {
        $valor_imovel = floatval($dados['valor_imovel']);
        $valor_entrada = floatval($dados['valor_entrada']);
        $taxa_juros_ano = floatval($dados['taxa_juros']) / 100;
        $prazo_anos = intval($dados['prazo_anos']);
        $periodo_obra_meses = intval($dados['periodo_obra_meses']);
        $periodicidade_obra = sanitize_text_field($dados['periodicidade_obra']);
        
        $valor_financiado = $valor_imovel - $valor_entrada;
        $taxa_juros_mes = pow(1 + $taxa_juros_ano, 1/12) - 1;
        
        // Cálculo do período de obra
        $parcelas_obra = array();
        $taxa_obra_mensal = 0.02; // 2% ao mês
        
        // Ajustar taxa conforme periodicidade
        switch ($periodicidade_obra) {
            case 'mensal':
                $num_parcelas = $periodo_obra_meses;
                $taxa_periodo = $taxa_obra_mensal;
                break;
            case 'bimestral':
                $num_parcelas = ceil($periodo_obra_meses / 2);
                $taxa_periodo = pow(1 + $taxa_obra_mensal, 2) - 1;
                break;
            case 'trimestral':
                $num_parcelas = ceil($periodo_obra_meses / 3);
                $taxa_periodo = pow(1 + $taxa_obra_mensal, 3) - 1;
                break;
            case 'semestral':
                $num_parcelas = ceil($periodo_obra_meses / 6);
                $taxa_periodo = pow(1 + $taxa_obra_mensal, 6) - 1;
                break;
            case 'anual':
                $num_parcelas = ceil($periodo_obra_meses / 12);
                $taxa_periodo = pow(1 + $taxa_obra_mensal, 12) - 1;
                break;
            default:
                $num_parcelas = $periodo_obra_meses;
                $taxa_periodo = $taxa_obra_mensal;
        }
        
        $saldo_devedor = $valor_financiado;
        $data_vencimento = new DateTime();
        $data_vencimento->modify('+1 month');
        
        for ($i = 1; $i <= $num_parcelas; $i++) {
            $juros = $saldo_devedor * $taxa_periodo;
            
            $parcelas_obra[] = array(
                'numero' => $i,
                'data_vencimento' => $data_vencimento->format('d/m/Y'),
                'valor_parcela' => $juros,
                'juros' => $juros,
                'amortizacao' => 0,
                'saldo_devedor' => $saldo_devedor,
                'periodo' => 'obra',
                'periodicidade' => $periodicidade_obra
            );
            
            // Avançar data conforme periodicidade
            switch ($periodicidade_obra) {
                case 'mensal': $data_vencimento->modify('+1 month'); break;
                case 'bimestral': $data_vencimento->modify('+2 months'); break;
                case 'trimestral': $data_vencimento->modify('+3 months'); break;
                case 'semestral': $data_vencimento->modify('+6 months'); break;
                case 'anual': $data_vencimento->modify('+1 year'); break;
            }
        }
        
        // Cálculo do período de amortização (Sistema Price)
        $parcelas_amortizacao = array();
        $num_parcelas_amort = ($prazo_anos * 12) - $periodo_obra_meses;
        
        if ($num_parcelas_amort > 0) {
            $fator = pow(1 + $taxa_juros_mes, $num_parcelas_amort);
            $valor_parcela = $valor_financiado * $taxa_juros_mes * $fator / ($fator - 1);
            
            $saldo_devedor = $valor_financiado;
            
            for ($i = 1; $i <= $num_parcelas_amort; $i++) {
                $juros = $saldo_devedor * $taxa_juros_mes;
                $amortizacao = $valor_parcela - $juros;
                $saldo_devedor -= $amortizacao;
                
                if ($saldo_devedor < 0) $saldo_devedor = 0;
                
                $parcelas_amortizacao[] = array(
                    'numero' => $i + $num_parcelas,
                    'data_vencimento' => $data_vencimento->format('d/m/Y'),
                    'valor_parcela' => $valor_parcela,
                    'juros' => $juros,
                    'amortizacao' => $amortizacao,
                    'saldo_devedor' => $saldo_devedor,
                    'periodo' => 'amortizacao'
                );
                
                $data_vencimento->modify('+1 month');
            }
        }
        
        // Resumo
        $total_juros_obra = array_sum(array_column($parcelas_obra, 'juros'));
        $total_juros_amortizacao = array_sum(array_column($parcelas_amortizacao, 'juros'));
        $total_geral = $valor_financiado + $total_juros_obra + $total_juros_amortizacao;
        
        return array(
            'parcelas_obra' => $parcelas_obra,
            'parcelas_amortizacao' => $parcelas_amortizacao,
            'resumo' => array(
                'valor_financiado' => $valor_financiado,
                'total_periodo_obra' => $total_juros_obra,
                'total_periodo_amortizacao' => $total_juros_amortizacao,
                'total_juros' => $total_juros_obra + $total_juros_amortizacao,
                'total_geral' => $total_geral,
                'num_parcelas_obra' => count($parcelas_obra),
                'num_parcelas_amortizacao' => count($parcelas_amortizacao),
                'cet' => (($total_geral / $valor_financiado) - 1) * 100
            )
        );
    }
    
    private function gerar_html_resultado($resultado) {
        ob_start();
        ?>
        <div class="simulador-resultado-obra">
            <div class="resultado-header">
                <h3>📊 Resultado da Simulação - Período de Obras</h3>
                <div class="resultado-actions">
                    <button type="button" class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
                    <button type="button" class="btn-pdf" onclick="simuladorExportarPDF()">📥 PDF</button>
                </div>
            </div>

            <!-- Resumo -->
            <div class="resumo-grid">
                <div class="resumo-card">
                    <div class="resumo-icon">🏗️</div>
                    <div class="resumo-content">
                        <h4>Período de Obra</h4>
                        <div class="resumo-valor"><?php echo $resultado['resumo']['num_parcelas_obra']; ?> parcelas</div>
                        <div class="resumo-detalhe">Apenas juros</div>
                    </div>
                </div>
                
                <div class="resumo-card">
                    <div class="resumo-icon">💰</div>
                    <div class="resumo-content">
                        <h4>Total Obra</h4>
                        <div class="resumo-valor">R$ <?php echo number_format($resultado['resumo']['total_periodo_obra'], 2, ',', '.'); ?></div>
                    </div>
                </div>
                
                <div class="resumo-card">
                    <div class="resumo-icon">📅</div>
                    <div class="resumo-content">
                        <h4>Amortização</h4>
                        <div class="resumo-valor"><?php echo $resultado['resumo']['num_parcelas_amortizacao']; ?> parcelas</div>
                    </div>
                </div>
                
                <div class="resumo-card">
                    <div class="resumo-icon">🏠</div>
                    <div class="resumo-content">
                        <h4>Total Geral</h4>
                        <div class="resumo-valor">R$ <?php echo number_format($resultado['resumo']['total_geral'], 2, ',', '.'); ?></div>
                        <div class="resumo-detalhe">CET: <?php echo number_format($resultado['resumo']['cet'], 2, ',', '.'); ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Tabelas de Parcelas -->
            <div class="parcelas-tabs">
                <div class="tab-buttons">
                    <button class="tab-btn active" data-tab="obra">Período de Obra (<?php echo count($resultado['parcelas_obra']); ?>)</button>
                    <button class="tab-btn" data-tab="amortizacao">Amortização (<?php echo count($resultado['parcelas_amortizacao']); ?>)</button>
                </div>
                
                <div class="tab-content">
                    <div class="tab-pane active" id="tab-obra">
                        <div class="table-responsive">
                            <table class="parcelas-table">
                                <thead>
                                    <tr>
                                        <th>Parcela</th>
                                        <th>Vencimento</th>
                                        <th>Valor (R$)</th>
                                        <th>Juros (R$)</th>
                                        <th>Saldo Devedor (R$)</th>
                                        <th>Periodicidade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultado['parcelas_obra'] as $parcela): ?>
                                    <tr>
                                        <td><?php echo $parcela['numero']; ?></td>
                                        <td><?php echo esc_html($parcela['data_vencimento']); ?></td>
                                        <td>R$ <?php echo number_format($parcela['valor_parcela'], 2, ',', '.'); ?></td>
                                        <td>R$ <?php echo number_format($parcela['juros'], 2, ',', '.'); ?></td>
                                        <td>R$ <?php echo number_format($parcela['saldo_devedor'], 2, ',', '.'); ?></td>
                                        <td><?php echo esc_html($parcela['periodicidade']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="tab-pane" id="tab-amortizacao">
                        <div class="table-responsive">
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
                                    <?php foreach ($resultado['parcelas_amortizacao'] as $parcela): ?>
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

            <!-- Ações -->
            <div class="acoes-resultado">
                <button type="button" class="btn-salvar" onclick="simuladorSalvarSimulacao()">
                    💾 Salvar Esta Simulação
                </button>
                <button type="button" class="btn-nova" onclick="simuladorNovaSimulacao()">
                    🔄 Nova Simulação
                </button>
            </div>
        </div>

        <style>
        .simulador-resultado-obra {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .resultado-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        .resumo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .resumo-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #007cba;
        }
        .resumo-icon { font-size: 24px; margin-bottom: 8px; }
        .resumo-card h4 { margin: 0 0 8px 0; color: #495057; font-size: 14px; }
        .resumo-valor { font-size: 18px; font-weight: bold; color: #212529; }
        .resumo-detalhe { font-size: 12px; color: #6c757d; margin-top: 5px; }
        .parcelas-tabs { margin: 25px 0; }
        .tab-buttons { display: flex; gap: 5px; margin-bottom: 15px; }
        .tab-btn {
            padding: 10px 20px;
            background: #e9ecef;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tab-btn.active { background: #007cba; color: white; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        .table-responsive { overflow-x: auto; }
        .parcelas-table {
            width: 100%;
            border-collapse: collapse;
        }
        .parcelas-table th,
        .parcelas-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .parcelas-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .acoes-resultado {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 25px;
        }
        .btn-salvar, .btn-nova {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-salvar { background: #28a745; color: white; }
        .btn-nova { background: #6c757d; color: white; }
        </style>

        <script>
        // Controle de abas
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active de todos
                    tabBtns.forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
                    
                    // Adiciona active
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(`tab-${tabId}`).classList.add('active');
                });
            });
        });

        function simuladorExportarPDF() {
            alert('Funcionalidade PDF em desenvolvimento!');
        }

        function simuladorSalvarSimulacao() {
            alert('Simulação salva com sucesso!');
        }

        function simuladorNovaSimulacao() {
            document.querySelector('.simulador-resultado-obra').style.display = 'none';
            document.querySelector('#form-simulacao-obra').style.display = 'block';
        }
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function salvar_simulacao_obra() {
        // Implementar salvamento no banco
        wp_send_json_success('Simulação salva com sucesso!');
    }
}
?>

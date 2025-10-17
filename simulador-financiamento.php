<?php
/**
 * Plugin Name: Simulador Financiamento Minimal
 * Description: Versão simplificada do simulador de financiamento
 * Version: 1.0.0
 * Author: Flavio RFDC
 */

// Prevenção de acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Versão minimal - apenas o essencial para ativar
function simulador_minimal_init() {
    // Criar tabelas se não existirem
    simulador_criar_tabelas();
    
    // Adicionar shortcodes
    add_shortcode('simulador_obra', 'simulador_obra_shortcode');
    
    // Adicionar AJAX
    add_action('wp_ajax_calcular_obra', 'simulador_calcular_obra_ajax');
    add_action('wp_ajax_nopriv_calcular_obra', 'simulador_calcular_obra_ajax');
}

function simulador_criar_tabelas() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'simulador_simulacoes';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        dados text NOT NULL,
        resultado text NOT NULL,
        data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function simulador_obra_shortcode($atts) {
    wp_enqueue_script('jquery');
    
    ob_start();
    ?>
    <div class="simulador-obra-minimal">
        <h3>Simulador de Financiamento com Obra</h3>
        
        <form id="form-obra-simples">
            <div class="campo">
                <label>Valor do Imóvel (R$):</label>
                <input type="number" name="valor_imovel" value="300000" required>
            </div>
            
            <div class="campo">
                <label>Entrada (R$):</label>
                <input type="number" name="valor_entrada" value="60000">
            </div>
            
            <div class="campo">
                <label>Taxa de Juros (% ao ano):</label>
                <input type="number" name="taxa_juros" value="8.5" step="0.1" required>
            </div>
            
            <div class="campo">
                <label>Prazo (anos):</label>
                <select name="prazo_anos" required>
                    <option value="20">20 anos</option>
                    <option value="25">25 anos</option>
                    <option value="30">30 anos</option>
                </select>
            </div>
            
            <div class="campo">
                <label>Período de Obra (meses):</label>
                <input type="number" name="periodo_obra" value="12" required>
            </div>
            
            <div class="campo">
                <label>Periodicidade:</label>
                <select name="periodicidade" required>
                    <option value="mensal">Mensal</option>
                    <option value="trimestral">Trimestral</option>
                    <option value="semestral">Semestral</option>
                </select>
            </div>
            
            <button type="button" id="btn-calcular-simples">Calcular</button>
        </form>
        
        <div id="resultado-simples" style="display:none; margin-top:20px; padding:15px; background:#f9f9f9; border-radius:5px;"></div>
    </div>
    
    <style>
    .simulador-obra-minimal {
        max-width: 600px;
        margin: 20px auto;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .campo {
        margin-bottom: 15px;
    }
    .campo label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .campo input, .campo select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    #btn-calcular-simples {
        background: #007cba;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    #btn-calcular-simples:hover {
        background: #005a87;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('#btn-calcular-simples').on('click', function() {
            var dados = $('#form-obra-simples').serialize();
            
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'calcular_obra',
                dados: dados
            }, function(resposta) {
                if (resposta.success) {
                    $('#resultado-simples').html(resposta.data).show();
                } else {
                    alert('Erro: ' + resposta.data);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

function simulador_calcular_obra_ajax() {
    // Dados básicos do formulário
    parse_str($_POST['dados'], $dados);
    
    $valor_imovel = floatval($dados['valor_imovel']);
    $entrada = floatval($dados['valor_entrada']);
    $taxa_juros = floatval($dados['taxa_juros']) / 100;
    $prazo_anos = intval($dados['prazo_anos']);
    $periodo_obra = intval($dados['periodo_obra']);
    $periodicidade = sanitize_text_field($dados['periodicidade']);
    
    // Cálculo simples
    $valor_financiado = $valor_imovel - $entrada;
    
    // Período de obra - apenas juros
    $taxa_mensal_obra = 0.02; // 2% ao mês
    $num_parcelas_obra = $periodo_obra;
    
    if ($periodicidade == 'trimestral') {
        $num_parcelas_obra = ceil($periodo_obra / 3);
        $taxa_mensal_obra = pow(1 + 0.02, 3) - 1;
    } elseif ($periodicidade == 'semestral') {
        $num_parcelas_obra = ceil($periodo_obra / 6);
        $taxa_mensal_obra = pow(1 + 0.02, 6) - 1;
    }
    
    $total_obra = $valor_financiado * $taxa_mensal_obra * $num_parcelas_obra;
    
    // Período de amortização
    $taxa_mensal = pow(1 + $taxa_juros, 1/12) - 1;
    $num_parcelas_amort = ($prazo_anos * 12) - $periodo_obra;
    
    $parcela_amort = $valor_financiado * $taxa_mensal * pow(1 + $taxa_mensal, $num_parcelas_amort) / 
                    (pow(1 + $taxa_mensal, $num_parcelas_amort) - 1);
    
    $total_amortizacao = $parcela_amort * $num_parcelas_amort;
    $total_geral = $total_obra + $total_amortizacao;
    
    // Gerar resultado
    $html = '
    <h4>Resultado da Simulação</h4>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0;">
        <div style="background: #e8f5e8; padding: 10px; border-radius: 5px;">
            <strong>Período de Obra</strong><br>
            ' . $num_parcelas_obra . ' parcelas ' . $periodicidade . '<br>
            Total: R$ ' . number_format($total_obra, 2, ',', '.') . '
        </div>
        <div style="background: #e3f2fd; padding: 10px; border-radius: 5px;">
            <strong>Período Amortização</strong><br>
            ' . $num_parcelas_amort . ' parcelas mensais<br>
            Parcela: R$ ' . number_format($parcela_amort, 2, ',', '.') . '
        </div>
    </div>
    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
        <strong>Total do Financiamento:</strong> R$ ' . number_format($total_geral, 2, ',', '.') . '<br>
        <small>Valor financiado: R$ ' . number_format($valor_financiado, 2, ',', '.') . '</small>
    </div>
    ';
    
    wp_send_json_success($html);
}

// Inicializar quando todos os plugins estiverem carregados
add_action('plugins_loaded', 'simulador_minimal_init');

// Ativação - criar tabelas
register_activation_hook(__FILE__, 'simulador_criar_tabelas');

// Adicionar menu admin simples
add_action('admin_menu', 'simulador_admin_menu');

function simulador_admin_menu() {
    add_menu_page(
        'Simulador Financiamento',
        'Simulador',
        'manage_options',
        'simulador-admin',
        'simulador_admin_page',
        'dashicons-calculator',
        30
    );
}

function simulador_admin_page() {
    echo '
    <div class="wrap">
        <h1>Simulador de Financiamento</h1>
        <p>Plugin funcionando corretamente!</p>
        <div style="background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;">
            <strong>✅ Plugin ativado com sucesso!</strong><br>
            Use o shortcode <code>[simulador_obra]</code> em qualquer página ou post para exibir o simulador.
        </div>
    </div>
    ';
}
?>

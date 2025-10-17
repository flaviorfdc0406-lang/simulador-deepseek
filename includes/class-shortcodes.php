<?php
if (!defined('ABSPATH')) {
    exit;
}

class Shortcodes_Handler {
    public function __construct() {
        add_shortcode('simulador_obra', array($this, 'simulador_obra_shortcode'));
        add_shortcode('simulador_financiamento', array($this, 'simulador_financiamento_shortcode'));
        add_shortcode('resultado_simulacao', array($this, 'resultado_simulacao_shortcode'));
        
        // Enfileirar scripts quando shortcode é usado
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function enqueue_scripts() {
        global $post;
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'simulador_obra') || has_shortcode($post->post_content, 'simulador_financiamento'))) {
            wp_enqueue_script('jquery');
            
            // CSS
            wp_enqueue_style('simulador-frontend-css', SIMULADOR_PLUGIN_URL . 'public/css/simulador.css', array(), SIMULADOR_VERSION);
            
            // JS
            wp_enqueue_script('simulador-frontend-js', SIMULADOR_PLUGIN_URL . 'public/js/simulador-frontend.js', array('jquery'), SIMULADOR_VERSION, true);
            
            // Localize script para AJAX
            wp_localize_script('simulador-frontend-js', 'simulador_ajax', array(
                'url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('simulador_nonce')
            ));
        }
    }
    
    public function simulador_obra_shortcode($atts) {
        $atts = shortcode_atts(array(
            'titulo' => 'Simulador de Financiamento com Período de Obras',
            'mostrar_titulo' => 'true',
            'valor_padrao' => '300000',
            'entrada_padrao' => '60000'
        ), $atts);
        
        // Carregar template
        $template_path = SIMULADOR_PLUGIN_PATH . 'public/templates/formulario-obra.php';
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        } else {
            // Fallback se o template não existir
            return $this->formulario_obra_fallback($atts);
        }
    }
    
    private function formulario_obra_fallback($atts) {
        ob_start();
        ?>
        <div class="simulador-obra-container">
            <?php if ($atts['mostrar_titulo'] === 'true'): ?>
            <h3><?php echo esc_html($atts['titulo']); ?></h3>
            <?php endif; ?>
            
            <form id="form-simulacao-obra" class="simulador-form">
                <?php wp_nonce_field('simulador_nonce', 'security'); ?>
                
                <div class="form-section">
                    <h4>Dados do Financiamento</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="valor_imovel">Valor do Imóvel (R$)*</label>
                            <input type="number" id="valor_imovel" name="valor_imovel" 
                                   value="<?php echo esc_attr($atts['valor_padrao']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="valor_entrada">Valor de Entrada (R$)</label>
                            <input type="number" id="valor_entrada" name="valor_entrada"
                                   value="<?php echo esc_attr($atts['entrada_padrao']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="taxa_juros">Taxa de Juros Anual (%)*</label>
                            <input type="number" id="taxa_juros" name="taxa_juros" 
                                   value="8.5" step="0.1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="prazo_anos">Prazo (anos)*</label>
                            <select id="prazo_anos" name="prazo_anos" required>
                                <option value="15">15 anos</option>
                                <option value="20" selected>20 anos</option>
                                <option value="25">25 anos</option>
                                <option value="30">30 anos</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h4>Período de Obras</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="periodo_obra_meses">Duração da Obra (meses)*</label>
                            <input type="number" id="periodo_obra_meses" name="periodo_obra_meses" 
                                   value="12" min="1" max="60" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="periodicidade_obra">Periodicidade*</label>
                            <select id="periodicidade_obra" name="periodicidade_obra" required>
                                <option value="mensal">Mensal</option>
                                <option value="bimestral">Bimestral</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="semestral">Semestral</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="data_vencimento">Primeiro Vencimento*</label>
                            <input type="text" id="data_vencimento" name="data_vencimento" 
                                   placeholder="dd/mm/aaaa" required class="date-mask">
                        </div>
                    </div>
                    
                    <div class="info-box">
                        <p>💡 <strong>Como funciona:</strong> Durante a obra você paga apenas os juros. A amortização começa após o término da construção.</p>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="btn-calcular-obra" class="btn-primary">
                        🧮 Calcular Simulação
                    </button>
                </div>
            </form>
            
            <div id="resultado-obra-container"></div>
        </div>

        <style>
        .simulador-obra-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .form-section h4 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .form-group input,
        .form-group select {
            padding: 10px;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 14px;
        }
        .info-box {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border-left: 4px solid #3498db;
        }
        .form-actions {
            text-align: center;
            margin-top: 20px;
        }
        .btn-primary {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Máscara para data
            $('#data_vencimento').mask('00/00/0000');
            
            $('#btn-calcular-obra').on('click', function() {
                var $btn = $(this);
                var $form = $('#form-simulacao-obra');
                
                if (!$form[0].checkValidity()) {
                    $form[0].reportValidity();
                    return;
                }
                
                $btn.prop('disabled', true).text('Calculando...');
                
                $.ajax({
                    url: simulador_ajax.url,
                    type: 'POST',
                    data: {
                        action: 'calcular_simulacao_obra',
                        security: simulador_ajax.nonce,
                        valor_imovel: $('#valor_imovel').val(),
                        valor_entrada: $('#valor_entrada').val(),
                        taxa_juros: $('#taxa_juros').val(),
                        prazo_anos: $('#prazo_anos').val(),
                        periodo_obra_meses: $('#periodo_obra_meses').val(),
                        periodicidade_obra: $('#periodicidade_obra').val(),
                        data_vencimento: $('#data_vencimento').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#resultado-obra-container').html(response.data.html);
                            $('html, body').animate({
                                scrollTop: $('#resultado-obra-container').offset().top - 100
                            }, 500);
                        } else {
                            alert('Erro: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Erro ao calcular. Tente novamente.');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('🧮 Calcular Simulação');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function simulador_financiamento_shortcode($atts) {
        return $this->simulador_obra_shortcode($atts);
    }
    
    public function resultado_simulacao_shortcode($atts) {
        return '<div id="resultado-simulacao-global"></div>';
    }
}
?>

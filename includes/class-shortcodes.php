<?php
class SimuladorShortcodes {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_shortcode('simulador_financiamento', array($this, 'render_simulador'));
        add_shortcode('simulador_resultados', array($this, 'render_resultados'));
        add_shortcode('simulador_estatisticas', array($this, 'render_estatisticas'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }
    
    public function enqueue_frontend_scripts() {
        // CSS
        wp_register_style('simulador-css', SIMULADOR_PLUGIN_URL . 'public/css/simulador.css', array(), '1.0.0');
        wp_register_style('simulador-etapas-css', SIMULADOR_PLUGIN_URL . 'public/css/etapas-3-4.css', array('simulador-css'), '1.0.0');
        
        // JavaScript
        wp_register_script('simulador-calculator-js', SIMULADOR_PLUGIN_URL . 'public/js/simulador-calculator.js', array(), '1.0.0', true);
        wp_register_script('simulador-frontend-js', SIMULADOR_PLUGIN_URL . 'public/js/simulador-frontend.js', array('jquery', 'simulador-calculator-js'), '1.0.0', true);
        wp_register_script('simulador-etapas-js', SIMULADOR_PLUGIN_URL . 'public/js/etapas-3-4.js', array('simulador-frontend-js'), '1.0.0', true);
        wp_register_script('simulador-relatorios-js', SIMULADOR_PLUGIN_URL . 'public/js/relatorios-final.js', array('simulador-etapas-js'), '1.0.0', true);
        
        // Localize script
        wp_localize_script('simulador-frontend-js', 'simulador_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simulador_nonce')
        ));
    }
    
    public function render_simulador($atts = array()) {
        // Enfileirar scripts e styles
        wp_enqueue_style('simulador-css');
        wp_enqueue_style('simulador-etapas-css');
        wp_enqueue_script('simulador-calculator-js');
        wp_enqueue_script('simulador-frontend-js');
        wp_enqueue_script('simulador-etapas-js');
        wp_enqueue_script('simulador-relatorios-js');
        
        // Carregar modalidades
        $modalidades = $this->get_modalidades();
        
        ob_start();
        include SIMULADOR_PLUGIN_PATH . 'public/templates/formulario-simulacao.php';
        return ob_get_clean();
    }
    
    public function render_resultados($atts = array()) {
        $atts = shortcode_atts(array(
            'tipo' => 'compacto',
            'mostrar_graficos' => 'true'
        ), $atts);
        
        return '<div id="simulador-resultados" data-tipo="' . esc_attr($atts['tipo']) . '" data-graficos="' . esc_attr($atts['mostrar_graficos']) . '"></div>';
    }
    
    public function render_estatisticas($atts = array()) {
        $atts = shortcode_atts(array(
            'limite' => '10',
            'tipo' => 'geral'
        ), $atts);
        
        $estatisticas = $this->get_estatisticas_publicas($atts['limite'], $atts['tipo']);
        
        ob_start();
        ?>
        <div class="simulador-estatisticas">
            <h3>📊 Estatísticas do Simulador</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($estatisticas['total_simulacoes']); ?></div>
                    <div class="stat-label">Simulações Realizadas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($estatisticas['corretores_ativos']); ?></div>
                    <div class="stat-label">Corretores Ativos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">R$ <?php echo number_format($estatisticas['valor_medio_imovel'], 0, ',', '.'); ?></div>
                    <div class="stat-label">Valor Médio do Imóvel</div>
                </div>
            </div>
        </div>
        <style>
        .simulador-estatisticas { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stat-item { text-align: center; padding: 15px; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007cba; }
        .stat-label { color: #666; margin-top: 5px; }
        </style>
        <?php
        return ob_get_clean();
    }
    
    private function get_modalidades() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}simulador_modalidades WHERE ativo = 1 ORDER BY nome");
    }
    
    private function get_estatisticas_publicas($limite, $tipo) {
        global $wpdb;
        
        return array(
            'total_simulacoes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}simulador_simulacoes"),
            'corretores_ativos' => $wpdb->get_var("SELECT COUNT(DISTINCT email_corretor) FROM {$wpdb->prefix}simulador_relatorios"),
            'valor_medio_imovel' => $wpdb->get_var("SELECT AVG(valor_imovel) FROM {$wpdb->prefix}simulador_simulacoes WHERE valor_imovel > 0") ?: 0
        );
    }
}
?>

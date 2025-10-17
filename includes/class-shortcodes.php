<?php
// includes/class-shortcodes.php
if (!defined('ABSPATH')) {
    exit;
}

class Shortcodes_Handler {
    public function __construct() {
        add_shortcode('simulador_financiamento', array($this, 'simulador_financiamento'));
        add_shortcode('simulador_obra', array($this, 'simulador_obra'));
        add_shortcode('resultado_simulacao', array($this, 'resultado_simulacao'));
    }
    
    public function simulador_financiamento($atts) {
        $atts = shortcode_atts(array(
            'tipo' => 'normal',
            'mostrar_titulo' => 'true'
        ), $atts);
        
        ob_start();
        
        if ($atts['mostrar_titulo'] === 'true') {
            echo '<h3>Simulador de Financiamento</h3>';
        }
        
        echo '<p>Simulador básico - em desenvolvimento</p>';
        
        return ob_get_clean();
    }
    
    public function simulador_obra($atts) {
        $atts = shortcode_atts(array(
            'mostrar_titulo' => 'true'
        ), $atts);
        
        // Carregar template do formulário de obras
        $template_path = SIMULADOR_PLUGIN_PATH . 'public/templates/formulario-obra.php';
        
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        } else {
            return '<p>Formulário de obras não encontrado.</p>';
        }
    }
    
    public function resultado_simulacao($atts) {
        return '<div id="resultado-simulacao-container"></div>';
    }
}
?>

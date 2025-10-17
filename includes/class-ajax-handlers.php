<?php
// includes/class-ajax-handlers.php
if (!defined('ABSPATH')) {
    exit;
}

class Ajax_Handlers {
    public function __construct() {
        // Handlers para simulação normal
        add_action('wp_ajax_calcular_simulacao', array($this, 'calcular_simulacao'));
        add_action('wp_ajax_nopriv_calcular_simulacao', array($this, 'calcular_simulacao'));
        
        // Handlers para simulação com obra
        add_action('wp_ajax_calcular_simulacao_obra', array($this, 'calcular_simulacao_obra'));
        add_action('wp_ajax_nopriv_calcular_simulacao_obra', array($this, 'calcular_simulacao_obra'));
        
        // Handlers para salvar simulação
        add_action('wp_ajax_salvar_simulacao', array($this, 'salvar_simulacao'));
        add_action('wp_ajax_nopriv_salvar_simulacao', array($this, 'salvar_simulacao'));
    }
    
    public function calcular_simulacao() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['security'], 'simulador_nonce')) {
            wp_die('Erro de segurança');
        }
        
        // Aqui viria o cálculo da simulação normal
        wp_send_json_success(array(
            'message' => 'Simulação calculada com sucesso',
            'html' => '<p>Funcionalidade em desenvolvimento</p>'
        ));
    }
    
    public function calcular_simulacao_obra() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['security'], 'simulador_nonce')) {
            wp_die('Erro de segurança');
        }
        
        try {
            // Inicializar calculadora de obras
            if (!class_exists('Obra_Calculator')) {
                require_once SIMULADOR_PLUGIN_PATH . 'includes/class-obra-calculator.php';
            }
            
            $calculator = new Obra_Calculator();
            
            // Validar dados
            $errors = $calculator->validar_dados_obra($_POST);
            if (!empty($errors)) {
                wp_send_json_error(implode('<br>', $errors));
            }
            
            // Calcular simulação
            $resultado = $calculator->calcular_financiamento_obra($_POST);
            
            // Gerar HTML do resultado
            ob_start();
            include SIMULADOR_PLUGIN_PATH . 'public/templates/resultado-obra.php';
            $html = ob_get_clean();
            
            wp_send_json_success(array(
                'html' => $html,
                'dados' => $resultado
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Erro ao calcular simulação: ' . $e->getMessage());
        }
    }
    
    public function salvar_simulacao() {
        // Implementar salvamento
        wp_send_json_success('Simulação salva com sucesso');
    }
}
?>

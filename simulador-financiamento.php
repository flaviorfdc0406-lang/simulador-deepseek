<?php
/**
 * Plugin Name: Simulador de Financiamento Living & Vivaz
 * Description: Simulador completo de financiamento imobiliário para Living & Vivaz
 * Version: 1.0.0
 * Author: Ferreira Costa
 */

// Prevenir acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Constantes do plugin
define('SIMULADOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SIMULADOR_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Classe principal do plugin
class SimuladorFinanciamentoLiving {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init() {
        $this->load_dependencies();
        $this->register_shortcodes();
        $this->enqueue_scripts();
    }
    
    private function load_dependencies() {
        require_once SIMULADOR_PLUGIN_PATH . 'includes/class-database.php';
        require_once SIMULADOR_PLUGIN_PATH . 'includes/class-calculator.php';
        require_once SIMULADOR_PLUGIN_PATH . 'includes/class-admin-dashboard.php';
        require_once SIMULADOR_PLUGIN_PATH . 'includes/class-shortcodes.php';
    }
    
    private function register_shortcodes() {
        add_shortcode('simulador_financiamento', array($this, 'render_simulador'));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('simulador-js', SIMULADOR_PLUGIN_URL . 'assets/js/simulador.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('simulador-css', SIMULADOR_PLUGIN_URL . 'assets/css/simulador.css', array(), '1.0.0');
        
        // Localize script para AJAX
        wp_localize_script('simulador-js', 'simulador_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simulador_nonce')
        ));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Simulador Financiamento',
            'Simulador',
            'manage_options',
            'simulador-dashboard',
            array($this, 'render_admin_dashboard'),
            'dashicons-calculator',
            30
        );
    }
    
    public function render_simulador() {
        ob_start();
        include SIMULADOR_PLUGIN_PATH . 'templates/formulario-simulacao.php';
        return ob_get_clean();
    }
    
    public function render_admin_dashboard() {
        include SIMULADOR_PLUGIN_PATH . 'admin/dashboard.php';
    }
    
    public function activate() {
        // Criar tabelas do banco de dados
        $database = new SimuladorDatabase();
        $database->create_tables();
    }
}

// Inicializar o plugin
new SimuladorFinanciamentoLiving();

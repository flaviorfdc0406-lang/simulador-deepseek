<?php
/**
 * Plugin Name: Simulador de Financiamento com Período de Obras
 * Plugin URI: https://github.com/flaviorfdc0406-lang/simulador-deepseek
 * Description: Sistema completo de simulação de financiamento com período de obras
 * Version: 1.0.0
 * Author: Flavio RFDC
 * Text Domain: simulador-financiamento
 */

// Prevenção de acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Definição de constantes
define('SIMULADOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SIMULADOR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SIMULADOR_VERSION', '1.0.0');

// Verificar dependências do PHP
register_activation_hook(__FILE__, 'simulador_check_requirements');

function simulador_check_requirements() {
    $errors = array();
    
    // Verificar versão do PHP
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errors[] = 'Este plugin requer PHP 7.4 ou superior. Versão atual: ' . PHP_VERSION;
    }
    
    // Verificar extensões necessárias
    $required_extensions = ['json', 'dom', 'libxml'];
    foreach ($required_extensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Extensão PHP {$ext} não está habilitada";
        }
    }
    
    if (!empty($errors)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(implode('<br>', $errors));
    }
}

// Carregar classes principais
function simulador_autoloader($class_name) {
    $class_map = array(
        'Database_Handler' => 'includes/class-database.php',
        'Financiamento_Calculator' => 'includes/class-calculator.php',
        'Obra_Calculator' => 'includes/class-obra-calculator.php',
        'Admin_Dashboard' => 'includes/class-admin-dashboard.php',
        'PDF_Generator' => 'includes/class-pdf-generator.php',
        'Email_Sender' => 'includes/class-email-sender.php',
        'Ajax_Handlers' => 'includes/class-ajax-handlers.php',
        'Shortcodes_Handler' => 'includes/class-shortcodes.php'
    );
    
    if (isset($class_map[$class_name])) {
        $file_path = SIMULADOR_PLUGIN_PATH . $class_map[$class_name];
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}

spl_autoload_register('simulador_autoloader');

// Inicialização do plugin
function simulador_init() {
    try {
        // Inicializar handlers
        new Ajax_Handlers();
        new Shortcodes_Handler();
        
        // Inicializar admin apenas se estiver na área administrativa
        if (is_admin()) {
            new Admin_Dashboard();
        }
        
    } catch (Exception $e) {
        error_log('Erro na inicialização do Simulador: ' . $e->getMessage());
    }
}

add_action('plugins_loaded', 'simulador_init');

// Registrar hooks de ativação/desativação
register_activation_hook(__FILE__, 'simulador_activate');
register_deactivation_hook(__FILE__, 'simulador_deactivate');

function simulador_activate() {
    require_once SIMULADOR_PLUGIN_PATH . 'install-simulador.php';
    simulador_install();
}

function simulador_deactivate() {
    require_once SIMULADOR_PLUGIN_PATH . 'uninstall-simulador.php';
    simulador_uninstall();
}

// Carregar text domain para internacionalização
function simulador_load_textdomain() {
    load_plugin_textdomain('simulador-financiamento', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'simulador_load_textdomain');

// Enfileirar scripts e styles
function simulador_enqueue_scripts() {
    // CSS
    wp_enqueue_style('simulador-public-css', SIMULADOR_PLUGIN_URL . 'public/css/simulador.css', array(), SIMULADOR_VERSION);
    wp_enqueue_style('simulador-etapas-css', SIMULADOR_PLUGIN_URL . 'public/css/etapas-3-4.css', array(), SIMULADOR_VERSION);
    
    // JS
    wp_enqueue_script('jquery');
    wp_enqueue_script('simulador-frontend-js', SIMULADOR_PLUGIN_URL . 'public/js/simulador-frontend.js', array('jquery'), SIMULADOR_VERSION, true);
    wp_enqueue_script('simulador-calculator-js', SIMULADOR_PLUGIN_URL . 'public/js/simulador-calculator.js', array('jquery'), SIMULADOR_VERSION, true);
    wp_enqueue_script('simulador-etapas-js', SIMULADOR_PLUGIN_URL . 'public/js/etapas-3-4.js', array('jquery'), SIMULADOR_VERSION, true);
    wp_enqueue_script('chart-loader-js', SIMULADOR_PLUGIN_URL . 'assets/js/chart-loader.js', array(), SIMULADOR_VERSION, true);
    
    // Localize script para AJAX
    wp_localize_script('simulador-frontend-js', 'simulador_ajax', array(
        'url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('simulador_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'simulador_enqueue_scripts');

// Enfileirar scripts do admin
function simulador_admin_scripts($hook) {
    if (strpos($hook, 'simulador-financiamento') === false) {
        return;
    }
    
    wp_enqueue_style('simulador-admin-css', SIMULADOR_PLUGIN_URL . 'admin/assets/css/admin.css', array(), SIMULADOR_VERSION);
    wp_enqueue_script('simulador-admin-js', SIMULADOR_PLUGIN_URL . 'admin/assets/js/admin.js', array('jquery'), SIMULADOR_VERSION, true);
}
add_action('admin_enqueue_scripts', 'simulador_admin_scripts');
?>

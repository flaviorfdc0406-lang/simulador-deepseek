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

// Carregar classes principais - VERIFICANDO SE EXISTEM
function simulador_carregar_classes() {
    $classes = array(
        'Database_Handler' => 'includes/class-database.php',
        'Financiamento_Calculator' => 'includes/class-calculator.php',
        'Obra_Calculator' => 'includes/class-obra-calculator.php',
        'Admin_Dashboard' => 'includes/class-admin-dashboard.php',
        'PDF_Generator' => 'includes/class-pdf-generator.php',
        'Email_Sender' => 'includes/class-email-sender.php',
        'Ajax_Handlers' => 'includes/class-ajax-handlers.php',
        'Shortcodes_Handler' => 'includes/class-shortcodes.php'
    );
    
    foreach ($classes as $class_name => $file_path) {
        $full_path = SIMULADOR_PLUGIN_PATH . $file_path;
        if (file_exists($full_path)) {
            require_once $full_path;
        } else {
            error_log("Arquivo não encontrado: {$full_path}");
        }
    }
}

// Inicialização do plugin - VERSÃO SEGURA
function simulador_init() {
    try {
        // Primeiro carrega as classes
        simulador_carregar_classes();
        
        // Inicializar apenas handlers que existem
        if (class_exists('Ajax_Handlers')) {
            new Ajax_Handlers();
        } else {
            // Fallback básico para AJAX
            add_action('wp_ajax_calcular_simulacao_obra', 'simulador_ajax_fallback');
            add_action('wp_ajax_nopriv_calcular_simulacao_obra', 'simulador_ajax_fallback');
        }
        
        if (class_exists('Shortcodes_Handler')) {
            new Shortcodes_Handler();
        } else {
            // Fallback para shortcodes
            add_shortcode('simulador_obra', 'simulador_shortcode_fallback');
        }
        
        // Inicializar admin apenas se estiver na área administrativa E a classe existir
        if (is_admin() && class_exists('Admin_Dashboard')) {
            new Admin_Dashboard();
        } else if (is_admin()) {
            // Fallback para admin menu
            add_action('admin_menu', 'simulador_admin_menu_fallback');
        }
        
    } catch (Exception $e) {
        error_log('Erro na inicialização do Simulador: ' . $e->getMessage());
    }
}

// Fallback functions para quando as classes não existem
function simulador_ajax_fallback() {
    wp_send_json_success(array(
        'message' => 'Plugin funcionando! Versão básica.',
        'html' => '<div style="padding:20px; background:#d4edda; border-radius:5px;">
                   <h3>✅ Plugin Simulador Ativo</h3>
                   <p>Funcionalidade completa em desenvolvimento.</p>
                   <p>Use o shortcode <code>[simulador_obra]</code> em suas páginas.</p>
                   </div>'
    ));
}

function simulador_shortcode_fallback($atts) {
    return '
    <div style="padding:20px; background:#e3f2fd; border-radius:8px; text-align:center;">
        <h3>🏗️ Simulador de Financiamento com Obra</h3>
        <p>Plugin instalado com sucesso!</p>
        <p>Em breve todas as funcionalidades estarão disponíveis.</p>
        <small>Use o menu "Simulador" no admin para configurações.</small>
    </div>
    ';
}

function simulador_admin_menu_fallback() {
    add_menu_page(
        'Simulador Financiamento',
        'Simulador',
        'manage_options',
        'simulador-admin',
        'simulador_admin_page_fallback',
        'dashicons-calculator',
        30
    );
}

function simulador_admin_page_fallback() {
    echo '
    <div class="wrap">
        <h1>Simulador de Financiamento</h1>
        
        <div style="background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h3>✅ Plugin Instalado com Sucesso!</h3>
            <p>O plugin foi instalado e está funcionando. As seguintes funcionalidades estão disponíveis:</p>
            <ul>
                <li><strong>Shortcode:</strong> <code>[simulador_obra]</code></li>
                <li><strong>Menu Admin:</strong> Configurações básicas</li>
                <li><strong>Estrutura:</strong> Pronta para expansão</li>
            </ul>
        </div>
        
        <div style="background: #fff3cd; padding: 20px; border-radius: 8px;">
            <h3>🚀 Próximos Passos</h3>
            <p>Para habilitar todas as funcionalidades, verifique se todos os arquivos estão presentes:</p>
            <ul>
                <li><code>includes/class-ajax-handlers.php</code></li>
                <li><code>includes/class-shortcodes.php</code></li>
                <li><code>includes/class-obra-calculator.php</code></li>
                <li><code>public/templates/formulario-obra.php</code></li>
            </ul>
        </div>
    </div>
    ';
}

// Registrar hooks de ativação/desativação
register_activation_hook(__FILE__, 'simulador_activate');
register_deactivation_hook(__FILE__, 'simulador_deactivate');

function simulador_activate() {
    // Criar tabelas básicas
    simulador_criar_tabelas_basicas();
    
    // Configurações padrão
    add_option('simulador_version', SIMULADOR_VERSION);
    add_option('simulador_db_version', '1.0');
    add_option('simulador_taxa_obra', '0.02');
}

function simulador_deactivate() {
    // Limpar agendamentos se houver
    wp_clear_scheduled_hook('simulador_daily_cleanup');
}

function simulador_criar_tabelas_basicas() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'simulador_simulacoes';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT NULL,
        tipo_simulacao varchar(50) DEFAULT 'obra',
        dados_simulacao longtext NOT NULL,
        resultado longtext NOT NULL,
        data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
        status varchar(20) DEFAULT 'ativo',
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY data_criacao (data_criacao)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Carregar text domain para internacionalização
function simulador_load_textdomain() {
    load_plugin_textdomain('simulador-financiamento', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'simulador_load_textdomain');

// Enfileirar scripts e styles - SOMENTE SE OS ARQUIVOS EXISTIREM
function simulador_enqueue_scripts() {
    // CSS - verificar se existe
    $css_path = SIMULADOR_PLUGIN_PATH . 'public/css/simulador.css';
    if (file_exists($css_path)) {
        wp_enqueue_style('simulador-public-css', SIMULADOR_PLUGIN_URL . 'public/css/simulador.css', array(), SIMULADOR_VERSION);
    }
    
    // JS - verificar se existe
    $js_path = SIMULADOR_PLUGIN_PATH . 'public/js/simulador-frontend.js';
    if (file_exists($js_path)) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('simulador-frontend-js', SIMULADOR_PLUGIN_URL . 'public/js/simulador-frontend.js', array('jquery'), SIMULADOR_VERSION, true);
        
        // Localize script para AJAX
        wp_localize_script('simulador-frontend-js', 'simulador_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simulador_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'simulador_enqueue_scripts');

// Enfileirar scripts do admin - SOMENTE SE OS ARQUIVOS EXISTIREM
function simulador_admin_scripts($hook) {
    if (strpos($hook, 'simulador-admin') === false) {
        return;
    }
    
    $admin_css_path = SIMULADOR_PLUGIN_PATH . 'admin/assets/css/admin.css';
    if (file_exists($admin_css_path)) {
        wp_enqueue_style('simulador-admin-css', SIMULADOR_PLUGIN_URL . 'admin/assets/css/admin.css', array(), SIMULADOR_VERSION);
    }
    
    $admin_js_path = SIMULADOR_PLUGIN_PATH . 'admin/assets/js/admin.js';
    if (file_exists($admin_js_path)) {
        wp_enqueue_script('simulador-admin-js', SIMULADOR_PLUGIN_URL . 'admin/assets/js/admin.js', array('jquery'), SIMULADOR_VERSION, true);
    }
}
add_action('admin_enqueue_scripts', 'simulador_admin_scripts');

// Inicializar quando todos os plugins estiverem carregados
add_action('plugins_loaded', 'simulador_init');

// Adicionar link de settings na lista de plugins
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'simulador_plugin_action_links');

function simulador_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=simulador-admin') . '">Configurações</a>';
    array_unshift($links, $settings_link);
    return $links;
}
?>

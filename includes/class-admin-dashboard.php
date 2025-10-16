<?php
class SimuladorAdminDashboard {
    
    private static $instance = null;
    private $page_slug = 'simulador-dashboard';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_simulador_admin_action', array($this, 'handle_ajax_request'));
    }
    
    public function add_admin_menu() {
        $icon_url = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M7 3h10c1.1 0 2 .9 2 2v14c0 1.1-.9 2-2 2H7c-1.1 0-2-.9-2-2V5c0-1.1.9-2 2-2zm0 2v14h10V5H7zm5 12c-2.8 0-5-2.2-5-5s2.2-5 5-5 5 2.2 5 5-2.2 5-5 5z"/></svg>');
        
        add_menu_page(
            'Simulador Financiamento',
            'Simulador',
            'manage_options',
            $this->page_slug,
            array($this, 'render_dashboard'),
            $icon_url,
            30
        );
        
        add_submenu_page(
            $this->page_slug,
            'Dashboard',
            'Dashboard',
            'manage_options',
            $this->page_slug,
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            $this->page_slug,
            'Modalidades',
            'Modalidades',
            'manage_options',
            'simulador-modalidades',
            array($this, 'render_modalidades')
        );
        
        add_submenu_page(
            $this->page_slug,
            'Relatórios',
            'Relatórios',
            'manage_options',
            'simulador-relatorios',
            array($this, 'render_relatorios')
        );
        
        add_submenu_page(
            $this->page_slug,
            'Personalização',
            'Personalização',
            'manage_options',
            'simulador-personalizacao',
            array($this, 'render_personalizacao')
        );
        
        add_submenu_page(
            $this->page_slug,
            'Configurações',
            'Configurações',
            'manage_options',
            'simulador-configuracoes',
            array($this, 'render_configuracoes')
        );
    }
    
    public function admin_init() {
        $this->register_settings();
        $this->handle_form_submissions();
    }
    
    private function register_settings() {
        register_setting('simulador_configuracoes', 'simulador_configuracoes');
        register_setting('simulador_personalizacao', 'simulador_personalizacao');
    }
    
    private function handle_form_submissions() {
        if (isset($_POST['simulador_save_configuracoes'])) {
            $this->save_configuracoes();
        }
        
        if (isset($_POST['simulador_save_personalizacao'])) {
            $this->save_personalizacao();
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'simulador') === false) return;
        
        wp_enqueue_style('simulador-admin-css', SIMULADOR_PLUGIN_URL . 'admin/assets/css/admin.css', array(), '1.0.0');
        wp_enqueue_script('simulador-admin-js', SIMULADOR_PLUGIN_URL . 'admin/assets/js/admin.js', array('jquery'), '1.0.0', true);
        
        // Chart.js para gráficos
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        wp_localize_script('simulador-admin-js', 'simulador_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simulador_admin_nonce'),
            'texts' => array(
                'confirm_delete' => 'Tem certeza que deseja excluir este item?',
                'loading' => 'Processando...',
                'success' => 'Operação realizada com sucesso!'
            )
        ));
        
        // DataTables para tabelas
        if ($hook === 'simulador_page_simulador-relatorios') {
            wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), '1.13.6', true);
            wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', array(), '1.13.6');
        }
    }
    
    public function render_dashboard() {
        $estatisticas = $this->get_estatisticas();
        $ultimas_simulacoes = $this->get_ultimas_simulacoes(10);
        $relatorios_recentes = $this->get_relatorios_recentes(5);
        
        include SIMULADOR_PLUGIN_PATH . 'admin/templates/dashboard.php';
    }
    
    public function render_modalidades() {
        $modalidades = $this->get_modalidades();
        include SIMULADOR_PLUGIN_PATH . 'admin/templates/modalidades.php';
    }
    
    public function render_relatorios() {
        $relatorios = $this->get_relatorios();
        $filtros = $this->get_filtros_relatorios();
        include SIMULADOR_PLUGIN_PATH . 'admin/templates/relatorios.php';
    }
    
    public function render_personalizacao() {
        $configuracoes = get_option('simulador_personalizacao', array());
        include SIMULADOR_PLUGIN_PATH . 'admin/templates/personalizacao.php';
    }
    
    public function render_configuracoes() {
        $configuracoes = get_option('simulador_configuracoes', array());
        include SIMULADOR_PLUGIN_PATH . 'admin/templates/configuracoes.php';
    }
    
    private function get_estatisticas() {
        global $wpdb;
        
        $hoje = date('Y-m-d');
        $este_mes = date('Y-m-01');
        $mes_passado = date('Y-m-01', strtotime('-1 month'));
        
        return array(
            'total_simulacoes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}simulador_simulacoes"),
            'total_relatorios' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}simulador_relatorios"),
            'simulacoes_hoje' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}simulador_simulacoes WHERE DATE(created_at) = '$hoje'"),
            'simulacoes_este_mes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}simulador_simulacoes WHERE created_at >= '$este_mes'"),
            'corretores_ativos' => $wpdb->get_var("SELECT COUNT(DISTINCT email_corretor) FROM {$wpdb->prefix}simulador_relatorios"),
            'taxa_conversao' => $this->calcular_taxa_conversao(),
            'valor_medio_imovel' => $wpdb->get_var("SELECT AVG(valor_imovel) FROM {$wpdb->prefix}simulador_simulacoes WHERE valor_imovel > 0"),
            'modalidade_mais_usada' => $this->get_modalidade_mais_usada()
        );
    }
    
    private function calcular_taxa_conversao() {
        global $wpdb;
        
        $total_simulacoes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}simulador_simulacoes");
        $total_relatorios = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}simulador_relatorios");
        
        if ($total_simulacoes > 0) {
            return round(($total_relatorios / $total_simulacoes) * 100, 1);
        }
        
        return 0;
    }
    
    private function get_modalidade_mais_usada() {
        global $wpdb;
        
        return $wpdb->get_var("
            SELECT m.nome 
            FROM {$wpdb->prefix}simulador_modalidades m 
            INNER JOIN {$wpdb->prefix}simulador_simulacoes s ON m.id = s.modalidade_id 
            GROUP BY m.id 
            ORDER BY COUNT(*) DESC 
            LIMIT 1
        ");
    }
    
    private function get_ultimas_simulacoes($limite = 10) {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT s.*, r.nome_cliente, r.nome_corretor 
            FROM {$wpdb->prefix}simulador_simulacoes s 
            LEFT JOIN {$wpdb->prefix}simulador_relatorios r ON s.id = r.id_simulacao 
            ORDER BY s.created_at DESC 
            LIMIT $limite
        ");
    }
    
    private function get_relatorios_recentes($limite = 5) {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT r.*, s.valor_imovel, s.parcela_calculada 
            FROM {$wpdb->prefix}simulador_relatorios r 
            LEFT JOIN {$wpdb->prefix}simulador_simulacoes s ON r.id_simulacao = s.id 
            ORDER BY r.created_at DESC 
            LIMIT $limite
        ");
    }
    
    private function get_modalidades() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}simulador_modalidades 
            ORDER BY ativo DESC, created_at DESC
        ");
    }
    
    private function get_relatorios() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT r.*, s.valor_imovel, s.parcela_calculada, s.prazo_meses 
            FROM {$wpdb->prefix}simulador_relatorios r 
            LEFT JOIN {$wpdb->prefix}simulador_simulacoes s ON r.id_simulacao = s.id 
            ORDER BY r.created_at DESC
        ");
    }
    
    private function get_filtros_relatorios() {
        global $wpdb;
        
        return array(
            'empreendimentos' => $wpdb->get_col("SELECT DISTINCT nome_empreendimento FROM {$wpdb->prefix}simulador_relatorios WHERE nome_empreendimento != ''"),
            'corretores' => $wpdb->get_col("SELECT DISTINCT nome_corretor FROM {$wpdb->prefix}simulador_relatorios WHERE nome_corretor != ''"),
            'empresas' => $wpdb->get_col("SELECT DISTINCT empresa FROM {$wpdb->prefix}simulador_relatorios WHERE empresa != ''")
        );
    }
    
    public function handle_ajax_request() {
        check_ajax_referer('simulador_admin_nonce', 'nonce');
        
        $acao = sanitize_text_field($_POST['acao']);
        $dados = $_POST['dados'] ?? array();
        
        switch ($acao) {
            case 'salvar_modalidade':
                $resultado = $this->salvar_modalidade($dados);
                break;
                
            case 'excluir_modalidade':
                $resultado = $this->excluir_modalidade($dados['id']);
                break;
                
            case 'toggle_modalidade':
                $resultado = $this->toggle_modalidade($dados['id'], $dados['ativo']);
                break;
                
            case 'get_estatisticas_avancadas':
                $resultado = $this->get_estatisticas_avancadas();
                break;
                
            case 'exportar_relatorios':
                $resultado = $this->exportar_relatorios($dados);
                break;
                
            default:
                wp_send_json_error('Ação não reconhecida');
        }
        
        if ($resultado['sucesso']) {
            wp_send_json_success($resultado['dados']);
        } else {
            wp_send_json_error($resultado['erro']);
        }
    }
    
    private function salvar_modalidade($dados) {
        global $wpdb;
        
        $dados_validados = array(
            'nome' => sanitize_text_field($dados['nome']),
            'taxa_juros' => floatval($dados['taxa_juros']),
            'limite_financiamento' => floatval($dados['limite_financiamento']),
            'limite_renda' => floatval($dados['limite_renda']),
            'valor_subsidio' => floatval($dados['valor_subsidio']),
            'faixa_renda' => sanitize_text_field($dados['faixa_renda']),
            'descricao' => sanitize_textarea_field($dados['descricao']),
            'ativo' => isset($dados['ativo']) ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        
        if (isset($dados['id']) && $dados['id']) {
            $resultado = $wpdb->update(
                $wpdb->prefix . 'simulador_modalidades',
                $dados_validados,
                array('id' => intval($dados['id']))
            );
        } else {
            $resultado = $wpdb->insert(
                $wpdb->prefix . 'simulador_modalidades',
                $dados_validados
            );
        }
        
        if ($resultado !== false) {
            return array('sucesso' => true, 'dados' => array('id' => $wpdb->insert_id));
        } else {
            return array('sucesso' => false, 'erro' => 'Erro ao salvar modalidade: ' . $wpdb->last_error);
        }
    }
    
    private function excluir_modalidade($id) {
        global $wpdb;
        
        $resultado = $wpdb->delete(
            $wpdb->prefix . 'simulador_modalidades',
            array('id' => intval($id))
        );
        
        if ($resultado !== false) {
            return array('sucesso' => true);
        } else {
            return array('sucesso' => false, 'erro' => 'Erro ao excluir modalidade');
        }
    }
    
    private function toggle_modalidade($id, $ativo) {
        global $wpdb;
        
        $resultado = $wpdb->update(
            $wpdb->prefix . 'simulador_modalidades',
            array('ativo' => $ativo ? 1 : 0),
            array('id' => intval($id))
        );
        
        if ($resultado !== false) {
            return array('sucesso' => true);
        } else {
            return array('sucesso' => false, 'erro' => 'Erro ao alterar status da modalidade');
        }
    }
    
    private function save_configuracoes() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'simulador_configuracoes-options')) {
            wp_die('Não autorizado');
        }
        
        $configuracoes = array(
            'titulo' => sanitize_text_field($_POST['titulo']),
            'descricao' => sanitize_text_field($_POST['descricao']),
            'cor_primaria' => sanitize_hex_color($_POST['cor_primaria']),
            'limite_maximo_imovel' => floatval($_POST['limite_maximo_imovel']),
            'limite_maximo_renda' => floatval($_POST['limite_maximo_renda']),
            'prazo_maximo' => intval($_POST['prazo_maximo']),
            'email_recebimento' => sanitize_email($_POST['email_recebimento']),
            'assunto_email' => sanitize_text_field($_POST['assunto_email'])
        );
        
        update_option('simulador_configuracoes', $configuracoes);
        
        wp_redirect(add_query_arg('settings-updated', 'true'));
        exit;
    }
    
    private function save_personalizacao() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'simulador_personalizacao-options')) {
            wp_die('Não autorizado');
        }
        
        $personalizacao = array(
            'logo_url' => esc_url_raw($_POST['logo_url']),
            'cor_secundaria' => sanitize_hex_color($_POST['cor_secundaria']),
            'fonte_principal' => sanitize_text_field($_POST['fonte_principal']),
            'texto_rodape' => sanitize_text_field($_POST['texto_rodape']),
            'cor_botoes' => sanitize_hex_color($_POST['cor_botoes'])
        );
        
        // Upload de logo
        if (!empty($_FILES['logo_upload']['name'])) {
            $upload = wp_handle_upload($_FILES['logo_upload'], array('test_form' => false));
            if (!isset($upload['error'])) {
                $personalizacao['logo_url'] = $upload['url'];
            }
        }
        
        update_option('simulador_personalizacao', $personalizacao);
        
        wp_redirect(add_query_arg('settings-updated', 'true'));
        exit;
    }
}
?>

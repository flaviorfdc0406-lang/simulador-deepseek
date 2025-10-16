<?php
class SimuladorDatabase {
    
    private static $instance = null;
    private $version = '1.0.0';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array(
            "simulador_modalidades" => "
                CREATE TABLE {$wpdb->prefix}simulador_modalidades (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    nome varchar(255) NOT NULL,
                    taxa_juros decimal(5,2) NOT NULL,
                    limite_financiamento decimal(12,2) NOT NULL,
                    limite_renda decimal(10,2) NOT NULL,
                    valor_subsidio decimal(10,2) DEFAULT 0,
                    faixa_renda varchar(100),
                    descricao text,
                    ativo tinyint(1) DEFAULT 1,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) $charset_collate;
            ",
            
            "simulador_simulacoes" => "
                CREATE TABLE {$wpdb->prefix}simulador_simulacoes (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    tipo_simulacao varchar(50) NOT NULL,
                    valor_imovel decimal(12,2),
                    valor_entrada decimal(12,2),
                    valor_fgts decimal(10,2),
                    prazo_meses int(6),
                    renda_mensal decimal(10,2),
                    valor_parcela decimal(10,2),
                    taxa_juros decimal(5,2),
                    tipo_amortizacao varchar(20),
                    parcela_calculada decimal(10,2),
                    modalidade_id mediumint(9),
                    ip_usuario varchar(45),
                    user_agent text,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_tipo_simulacao (tipo_simulacao),
                    KEY idx_created_at (created_at)
                ) $charset_collate;
            ",
            
            "simulador_relatorios" => "
                CREATE TABLE {$wpdb->prefix}simulador_relatorios (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    id_simulacao mediumint(9),
                    nome_empreendimento varchar(255),
                    unidade varchar(100),
                    nome_corretor varchar(255),
                    email_corretor varchar(255),
                    empresa varchar(255),
                    creci varchar(50),
                    nome_cliente varchar(255),
                    email_cliente varchar(255),
                    telefone_corretor varchar(20),
                    observacoes text,
                    relatorio_gerado tinyint(1) DEFAULT 0,
                    pdf_path varchar(500),
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    FOREIGN KEY (id_simulacao) REFERENCES {$wpdb->prefix}simulador_simulacoes(id) ON DELETE SET NULL,
                    KEY idx_corretor (nome_corretor),
                    KEY idx_empreendimento (nome_empreendimento),
                    KEY idx_created_at (created_at)
                ) $charset_collate;
            ",
            
            "simulador_emails" => "
                CREATE TABLE {$wpdb->prefix}simulador_emails (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    id_relatorio mediumint(9),
                    email_destino varchar(255) NOT NULL,
                    assunto varchar(255),
                    status varchar(20),
                    error_message text,
                    data_envio datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_status (status),
                    KEY idx_data_envio (data_envio)
                ) $charset_collate;
            "
        );
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table_name => $sql) {
            dbDelta($sql);
        }
        
        update_option('simulador_db_version', $this->version);
    }
    
    public function insert_default_data() {
        global $wpdb;
        
        $modalidades = array(
            array(
                'nome' => 'Minha Casa Minha Vida - Faixa 1',
                'taxa_juros' => 4.5,
                'limite_financiamento' => 350000,
                'limite_renda' => 2000,
                'valor_subsidio' => 45000,
                'faixa_renda' => 'Até R$ 2.000',
                'descricao' => 'Programa social para famílias de baixa renda'
            ),
            array(
                'nome' => 'SBPE - Poupança',
                'taxa_juros' => 8.5,
                'limite_financiamento' => 1500000,
                'limite_renda' => 15000,
                'valor_subsidio' => 0,
                'faixa_renda' => 'Todas as faixas',
                'descricao' => 'Sistema Brasileiro de Poupança e Empréstimo'
            ),
            array(
                'nome' => 'Financiamento com FGTS',
                'taxa_juros' => 7.5,
                'limite_financiamento' => 800000,
                'limite_renda' => 10000,
                'valor_subsidio' => 0,
                'faixa_renda' => 'Todas as faixas',
                'descricao' => 'Utilização do FGTS para entrada ou amortização'
            )
        );
        
        foreach ($modalidades as $modalidade) {
            $wpdb->insert(
                $wpdb->prefix . 'simulador_modalidades',
                $modalidade
            );
        }
        
        // Configurações padrão
        $configuracoes_padrao = array(
            'titulo' => 'Simulador de Financiamento Living & Vivaz',
            'descricao' => 'Calcule sua parcela com as condições reais da Caixa Econômica Federal',
            'cor_primaria' => '#007cba',
            'limite_maximo_imovel' => 5000000,
            'limite_maximo_renda' => 50000,
            'prazo_maximo' => 420,
            'email_recebimento' => 'relatorios@livingvivaz.com.br',
            'assunto_email' => 'Relatório de Simulação - Living & Vivaz'
        );
        
        update_option('simulador_configuracoes', $configuracoes_padrao);
    }
    
    public function check_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'simulador_modalidades',
            $wpdb->prefix . 'simulador_simulacoes',
            $wpdb->prefix . 'simulador_relatorios',
            $wpdb->prefix . 'simulador_emails'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                return false;
            }
        }
        
        return true;
    }
}
?>

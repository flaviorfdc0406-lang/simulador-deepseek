<?php
// install-simulador.php
if (!defined('ABSPATH')) {
    exit;
}

function simulador_install() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Tabela de simulações
    $table_simulacoes = $wpdb->prefix . 'simulador_financiamento';
    
    $sql_simulacoes = "CREATE TABLE $table_simulacoes (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT NULL,
        tipo_simulacao varchar(50) NOT NULL,
        dados_simulacao longtext NOT NULL,
        resultado longtext NOT NULL,
        data_criacao datetime DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status varchar(20) DEFAULT 'ativo',
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY tipo_simulacao (tipo_simulacao),
        KEY data_criacao (data_criacao)
    ) $charset_collate;";
    
    // Tabela de período de obras
    $table_obras = $wpdb->prefix . 'simulador_periodo_obras';
    
    $sql_obras = "CREATE TABLE $table_obras (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        simulacao_id mediumint(9) NOT NULL,
        periodo_obra_meses int NOT NULL,
        periodicidade varchar(20) NOT NULL,
        data_vencimento date NOT NULL,
        tipo_obra varchar(50) DEFAULT 'construcao',
        parcelas_obra longtext NOT NULL,
        PRIMARY KEY (id),
        KEY simulacao_id (simulacao_id),
        FOREIGN KEY (simulacao_id) REFERENCES $table_simulacoes(id) ON DELETE CASCADE
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    dbDelta($sql_simulacoes);
    dbDelta($sql_obras);
    
    // Verificar se as tabelas foram criadas
    $tables = array($table_simulacoes, $table_obras);
    $errors = array();
    
    foreach ($tables as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            $errors[] = "Falha ao criar tabela: $table";
        }
    }
    
    if (!empty($errors)) {
        error_log('Erro na instalação do Simulador: ' . implode(', ', $errors));
        return false;
    }
    
    // Adicionar opções padrão
    add_option('simulador_version', SIMULADOR_VERSION);
    add_option('simulador_db_version', '1.0');
    add_option('simulador_taxa_obra', '0.02');
    add_option('simulador_email_notificacao', get_option('admin_email'));
    
    return true;
}
?>

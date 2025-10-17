<?php
// includes/class-database.php
if (!defined('ABSPATH')) {
    exit;
}

class Database_Handler {
    private $wpdb;
    private $table_simulacoes;
    private $table_obras;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_simulacoes = $wpdb->prefix . 'simulador_financiamento';
        $this->table_obras = $wpdb->prefix . 'simulador_periodo_obras';
    }
    
    public function salvar_simulacao($dados) {
        $defaults = array(
            'user_id' => get_current_user_id(),
            'tipo_simulacao' => 'financiamento',
            'status' => 'ativo'
        );
        
        $dados = wp_parse_args($dados, $defaults);
        
        // Serializar dados arrays
        if (is_array($dados['dados_simulacao'])) {
            $dados['dados_simulacao'] = json_encode($dados['dados_simulacao']);
        }
        
        if (is_array($dados['resultado'])) {
            $dados['resultado'] = json_encode($dados['resultado']);
        }
        
        $result = $this->wpdb->insert(
            $this->table_simulacoes,
            $dados,
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    public function get_simulacao($id) {
        $simulacao = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM $this->table_simulacoes WHERE id = %d", $id)
        );
        
        if ($simulacao) {
            $simulacao->dados_simulacao = json_decode($simulacao->dados_simulacao, true);
            $simulacao->resultado = json_decode($simulacao->resultado, true);
        }
        
        return $simulacao;
    }
}
?>

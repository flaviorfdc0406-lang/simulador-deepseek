<?php
class SimuladorPDFHandlers {
    
    private $pdf_generator;
    private $email_sender;
    
    public function __construct() {
        $this->pdf_generator = new SimuladorPDFGenerator();
        $this->email_sender = new SimuladorEmailSender();
        
        add_action('wp_ajax_gerar_pdf_relatorio', array($this, 'gerar_pdf_relatorio'));
        add_action('wp_ajax_nopriv_gerar_pdf_relatorio', array($this, 'gerar_pdf_relatorio'));
        
        add_action('wp_ajax_salvar_relatorio_completo', array($this, 'salvar_relatorio_completo'));
        add_action('wp_ajax_nopriv_salvar_relatorio_completo', array($this, 'salvar_relatorio_completo'));
    }
    
    public function gerar_pdf_relatorio() {
        check_ajax_referer('simulador_nonce', 'nonce');
        
        $relatorio_id = intval($_POST['id']);
        $dados = $this->obter_dados_completos_relatorio($relatorio_id);
        
        if (!$dados) {
            wp_send_json_error('Relatório não encontrado');
        }
        
        try {
            $pdf_content = $this->pdf_generator->gerar_relatorio_pdf($dados);
            
            // Forçar download do PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="relatorio_' . sanitize_title($dados['nome_cliente']) . '.pdf"');
            header('Content-Length: ' . strlen($pdf_content));
            
            echo $pdf_content;
            exit;
            
        } catch (Exception $e) {
            wp_send_json_error('Erro ao gerar PDF: ' . $e->getMessage());
        }
    }
    
    public function salvar_relatorio_completo() {
        check_ajax_referer('simulador_nonce', 'nonce');
        
        $dados = json_decode(stripslashes($_POST['dados']), true);
        
        try {
            // Salvar no banco de dados
            $relatorio_id = $this->salvar_relatorio_bd($dados);
            
            // Adicionar ID ao dados para o PDF
            $dados['id_relatorio'] = $relatorio_id;
            
            // Enviar email
            $email_enviado = $this->email_sender->enviar_relatorio_completo($dados);
            
            wp_send_json_success(array(
                'relatorio_id' => $relatorio_id,
                'email_enviado' => $email_enviado,
                'mensagem' => 'Relatório gerado com sucesso!'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Erro ao salvar relatório: ' . $e->getMessage());
        }
    }
    
    private function obter_dados_completos_relatorio($relatorio_id) {
        global $wpdb;
        
        $relatorio = $wpdb->get_row($wpdb->prepare("
            SELECT r.*, s.*, m.nome as modalidade_nome
            FROM {$wpdb->prefix}simulador_relatorios r 
            LEFT JOIN {$wpdb->prefix}simulador_simulacoes s ON r.id_simulacao = s.id
            LEFT JOIN {$wpdb->prefix}simulador_modalidades m ON s.modalidade_id = m.id
            WHERE r.id = %d
        ", $relatorio_id));
        
        if (!$relatorio) {
            return false;
        }
        
        // Recriar cálculo das parcelas
        $calculator = SimuladorCalculator::get_instance();
        
        $valor_financiado = $relatorio->valor_imovel - ($relatorio->valor_entrada ?? 0);
        
        if ($relatorio->tipo_amortizacao === 'sac') {
            $parcelas = $calculator->calcular_sac($valor_financiado, $relatorio->taxa_juros, $relatorio->prazo_meses);
        } else {
            $parcelas = $calculator->calcular_price($valor_financiado, $relatorio->taxa_juros, $relatorio->prazo_meses);
        }
        
        $totais = $calculator->calcular_totais($parcelas);
        $compatibilidade = $calculator->analisar_compatibilidade_renda($relatorio->parcela_calculada, $relatorio->renda_mensal);
        
        return array(
            'id_relatorio' => $relatorio->id,
            'nome_cliente' => $relatorio->nome_cliente,
            'nome_corretor' => $relatorio->nome_corretor,
            'email_corretor' => $relatorio->email_corretor,
            'email_cliente' => $relatorio->email_cliente,
            'telefone_corretor' => $relatorio->telefone_corretor,
            'empresa' => $relatorio->empresa,
            'creci' => $relatorio->creci,
            'nome_empreendimento' => $relatorio->nome_empreendimento,
            'unidade' => $relatorio->unidade,
            'observacoes' => $relatorio->observacoes,
            'valor_imovel' => $relatorio->valor_imovel,
            'entrada' => $relatorio->valor_entrada,
            'valor_financiado' => $valor_financiado,
            'parcela_calculada' => $relatorio->parcela_calculada,
            'prazo_meses' => $relatorio->prazo_meses,
            'taxa_juros' => $relatorio->taxa_juros,
            'tipo_amortizacao' => $relatorio->tipo_amortizacao,
            'modalidade_nome' => $relatorio->modalidade_nome,
            'parcelas' => $parcelas,
            'totais' => $totais,
            'compatibilidade_renda' => $compatibilidade,
            'data_geracao' => $relatorio->created_at
        );
    }
    
    private function salvar_relatorio_bd($dados) {
        global $wpdb;
        
        // Salvar simulação
        $simulacao_data = array(
            'tipo_simulacao' => sanitize_text_field($dados['tipo_simulacao']),
            'valor_imovel' => floatval($dados['valor_imovel']),
            'valor_entrada' => floatval($dados['entrada'] ?? 0),
            'valor_fgts' => floatval($dados['valor_fgts'] ?? 0),
            'prazo_meses' => intval($dados['prazo_meses']),
            'renda_mensal' => isset($dados['renda_mensal']) ? floatval($dados['renda_mensal']) : null,
            'valor_parcela' => isset($dados['valor_parcela']) ? floatval($dados['valor_parcela']) : null,
            'taxa_juros' => floatval($dados['taxa_juros']),
            'tipo_amortizacao' => sanitize_text_field($dados['tipo_amortizacao']),
            'parcela_calculada' => floatval($dados['parcela_calculada']),
            'modalidade_id' => isset($dados['modalidade_id']) ? intval($dados['modalidade_id']) : null,
            'ip_usuario' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert($wpdb->prefix . 'simulador_simulacoes', $simulacao_data);
        $simulacao_id = $wpdb->insert_id;
        
        // Salvar relatório
        $relatorio_data = array(
            'id_simulacao' => $simulacao_id,
            'nome_empreendimento' => sanitize_text_field($dados['nome_empreendimento']),
            'unidade' => sanitize_text_field($dados['unidade']),
            'nome_corretor' => sanitize_text_field($dados['nome_corretor']),
            'email_corretor' => sanitize_email($dados['email_corretor']),
            'empresa' => sanitize_text_field($dados['empresa']),
            'creci' => sanitize_text_field($dados['creci']),
            'nome_cliente' => sanitize_text_field($dados['nome_cliente']),
            'email_cliente' => sanitize_email($dados['email_cliente'] ?? ''),
            'telefone_corretor' => sanitize_text_field($dados['telefone_corretor'] ?? ''),
            'observacoes' => sanitize_textarea_field($dados['observacoes'] ?? ''),
            'relatorio_gerado' => 1,
            'created_at' => current_time('mysql')
        );
        
        $wpdb->insert($wpdb->prefix . 'simulador_relatorios', $relatorio_data);
        
        return $wpdb->insert_id;
    }
    
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '';
        }
    }
}
?>

<?php
class SimuladorAjaxHandlers {
    
    public function __construct() {
        // Handlers de simulação
        add_action('wp_ajax_calcular_simulacao', array($this, 'calcular_simulacao'));
        add_action('wp_ajax_nopriv_calcular_simulacao', array($this, 'calcular_simulacao'));
        
        // Handlers de email
        add_action('wp_ajax_enviar_email_relatorio', array($this, 'enviar_email_relatorio'));
        add_action('wp_ajax_nopriv_enviar_email_relatorio', array($this, 'enviar_email_relatorio'));
        
        // Handlers de templates
        add_action('wp_ajax_carregar_formulario_informacoes', array($this, 'carregar_formulario_informacoes'));
        add_action('wp_ajax_nopriv_carregar_formulario_informacoes', array($this, 'carregar_formulario_informacoes'));
    }
    
    public function calcular_simulacao() {
        check_ajax_referer('simulador_nonce', 'nonce');
        
        $dados = $_POST['dados'];
        $calculator = SimuladorCalculator::get_instance();
        
        // Validar dados básicos
        $validacao = $this->validar_dados_simulacao($dados);
        if (!$validacao['valido']) {
            wp_send_json_error($validacao['erros']);
        }
        
        try {
            switch ($dados['tipo_simulacao']) {
                case 'valor_imovel':
                    $resultado = $this->calcular_por_valor_imovel($dados, $calculator);
                    break;
                    
                case 'renda_mensal':
                    $resultado = $this->calcular_por_renda($dados, $calculator);
                    break;
                    
                case 'valor_parcela':
                    $resultado = $this->calcular_por_parcela($dados, $calculator);
                    break;
                    
                default:
                    wp_send_json_error('Tipo de simulação inválido');
            }
            
            // Salvar simulação no banco (sem relatório ainda)
            $this->salvar_simulacao_bd($dados, $resultado);
            
            wp_send_json_success($resultado);
            
        } catch (Exception $e) {
            wp_send_json_error('Erro ao calcular simulação: ' . $e->getMessage());
        }
    }
    
    private function validar_dados_simulacao($dados) {
        $erros = [];
        
        if (empty($dados['tipo_simulacao'])) {
            $erros[] = 'Tipo de simulação não especificado';
        }
        
        if (empty($dados['prazo_meses']) || $dados['prazo_meses'] < 60) {
            $erros[] = 'Prazo mínimo é de 60 meses';
        }
        
        $taxa_juros = $this->obter_taxa_juros($dados);
        if ($taxa_juros <= 0) {
            $erros[] = 'Taxa de juros inválida';
        }
        
        // Validações específicas por tipo
        switch ($dados['tipo_simulacao']) {
            case 'valor_imovel':
                if (empty($dados['valor_imovel']) || $dados['valor_imovel'] <= 0) {
                    $erros[] = 'Valor do imóvel é obrigatório';
                }
                if (isset($dados['entrada']) && $dados['entrada'] >= $dados['valor_imovel']) {
                    $erros[] = 'Entrada não pode ser maior ou igual ao valor do imóvel';
                }
                break;
                
            case 'renda_mensal':
                if (empty($dados['renda_mensal']) || $dados['renda_mensal'] <= 0) {
                    $erros[] = 'Renda mensal é obrigatória';
                }
                break;
                
            case 'valor_parcela':
                if (empty($dados['valor_parcela']) || $dados['valor_parcela'] <= 0) {
                    $erros[] = 'Valor da parcela é obrigatório';
                }
                break;
        }
        
        return [
            'valido' => empty($erros),
            'erros' => $erros
        ];
    }
    
    private function calcular_por_valor_imovel($dados, $calculator) {
        $valor_financiado = $dados['valor_imovel'] - ($dados['entrada'] ?? 0);
        $taxa_juros = $this->obter_taxa_juros($dados);
        
        if ($dados['tipo_amortizacao'] === 'sac') {
            $parcelas = $calculator->calcular_sac($valor_financiado, $taxa_juros, $dados['prazo_meses']);
        } else {
            $parcelas = $calculator->calcular_price($valor_financiado, $taxa_juros, $dados['prazo_meses']);
        }
        
        $totais = $calculator->calcular_totais($parcelas);
        $compatibilidade = $calculator->analisar_compatibilidade_renda($parcelas[0]['parcela'], $dados['renda_mensal'] ?? null);
        
        return [
            'parcela_calculada' => $parcelas[0]['parcela'],
            'valor_financiado' => $valor_financiado,
            'parcelas' => $parcelas,
            'totais' => $totais,
            'compatibilidade_renda' => $compatibilidade,
            'taxa_juros_utilizada' => $taxa_juros
        ];
    }
    
    private function calcular_por_renda($dados, $calculator) {
        $taxa_juros = $this->obter_taxa_juros($dados);
        
        $valor_maximo_imovel = $calculator->calcular_valor_maximo_imovel(
            $dados['renda_mensal'],
            $taxa_juros,
            $dados['prazo_meses'],
            $dados['tipo_amortizacao']
        );
        
        $parcela_calculada = $calculator->calcular_parcela(
            $valor_maximo_imovel,
            0,
            $taxa_juros,
            $dados['prazo_meses'],
            $dados['tipo_amortizacao']
        );
        
        $compatibilidade = $calculator->analisar_compatibilidade_renda($parcela_calculada, $dados['renda_mensal']);
        
        return [
            'valor_maximo_imovel' => $valor_maximo_imovel,
            'parcela_calculada' => $parcela_calculada,
            'valor_financiado' => $valor_maximo_imovel,
            'compatibilidade_renda' => $compatibilidade,
            'taxa_juros_utilizada' => $taxa_juros
        ];
    }
    
    private function calcular_por_parcela($dados, $calculator) {
        $taxa_juros = $this->obter_taxa_juros($dados);
        
        $valor_financiado = $calculator->calcular_valor_financiamento_por_parcela(
            $dados['valor_parcela'],
            $taxa_juros,
            $dados['prazo_meses'],
            $dados['tipo_amortizacao']
        );
        
        $valor_imovel = $valor_financiado; // Considerando entrada zero
        
        $compatibilidade = $calculator->analisar_compatibilidade_renda($dados['valor_parcela'], $dados['renda_mensal'] ?? null);
        
        return [
            'valor_imovel_calculado' => $valor_imovel,
            'valor_financiado' => $valor_financiado,
            'compatibilidade_renda' => $compatibilidade,
            'taxa_juros_utilizada' => $taxa_juros
        ];
    }
    
    private function obter_taxa_juros($dados) {
        if ($dados['tipo_taxa'] === 'modalidade' && !empty($dados['modalidade_id'])) {
            global $wpdb;
            $modalidade = $wpdb->get_row($wpdb->prepare(
                "SELECT taxa_juros FROM {$wpdb->prefix}simulador_modalidades WHERE id = %d",
                $dados['modalidade_id']
            ));
            return $modalidade ? floatval($modalidade->taxa_juros) : 0;
        } else {
            return floatval($dados['taxa_juros_personalizada'] ?? 0);
        }
    }
    
    private function salvar_simulacao_bd($dados, $resultado) {
        global $wpdb;
        
        $simulacao_data = [
            'tipo_simulacao' => $dados['tipo_simulacao'],
            'valor_imovel' => $dados['valor_imovel'] ?? $resultado['valor_maximo_imovel'] ?? $resultado['valor_imovel_calculado'] ?? null,
            'valor_entrada' => $dados['entrada'] ?? 0,
            'prazo_meses' => $dados['prazo_meses'],
            'renda_mensal' => $dados['renda_mensal'] ?? null,
            'valor_parcela' => $dados['valor_parcela'] ?? null,
            'taxa_juros' => $resultado['taxa_juros_utilizada'],
            'tipo_amortizacao' => $dados['tipo_amortizacao'],
            'parcela_calculada' => $resultado['parcela_calculada'],
            'modalidade_id' => $dados['modalidade_id'] ?? null,
            'ip_usuario' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => current_time('mysql')
        ];
        
        $wpdb->insert($wpdb->prefix . 'simulador_simulacoes', $simulacao_data);
    }
    
    public function enviar_email_relatorio() {
        check_ajax_referer('simulador_nonce', 'nonce');
        
        $relatorio_id = intval($_POST['relatorio_id']);
        $email_sender = new SimuladorEmailSender();
        $pdf_handlers = new SimuladorPDFHandlers();
        
        $dados = $pdf_handlers->obter_dados_completos_relatorio($relatorio_id);
        
        if (!$dados) {
            wp_send_json_error('Relatório não encontrado');
        }
        
        $resultado = $email_sender->enviar_relatorio_completo($dados);
        
        if ($resultado) {
            wp_send_json_success('Email enviado com sucesso!');
        } else {
            wp_send_json_error('Erro ao enviar email');
        }
    }
    
    public function carregar_formulario_informacoes() {
        check_ajax_referer('simulador_nonce', 'nonce');
        
        $dados_simulacao = $_POST['dados'] ?? [];
        
        ob_start();
        include SIMULADOR_PLUGIN_PATH . 'public/templates/formulario-informacoes.php';
        $html = ob_get_clean();
        
        wp_send_json_success($html);
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

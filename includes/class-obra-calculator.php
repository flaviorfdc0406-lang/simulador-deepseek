<?php
if (!defined('ABSPATH')) {
    exit;
}

class Obra_Calculator {
    
    private $db;
    private $taxa_obra;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->taxa_obra = get_option('simulador_taxa_obra', 0.02); // 2% ao mês padrão
    }
    
    /**
     * Calcula o financiamento com período de obra
     */
    public function calcular_financiamento_obra($dados) {
        $valor_imovel = floatval($dados['valor_imovel']);
        $valor_entrada = floatval($dados['valor_entrada']);
        $taxa_juros_ano = floatval($dados['taxa_juros']) / 100;
        $prazo_anos = intval($dados['prazo_anos']);
        $periodo_obra_meses = intval($dados['periodo_obra_meses']);
        $periodicidade_obra = sanitize_text_field($dados['periodicidade_obra']);
        $data_vencimento = sanitize_text_field($dados['data_vencimento']);
        
        $valor_financiado = $valor_imovel - $valor_entrada;
        $taxa_juros_mes = pow(1 + $taxa_juros_ano, 1/12) - 1;
        
        // Cálculo do período de obra
        $parcelas_obra = $this->calcular_parcelas_obra(
            $valor_financiado,
            $periodo_obra_meses,
            $periodicidade_obra,
            $data_vencimento
        );
        
        // Cálculo do período de amortização
        $parcelas_amortizacao = $this->calcular_parcelas_amortizacao(
            $valor_financiado,
            $taxa_juros_mes,
            $prazo_anos,
            $periodo_obra_meses,
            $parcelas_obra
        );
        
        return [
            'parcelas_obra' => $parcelas_obra,
            'parcelas_amortizacao' => $parcelas_amortizacao,
            'resumo' => $this->gerar_resumo($valor_financiado, $parcelas_obra, $parcelas_amortizacao),
            'graficos' => $this->gerar_dados_graficos($parcelas_obra, $parcelas_amortizacao)
        ];
    }
    
    /**
     * Calcula as parcelas do período de obra
     */
    private function calcular_parcelas_obra($valor_financiado, $periodo_obra_meses, $periodicidade, $data_vencimento) {
        $parcelas = [];
        
        // Converter data para objeto DateTime
        try {
            $data_vencimento_obj = DateTime::createFromFormat('d/m/Y', $data_vencimento);
            if (!$data_vencimento_obj) {
                // Tentar formato alternativo se o primeiro falhar
                $data_vencimento_obj = new DateTime('+1 month');
            }
        } catch (Exception $e) {
            $data_vencimento_obj = new DateTime('+1 month');
        }
        
        // Define número de parcelas e intervalo baseado na periodicidade
        switch ($periodicidade) {
            case 'mensal':
                $num_parcelas = $periodo_obra_meses;
                $intervalo = 'P1M';
                $taxa_periodo = $this->taxa_obra;
                break;
                
            case 'bimestral':
                $num_parcelas = ceil($periodo_obra_meses / 2);
                $intervalo = 'P2M';
                $taxa_periodo = pow(1 + $this->taxa_obra, 2) - 1;
                break;
                
            case 'trimestral':
                $num_parcelas = ceil($periodo_obra_meses / 3);
                $intervalo = 'P3M';
                $taxa_periodo = pow(1 + $this->taxa_obra, 3) - 1;
                break;
                
            case 'semestral':
                $num_parcelas = ceil($periodo_obra_meses / 6);
                $intervalo = 'P6M';
                $taxa_periodo = pow(1 + $this->taxa_obra, 6) - 1;
                break;
                
            case 'anual':
                $num_parcelas = ceil($periodo_obra_meses / 12);
                $intervalo = 'P1Y';
                $taxa_periodo = pow(1 + $this->taxa_obra, 12) - 1;
                break;
                
            default:
                $num_parcelas = $periodo_obra_meses;
                $intervalo = 'P1M';
                $taxa_periodo = $this->taxa_obra;
        }
        
        $saldo_devedor = $valor_financiado;
        
        for ($i = 1; $i <= $num_parcelas; $i++) {
            $juros = $saldo_devedor * $taxa_periodo;
            $parcela = $juros; // No período de obra, só paga juros
            
            $parcelas[] = [
                'numero' => $i,
                'data_vencimento' => $data_vencimento_obj->format('d/m/Y'),
                'valor_parcela' => $parcela,
                'juros' => $juros,
                'amortizacao' => 0,
                'saldo_devedor' => $saldo_devedor,
                'periodo' => 'obra',
                'periodicidade' => $periodicidade
            ];
            
            // Atualiza data para próxima parcela
            $data_vencimento_obj->add(new DateInterval($intervalo));
        }
        
        return $parcelas;
    }
    
    /**
     * Calcula as parcelas do período de amortização
     */
    private function calcular_parcelas_amortizacao($valor_financiado, $taxa_juros_mes, $prazo_anos, $periodo_obra_meses, $parcelas_obra) {
        $parcelas = [];
        $num_parcelas_amortizacao = ($prazo_anos * 12) - $periodo_obra_meses;
        
        if ($num_parcelas_amortizacao <= 0) {
            return $parcelas;
        }
        
        // Usa a última data do período de obra como base
        $ultima_parcela_obra = end($parcelas_obra);
        try {
            $data_vencimento = DateTime::createFromFormat('d/m/Y', $ultima_parcela_obra['data_vencimento']);
            if (!$data_vencimento) {
                $data_vencimento = new DateTime('+1 month');
            }
        } catch (Exception $e) {
            $data_vencimento = new DateTime('+1 month');
        }
        
        $data_vencimento->add(new DateInterval('P1M')); // Próximo mês
        
        // Calcula valor da parcela pelo sistema Price
        $fator = pow(1 + $taxa_juros_mes, $num_parcelas_amortizacao);
        $valor_parcela = $valor_financiado * $taxa_juros_mes * $fator / ($fator - 1);
        
        $saldo_devedor = $valor_financiado;
        
        for ($i = 1; $i <= $num_parcelas_amortizacao; $i++) {
            $juros = $saldo_devedor * $taxa_juros_mes;
            $amortizacao = $valor_parcela - $juros;
            $saldo_devedor -= $amortizacao;
            
            if ($saldo_devedor < 0) {
                $saldo_devedor = 0;
            }
            
            // Ajustar última parcela para fechar o saldo
            if ($i == $num_parcelas_amortizacao && abs($saldo_devedor) > 0.01) {
                $valor_parcela += $saldo_devedor;
                $amortizacao += $saldo_devedor;
                $saldo_devedor = 0;
            }
            
            $parcelas[] = [
                'numero' => $i + count($parcelas_obra),
                'data_vencimento' => $data_vencimento->format('d/m/Y'),
                'valor_parcela' => $valor_parcela,
                'juros' => $juros,
                'amortizacao' => $amortizacao,
                'saldo_devedor' => $saldo_devedor,
                'periodo' => 'amortizacao'
            ];
            
            $data_vencimento->add(new DateInterval('P1M'));
        }
        
        return $parcelas;
    }
    
    /**
     * Gera resumo do financiamento
     */
    private function gerar_resumo($valor_financiado, $parcelas_obra, $parcelas_amortizacao) {
        $total_juros_obra = array_sum(array_column($parcelas_obra, 'juros'));
        $total_juros_amortizacao = array_sum(array_column($parcelas_amortizacao, 'juros'));
        $total_amortizacao = array_sum(array_column($parcelas_amortizacao, 'amortizacao'));
        $total_geral = $valor_financiado + $total_juros_obra + $total_juros_amortizacao;
        
        return [
            'valor_financiado' => $valor_financiado,
            'total_periodo_obra' => $total_juros_obra,
            'total_periodo_amortizacao' => $total_juros_amortizacao,
            'total_amortizacao' => $total_amortizacao,
            'total_juros' => $total_juros_obra + $total_juros_amortizacao,
            'total_geral' => $total_geral,
            'num_parcelas_obra' => count($parcelas_obra),
            'num_parcelas_amortizacao' => count($parcelas_amortizacao),
            'cet' => $this->calcular_cet($valor_financiado, $parcelas_obra, $parcelas_amortizacao),
            'economia_entrada' => $this->calcular_economia_entrada($valor_financiado, $total_geral)
        ];
    }
    
    /**
     * Calcula Custo Efetivo Total (simplificado)
     */
    private function calcular_cet($valor_financiado, $parcelas_obra, $parcelas_amortizacao) {
        $total_pago = array_sum(array_column($parcelas_obra, 'valor_parcela')) + 
                     array_sum(array_column($parcelas_amortizacao, 'valor_parcela'));
        
        if ($valor_financiado <= 0) return 0;
        
        return (($total_pago / $valor_financiado) - 1) * 100;
    }
    
    /**
     * Calcula economia com entrada
     */
    private function calcular_economia_entrada($valor_financiado, $total_geral) {
        // Simulação sem entrada (para comparação)
        $taxa_sem_entrada = 0.025; // Taxa maior sem entrada
        $parcela_sem_entrada = $valor_financiado * $taxa_sem_entrada;
        $total_sem_entrada = $parcela_sem_entrada * 360; // 30 anos
        
        return max(0, $total_sem_entrada - $total_geral);
    }
    
    /**
     * Gera dados para gráficos
     */
    private function gerar_dados_graficos($parcelas_obra, $parcelas_amortizacao) {
        $todas_parcelas = array_merge($parcelas_obra, $parcelas_amortizacao);
        
        return [
            'labels' => array_column($todas_parcelas, 'numero'),
            'saldo_devedor' => array_column($todas_parcelas, 'saldo_devedor'),
            'juros' => array_column($todas_parcelas, 'juros'),
            'amortizacao' => array_column($todas_parcelas, 'amortizacao'),
            'parcelas' => array_column($todas_parcelas, 'valor_parcela')
        ];
    }
    
    /**
     * Valida dados do formulário
     */
    public function validar_dados_obra($dados) {
        $errors = [];
        
        if (empty($dados['valor_imovel']) || $dados['valor_imovel'] <= 0) {
            $errors[] = 'Valor do imóvel é obrigatório e deve ser maior que zero';
        }
        
        if (!empty($dados['valor_entrada']) && $dados['valor_entrada'] >= $dados['valor_imovel']) {
            $errors[] = 'Valor de entrada deve ser menor que o valor do imóvel';
        }
        
        if (empty($dados['taxa_juros']) || $dados['taxa_juros'] <= 0) {
            $errors[] = 'Taxa de juros é obrigatória';
        }
        
        if (empty($dados['prazo_anos']) || $dados['prazo_anos'] < 1) {
            $errors[] = 'Prazo do financiamento é obrigatório';
        }
        
        if (empty($dados['periodo_obra_meses']) || $dados['periodo_obra_meses'] <= 0) {
            $errors[] = 'Período de obra é obrigatório';
        }
        
        if (empty($dados['data_vencimento'])) {
            $errors[] = 'Data de vencimento é obrigatória';
        } else {
            $data = DateTime::createFromFormat('d/m/Y', $dados['data_vencimento']);
            if (!$data || $data->format('d/m/Y') !== $dados['data_vencimento']) {
                $errors[] = 'Data de vencimento inválida. Use o formato dd/mm/aaaa';
            }
        }
        
        // Validar se período de obra não é maior que prazo total
        $periodo_obra_meses = intval($dados['periodo_obra_meses']);
        $prazo_total_meses = intval($dados['prazo_anos']) * 12;
        
        if ($periodo_obra_meses >= $prazo_total_meses) {
            $errors[] = 'Período de obra deve ser menor que o prazo total do financiamento';
        }
        
        return $errors;
    }
    
    /**
     * Compara diferentes periodicidades
     */
    public function comparar_periodicidades($dados_base) {
        $periodicidades = ['mensal', 'bimestral', 'trimestral', 'semestral', 'anual'];
        $comparacao = [];
        
        foreach ($periodicidades as $periodicidade) {
            $dados = $dados_base;
            $dados['periodicidade_obra'] = $periodicidade;
            
            $resultado = $this->calcular_financiamento_obra($dados);
            $resumo = $resultado['resumo'];
            
            $comparacao[] = [
                'periodicidade' => $periodicidade,
                'num_parcelas_obra' => $resumo['num_parcelas_obra'],
                'total_periodo_obra' => $resumo['total_periodo_obra'],
                'total_geral' => $resumo['total_geral'],
                'cet' => $resumo['cet']
            ];
        }
        
        return $comparacao;
    }
    
    /**
     * Calcula valor máximo do imóvel baseado na renda
     */
    public function calcular_limite_financiamento($renda_mensal, $entrada_percentual = 0.2) {
        // Regra: parcela máxima = 30% da renda
        $parcela_maxima = $renda_mensal * 0.3;
        $taxa_juros_mes = 0.08 / 12; // 8% ao ano
        $prazo_meses = 30 * 12; // 30 anos
        
        $fator = pow(1 + $taxa_juros_mes, $prazo_meses);
        $valor_financiado_maximo = $parcela_maxima * ($fator - 1) / ($taxa_juros_mes * $fator);
        
        $valor_imovel_maximo = $valor_financiado_maximo / (1 - $entrada_percentual);
        
        return [
            'renda_mensal' => $renda_mensal,
            'parcela_maxima' => $parcela_maxima,
            'valor_financiado_maximo' => $valor_financiado_maximo,
            'valor_imovel_maximo' => $valor_imovel_maximo,
            'entrada_minima' => $valor_imovel_maximo * $entrada_percentual
        ];
    }
}
?>

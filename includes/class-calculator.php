<?php
class SimuladorCalculator {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function calcular_sac($valor_financiado, $taxa_juros_anual, $prazo_meses) {
        $taxa_mensal = $this->converter_taxa_anual_para_mensal($taxa_juros_anual);
        $amortizacao = $valor_financiado / $prazo_meses;
        
        $parcelas = array();
        $saldo_devedor = $valor_financiado;
        
        for ($mes = 1; $mes <= $prazo_meses; $mes++) {
            $juros = $saldo_devedor * $taxa_mensal;
            $parcela = $amortizacao + $juros;
            $saldo_devedor -= $amortizacao;
            
            $parcelas[] = array(
                'mes' => $mes,
                'parcela' => round($parcela, 2),
                'amortizacao' => round($amortizacao, 2),
                'juros' => round($juros, 2),
                'saldo_devedor' => round(max(0, $saldo_devedor), 2)
            );
        }
        
        return $parcelas;
    }
    
    public function calcular_price($valor_financiado, $taxa_juros_anual, $prazo_meses) {
        $taxa_mensal = $this->converter_taxa_anual_para_mensal($taxa_juros_anual);
        
        if ($taxa_mensal == 0) {
            $parcela = $valor_financiado / $prazo_meses;
        } else {
            $fator = pow(1 + $taxa_mensal, $prazo_meses);
            $parcela = $valor_financiado * ($taxa_mensal * $fator) / ($fator - 1);
        }
        
        $parcelas = array();
        $saldo_devedor = $valor_financiado;
        
        for ($mes = 1; $mes <= $prazo_meses; $mes++) {
            $juros = $saldo_devedor * $taxa_mensal;
            $amortizacao = $parcela - $juros;
            $saldo_devedor -= $amortizacao;
            
            $parcelas[] = array(
                'mes' => $mes,
                'parcela' => round($parcela, 2),
                'amortizacao' => round($amortizacao, 2),
                'juros' => round($juros, 2),
                'saldo_devedor' => round(max(0, $saldo_devedor), 2)
            );
        }
        
        return $parcelas;
    }
    
    public function converter_taxa_anual_para_mensal($taxa_anual) {
        return pow(1 + ($taxa_anual / 100), 1/12) - 1;
    }
    
    public function calcular_valor_maximo_imovel($renda_mensal, $taxa_juros_anual, $prazo_meses, $tipo_amortizacao) {
        $limite_parcela = $renda_mensal * 0.3;
        
        // Busca binária para encontrar valor máximo
        $min = 0;
        $max = $limite_parcela * $prazo_meses * 2;
        $precisao = 100;
        
        for ($i = 0; $i < 50; $i++) {
            $valor_teste = ($min + $max) / 2;
            $parcela_teste = $this->calcular_parcela($valor_teste, 0, $taxa_juros_anual, $prazo_meses, $tipo_amortizacao);
            
            if (abs($parcela_teste - $limite_parcela) < $precisao) {
                break;
            }
            
            if ($parcela_teste > $limite_parcela) {
                $max = $valor_teste;
            } else {
                $min = $valor_teste;
            }
        }
        
        return round($valor_teste, 2);
    }
    
    public function calcular_parcela($valor_imovel, $entrada, $taxa_juros_anual, $prazo_meses, $tipo_amortizacao) {
        $valor_financiado = $valor_imovel - $entrada;
        
        if ($tipo_amortizacao === 'sac') {
            $parcelas = $this->calcular_sac($valor_financiado, $taxa_juros_anual, $prazo_meses);
            return $parcelas[0]['parcela'];
        } else {
            $parcelas = $this->calcular_price($valor_financiado, $taxa_juros_anual, $prazo_meses);
            return $parcelas[0]['parcela'];
        }
    }
    
    public function calcular_valor_financiamento_por_parcela($parcela_desejada, $taxa_juros_anual, $prazo_meses, $tipo_amortizacao) {
        if ($tipo_amortizacao === 'price') {
            $taxa_mensal = $this->converter_taxa_anual_para_mensal($taxa_juros_anual);
            
            if ($taxa_mensal == 0) {
                $valor_financiado = $parcela_desejada * $prazo_meses;
            } else {
                $fator = pow(1 + $taxa_mensal, $prazo_meses);
                $valor_financiado = $parcela_desejada * ($fator - 1) / ($taxa_mensal * $fator);
            }
        } else {
            // Para SAC, usar iteração
            $min = 0;
            $max = $parcela_desejada * $prazo_meses * 2;
            $precisao = 100;
            
            for ($i = 0; $i < 50; $i++) {
                $valor_teste = ($min + $max) / 2;
                $parcelas = $this->calcular_sac($valor_teste, $taxa_juros_anual, $prazo_meses);
                $primeira_parcela = $parcelas[0]['parcela'];
                
                if (abs($primeira_parcela - $parcela_desejada) < $precisao) {
                    break;
                }
                
                if ($primeira_parcela > $parcela_desejada) {
                    $max = $valor_teste;
                } else {
                    $min = $valor_teste;
                }
            }
            
            $valor_financiado = $valor_teste;
        }
        
        return round($valor_financiado, 2);
    }
    
    public function validar_limites($dados_simulacao, $modalidade) {
        $erros = array();
        
        $valor_financiado = $dados_simulacao['valor_imovel'] - ($dados_simulacao['entrada'] ?? 0);
        
        if ($valor_financiado > $modalidade['limite_financiamento']) {
            $erros[] = sprintf(
                'Valor financiado (R$ %s) excede o limite de R$ %s para esta modalidade',
                number_format($valor_financiado, 2, ',', '.'),
                number_format($modalidade['limite_financiamento'], 2, ',', '.')
            );
        }
        
        if (isset($dados_simulacao['renda_mensal']) && $dados_simulacao['renda_mensal'] > $modalidade['limite_renda']) {
            $erros[] = sprintf(
                'Renda mensal (R$ %s) excede o limite de R$ %s para esta modalidade',
                number_format($dados_simulacao['renda_mensal'], 2, ',', '.'),
                number_format($modalidade['limite_renda'], 2, ',', '.')
            );
        }
        
        if (isset($dados_simulacao['renda_mensal']) && isset($dados_simulacao['parcela_calculada'])) {
            $percentual = ($dados_simulacao['parcela_calculada'] / $dados_simulacao['renda_mensal']) * 100;
            if ($percentual > 30) {
                $erros[] = sprintf(
                    'Parcela representa %.1f%% da renda (máximo recomendado: 30%%)',
                    $percentual
                );
            }
        }
        
        return $erros;
    }
    
    public function calcular_totais($parcelas) {
        $total_juros = 0;
        $total_amortizacao = 0;
        $total_pago = 0;
        
        foreach ($parcelas as $parcela) {
            $total_juros += $parcela['juros'];
            $total_amortizacao += $parcela['amortizacao'];
            $total_pago += $parcela['parcela'];
        }
        
        return array(
            'total_juros' => round($total_juros, 2),
            'total_amortizacao' => round($total_amortizacao, 2),
            'total_pago' => round($total_pago, 2),
            'custo_efetivo_total' => $this->calcular_cet($parcelas, $parcelas[0]['parcela'] * $parcelas[0]['mes'])
        );
    }
    
    private function calcular_cet($parcelas, $valor_financiado) {
        // Cálculo simplificado do CET
        $total_juros = 0;
        foreach ($parcelas as $parcela) {
            $total_juros += $parcela['juros'];
        }
        
        $cet = (pow(1 + ($total_juros / $valor_financiado), 1 / count($parcelas)) - 1) * 100;
        return round($cet, 2);
    }
    
    public function analisar_compatibilidade_renda($parcela, $renda_mensal) {
        if (!$renda_mensal || $renda_mensal <= 0) {
            return null;
        }
        
        $percentual = ($parcela / $renda_mensal) * 100;
        
        return array(
            'percentual' => round($percentual, 1),
            'compativel' => $percentual <= 30,
            'limite_ideal' => $renda_mensal * 0.3,
            'classificacao' => $this->classificar_compatibilidade($percentual)
        );
    }
    
    private function classificar_compatibilidade($percentual) {
        if ($percentual <= 25) return 'excelente';
        if ($percentual <= 30) return 'boa';
        if ($percentual <= 35) return 'regular';
        return 'critica';
    }
}
?>

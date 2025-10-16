// assets/js/simulador-calculator.js

class SimuladorCalculatorJS {
    
    // Cálculo SAC em JavaScript
    calcularSAC(valorFinanciado, taxaJurosAnual, prazoMeses) {
        const taxaMensal = this.converterTaxaAnualParaMensal(taxaJurosAnual);
        const amortizacao = valorFinanciado / prazoMeses;
        
        const parcelas = [];
        let saldoDevedor = valorFinanciado;
        
        for (let mes = 1; mes <= prazoMeses; mes++) {
            const juros = saldoDevedor * taxaMensal;
            const parcela = amortizacao + juros;
            saldoDevedor -= amortizacao;
            
            parcelas.push({
                mes: mes,
                parcela: Math.round(parcela * 100) / 100,
                amortizacao: Math.round(amortizacao * 100) / 100,
                juros: Math.round(juros * 100) / 100,
                saldoDevedor: Math.round(saldoDevedor * 100) / 100
            });
        }
        
        return parcelas;
    }
    
    // Cálculo Price em JavaScript
    calcularPrice(valorFinanciado, taxaJurosAnual, prazoMeses) {
        const taxaMensal = this.converterTaxaAnualParaMensal(taxaJurosAnual);
        
        // Fórmula Price
        const fator = Math.pow(1 + taxaMensal, prazoMeses);
        const parcela = valorFinanciado * (taxaMensal * fator) / (fator - 1);
        
        const parcelas = [];
        let saldoDevedor = valorFinanciado;
        
        for (let mes = 1; mes <= prazoMeses; mes++) {
            const juros = saldoDevedor * taxaMensal;
            const amortizacao = parcela - juros;
            saldoDevedor -= amortizacao;
            
            parcelas.push({
                mes: mes,
                parcela: Math.round(parcela * 100) / 100,
                amortizacao: Math.round(amortizacao * 100) / 100,
                juros: Math.round(juros * 100) / 100,
                saldoDevedor: Math.round(saldoDevedor * 100) / 100
            });
        }
        
        return parcelas;
    }
    
    // Converter taxa anual para mensal
    converterTaxaAnualParaMensal(taxaAnual) {
        return Math.pow(1 + (taxaAnual / 100), 1/12) - 1;
    }
    
    // Calcular primeira parcela para preview rápido
    calcularParcelaPreview(valorImovel, entrada, taxaJurosAnual, prazoMeses, tipoAmortizacao) {
        const valorFinanciado = valorImovel - entrada;
        
        if (tipoAmortizacao === 'sac') {
            const parcelas = this.calcularSAC(valorFinanciado, taxaJurosAnual, prazoMeses);
            return parcelas[0].parcela;
        } else {
            const parcelas = this.calcularPrice(valorFinanciado, taxaJurosAnual, prazoMeses);
            return parcelas[0].parcela;
        }
    }
    
    // Validar limites em tempo real
    validarLimitesTempoReal(dados, modalidade) {
        const erros = [];
        const valorFinanciado = dados.valorImovel - (dados.entrada || 0);
        
        // Limite de financiamento
        if (valorFinanciado > modalidade.limite_financiamento) {
            erros.push(`Valor financiado excede o limite de R$ ${modalidade.limite_financiamento.toLocaleString('pt-BR')}`);
        }
        
        // Limite de renda
        if (dados.rendaMensal && dados.rendaMensal > modalidade.limite_renda) {
            erros.push(`Renda excede o limite de R$ ${modalidade.limite_renda.toLocaleString('pt-BR')}`);
        }
        
        return erros;
    }
    
    // Calcular totais
    calcularTotais(parcelas) {
        let totalJuros = 0;
        let totalAmortizacao = 0;
        let totalPago = 0;
        
        parcelas.forEach(parcela => {
            totalJuros += parcela.juros;
            totalAmortizacao += parcela.amortizacao;
            totalPago += parcela.parcela;
        });
        
        return {
            totalJuros: Math.round(totalJuros * 100) / 100,
            totalAmortizacao: Math.round(totalAmortizacao * 100) / 100,
            totalPago: Math.round(totalPago * 100) / 100
        };
    }
    
    // Formatar moeda brasileira
    formatarMoeda(valor) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    }
    
    // Formatar porcentagem
    formatarPorcentagem(valor) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'percent',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(valor / 100);
    }
}

// Instância global para uso no frontend
const CalculadoraFinanciamento = new SimuladorCalculatorJS();

// assets/js/simulador-frontend.js

class SimuladorFrontend {
    constructor() {
        this.etapaAtual = 1;
        this.dadosSimulacao = {};
        this.modalidades = [];
        this.init();
    }

    init() {
        this.carregarModalidades();
        this.bindEvents();
        this.iniciarEtapa1();
    }

    carregarModalidades() {
        // Carregar modalidades do HTML
        const selectModalidades = document.getElementById('modalidade_financiamento');
        if (selectModalidades) {
            this.modalidades = Array.from(selectModalidades.options).slice(1).map(option => ({
                id: option.value,
                nome: option.textContent,
                taxa: parseFloat(option.dataset.taxa),
                limite_financiamento: parseFloat(option.dataset.limiteFinanciamento),
                limite_renda: parseFloat(option.dataset.limiteRenda),
                subsidio: parseFloat(option.dataset.subsidio)
            }));
        }
    }

    bindEvents() {
        // Etapa 1 - Seleção do tipo
        document.getElementById('tipo_simulacao_select').addEventListener('change', (e) => {
            this.handleSelecaoTipo(e.target.value);
        });

        document.getElementById('avancar-etapa1').addEventListener('click', () => {
            this.avancarParaEtapa2();
        });

        // Etapa 2 - Navegação
        document.getElementById('voltar-etapa1').addEventListener('click', () => {
            this.voltarParaEtapa1();
        });

        // Etapa 2 - Formulário
        document.getElementById('form-simulacao').addEventListener('submit', (e) => {
            e.preventDefault();
            this.calcularSimulacao();
        });

        // Controles dinâmicos
        this.bindControlesDinamicos();
    }

    bindControlesDinamicos() {
        // Controle Modalidade vs Taxa Personalizada
        document.querySelectorAll('input[name="tipo_taxa"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.toggleTipoTaxa(e.target.value);
            });
        });

        // Controle FGTS
        document.querySelectorAll('input[name="usa_fgts"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.toggleCampoFGTS(e.target.value);
            });
        });

        // Preview em tempo real
        this.bindPreviewTempoReal();
    }

    bindPreviewTempoReal() {
        const camposPreview = ['valor_imovel', 'pagamento_inicial', 'prazo_meses', 'modalidade_financiamento', 'taxa_juros_personalizada'];
        
        camposPreview.forEach(campoId => {
            const elemento = document.getElementById(campoId);
            if (elemento) {
                elemento.addEventListener('input', () => {
                    this.atualizarPreview();
                });
            }
        });

        document.querySelectorAll('input[name="tipo_amortizacao"]').forEach(radio => {
            radio.addEventListener('change', () => {
                this.atualizarPreview();
            });
        });
    }

    handleSelecaoTipo(tipo) {
        const btnAvancar = document.getElementById('avancar-etapa1');
        const descricaoContainer = document.querySelector('.select-description');
        
        // Habilitar/desabilitar botão
        btnAvancar.disabled = !tipo;
        
        // Mostrar descrição
        document.querySelectorAll('.descricao-opcao').forEach(desc => {
            desc.style.display = 'none';
        });
        
        if (tipo) {
            const descricao = document.getElementById(`descricao-${tipo}`);
            if (descricao) {
                descricao.style.display = 'block';
                descricaoContainer.style.display = 'block';
            }
        } else {
            descricaoContainer.style.display = 'none';
        }
    }

    avancarParaEtapa2() {
        const tipoSelecionado = document.getElementById('tipo_simulacao_select').value;
        
        if (!tipoSelecionado) return;

        this.dadosSimulacao.tipo_simulacao = tipoSelecionado;
        
        // Atualizar título
        const titulos = {
            'valor_imovel': 'Simulação por Valor do Imóvel',
            'renda_mensal': 'Simulação por Renda Mensal',
            'valor_parcela': 'Simulação por Valor de Parcela'
        };
        
        document.getElementById('subtipo-simulacao').textContent = titulos[tipoSelecionado];
        
        // Mostrar formulário específico
        document.querySelectorAll('.formulario-especifico').forEach(form => {
            form.style.display = 'none';
        });
        document.getElementById(`form-${tipoSelecionado.replace('_', '-')}`).style.display = 'block';
        
        // Atualizar título dos campos específicos
        const titulosCampos = {
            'valor_imovel': '💵 Dados do Imóvel',
            'renda_mensal': '💰 Dados de Renda',
            'valor_parcela': '📅 Parcela Desejada'
        };
        document.getElementById('titulo-campos-especificos').textContent = titulosCampos[tipoSelecionado];
        
        this.mudarEtapa(2);
    }

    voltarParaEtapa1() {
        this.mudarEtapa(1);
    }

    toggleTipoTaxa(tipo) {
        const campoModalidade = document.getElementById('campo-modalidade');
        const campoTaxaPersonalizada = document.getElementById('campo-taxa-personalizada');
        
        if (tipo === 'modalidade') {
            campoModalidade.style.display = 'block';
            campoTaxaPersonalizada.style.display = 'none';
        } else {
            campoModalidade.style.display = 'none';
            campoTaxaPersonalizada.style.display = 'block';
        }
        
        this.atualizarPreview();
    }

    toggleCampoFGTS(valor) {
        const campoValorFGTS = document.getElementById('campo-valor-fgts');
        campoValorFGTS.style.display = valor === 'sim' ? 'block' : 'none';
    }

    async atualizarPreview() {
        const dados = this.coletarDadosPreview();
        
        if (!this.dadosValidosParaPreview(dados)) {
            document.getElementById('preview-rapido').style.display = 'none';
            return;
        }
        
        try {
            const parcela = CalculadoraFinanciamento.calcularParcelaPreview(
                dados.valorImovel,
                dados.entrada,
                dados.taxaJuros,
                dados.prazoMeses,
                dados.tipoAmortizacao
            );
            
            this.mostrarPreview(dados.valorImovel - dados.entrada, parcela, dados.rendaMensal);
        } catch (error) {
            console.error('Erro no preview:', error);
        }
    }

    coletarDadosPreview() {
        const tipoSimulacao = this.dadosSimulacao.tipo_simulacao;
        
        return {
            valorImovel: this.obterValorNumerico('valor_imovel') || this.obterValorNumerico('valor_imovel_renda'),
            entrada: this.obterValorNumerico('pagamento_inicial') || 0,
            taxaJuros: this.obterTaxaJuros(),
            prazoMeses: parseInt(document.getElementById('prazo_meses').value) || 360,
            tipoAmortizacao: document.querySelector('input[name="tipo_amortizacao"]:checked')?.value || 'price',
            rendaMensal: this.obterValorNumerico('renda_mensal') || 
                         this.obterValorNumerico('renda_mensal_vi') || 
                         this.obterValorNumerico('renda_mensal_vp')
        };
    }

    dadosValidosParaPreview(dados) {
        return dados.valorImovel > 0 && dados.taxaJuros > 0 && dados.prazoMeses > 0;
    }

    mostrarPreview(valorFinanciado, parcela, rendaMensal) {
        document.getElementById('preview-rapido').style.display = 'block';
        document.getElementById('preview-valor-financiado').textContent = CalculadoraFinanciamento.formatarMoeda(valorFinanciado);
        document.getElementById('preview-parcela').textContent = CalculadoraFinanciamento.formatarMoeda(parcela);
        
        if (rendaMensal) {
            const percentual = (parcela / rendaMensal) * 100;
            const compativel = percentual <= 30;
            
            document.getElementById('preview-compatibilidade').innerHTML = 
                `<span style="color: ${compativel ? 'var(--success)' : 'var(--warning)'}">
                    ${percentual.toFixed(1)}% da renda ${compativel ? '✅' : '⚠️'}
                </span>`;
        } else {
            document.getElementById('preview-compatibilidade').textContent = 'Informe a renda para análise';
        }
    }

    async calcularSimulacao() {
        this.mostrarLoading(true);
        
        try {
            const dados = this.coletarDadosFormulario();
            const validacao = this.validarDadosFormulario(dados);
            
            if (!validacao.valido) {
                this.mostrarErro(validacao.erros.join('<br>'));
                return;
            }
            
            const resultado = await this.enviarParaCalculo(dados);
            this.mostrarResultadoCompacto(resultado);
            
        } catch (error) {
            this.mostrarErro('Erro ao calcular simulação. Tente novamente.');
            console.error('Erro:', error);
        } finally {
            this.mostrarLoading(false);
        }
    }

    coletarDadosFormulario() {
        const tipoSimulacao = this.dadosSimulacao.tipo_simulacao;
        const dados = {
            tipo_simulacao: tipoSimulacao,
            tipo_taxa: document.querySelector('input[name="tipo_taxa"]:checked').value,
            modalidade_id: document.getElementById('modalidade_financiamento').value,
            taxa_juros_personalizada: this.obterValorNumerico('taxa_juros_personalizada'),
            prazo_meses: parseInt(document.getElementById('prazo_meses').value),
            tipo_amortizacao: document.querySelector('input[name="tipo_amortizacao"]:checked').value,
            nonce: simulador_ajax.nonce
        };
        
        // Campos específicos por tipo
        if (tipoSimulacao === 'valor_imovel') {
            dados.valor_imovel = this.obterValorNumerico('valor_imovel');
            dados.entrada = this.obterValorNumerico('pagamento_inicial') || 0;
            dados.usa_fgts = document.querySelector('input[name="usa_fgts"]:checked')?.value;
            dados.valor_fgts = this.obterValorNumerico('valor_fgts') || 0;
            dados.renda_mensal = this.obterValorNumerico('renda_mensal_vi');
        } else if (tipoSimulacao === 'renda_mensal') {
            dados.renda_mensal = this.obterValorNumerico('renda_mensal');
            dados.valor_imovel = this.obterValorNumerico('valor_imovel_renda');
        } else if (tipoSimulacao === 'valor_parcela') {
            dados.valor_parcela = this.obterValorNumerico('valor_parcela');
            dados.renda_mensal = this.obterValorNumerico('renda_mensal_vp');
        }
        
        return dados;
    }

    validarDadosFormulario(dados) {
        const erros = [];
        
        if (dados.tipo_taxa === 'modalidade' && !dados.modalidade_id) {
            erros.push('Selecione uma modalidade de financiamento');
        }
        
        if (dados.tipo_taxa === 'personalizada' && (!dados.taxa_juros_personalizada || dados.taxa_juros_personalizada <= 0)) {
            erros.push('Informe uma taxa de juros válida');
        }
        
        if (!dados.prazo_meses || dados.prazo_meses < 60) {
            erros.push('Prazo mínimo é de 60 meses');
        }
        
        // Validações específicas por tipo
        if (dados.tipo_simulacao === 'valor_imovel') {
            if (!dados.valor_imovel || dados.valor_imovel <= 0) {
                erros.push('Informe o valor do imóvel');
            }
            if (dados.entrada >= dados.valor_imovel) {
                erros.push('A entrada não pode ser maior ou igual ao valor do imóvel');
            }
        } else if (dados.tipo_simulacao === 'renda_mensal') {
            if (!dados.renda_mensal || dados.renda_mensal <= 0) {
                erros.push('Informe a renda mensal');
            }
        } else if (dados.tipo_simulacao === 'valor_parcela') {
            if (!dados.valor_parcela || dados.valor_parcela <= 0) {
                erros.push('Informe o valor da parcela desejada');
            }
        }
        
        return {
            valido: erros.length === 0,
            erros: erros
        };
    }

    async enviarParaCalculo(dados) {
        const response = await fetch(simulador_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'calcular_simulacao',
                dados: JSON.stringify(dados),
                nonce: dados.nonce
            })
        });
        
        const resultado = await response.json();
        
        if (!resultado.success) {
            throw new Error(resultado.data);
        }
        
        return resultado.data;
    }

    mostrarResultadoCompacto(resultado) {
        this.dadosSimulacao.resultado = resultado;
        this.mudarEtapa(3);
        
        // Aqui será carregado o template de resultado via AJAX
        this.carregarTemplateResultado(resultado);
    }

    carregarTemplateResultado(resultado) {
        // Implementar carregamento do template de resultado
        const etapa3 = document.getElementById('etapa-3');
        etapa3.innerHTML = this.gerarHTMLResultadoCompacto(resultado);
    }

    gerarHTMLResultadoCompacto(resultado) {
        return `
            <div class="resultado-compacto">
                <div class="header-resultado">
                    <button onclick="simuladorFrontend.voltarParaEtapa2()" class="btn-voltar">← Voltar</button>
                    <h1>✅ Simulação Concluída</h1>
                    <div class="status-geral ${resultado.compatibilidade_renda?.compativel ? 'aprovado' : 'alerta'}">
                        <span class="status-icon">${resultado.compatibilidade_renda?.compativel ? '✅' : '⚠️'}</span>
                        <span class="status-text">${resultado.compatibilidade_renda?.compativel ? 'Financiamento Viável' : 'Análise Necessária'}</span>
                    </div>
                </div>
                
                <div class="cartao-compacto">
                    <div class="resumo-principal">
                        <div class="destaque-principal">
                            <span class="label">Parcela Mensal</span>
                            <span class="valor">${CalculadoraFinanciamento.formatarMoeda(resultado.parcela_calculada)}</span>
                            ${resultado.compatibilidade_renda ? `
                                <div class="info-adicional ${resultado.compatibilidade_renda.compativel ? 'compativel' : 'alerta'}">
                                    ${resultado.compatibilidade_renda.compativel ? '✅' : '⚠️'} 
                                    ${resultado.compatibilidade_renda.percentual}% da renda
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="acoes-resultado">
                        <button onclick="simuladorFrontend.solicitarRelatorioDetalhado()" class="btn-relatorio">
                            📊 Solicitar Relatório Detalhado
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    voltarParaEtapa2() {
        this.mudarEtapa(2);
    }

    solicitarRelatorioDetalhado() {
        this.mudarEtapa(4);
        this.carregarFormularioInformacoes();
    }

    carregarFormularioInformacoes() {
        // Implementar carregamento do formulário de informações
        const etapa4 = document.getElementById('etapa-4');
        etapa4.innerHTML = this.gerarHTMLFormularioInformacoes();
    }

    // ... Métodos auxiliares

    obterValorNumerico(id) {
        const elemento = document.getElementById(id);
        return elemento && elemento.value ? parseFloat(elemento.value) : null;
    }

    obterTaxaJuros() {
        const tipoTaxa = document.querySelector('input[name="tipo_taxa"]:checked').value;
        
        if (tipoTaxa === 'modalidade') {
            const select = document.getElementById('modalidade_financiamento');
            const optionSelecionada = select.options[select.selectedIndex];
            return optionSelecionada ? parseFloat(optionSelecionada.dataset.taxa) : 0;
        } else {
            return this.obterValorNumerico('taxa_juros_personalizada') || 0;
        }
    }

    mudarEtapa(novaEtapa) {
        document.querySelectorAll('.etapa-simulacao').forEach(etapa => {
            etapa.classList.remove('etapa-ativa');
        });
        
        document.getElementById(`etapa-${novaEtapa}`).classList.add('etapa-ativa');
        this.etapaAtual = novaEtapa;
        
        // Scroll para o topo
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    mostrarLoading(mostrar) {
        document.getElementById('simulador-loading').style.display = mostrar ? 'flex' : 'none';
    }

    mostrarErro(mensagem) {
        alert(mensagem); // Em produção, usar um toast ou modal melhor
    }

    iniciarEtapa1() {
        this.mudarEtapa(1);
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.simuladorFrontend = new SimuladorFrontend();
});

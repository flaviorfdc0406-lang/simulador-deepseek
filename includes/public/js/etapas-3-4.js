// assets/js/etapas-3-4.js

class EtapasAvancadas {
    constructor(simuladorFrontend) {
        this.frontend = simuladorFrontend;
        this.bindEventsEtapa4();
    }

    bindEventsEtapa4() {
        // Controle do select de empreendimento
        document.addEventListener('change', (e) => {
            if (e.target.id === 'nome_empreendimento') {
                this.toggleCampoOutroEmpreendimento(e.target.value);
            }
        });

        // Auto-preenchimento de dados do corretor
        this.autoPreencherDadosCorretor();

        // Submit do formulário de informações
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'form-informacoes') {
                e.preventDefault();
                this.processarFormularioInformacoes();
            }
        });
    }

    toggleCampoOutroEmpreendimento(valor) {
        const campoOutro = document.getElementById('campo-outro-empreendimento');
        const inputOutro = document.getElementById('outro_empreendimento');
        
        if (valor === 'outro') {
            campoOutro.style.display = 'block';
            inputOutro.required = true;
        } else {
            campoOutro.style.display = 'none';
            inputOutro.required = false;
        }
    }

    autoPreencherDadosCorretor() {
        // Tentar carregar dados salvos do localStorage
        const dadosSalvos = JSON.parse(localStorage.getItem('dados_corretor') || '{}');
        
        const campos = ['nome_corretor', 'email_corretor', 'empresa', 'creci'];
        campos.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento && dadosSalvos[campo]) {
                elemento.value = dadosSalvos[campo];
            }
        });
    }

    salvarDadosCorretor() {
        const dados = {
            nome_corretor: document.getElementById('nome_corretor').value,
            email_corretor: document.getElementById('email_corretor').value,
            empresa: document.getElementById('empresa').value,
            creci: document.getElementById('creci').value
        };
        
        localStorage.setItem('dados_corretor', JSON.stringify(dados));
    }

    async processarFormularioInformacoes() {
        this.frontend.mostrarLoading(true);

        try {
            const dadosValidos = this.validarFormularioInformacoes();
            if (!dadosValidos.valido) {
                this.mostrarErroFormulario(dadosValidos.erros);
                return;
            }

            const dadosCompletos = this.coletarDadosCompletos();
            await this.gerarRelatorioDetalhado(dadosCompletos);

        } catch (error) {
            this.mostrarErro('Erro ao gerar relatório. Tente novamente.');
            console.error('Erro:', error);
        } finally {
            this.frontend.mostrarLoading(false);
        }
    }

    validarFormularioInformacoes() {
        const erros = [];
        const camposObrigatorios = [
            'nome_empreendimento', 'unidade', 'nome_corretor', 
            'email_corretor', 'empresa', 'creci', 'nome_cliente'
        ];

        camposObrigatorios.forEach(campoId => {
            const elemento = document.getElementById(campoId);
            if (!elemento || !elemento.value.trim()) {
                erros.push(`Preencha o campo: ${elemento.labels[0].textContent}`);
            }
        });

        // Validar email
        const email = document.getElementById('email_corretor').value;
        if (email && !this.validarEmail(email)) {
            erros.push('Informe um email válido');
        }

        // Validar termos
        if (!document.getElementById('concorda_termos').checked) {
            erros.push('É necessário concordar com os termos de uso');
        }

        // Validar empreendimento "outro"
        if (document.getElementById('nome_empreendimento').value === 'outro') {
            const outroEmpreendimento = document.getElementById('outro_empreendimento').value;
            if (!outroEmpreendimento.trim()) {
                erros.push('Informe o nome do empreendimento');
            }
        }

        return {
            valido: erros.length === 0,
            erros: erros
        };
    }

    validarEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    coletarDadosCompletos() {
        const empreendimento = document.getElementById('nome_empreendimento').value;
        const nomeEmpreendimento = empreendimento === 'outro' 
            ? document.getElementById('outro_empreendimento').value 
            : empreendimento;

        // Salvar dados do corretor para futuro uso
        this.salvarDadosCorretor();

        return {
            // Dados da simulação
            ...this.frontend.dadosSimulacao,
            
            // Dados do empreendimento
            nome_empreendimento: nomeEmpreendimento,
            unidade: document.getElementById('unidade').value,
            
            // Dados do corretor
            nome_corretor: document.getElementById('nome_corretor').value,
            email_corretor: document.getElementById('email_corretor').value,
            empresa: document.getElementById('empresa').value,
            creci: document.getElementById('creci').value,
            
            // Dados do cliente
            nome_cliente: document.getElementById('nome_cliente').value,
            email_cliente: document.getElementById('email_cliente').value,
            telefone_cliente: document.getElementById('telefone_cliente').value,
            observacoes: document.getElementById('observacoes').value,
            
            // Metadata
            data_geracao: new Date().toLocaleString('pt-BR'),
            ip_usuario: this.obterIPUsuario()
        };
    }

    obterIPUsuario() {
        // Em produção, isso viria do servidor
        return 'Não disponível no cliente';
    }

    async gerarRelatorioDetalhado(dadosCompletos) {
        // Aqui você implementaria a geração do PDF
        // Por enquanto, vamos simular o processo
        
        const response = await fetch(simulador_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'gerar_relatorio',
                dados: JSON.stringify(dadosCompletos),
                nonce: simulador_ajax.nonce
            })
        });

        const resultado = await response.json();

        if (resultado.success) {
            this.mostrarSucessoRelatorio(resultado.data);
        } else {
            throw new Error(resultado.data);
        }
    }

    mostrarSucessoRelatorio(dadosRelatorio) {
        const html = `
            <div class="sucesso-relatorio">
                <div class="sucesso-icon">🎉</div>
                <h2>Relatório Gerado com Sucesso!</h2>
                <p>Seu relatório detalhado foi gerado e está pronto para download.</p>
                
                <div class="detalhes-relatorio">
                    <div class="info-item">
                        <strong>Número do Relatório:</strong>
                        <span>#${dadosRelatorio.id || '0001'}</span>
                    </div>
                    <div class="info-item">
                        <strong>Data de Geração:</strong>
                        <span>${new Date().toLocaleString('pt-BR')}</span>
                    </div>
                    <div class="info-item">
                        <strong>Cliente:</strong>
                        <span>${dadosRelatorio.nome_cliente}</span>
                    </div>
                </div>

                <div class="acoes-relatorio">
                    <button onclick="simuladorFrontend.downloadRelatorio(${dadosRelatorio.id})" class="btn-download">
                        📥 Download do PDF
                    </button>
                    <button onclick="simuladorFrontend.enviarEmailRelatorio(${dadosRelatorio.id})" class="btn-email">
                        📧 Enviar por Email
                    </button>
                    <button onclick="simuladorFrontend.novaSimulacao()" class="btn-nova">
                        🔄 Nova Simulação
                    </button>
                </div>

                <div class="info-rodape">
                    <p>O relatório foi salvo em nosso sistema e está disponível para acesso futuro.</p>
                </div>
            </div>
        `;

        document.getElementById('etapa-4').innerHTML = html;
    }

    mostrarErroFormulario(erros) {
        const mensagem = erros.map(erro => `• ${erro}`).join('<br>');
        this.mostrarErro(`Por favor, corrija os seguintes erros:<br><br>${mensagem}`);
    }

    mostrarErro(mensagem) {
        // Em produção, usar um modal ou toast melhor
        alert(mensagem);
    }
}

// Adicionar métodos ao SimuladorFrontend
SimuladorFrontend.prototype.imprimirResultado = function() {
    window.print();
};

SimuladorFrontend.prototype.compartilharResultado = function() {
    const email = this.dadosSimulacao.email_corretor || 'corretores@livingvivaz.com.br';
    const assunto = 'Simulação de Financiamento - Living & Vivaz';
    const corpo = `Segue simulação de financiamento solicitada:\n\n` +
                  `Valor do Imóvel: R$ ${this.dadosSimulacao.resultado.valor_imovel?.toLocaleString('pt-BR') || this.dadosSimulacao.resultado.valor_maximo_imovel?.toLocaleString('pt-BR')}\n` +
                  `Parcela: R$ ${this.dadosSimulacao.resultado.parcela_calculada.toLocaleString('pt-BR')}\n` +
                  `Prazo: ${this.dadosSimulacao.resultado.prazo_meses} meses`;
    
    window.location.href = `mailto:${email}?subject=${encodeURIComponent(assunto)}&body=${encodeURIComponent(corpo)}`;
};

SimuladorFrontend.prototype.falarComCorretor = function() {
    const telefone = '5521999999999'; // Número da Living & Vivaz
    const mensagem = 'Olá! Gostaria de mais informações sobre a simulação de financiamento.';
    
    window.open(`https://wa.me/${telefone}?text=${encodeURIComponent(mensagem)}`, '_blank');
};

SimuladorFrontend.prototype.voltarParaEtapa3 = function() {
    this.mudarEtapa(3);
};

SimuladorFrontend.prototype.solicitarRelatorioDetalhado = function() {
    this.mudarEtapa(4);
    this.carregarFormularioInformacoes();
};

SimuladorFrontend.prototype.carregarFormularioInformacoes = function() {
    // Fazer requisição para carregar o template
    fetch(simulador_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'carregar_formulario_informacoes',
            dados: JSON.stringify(this.dadosSimulacao),
            nonce: simulador_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(resultado => {
        if (resultado.success) {
            document.getElementById('etapa-4').innerHTML = resultado.data;
            // Inicializar a classe de etapas avançadas
            if (!this.etapasAvancadas) {
                this.etapasAvancadas = new EtapasAvancadas(this);
            }
        }
    })
    .catch(error => {
        console.error('Erro ao carregar formulário:', error);
        this.mostrarErro('Erro ao carregar formulário de informações.');
    });
};

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    if (window.simuladorFrontend) {
        window.simuladorFrontend.etapasAvancadas = new EtapasAvancadas(window.simuladorFrontend);
    }
});

// assets/js/relatorios-final.js

class RelatoriosManager {
    constructor(simuladorFrontend) {
        this.frontend = simuladorFrontend;
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Formulário de informações
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'form-informacoes') {
                e.preventDefault();
                this.processarRelatorioCompleto();
            }
        });
    }

    async processarRelatorioCompleto() {
        this.frontend.mostrarLoading(true);

        try {
            const dadosCompletos = this.coletarDadosCompletos();
            const resultado = await this.salvarRelatorioCompleto(dadosCompletos);
            
            this.mostrarSucessoRelatorio(resultado);
            
            // Enviar email automaticamente
            if (resultado.email_enviado) {
                this.mostrarSucesso('Relatório enviado por email com sucesso!');
            }

        } catch (error) {
            this.mostrarErro('Erro ao gerar relatório: ' + error);
        } finally {
            this.frontend.mostrarLoading(false);
        }
    }

    coletarDadosCompletos() {
        return {
            // Dados da simulação
            ...this.frontend.dadosSimulacao,
            
            // Dados do formulário de informações
            nome_empreendimento: this.obterNomeEmpreendimento(),
            unidade: document.getElementById('unidade').value,
            nome_corretor: document.getElementById('nome_corretor').value,
            email_corretor: document.getElementById('email_corretor').value,
            empresa: document.getElementById('empresa').value,
            creci: document.getElementById('creci').value,
            nome_cliente: document.getElementById('nome_cliente').value,
            email_cliente: document.getElementById('email_cliente').value,
            telefone_corretor: document.getElementById('telefone_cliente').value,
            observacoes: document.getElementById('observacoes').value,
            
            // Metadata
            data_geracao: new Date().toLocaleString('pt-BR')
        };
    }

    obterNomeEmpreendimento() {
        const select = document.getElementById('nome_empreendimento');
        if (select.value === 'outro') {
            return document.getElementById('outro_empreendimento').value;
        }
        return select.value;
    }

    async salvarRelatorioCompleto(dadosCompletos) {
        const response = await fetch(simulador_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'salvar_relatorio_completo',
                dados: JSON.stringify(dadosCompletos),
                nonce: simulador_ajax.nonce
            })
        });

        const resultado = await response.json();

        if (!resultado.success) {
            throw new Error(resultado.data);
        }

        return resultado.data;
    }

    mostrarSucessoRelatorio(dados) {
        const html = `
            <div class="sucesso-relatorio">
                <div class="sucesso-header">
                    <div class="sucesso-icon">🎉</div>
                    <h2>Relatório Gerado com Sucesso!</h2>
                </div>
                
                <div class="sucesso-content">
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Número do Relatório:</strong>
                            <span>#${dados.relatorio_id}</span>
                        </div>
                        <div class="info-item">
                            <strong>Cliente:</strong>
                            <span>${this.frontend.dadosSimulacao.informacoes?.nome_cliente}</span>
                        </div>
                        <div class="info-item">
                            <strong>Data de Geração:</strong>
                            <span>${new Date().toLocaleString('pt-BR')}</span>
                        </div>
                    </div>

                    <div class="acoes-relatorio">
                        <button onclick="relatoriosManager.downloadPDF(${dados.relatorio_id})" class="btn-download">
                            📥 Download do PDF
                        </button>
                        <button onclick="relatoriosManager.reenviarEmail(${dados.relatorio_id})" class="btn-email">
                            📧 Reenviar por Email
                        </button>
                        <button onclick="relatoriosManager.novaSimulacao()" class="btn-nova">
                            🔄 Nova Simulação
                        </button>
                    </div>

                    <div class="info-adicional">
                        <p>✅ Relatório salvo em nosso sistema</p>
                        <p>✅ PDF gerado e disponível para download</p>
                        ${dados.email_enviado ? '<p>✅ Email enviado para o cliente e corretor</p>' : ''}
                    </div>
                </div>
            </div>
        `;

        document.getElementById('etapa-4').innerHTML = html;
    }

    async downloadPDF(relatorioId) {
        this.frontend.mostrarLoading(true);

        try {
            const response = await fetch(simulador_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'gerar_pdf_relatorio',
                    id: relatorioId,
                    nonce: simulador_ajax.nonce
                })
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `relatorio-${relatorioId}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.mostrarSucesso('PDF baixado com sucesso!');
            } else {
                throw new Error('Erro ao baixar PDF');
            }
        } catch (error) {
            this.mostrarErro('Erro ao baixar PDF: ' + error);
        } finally {
            this.frontend.mostrarLoading(false);
        }
    }

    async reenviarEmail(relatorioId) {
        if (!confirm('Deseja reenviar o relatório por email?')) {
            return;
        }

        this.frontend.mostrarLoading(true);

        try {
            const response = await fetch(simulador_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'enviar_email_relatorio',
                    relatorio_id: relatorioId,
                    nonce: simulador_ajax.nonce
                })
            });

            const resultado = await response.json();

            if (resultado.success) {
                this.mostrarSucesso('Email reenviado com sucesso!');
            } else {
                throw new Error(resultado.data);
            }
        } catch (error) {
            this.mostrarErro('Erro ao reenviar email: ' + error);
        } finally {
            this.frontend.mostrarLoading(false);
        }
    }

    novaSimulacao() {
        location.reload(); // Recarrega a página para nova simulação
    }

    mostrarSucesso(mensagem) {
        alert(mensagem); // Em produção, usar toast notification
    }

    mostrarErro(mensagem) {
        alert('Erro: ' + mensagem); // Em produção, usar toast notification
    }
}

// CSS adicional para a tela de sucesso
const estiloRelatorios = `
    .sucesso-relatorio {
        text-align: center;
        padding: 40px 20px;
    }
    
    .sucesso-header {
        margin-bottom: 30px;
    }
    
    .sucesso-icon {
        font-size: 80px;
        margin-bottom: 20px;
    }
    
    .sucesso-header h2 {
        color: #28a745;
        margin-bottom: 10px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 30px 0;
        text-align: left;
    }
    
    .info-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #007cba;
    }
    
    .info-item strong {
        display: block;
        color: #333;
        margin-bottom: 5px;
    }
    
    .acoes-relatorio {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin: 30px 0;
        flex-wrap: wrap;
    }
    
    .btn-download, .btn-email, .btn-nova {
        padding: 15px 25px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 16px;
    }
    
    .btn-download {
        background: #007cba;
        color: white;
    }
    
    .btn-email {
        background: #28a745;
        color: white;
    }
    
    .btn-nova {
        background: #6c757d;
        color: white;
    }
    
    .btn-download:hover, .btn-email:hover, .btn-nova:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    .info-adicional {
        margin-top: 30px;
        padding: 20px;
        background: #e8f5e8;
        border-radius: 8px;
        border: 1px solid #28a745;
    }
    
    .info-adicional p {
        margin: 5px 0;
        color: #2e7d32;
    }
`;

// Adicionar CSS ao documento
const styleSheet = document.createElement('style');
styleSheet.textContent = estiloRelatorios;
document.head.appendChild(styleSheet);

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    if (window.simuladorFrontend) {
        window.relatoriosManager = new RelatoriosManager(window.simuladorFrontend);
    }
});

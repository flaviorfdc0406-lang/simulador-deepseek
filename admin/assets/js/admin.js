// admin/assets/js/admin.js

class SimuladorAdmin {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initCharts();
        this.initDataTables();
    }

    bindEvents() {
        // Modal de modalidades
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('close') || e.target.closest('.simulador-modal')) {
                this.fecharModal(e.target.closest('.simulador-modal'));
            }
        });

        // Formulário de modalidade
        const formModalidade = document.getElementById('formModalidade');
        if (formModalidade) {
            formModalidade.addEventListener('submit', (e) => this.salvarModalidade(e));
        }

        // Filtros de relatórios
        const formFiltros = document.getElementById('filtrosRelatorios');
        if (formFiltros) {
            formFiltros.addEventListener('submit', (e) => this.filtrarRelatorios(e));
        }
    }

    initCharts() {
        if (typeof chartData !== 'undefined') {
            this.renderChartSimulacoesMensais();
            this.renderChartModalidades();
        }
    }

    initDataTables() {
        // Inicializar DataTables se existir
        if (jQuery().DataTable) {
            jQuery('#tabelaRelatorios').DataTable({
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
                }
            });
        }
    }

    renderChartSimulacoesMensais() {
        const ctx = document.getElementById('chartSimulacoesMensais').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.simulacoesMensais.meses,
                datasets: [{
                    label: 'Simulações',
                    data: chartData.simulacoesMensais.quantidades,
                    borderColor: '#007cba',
                    backgroundColor: 'rgba(0, 124, 186, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    renderChartModalidades() {
        const ctx = document.getElementById('chartModalidades').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.modalidades.nomes,
                datasets: [{
                    data: chartData.modalidades.quantidades,
                    backgroundColor: [
                        '#007cba', '#46b450', '#ffb900', 
                        '#dc3232', '#826eb4', '#00a0d2'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // MODALIDADES
    abrirModalNovaModalidade() {
        document.getElementById('modalModalidadeTitulo').textContent = 'Nova Modalidade';
        document.getElementById('formModalidade').reset();
        document.getElementById('modalidade_id').value = '';
        this.abrirModal('modalModalidade');
    }

    async editarModalidade(id) {
        try {
            const response = await this.ajaxRequest('get_modalidade', { id });
            const modalidade = response.data;
            
            document.getElementById('modalModalidadeTitulo').textContent = 'Editar Modalidade';
            document.getElementById('modalidade_id').value = modalidade.id;
            document.getElementById('modalidade_nome').value = modalidade.nome;
            document.getElementById('modalidade_taxa_juros').value = modalidade.taxa_juros;
            document.getElementById('modalidade_limite_financiamento').value = modalidade.limite_financiamento;
            document.getElementById('modalidade_limite_renda').value = modalidade.limite_renda;
            document.getElementById('modalidade_valor_subsidio').value = modalidade.valor_subsidio;
            document.getElementById('modalidade_faixa_renda').value = modalidade.faixa_renda;
            document.getElementById('modalidade_descricao').value = modalidade.descricao;
            document.getElementById('modalidade_ativo').checked = modalidade.ativo;
            
            this.abrirModal('modalModalidade');
        } catch (error) {
            this.mostrarErro('Erro ao carregar modalidade: ' + error);
        }
    }

    async salvarModalidade(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const dados = Object.fromEntries(formData);
        
        try {
            await this.ajaxRequest('salvar_modalidade', dados);
            this.mostrarSucesso('Modalidade salva com sucesso!');
            this.fecharModal('modalModalidade');
            location.reload(); // Recarregar para atualizar a lista
        } catch (error) {
            this.mostrarErro('Erro ao salvar modalidade: ' + error);
        }
    }

    async toggleModalidade(id, ativo) {
        if (!confirm(`Tem certeza que deseja ${ativo ? 'ativar' : 'desativar'} esta modalidade?`)) {
            return;
        }

        try {
            await this.ajaxRequest('toggle_modalidade', { id, ativo });
            this.mostrarSucesso(`Modalidade ${ativo ? 'ativada' : 'desativada'} com sucesso!`);
            location.reload();
        } catch (error) {
            this.mostrarErro('Erro ao alterar status da modalidade: ' + error);
        }
    }

    async excluirModalidade(id) {
        if (!confirm('Tem certeza que deseja excluir esta modalidade? Esta ação não pode ser desfeita.')) {
            return;
        }

        try {
            await this.ajaxRequest('excluir_modalidade', { id });
            this.mostrarSucesso('Modalidade excluída com sucesso!');
            location.reload();
        } catch (error) {
            this.mostrarErro('Erro ao excluir modalidade: ' + error);
        }
    }

    // RELATÓRIOS
    async filtrarRelatorios(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const filtros = Object.fromEntries(formData);
        
        try {
            const response = await this.ajaxRequest('filtrar_relatorios', filtros);
            this.atualizarTabelaRelatorios(response.data);
            this.mostrarSucesso('Filtros aplicados!');
        } catch (error) {
            this.mostrarErro('Erro ao filtrar relatórios: ' + error);
        }
    }

    atualizarTabelaRelatorios(relatorios) {
        const tbody = document.querySelector('#tabelaRelatorios tbody');
        const contador = document.getElementById('contadorRelatorios');
        
        if (relatorios.length === 0) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center">Nenhum relatório encontrado</td></tr>';
        } else {
            tbody.innerHTML = relatorios.map(relatorio => `
                <tr data-relatorio-id="${relatorio.id}">
                    <td>${this.formatarData(relatorio.created_at)}</td>
                    <td>${this.escapeHtml(relatorio.nome_cliente)}</td>
                    <td>${this.escapeHtml(relatorio.nome_corretor)}</td>
                    <td>${this.escapeHtml(relatorio.email_corretor)}</td>
                    <td>${this.escapeHtml(relatorio.creci)}</td>
                    <td>${this.escapeHtml(relatorio.empresa)}</td>
                    <td>${this.escapeHtml(relatorio.nome_empreendimento)}</td>
                    <td>R$ ${this.formatarMoeda(relatorio.valor_imovel)}</td>
                    <td>R$ ${this.formatarMoeda(relatorio.parcela_calculada)}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="button button-small" onclick="verDetalhesRelatorio(${relatorio.id})">
                                👁️ Detalhes
                            </button>
                            <button class="button button-small button-primary" onclick="gerarPDFRelatorio(${relatorio.id})">
                                📥 PDF
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        contador.textContent = `Mostrando ${relatorios.length} relatórios`;
    }

    async verDetalhesRelatorio(id) {
        try {
            const response = await this.ajaxRequest('get_detalhes_relatorio', { id });
            document.getElementById('modalRelatorioContent').innerHTML = response.data;
            this.abrirModal('modalRelatorio');
        } catch (error) {
            this.mostrarErro('Erro ao carregar detalhes: ' + error);
        }
    }

    async gerarPDFRelatorio(id) {
        this.mostrarLoading(true);
        
        try {
            const response = await fetch(simulador_admin.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'gerar_pdf_relatorio',
                    id: id,
                    nonce: simulador_admin.nonce
                })
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `relatorio-${id}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } else {
                throw new Error('Erro ao gerar PDF');
            }
        } catch (error) {
            this.mostrarErro('Erro ao gerar PDF: ' + error);
        } finally {
            this.mostrarLoading(false);
        }
    }

    async exportarTodosRelatorios() {
        this.mostrarLoading(true);
        
        try {
            const response = await this.ajaxRequest('exportar_relatorios', {});
            
            // Criar e baixar arquivo CSV
            const blob = new Blob([response.data], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `relatorios-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            this.mostrarSucesso('Relatórios exportados com sucesso!');
        } catch (error) {
            this.mostrarErro('Erro ao exportar relatórios: ' + error);
        } finally {
            this.mostrarLoading(false);
        }
    }

    // UTILITÁRIOS
    async ajaxRequest(acao, dados) {
        const response = await fetch(simulador_admin.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'simulador_admin_action',
                acao: acao,
                dados: JSON.stringify(dados),
                nonce: simulador_admin.nonce
            })
        });

        const resultado = await response.json();

        if (!resultado.success) {
            throw new Error(resultado.data);
        }

        return resultado;
    }

    abrirModal(id) {
        document.getElementById(id).style.display = 'flex';
    }

    fecharModal(modal) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        modal.style.display = 'none';
    }

    mostrarSucesso(mensagem) {
        // Usar notificações do WordPress
        const notice = document.createElement('div');
        notice.className = 'notice notice-success is-dismissible';
        notice.innerHTML = `<p>${mensagem}</p>`;
        document.querySelector('.wrap').insertBefore(notice, document.querySelector('.wrap').firstChild);
        
        setTimeout(() => notice.remove(), 5000);
    }

    mostrarErro(mensagem) {
        const notice = document.createElement('div');
        notice.className = 'notice notice-error is-dismissible';
        notice.innerHTML = `<p>${mensagem}</p>`;
        document.querySelector('.wrap').insertBefore(notice, document.querySelector('.wrap').firstChild);
        
        setTimeout(() => notice.remove(), 5000);
    }

    mostrarLoading(mostrar) {
        // Implementar loading global
    }

    formatarData(data) {
        return new Date(data).toLocaleDateString('pt-BR');
    }

    formatarMoeda(valor) {
        return parseFloat(valor).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.simuladorAdmin = new SimuladorAdmin();
});

// Funções globais para os onclick
function abrirModalNovaModalidade() {
    window.simuladorAdmin.abrirModalNovaModalidade();
}

function editarModalidade(id) {
    window.simuladorAdmin.editarModalidade(id);
}

function toggleModalidade(id, ativo) {
    window.simuladorAdmin.toggleModalidade(id, ativo);
}

function excluirModalidade(id) {
    window.simuladorAdmin.excluirModalidade(id);
}

function fecharModalModalidade() {
    window.simuladorAdmin.fecharModal('modalModalidade');
}

function verDetalhesRelatorio(id) {
    window.simuladorAdmin.verDetalhesRelatorio(id);
}

function gerarPDFRelatorio(id) {
    window.simuladorAdmin.gerarPDFRelatorio(id);
}

function exportarTodosRelatorios() {
    window.simuladorAdmin.exportarTodosRelatorios();
}

function limparFiltros() {
    document.getElementById('filtrosRelatorios').reset();
    window.simuladorAdmin.filtrarRelatorios({ preventDefault: () => {} });
}

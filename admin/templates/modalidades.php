<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap simulador-admin">
    <h1 class="wp-heading-inline">🏦 Gerenciar Modalidades</h1>
    <button class="page-title-action" onclick="abrirModalNovaModalidade()">+ Adicionar Modalidade</button>
    
    <div class="simulador-card">
        <div class="card-header">
            <h3>Modalidades Cadastradas</h3>
        </div>
        
        <div class="table-responsive">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Taxa de Juros</th>
                        <th>Limite Financiamento</th>
                        <th>Limite Renda</th>
                        <th>Subsídio</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($modalidades)): ?>
                        <tr><td colspan="7" class="text-center">Nenhuma modalidade cadastrada</td></tr>
                    <?php else: foreach ($modalidades as $modalidade): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($modalidade->nome); ?></strong>
                            <?php if ($modalidade->descricao): ?>
                                <br><small class="text-muted"><?php echo esc_html($modalidade->descricao); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($modalidade->taxa_juros, 2, ',', '.'); ?>% a.a.</td>
                        <td>R$ <?php echo number_format($modalidade->limite_financiamento, 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($modalidade->limite_renda, 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($modalidade->valor_subsidio, 2, ',', '.'); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $modalidade->ativo ? 'success' : 'danger'; ?>">
                                <?php echo $modalidade->ativo ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="button button-small" onclick="editarModalidade(<?php echo $modalidade->id; ?>)">✏️ Editar</button>
                            <button class="button button-small <?php echo $modalidade->ativo ? 'button-secondary' : 'button-primary'; ?>" 
                                    onclick="toggleModalidade(<?php echo $modalidade->id; ?>, <?php echo $modalidade->ativo ? 0 : 1; ?>)">
                                <?php echo $modalidade->ativo ? '❌ Desativar' : '✅ Ativar'; ?>
                            </button>
                            <button class="button button-small button-danger" 
                                    onclick="excluirModalidade(<?php echo $modalidade->id; ?>)">
                                🗑️ Excluir
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Formulário -->
<div id="modalModalidade" class="simulador-modal" style="display: none;">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="modalModalidadeTitulo">Nova Modalidade</h3>
            <span class="close">&times;</span>
        </div>
        <form id="formModalidade" class="modal-body">
            <input type="hidden" id="modalidade_id" name="id" value="">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="modalidade_nome">Nome da Modalidade *</label>
                    <input type="text" id="modalidade_nome" name="nome" required placeholder="Ex: Minha Casa Minha Vida - Faixa 1">
                </div>
                
                <div class="form-group">
                    <label for="modalidade_taxa_juros">Taxa de Juros (% a.a.) *</label>
                    <input type="number" id="modalidade_taxa_juros" name="taxa_juros" step="0.1" min="0" max="20" required placeholder="8.5">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="modalidade_limite_financiamento">Limite de Financiamento (R$) *</label>
                    <input type="number" id="modalidade_limite_financiamento" name="limite_financiamento" required placeholder="350000">
                </div>
                
                <div class="form-group">
                    <label for="modalidade_limite_renda">Limite de Renda (R$) *</label>
                    <input type="number" id="modalidade_limite_renda" name="limite_renda" required placeholder="8000">
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="modalidade_valor_subsidio">Valor do Subsídio (R$)</label>
                    <input type="number" id="modalidade_valor_subsidio" name="valor_subsidio" placeholder="0">
                </div>
                
                <div class="form-group">
                    <label for="modalidade_faixa_renda">Faixa de Renda para Subsídio</label>
                    <input type="text" id="modalidade_faixa_renda" name="faixa_renda" placeholder="Ex: Até R$ 2.000">
                </div>
            </div>
            
            <div class="form-group">
                <label for="modalidade_descricao">Descrição</label>
                <textarea id="modalidade_descricao" name="descricao" rows="3" placeholder="Descrição detalhada da modalidade..."></textarea>
            </div>
            
            <div class="form-group">
                <label><input type="checkbox" id="modalidade_ativo" name="ativo" value="1" checked> Modalidade ativa</label>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="button button-secondary" onclick="fecharModalModalidade()">Cancelar</button>
                <button type="submit" class="button button-primary">Salvar Modalidade</button>
            </div>
        </form>
    </div>
</div>

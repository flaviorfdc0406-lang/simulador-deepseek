<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap simulador-admin">
    <h1 class="wp-heading-inline">📋 Relatórios Gerados</h1>
    <button class="page-title-action" onclick="exportarTodosRelatorios()">📥 Exportar CSV</button>
    
    <!-- Filtros -->
    <div class="simulador-card">
        <div class="card-header">
            <h3>Filtros</h3>
        </div>
        <div class="card-body">
            <form id="filtrosRelatorios" class="form-inline">
                <div class="form-group">
                    <label for="filtro_data_inicio">Data Início:</label>
                    <input type="date" id="filtro_data_inicio" name="data_inicio">
                </div>
                
                <div class="form-group">
                    <label for="filtro_data_fim">Data Fim:</label>
                    <input type="date" id="filtro_data_fim" name="data_fim">
                </div>
                
                <div class="form-group">
                    <label for="filtro_empreendimento">Empreendimento:</label>
                    <select id="filtro_empreendimento" name="empreendimento">
                        <option value="">Todos</option>
                        <?php foreach ($filtros['empreendimentos'] as $empreendimento): ?>
                            <option value="<?php echo esc_attr($empreendimento); ?>"><?php echo esc_html($empreendimento); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="filtro_corretor">Corretor:</label>
                    <select id="filtro_corretor" name="corretor">
                        <option value="">Todos</option>
                        <?php foreach ($filtros['corretores'] as $corretor): ?>
                            <option value="<?php echo esc_attr($corretor); ?>"><?php echo esc_html($corretor); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="button button-primary">🔍 Filtrar</button>
                <button type="button" class="button button-secondary" onclick="limparFiltros()">🔄 Limpar</button>
            </form>
        </div>
    </div>

    <!-- Lista de Relatórios -->
    <div class="simulador-card">
        <div class="card-header">
            <h3>Relatórios (<?php echo count($relatorios); ?>)</h3>
            <div class="card-actions">
                <span id="contadorRelatorios">Mostrando <?php echo count($relatorios); ?> relatórios</span>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="wp-list-table widefat fixed striped" id="tabelaRelatorios">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Corretor</th>
                        <th>Email</th>
                        <th>CRECI</th>
                        <th>Empresa</th>
                        <th>Empreendimento</th>
                        <th>Valor do Imóvel</th>
                        <th>Parcela</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($relatorios)): ?>
                        <tr><td colspan="10" class="text-center">Nenhum relatório encontrado</td></tr>
                    <?php else: foreach ($relatorios as $relatorio): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($relatorio->created_at)); ?></td>
                        <td><?php echo esc_html($relatorio->nome_cliente); ?></td>
                        <td><?php echo esc_html($relatorio->nome_corretor); ?></td>
                        <td><?php echo esc_html($relatorio->email_corretor); ?></td>
                        <td><?php echo esc_html($relatorio->creci); ?></td>
                        <td><?php echo esc_html($relatorio->empresa); ?></td>
                        <td><?php echo esc_html($relatorio->nome_empreendimento); ?></td>
                        <td>R$ <?php echo number_format($relatorio->valor_imovel, 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($relatorio->parcela_calculada, 2, ',', '.'); ?></td>
                        <td>
                            <button class="button button-small" onclick="verDetalhesRelatorio(<?php echo $relatorio->id; ?>)">👁️ Detalhes</button>
                            <button class="button button-small button-primary" onclick="gerarPDFRelatorio(<?php echo $relatorio->id; ?>)">📥 PDF</button>
                            <button class="button button-small button-secondary" onclick="reenviarEmail(<?php echo $relatorio->id; ?>)">📧 Email</button>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

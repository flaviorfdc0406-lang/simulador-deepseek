<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap simulador-admin">
    <h1 class="wp-heading-inline">📊 Dashboard - Simulador de Financiamento</h1>
    
    <!-- Estatísticas -->
    <div class="simulador-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($estatisticas['total_simulacoes']); ?></div>
                <div class="stat-label">Total de Simulações</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($estatisticas['total_relatorios']); ?></div>
                <div class="stat-label">Relatórios Gerados</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($estatisticas['simulacoes_hoje']); ?></div>
                <div class="stat-label">Simulações Hoje</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👔</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($estatisticas['corretores_ativos']); ?></div>
                <div class="stat-label">Corretores Ativos</div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="simulador-charts-row">
        <div class="chart-container">
            <h3>📈 Simulações por Mês</h3>
            <div class="chart-wrapper">
                <canvas id="chartSimulacoesMensais" height="250"></canvas>
            </div>
        </div>
        
        <div class="chart-container">
            <h3>🏦 Modalidades Mais Usadas</h3>
            <div class="chart-wrapper">
                <canvas id="chartModalidades" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Últimas Simulações -->
    <div class="simulador-card">
        <div class="card-header">
            <h3>🕒 Últimas Simulações</h3>
            <a href="<?php echo admin_url('admin.php?page=simulador-relatorios'); ?>" class="button">Ver Todos</a>
        </div>
        
        <div class="table-responsive">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Corretor</th>
                        <th>Tipo</th>
                        <th>Valor do Imóvel</th>
                        <th>Parcela</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimas_simulacoes)): ?>
                        <tr><td colspan="6" class="text-center">Nenhuma simulação encontrada</td></tr>
                    <?php else: foreach ($ultimas_simulacoes as $simulacao): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($simulacao->created_at)); ?></td>
                        <td><?php echo esc_html($simulacao->nome_cliente ?: 'N/A'); ?></td>
                        <td><?php echo esc_html($simulacao->nome_corretor ?: 'N/A'); ?></td>
                        <td><span class="badge badge-<?php echo $simulacao->tipo_simulacao; ?>"><?php echo ucfirst(str_replace('_', ' ', $simulacao->tipo_simulacao)); ?></span></td>
                        <td>R$ <?php echo number_format($simulacao->valor_imovel, 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($simulacao->parcela_calculada, 2, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Relatórios Recentes -->
    <div class="simulador-card">
        <div class="card-header">
            <h3>📄 Relatórios Recentes</h3>
            <a href="<?php echo admin_url('admin.php?page=simulador-relatorios'); ?>" class="button">Ver Todos</a>
        </div>
        
        <div class="table-responsive">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Corretor</th>
                        <th>Empreendimento</th>
                        <th>Empresa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($relatorios_recentes)): ?>
                        <tr><td colspan="5" class="text-center">Nenhum relatório encontrado</td></tr>
                    <?php else: foreach ($relatorios_recentes as $relatorio): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($relatorio->created_at)); ?></td>
                        <td><?php echo esc_html($relatorio->nome_cliente); ?></td>
                        <td><?php echo esc_html($relatorio->nome_corretor); ?></td>
                        <td><?php echo esc_html($relatorio->nome_empreendimento); ?></td>
                        <td><?php echo esc_html($relatorio->empresa); ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const chartData = {
    simulacoesMensais: {
        meses: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
        quantidades: [45, 52, 48, 65, 72, 68]
    },
    modalidades: {
        nomes: ['SBPE', 'MCMV', 'FGTS'],
        quantidades: [120, 85, 45]
    }
};
</script>

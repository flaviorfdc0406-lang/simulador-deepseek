<?php
class SimuladorPDFGenerator {
    
    private $config;
    
    public function __construct() {
        $this->config = get_option('simulador_configuracoes', array());
    }
    
    public function gerar_relatorio_pdf($dados_completos) {
        // Em produção, usar TCPDF ou Dompdf
        // Aqui vamos gerar um HTML que pode ser convertido para PDF
        return $this->gerar_html_relatorio($dados_completos);
    }
    
    private function gerar_html_relatorio($dados) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Relatório de Simulação - Living & Vivaz</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
                .header { text-align: center; margin-bottom: 30px; padding: 20px; background: #007cba; color: white; }
                .section { margin-bottom: 25px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
                .destaque { font-size: 24px; font-weight: bold; color: #007cba; text-align: center; margin: 15px 0; }
                .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
                .card { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
                th { background: #007cba; color: white; }
                .compativel { color: #28a745; font-weight: bold; }
                .alerta { color: #ffc107; font-weight: bold; }
                .footer { margin-top: 30px; padding: 15px; background: #343a40; color: white; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Relatório de Simulação de Financiamento</h1>
                <h2>Living & Vivaz - Cyrela Brazil Realty</h2>
            </div>

            <!-- Dados do Cliente -->
            <div class="section">
                <h3>👤 Dados do Cliente</h3>
                <div class="grid-2">
                    <div><strong>Nome:</strong> <?php echo esc_html($dados['nome_cliente']); ?></div>
                    <div><strong>Email:</strong> <?php echo esc_html($dados['email_cliente'] ?? 'Não informado'); ?></div>
                    <div><strong>Empreendimento:</strong> <?php echo esc_html($dados['nome_empreendimento']); ?></div>
                    <div><strong>Unidade:</strong> <?php echo esc_html($dados['unidade']); ?></div>
                </div>
            </div>

            <!-- Resumo Financeiro -->
            <div class="section">
                <h3>💰 Resumo Financeiro</h3>
                <div class="destaque">
                    Parcela Mensal: R$ <?php echo number_format($dados['parcela_calculada'], 2, ',', '.'); ?>
                </div>
                <div class="grid-2">
                    <div class="card">
                        <strong>Valor do Imóvel:</strong><br>
                        R$ <?php echo number_format($dados['valor_imovel'], 2, ',', '.'); ?>
                    </div>
                    <div class="card">
                        <strong>Valor Financiado:</strong><br>
                        R$ <?php echo number_format($dados['valor_financiado'], 2, ',', '.'); ?>
                    </div>
                    <div class="card">
                        <strong>Entrada:</strong><br>
                        R$ <?php echo number_format($dados['entrada'] ?? 0, 2, ',', '.'); ?>
                    </div>
                    <div class="card">
                        <strong>Prazo:</strong><br>
                        <?php echo $dados['prazo_meses']; ?> meses
                    </div>
                </div>
            </div>

            <!-- Detalhes do Financiamento -->
            <div class="section">
                <h3>📊 Detalhes do Financiamento</h3>
                <div class="grid-2">
                    <div><strong>Modalidade:</strong> <?php echo esc_html($dados['modalidade_nome'] ?? 'Taxa Personalizada'); ?></div>
                    <div><strong>Taxa de Juros:</strong> <?php echo number_format($dados['taxa_juros'], 2, ',', '.'); ?>% a.a.</div>
                    <div><strong>Sistema:</strong> <?php echo $dados['tipo_amortizacao'] == 'sac' ? 'Tabela SAC' : 'Tabela Price'; ?></div>
                    <div><strong>Data da Simulação:</strong> <?php echo $dados['data_geracao']; ?></div>
                </div>
            </div>

            <?php if (isset($dados['compatibilidade_renda'])): ?>
            <!-- Análise de Compatibilidade -->
            <div class="section">
                <h3>📈 Análise de Compatibilidade</h3>
                <?php $comp = $dados['compatibilidade_renda']; ?>
                <div class="<?php echo $comp['compativel'] ? 'compativel' : 'alerta'; ?>">
                    Status: <?php echo $comp['compativel'] ? '✅ COMPATÍVEL' : '⚠️ ANÁLISE RECOMENDADA'; ?>
                </div>
                <p><strong>Parcela/Renda:</strong> <?php echo $comp['percentual']; ?>%</p>
                <p><strong>Limite Ideal (30%):</strong> R$ <?php echo number_format($comp['limite_ideal'], 2, ',', '.'); ?></p>
            </div>
            <?php endif; ?>

            <!-- Primeiras Parcelas -->
            <div class="section">
                <h3>📅 Primeiras Parcelas</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <th>Parcela</th>
                            <th>Amortização</th>
                            <th>Juros</th>
                            <th>Saldo Devedor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $parcelas = array_slice($dados['parcelas'] ?? [], 0, 12);
                        foreach ($parcelas as $parcela): 
                        ?>
                        <tr>
                            <td><?php echo $parcela['mes']; ?></td>
                            <td>R$ <?php echo number_format($parcela['parcela'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($parcela['amortizacao'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($parcela['juros'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($parcela['saldo_devedor'], 2, ',', '.'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Dados do Corretor -->
            <div class="section">
                <h3>👔 Dados do Corretor</h3>
                <div class="grid-2">
                    <div><strong>Nome:</strong> <?php echo esc_html($dados['nome_corretor']); ?></div>
                    <div><strong>Email:</strong> <?php echo esc_html($dados['email_corretor']); ?></div>
                    <div><strong>Empresa:</strong> <?php echo esc_html($dados['empresa']); ?></div>
                    <div><strong>CRECI:</strong> <?php echo esc_html($dados['creci']); ?></div>
                    <div><strong>Telefone:</strong> <?php echo esc_html($dados['telefone_corretor'] ?? '(21) 3000-0000'); ?></div>
                </div>
            </div>

            <?php if (!empty($dados['observacoes'])): ?>
            <!-- Observações -->
            <div class="section">
                <h3>📝 Observações</h3>
                <p><?php echo nl2br(esc_html($dados['observacoes'])); ?></p>
            </div>
            <?php endif; ?>

            <div class="footer">
                <p>Relatório gerado automaticamente em <?php echo date('d/m/Y H:i'); ?></p>
                <p>Living & Vivaz - Av. das Américas, 500 - Barra da Tijuca, Rio de Janeiro - RJ</p>
                <p>Tel: (21) 3000-0000 | www.livingvivaz.com.br</p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    public function gerar_pdf_simplificado($dados) {
        return $this->gerar_html_relatorio($dados); // Usar mesma base por enquanto
    }
}
?>

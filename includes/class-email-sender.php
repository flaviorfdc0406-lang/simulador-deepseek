<?php
class SimuladorEmailSender {
    
    private $pdf_generator;
    
    public function __construct() {
        $this->pdf_generator = new SimuladorPDFGenerator();
    }
    
    public function enviar_relatorio_completo($dados_completos) {
        $emails = array_filter([
            $dados_completos['email_corretor'],
            $dados_completos['email_cliente'] ?? null
        ]);
        
        $assunto = $this->gerar_assunto_email($dados_completos);
        $mensagem = $this->gerar_corpo_email($dados_completos);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: Living & Vivaz <nao-responda@livingvivaz.com.br>',
            'Reply-To: ' . $dados_completos['email_corretor']
        ];
        
        $sucesso = true;
        foreach ($emails as $email) {
            $resultado = wp_mail($email, $assunto, $mensagem, $headers);
            $sucesso = $sucesso && $resultado;
        }
        
        return $sucesso;
    }
    
    private function gerar_assunto_email($dados) {
        $configuracoes = get_option('simulador_configuracoes', array());
        $assunto_padrao = $configuracoes['assunto_email'] ?? 'Relatório de Simulação - Living & Vivaz';
        
        return str_replace(
            ['{cliente}', '{empreendimento}', '{corretor}'],
            [$dados['nome_cliente'], $dados['nome_empreendimento'], $dados['nome_corretor']],
            $assunto_padrao
        );
    }
    
    private function gerar_corpo_email($dados) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #007cba, #005a87); color: white; padding: 30px; text-align: center; }
                .content { padding: 20px; }
                .card { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 15px 0; border-left: 4px solid #007cba; }
                .destaque { font-size: 24px; font-weight: bold; color: #007cba; text-align: center; margin: 20px 0; }
                .btn { display: inline-block; background: #007cba; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
                .footer { background: #343a40; color: white; padding: 20px; text-align: center; font-size: 12px; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📊 Relatório de Simulação</h1>
                <p>Living & Vivaz - Cyrela Brazil Realty</p>
            </div>
            
            <div class="content">
                <p>Olá <strong><?php echo esc_html($dados['nome_cliente']); ?></strong>,</p>
                
                <p>Segue o resumo da simulação de financiamento para o empreendimento <strong><?php echo esc_html($dados['nome_empreendimento']); ?></strong>.</p>
                
                <div class="card">
                    <h3 style="margin-top: 0;">📋 Resumo da Simulação</h3>
                    <p><strong>Valor do Imóvel:</strong> R$ <?php echo number_format($dados['valor_imovel'], 2, ',', '.'); ?></p>
                    <div class="destaque">Parcela Mensal: R$ <?php echo number_format($dados['parcela_calculada'], 2, ',', '.'); ?></div>
                    <p><strong>Prazo:</strong> <?php echo $dados['prazo_meses']; ?> meses (<?php echo round($dados['prazo_meses'] / 12); ?> anos)</p>
                    <p><strong>Modalidade:</strong> <?php echo $dados['modalidade_nome'] ?? 'Taxa Personalizada'; ?></p>
                </div>
                
                <?php if (isset($dados['compatibilidade_renda'])): ?>
                <div class="card">
                    <h3>💰 Análise de Compatibilidade</h3>
                    <p><strong>Status:</strong> 
                        <span style="color: <?php echo $dados['compatibilidade_renda']['compativel'] ? '#28a745' : '#ffc107'; ?>; font-weight: bold;">
                            <?php echo $dados['compatibilidade_renda']['compativel'] ? '✅ COMPATÍVEL' : '⚠️ ANÁLISE RECOMENDADA'; ?>
                        </span>
                    </p>
                    <p><strong>Parcela/Renda:</strong> <?php echo $dados['compatibilidade_renda']['percentual']; ?>%</p>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <h3>👔 Dados do Corretor</h3>
                    <p><strong>Nome:</strong> <?php echo esc_html($dados['nome_corretor']); ?></p>
                    <p><strong>Email:</strong> <?php echo esc_html($dados['email_corretor']); ?></p>
                    <p><strong>Telefone:</strong> <?php echo esc_html($dados['telefone_corretor'] ?? '(21) 3000-0000'); ?></p>
                    <p><strong>CRECI:</strong> <?php echo esc_html($dados['creci']); ?></p>
                </div>
                
                <p>Para mais informações ou para agendar uma visita, entre em contato com nosso corretor.</p>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="tel:<?php echo esc_attr($dados['telefone_corretor'] ?? '552130000000'); ?>" class="btn">📞 Ligar Agora</a>
                    <a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $dados['telefone_corretor'] ?? '552130000000')); ?>?text=Olá! Gostaria de mais informações sobre a simulação para <?php echo urlencode($dados['nome_empreendimento']); ?>" class="btn">💬 WhatsApp</a>
                </div>
            </div>
            
            <div class="footer">
                <p>Living & Vivaz - Cyrela Brazil Realty</p>
                <p>Av. das Américas, 500 - Barra da Tijuca, Rio de Janeiro - RJ</p>
                <p>Tel: (21) 3000-0000 | www.livingvivaz.com.br</p>
                <p><em>Este é um email automático, por favor não responda.</em></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
?>

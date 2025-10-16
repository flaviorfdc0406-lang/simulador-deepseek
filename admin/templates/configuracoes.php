<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap simulador-admin">
    <h1 class="wp-heading-inline">⚙️ Configurações do Simulador</h1>
    
    <div class="simulador-card">
        <form method="post" action="options.php">
            <?php settings_fields('simulador_configuracoes'); ?>
            
            <div class="form-section">
                <h3>📊 Limites do Simulador</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="limite_maximo_imovel">Limite Máximo do Imóvel (R$)</label>
                        <input type="number" id="limite_maximo_imovel" name="simulador_configuracoes[limite_maximo_imovel]" value="<?php echo esc_attr($configuracoes['limite_maximo_imovel'] ?? '5000000'); ?>" class="regular-text">
                    </div>
                    
                    <div class="form-group">
                        <label for="limite_maximo_renda">Limite Máximo de Renda (R$)</label>
                        <input type="number" id="limite_maximo_renda" name="simulador_configuracoes[limite_maximo_renda]" value="<?php echo esc_attr($configuracoes['limite_maximo_renda'] ?? '50000'); ?>" class="regular-text">
                    </div>
                    
                    <div class="form-group">
                        <label for="prazo_maximo">Prazo Máximo (meses)</label>
                        <input type="number" id="prazo_maximo" name="simulador_configuracoes[prazo_maximo]" value="<?php echo esc_attr($configuracoes['prazo_maximo'] ?? '420'); ?>" class="regular-text">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>📧 Configurações de Email</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="email_recebimento">Email para Recebimento</label>
                        <input type="email" id="email_recebimento" name="simulador_configuracoes[email_recebimento]" value="<?php echo esc_attr($configuracoes['email_recebimento'] ?? 'relatorios@livingvivaz.com.br'); ?>" class="regular-text">
                    </div>
                    
                    <div class="form-group">
                        <label for="assunto_email">Assunto do Email</label>
                        <input type="text" id="assunto_email" name="simulador_configuracoes[assunto_email]" value="<?php echo esc_attr($configuracoes['assunto_email'] ?? 'Relatório de Simulação - Living & Vivaz'); ?>" class="regular-text">
                        <p class="description">Use {cliente}, {empreendimento}, {corretor} como variáveis</p>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>🛠️ Configurações Avançadas</h3>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="simulador_configuracoes[log_ativado]" value="1" <?php checked($configuracoes['log_ativado'] ?? 1); ?>>
                        Ativar logging de simulações
                    </label>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="simulador_configuracoes[analise_renda_obrigatoria]" value="1" <?php checked($configuracoes['analise_renda_obrigatoria'] ?? 0); ?>>
                        Análise de renda obrigatória
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="simulador_save_configuracoes" class="button button-primary">💾 Salvar Configurações</button>
            </div>
        </form>
    </div>

    <!-- Status do Sistema -->
    <div class="simulador-card">
        <h3>🔧 Status do Sistema</h3>
        <div class="system-status">
            <?php
            $database = SimuladorDatabase::get_instance();
            $tables_ok = $database->check_tables();
            ?>
            <div class="status-item <?php echo $tables_ok ? 'status-ok' : 'status-error'; ?>">
                <strong>Tabelas do Banco:</strong> 
                <?php echo $tables_ok ? '✅ Configuradas' : '❌ Problemas detectados'; ?>
            </div>
            
            <div class="status-item status-ok">
                <strong>Versão do Plugin:</strong> 1.0.0
            </div>
            
            <div class="status-item status-ok">
                <strong>WordPress:</strong> <?php echo get_bloginfo('version'); ?>
            </div>
            
            <div class="status-item status-ok">
                <strong>PHP:</strong> <?php echo phpversion(); ?>
           

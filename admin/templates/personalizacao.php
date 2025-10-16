<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap simulador-admin">
    <h1 class="wp-heading-inline">🎨 Personalização do Simulador</h1>
    
    <div class="simulador-card">
        <form method="post" action="options.php" enctype="multipart/form-data">
            <?php settings_fields('simulador_personalizacao'); ?>
            
            <div class="form-section">
                <h3>🎨 Cores e Aparência</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="cor_primaria">Cor Primária</label>
                        <input type="color" id="cor_primaria" name="simulador_personalizacao[cor_primaria]" value="<?php echo esc_attr($configuracoes['cor_primaria'] ?? '#007cba'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="cor_secundaria">Cor Secundária</label>
                        <input type="color" id="cor_secundaria" name="simulador_personalizacao[cor_secundaria]" value="<?php echo esc_attr($configuracoes['cor_secundaria'] ?? '#005a87'); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>🖼️ Logo e Marca</h3>
                <div class="form-group">
                    <label for="logo_upload">Upload de Logo</label>
                    <input type="file" id="logo_upload" name="logo_upload" accept="image/*">
                    <?php if (!empty($configuracoes['logo_url'])): ?>
                        <p>Logo atual: <img src="<?php echo esc_url($configuracoes['logo_url']); ?>" height="40" style="vertical-align: middle;"></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-section">
                <h3>📝 Textos e Conteúdo</h3>
                <div class="form-group">
                    <label for="titulo">Título do Simulador</label>
                    <input type="text" id="titulo" name="simulador_personalizacao[titulo]" value="<?php echo esc_attr($configuracoes['titulo'] ?? 'Simulador de Financiamento'); ?>" class="regular-text">
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="simulador_personalizacao[descricao]" rows="3" class="regular-text"><?php echo esc_textarea($configuracoes['descricao'] ?? 'Calcule sua parcela com condições reais'); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="texto_rodape">Texto do Rodapé</label>
                    <input type="text" id="texto_rodape" name="simulador_personalizacao[texto_rodape]" value="<?php echo esc_attr($configuracoes['texto_rodape'] ?? 'Living & Vivaz - Cyrela Brazil Realty'); ?>" class="regular-text">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="simulador_save_personalizacao" class="button button-primary">💾 Salvar Personalização</button>
            </div>
        </form>
    </div>

    <!-- Preview -->
    <div class="simulador-card">
        <h3>👀 Preview do Simulador</h3>
        <div class="preview-container" style="border: 2px dashed #ddd; padding: 20px; border-radius: 8px;">
            <div style="background: <?php echo esc_attr($configuracoes['cor_primaria'] ?? '#007cba'); ?>; color: white; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
                <h2 style="margin: 0; color: white;"><?php echo esc_html($configuracoes['titulo'] ?? 'Simulador de Financiamento'); ?></h2>
            </div>
            <p><?php echo esc_html($configuracoes['descricao'] ?? 'Calcule sua parcela com condições reais'); ?></p>
            <button style="background: <?php echo esc_attr($configuracoes['cor_primaria'] ?? '#007cba'); ?>; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Calcular Simulação</button>
        </div>
    </div>
</div>

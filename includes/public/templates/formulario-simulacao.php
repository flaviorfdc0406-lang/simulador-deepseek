<?php
// templates/formulario-simulacao.php

// Carregar modalidades do banco de dados
global $wpdb;
$modalidades = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}simulador_modalidades WHERE ativo = 1");

// Configurações do simulador
$configuracoes = get_option('simulador_configuracoes', array(
    'titulo' => 'Simulador de Financiamento Living & Vivaz',
    'descricao' => 'Calcule sua parcela com as condições reais da Caixa Econômica Federal',
    'cor_primaria' => '#007cba'
));
?>

<div id="simulador-financiamento" class="simulador-living" data-cor-primaria="<?php echo esc_attr($configuracoes['cor_primaria']); ?>">
    
    <!-- ETAPA 1: SELEÇÃO DO TIPO -->
    <div id="etapa-1" class="etapa-simulacao etapa-ativa">
        <div class="simulador-header">
            <h1><?php echo esc_html($configuracoes['titulo']); ?></h1>
            <p><?php echo esc_html($configuracoes['descricao']); ?></p>
        </div>
        
        <div class="form-group">
            <label for="tipo_simulacao_select">📊 Tipo de Simulação *</label>
            <select id="tipo_simulacao_select" name="tipo_simulacao" class="select-flutuante" required>
                <option value="">-- Selecione uma opção --</option>
                <option value="valor_imovel">🏠 Simulação por Valor do Imóvel</option>
                <option value="renda_mensal">💰 Simulação por Renda Mensal</option>
                <option value="valor_parcela">📅 Simulação por Valor de Parcela</option>
            </select>
            
            <div class="select-description">
                <div id="descricao-valor_imovel" class="descricao-opcao" style="display: none;">
                    <strong>🏠 Simulação por Valor do Imóvel</strong>
                    <p>Ideal quando você já sabe o valor do imóvel desejado. Calcularemos a parcela mensal e analisaremos a compatibilidade com sua renda.</p>
                </div>
                <div id="descricao-renda_mensal" class="descricao-opcao" style="display: none;">
                    <strong>💰 Simulação por Renda Mensal</strong>
                    <p>Baseado na sua renda, descobrimos o valor máximo do imóvel que você pode adquirir. Perfecto para planejamento financeiro.</p>
                </div>
                <div id="descricao-valor_parcela" class="descricao-opcao" style="display: none;">
                    <strong>📅 Simulação por Valor de Parcela</strong>
                    <p>Se você tem um valor de parcela em mente, calculamos o imóvel que se encaixa no seu orçamento mensal.</p>
                </div>
            </div>
        </div>
        
        <button id="avancar-etapa1" class="btn-avancar" disabled>▶️ Avançar para Preenchimento</button>
    </div>

    <!-- ETAPA 2: FORMULÁRIO DINÂMICO -->
    <div id="etapa-2" class="etapa-simulacao">
        <div class="header-formulario">
            <button id="voltar-etapa1" class="btn-voltar">← Voltar</button>
            <h1><?php echo esc_html($configuracoes['titulo']); ?></h1>
            <h2 id="subtipo-simulacao">Simulação por Valor do Imóvel</h2>
        </div>

        <form id="form-simulacao" class="form-simulacao">
            
            <!-- CAMPOS COMUNS -->
            <div class="secao-formulario">
                <h3>📊 Condições do Financiamento</h3>
                
                <!-- TIPO DE MODALIDADE -->
                <div class="form-group">
                    <label>Tipo de Modalidade *</label>
                    <div class="radio-group-vertical">
                        <label class="radio-option">
                            <input type="radio" name="tipo_taxa" value="modalidade" required checked>
                            <span class="radio-label">Escolher Modalidade</span>
                        </label>
                        
                        <label class="radio-option">
                            <input type="radio" name="tipo_taxa" value="personalizada" required>
                            <span class="radio-label">Taxa de Juros Personalizada</span>
                        </label>
                    </div>
                </div>

                <!-- MODALIDADE -->
                <div id="campo-modalidade" class="form-group">
                    <label for="modalidade_financiamento">Modalidade de Financiamento *</label>
                    <select id="modalidade_financiamento" class="select-flutuante" required>
                        <option value="">-- Selecione a modalidade --</option>
                        <?php foreach ($modalidades as $modalidade): ?>
                        <option value="<?php echo esc_attr($modalidade->id); ?>" 
                                data-taxa="<?php echo esc_attr($modalidade->taxa_juros); ?>"
                                data-limite-financiamento="<?php echo esc_attr($modalidade->limite_financiamento); ?>"
                                data-limite-renda="<?php echo esc_attr($modalidade->limite_renda); ?>"
                                data-subsidio="<?php echo esc_attr($modalidade->valor_subsidio); ?>">
                            <?php echo esc_html($modalidade->nome); ?> - <?php echo esc_html($modalidade->taxa_juros); ?>% a.a.
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- TAXA PERSONALIZADA -->
                <div id="campo-taxa-personalizada" class="form-group" style="display: none;">
                    <label for="taxa_juros_personalizada">Taxa de Juros Personalizada (% ao ano) *</label>
                    <div class="input-with-suffix">
                        <input type="number" id="taxa_juros_personalizada" step="0.1" min="1" max="20" placeholder="8.6">
                        <span class="suffix">% a.a.</span>
                    </div>
                </div>

                <!-- PRAZO -->
                <div class="form-group">
                    <label for="prazo_meses">Prazo de Financiamento (meses) *</label>
                    <select id="prazo_meses" class="select-flutuante" required>
                        <option value="120">120 meses (10 anos)</option>
                        <option value="180">180 meses (15 anos)</option>
                        <option value="240">240 meses (20 anos)</option>
                        <option value="300">300 meses (25 anos)</option>
                        <option value="360" selected>360 meses (30 anos)</option>
                        <option value="420">420 meses (35 anos)</option>
                    </select>
                </div>

                <!-- TIPO AMORTIZAÇÃO -->
                <div class="form-group">
                    <label>Tipo de Amortização *</label>
                    <div class="radio-group-vertical">
                        <label class="radio-option">
                            <input type="radio" name="tipo_amortizacao" value="sac" required>
                            <span class="radio-label">Tabela SAC (Decrescente)</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="tipo_amortizacao" value="price" required checked>
                            <span class="radio-label">Tabela Price (Fixa)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- CAMPOS ESPECÍFICOS -->
            <div class="secao-formulario">
                <h3 id="titulo-campos-especificos">💵 Dados do Imóvel</h3>
                
                <!-- SIMULAÇÃO POR VALOR DO IMÓVEL -->
                <div id="form-valor-imovel" class="formulario-especifico">
                    <div class="form-group">
                        <label for="valor_imovel">Valor do Imóvel (R$) *</label>
                        <input type="number" id="valor_imovel" placeholder="350.000,00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="pagamento_inicial">Pagamento Inicial (R$)</label>
                        <input type="number" id="pagamento_inicial" placeholder="70.000,00">
                    </div>
                    
                    <div class="form-group">
                        <label>Utilização de FGTS *</label>
                        <div class="radio-group-horizontal">
                            <label class="radio-option">
                                <input type="radio" name="usa_fgts" value="sim">
                                <span class="radio-label">Sim</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="usa_fgts" value="nao" checked>
                                <span class="radio-label">Não</span>
                            </label>
                        </div>
                    </div>
                    
                    <div id="campo-valor-fgts" class="form-group" style="display: none;">
                        <label for="valor_fgts">Valor do FGTS (R$) *</label>
                        <input type="number" id="valor_fgts" placeholder="30.000,00">
                    </div>

                    <div class="form-group">
                        <label for="renda_mensal_vi">Renda Mensal do Cliente (R$)</label>
                        <input type="number" id="renda_mensal_vi" placeholder="8.000,00">
                        <div class="input-info">💡 Opcional - para análise de compatibilidade</div>
                    </div>
                </div>

                <!-- SIMULAÇÃO POR RENDA -->
                <div id="form-renda-mensal" class="formulario-especifico" style="display: none;">
                    <div class="form-group">
                        <label for="renda_mensal">Renda Mensal (R$) *</label>
                        <input type="number" id="renda_mensal" placeholder="8.000,00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="valor_imovel_renda">Valor Estimado do Imóvel (R$)</label>
                        <input type="number" id="valor_imovel_renda" placeholder="350.000,00">
                        <div class="input-info">💡 Opcional - para cálculo mais preciso</div>
                    </div>
                </div>

                <!-- SIMULAÇÃO POR PARCELA -->
                <div id="form-valor-parcela" class="formulario-especifico" style="display: none;">
                    <div class="form-group">
                        <label for="valor_parcela">Valor da Parcela Desejada (R$) *</label>
                        <input type="number" id="valor_parcela" placeholder="2.100,00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="renda_mensal_vp">Renda Mensal do Cliente (R$)</label>
                        <input type="number" id="renda_mensal_vp" placeholder="8.000,00">
                        <div class="input-info">💡 Opcional - para análise de compatibilidade</div>
                    </div>
                </div>
            </div>

            <!-- PREVIEW RÁPIDO -->
            <div id="preview-rapido" class="preview-container" style="display: none;">
                <h3>👀 Preview Rápido</h3>
                <div class="preview-content">
                    <div class="preview-item">
                        <span>Valor Financiado:</span>
                        <strong id="preview-valor-financiado">R$ 0,00</strong>
                    </div>
                    <div class="preview-item">
                        <span>Parcela Estimada:</span>
                        <strong id="preview-parcela">R$ 0,00</strong>
                    </div>
                    <div class="preview-item">
                        <span>Compatibilidade:</span>
                        <span id="preview-compatibilidade">-</span>
                    </div>
                </div>
            </div>

            <!-- BOTÃO CALCULAR -->
            <div class="acoes-formulario">
                <button type="submit" id="calcular-simulacao" class="btn-calcular">
                    🖩 Calcular Simulação
                </button>
            </div>
        </form>
    </div>

    <!-- ETAPA 3: RESULTADO COMPACTO -->
    <div id="etapa-3" class="etapa-simulacao">
        <!-- Será carregado via AJAX -->
    </div>

    <!-- ETAPA 4: COLETA DE INFORMAÇÕES -->
    <div id="etapa-4" class="etapa-simulacao">
        <!-- Será carregado via AJAX -->
    </div>
</div>

<!-- LOADING -->
<div id="simulador-loading" class="simulador-loading" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Calculando sua simulação...</p>
    </div>
</div>

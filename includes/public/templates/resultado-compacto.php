<?php
// templates/resultado-compacto.php

function gerar_resultado_compacto($resultado) {
    $compatibilidade = $resultado['compatibilidade_renda'] ?? null;
    $status_class = $compatibilidade && $compatibilidade['compativel'] ? 'aprovado' : ($compatibilidade ? 'alerta' : 'neutro');
    $status_icon = $compatibilidade && $compatibilidade['compativel'] ? '✅' : '⚠️';
    $status_text = $compatibilidade && $compatibilidade['compativel'] ? 'Financiamento Viável' : 'Análise Recomendada';
    
    ob_start();
    ?>
    <div class="resultado-compacto">
        <div class="header-resultado">
            <button onclick="simuladorFrontend.voltarParaEtapa2()" class="btn-voltar">← Voltar</button>
            <h1>✅ Simulação Concluída</h1>
            <div class="status-geral <?php echo $status_class; ?>">
                <span class="status-icon"><?php echo $status_icon; ?></span>
                <span class="status-text"><?php echo $status_text; ?></span>
            </div>
        </div>

        <div class="cartao-compacto">
            
            <!-- RESUMO PRINCIPAL -->
            <div class="resumo-principal">
                <div class="destaque-principal">
                    <span class="label">Parcela Mensal</span>
                    <span class="valor">R$ <?php echo number_format($resultado['parcela_calculada'], 2, ',', '.'); ?></span>
                    <?php if ($compatibilidade): ?>
                        <div class="info-adicional <?php echo $compatibilidade['compativel'] ? 'compativel' : 'alerta'; ?>">
                            <?php echo $compatibilidade['compativel'] ? '✅' : '⚠️'; ?>
                            <?php echo $compatibilidade['percentual']; ?>% da renda
                            <?php if (!$compatibilidade['compativel']): ?>
                                <br><small>Limite ideal: R$ <?php echo number_format($compatibilidade['limite_ideal'], 2, ',', '.'); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mini-grid">
                    <div class="mini-item">
                        <span class="label">Valor do Imóvel</span>
                        <span class="valor">R$ <?php echo number_format($resultado['valor_imovel'] ?? $resultado['valor_maximo_imovel'] ?? $resultado['valor_imovel_calculado'] ?? 0, 2, ',', '.'); ?></span>
                    </div>
                    <div class="mini-item">
                        <span class="label">Entrada</span>
                        <span class="valor">R$ <?php echo number_format($resultado['entrada'] ?? 0, 2, ',', '.'); ?></span>
                    </div>
                    <div class="mini-item">
                        <span class="label">Financiado</span>
                        <span class="valor">R$ <?php echo number_format($resultado['valor_financiado'], 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>

            <!-- DETALHES RÁPIDOS -->
            <div class="detalhes-rapidos">
                <div class="detalhe-linha">
                    <span class="icone">📅</span>
                    <span class="texto"><?php echo $resultado['prazo_meses']; ?> meses (<?php echo round($resultado['prazo_meses'] / 12); ?> anos)</span>
                </div>
                <div class="detalhe-linha">
                    <span class="icone">📊</span>
                    <span class="texto"><?php echo $resultado['modalidade_nome'] ?? 'Taxa Personalizada'; ?></span>
                </div>
                <div class="detalhe-linha">
                    <span class="icone">📈</span>
                    <span class="texto">Taxa: <?php echo number_format($resultado['taxa_juros'], 2, ',', '.'); ?>% a.a.</span>
                </div>
                <div class="detalhe-linha">
                    <span class="icone">💼</span>
                    <span class="texto"><?php echo $resultado['tipo_amortizacao'] == 'sac' ? 'Tabela SAC' : 'Tabela Price'; ?></span>
                </div>
                <?php if ($compatibilidade && $resultado['renda_mensal']): ?>
                <div class="detalhe-linha">
                    <span class="icone">💰</span>
                    <span class="texto">Renda compatível: R$ <?php echo number_format($resultado['renda_mensal'], 2, ',', '.'); ?>/mês</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- GRÁFICO MINI -->
            <div class="grafico-mini">
                <div class="grafico-barras">
                    <?php 
                    $valor_imovel = $resultado['valor_imovel'] ?? $resultado['valor_maximo_imovel'] ?? $resultado['valor_imovel_calculado'] ?? 1;
                    $percentual_financiado = min(100, ($resultado['valor_financiado'] / $valor_imovel) * 100);
                    ?>
                    <div class="barra-item">
                        <div class="barra-label">Valor do Imóvel (100%)</div>
                        <div class="barra-container">
                            <div class="barra-total" style="width: 100%"></div>
                        </div>
                        <span class="barra-texto">R$ <?php echo number_format($valor_imovel, 2, ',', '.'); ?></span>
                    </div>
                    <div class="barra-item">
                        <div class="barra-label">Financiado (<?php echo round($percentual_financiado); ?>%)</div>
                        <div class="barra-container">
                            <div class="barra-financiado" style="width: <?php echo $percentual_financiado; ?>%"></div>
                        </div>
                        <span class="barra-texto">R$ <?php echo number_format($resultado['valor_financiado'], 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>

            <!-- RECOMENDAÇÕES -->
            <?php if ($compatibilidade && !$compatibilidade['compativel']): ?>
            <div class="recomendacoes">
                <h5>💡 Recomendações para Aprovação</h5>
                <div class="recomendacoes-lista">
                    <?php
                    $parcela_ideal = $compatibilidade['limite_ideal'];
                    $parcela_atual = $resultado['parcela_calculada'];
                    $diferenca = $parcela_atual - $parcela_ideal;
                    
                    if ($diferenca > 0) {
                        echo "<div class='recomendacao'>Aumentar entrada em aproximadamente <strong>R$ " . number_format($diferenca * 100, 2, ',', '.') . "</strong></div>";
                        echo "<div class='recomendacao'>Estender prazo para <strong>" . ($resultado['prazo_meses'] + 60) . " meses</strong></div>";
                        echo "<div class='recomendacao'>Buscar taxa de juros diferenciada</div>";
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- AÇÕES RÁPIDAS -->
        <div class="acoes-rapidas">
            <button class="btn-acao secundario" onclick="simuladorFrontend.imprimirResultado()">
                🖨️ Imprimir
            </button>
            <button class="btn-acao secundario" onclick="simuladorFrontend.compartilharResultado()">
                📧 Enviar por Email
            </button>
            <button class="btn-acao principal" onclick="simuladorFrontend.solicitarRelatorioDetalhado()">
                📊 Solicitar Relatório Detalhado
            </button>
        </div>

        <!-- CTA CORRETOR -->
        <div class="cta-corretor">
            <div class="cta-content">
                <div class="cta-icon">👔</div>
                <div class="cta-text">
                    <strong>Quer tirar dúvidas ou fazer uma simulação personalizada?</strong>
                    <p>Fale com um corretor especializado Living & Vivaz</p>
                </div>
                <button class="btn-corretor" onclick="simuladorFrontend.falarComCorretor()">
                    Falar com Corretor
                </button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

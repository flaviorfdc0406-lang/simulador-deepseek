<?php
// templates/formulario-informacoes.php

function gerar_formulario_informacoes($dados_simulacao) {
    ob_start();
    ?>
    <div class="coleta-informacoes">
        
        <!-- CABEÇALHO -->
        <div class="header-coleta">
            <button onclick="simuladorFrontend.voltarParaEtapa3()" class="btn-voltar">← Voltar</button>
            <h1>📋 Informações para Relatório</h1>
            <p>Preencha os dados abaixo para gerar o relatório detalhado da simulação</p>
        </div>

        <!-- RESUMO RÁPIDO DA SIMULAÇÃO -->
        <div class="resumo-rapido">
            <h3>📊 Resumo da Simulação</h3>
            <div class="resumo-grid">
                <div class="resumo-item">
                    <span>Valor do Imóvel:</span>
                    <strong>R$ <?php echo number_format($dados_simulacao['valor_imovel'] ?? $dados_simulacao['valor_maximo_imovel'] ?? $dados_simulacao['valor_imovel_calculado'] ?? 0, 2, ',', '.'); ?></strong>
                </div>
                <div class="resumo-item">
                    <span>Parcela:</span>
                    <strong>R$ <?php echo number_format($dados_simulacao['parcela_calculada'], 2, ',', '.'); ?></strong>
                </div>
                <div class="resumo-item">
                    <span>Prazo:</span>
                    <strong><?php echo $dados_simulacao['prazo_meses']; ?> meses</strong>
                </div>
                <div class="resumo-item">
                    <span>Modalidade:</span>
                    <strong><?php echo $dados_simulacao['modalidade_nome'] ?? 'Taxa Personalizada'; ?></strong>
                </div>
            </div>
        </div>

        <!-- FORMULÁRIO DE INFORMAÇÕES -->
        <form id="form-informacoes" class="form-informacoes">
            
            <!-- SEÇÃO: DADOS DO EMPREENDIMENTO -->
            <div class="secao-informacoes">
                <h3>🏢 Dados do Empreendimento</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome_empreendimento">Nome do Empreendimento *</label>
                        <select id="nome_empreendimento" class="select-flutuante" required>
                            <option value="">-- Selecione o empreendimento --</option>
                            <option value="Living Jardins">Living Jardins</option>
                            <option value="Living Alto Leblon">Living Alto Leblon</option>
                            <option value="Living Modern">Living Modern</option>
                            <option value="Vivaz Residencial">Vivaz Residencial</option>
                            <option value="Vivaz Premium">Vivaz Premium</option>
                            <option value="Vivaz Elegance">Vivaz Elegance</option>
                            <option value="outro">Outro (especificar)</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="campo-outro-empreendimento" style="display: none;">
                        <label for="outro_empreendimento">Especificar Empreendimento *</label>
                        <input type="text" id="outro_empreendimento" placeholder="Nome do empreendimento">
                    </div>
                    
                    <div class="form-group">
                        <label for="unidade">Unidade *</label>
                        <input type="text" id="unidade" placeholder="Ex: Torre A - Apt 1204" required>
                        <div class="input-info">Torre, andar, número do apartamento</div>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO: DADOS DO CORRETOR -->
            <div class="secao-informacoes">
                <h3>👔 Dados do Corretor</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome_corretor">Nome do Corretor *</label>
                        <input type="text" id="nome_corretor" placeholder="Seu nome completo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email_corretor">Email do Corretor *</label>
                        <input type="email" id="email_corretor" placeholder="seu.email@imobiliaria.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="empresa">Empresa/Imobiliária *</label>
                        <input type="text" id="empresa" placeholder="Nome da imobiliária" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="creci">CRECI *</label>
                        <input type="text" id="creci" placeholder="Número do CRECI" required>
                        <div class="input-info">Número de registro no CRECI</div>
                    </div>
                </div>
            </div>

            <!-- SEÇÃO: DADOS DO CLIENTE -->
            <div class="secao-informacoes">
                <h3>👤 Dados do Cliente</h3>
                
                <div class="form-group">
                    <label for="nome_cliente">Nome do Cliente *</label>
                    <input type="text" id="nome_cliente" placeholder="Nome completo do cliente" required>
                    <div class="input-info">Nome completo para o relatório personalizado</div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="email_cliente">Email do Cliente</label>
                        <input type="email" id="email_cliente" placeholder="cliente@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone_cliente">Telefone do Cliente</label>
                        <input type="tel" id="telefone_cliente" placeholder="(21) 99999-9999">
                    </div>
                </div>
            </div>

            <!-- SEÇÃO: OBSERVAÇÕES OPCIONAIS -->
            <div class="secao-informacoes">
                <h3>📝 Observações Adicionais (Opcional)</h3>
                
                <div class="form-group">
                    <label for="observacoes">Observações para o relatório</label>
                    <textarea id="observacoes" placeholder="Ex: Cliente tem interesse em vaga de garagem adicional, possui FGTS para utilizar, restrições específicas..." rows="4"></textarea>
                    <div class="input-info">Informações extras que devem constar no relatório</div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="concorda_termos" required>
                        Concordo com os <a href="#" target="_blank">termos de uso</a> e 
                        <a href="#" target="_blank">política de privacidade</a> *
                    </label>
                </div>
            </div>

            <!-- AÇÕES -->
            <div class="acoes-coleta">
                <button type="button" class="btn-voltar-form" onclick="simuladorFrontend.voltarParaEtapa3()">
                    ↩️ Voltar para Resultado
                </button>
                
                <button type="submit" class="btn-gerar-relatorio">
                    📊 Gerar Relatório Detalhado
                </button>
            </div>

        </form>
    </div>
    <?php
    return ob_get_clean();
}
?>

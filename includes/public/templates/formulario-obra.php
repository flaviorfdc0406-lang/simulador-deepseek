<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="simulador-obra">
    <div class="obra-header">
        <h2>Simulador - Período de Obras</h2>
        <p>Calcule seu financiamento considerando o período de construção</p>
    </div>

    <form id="form-simulacao-obra" class="simulador-form">
        <?php wp_nonce_field('simulacao_obra_nonce', 'security'); ?>
        
        <div class="form-section">
            <h3>Dados do Imóvel e Financiamento</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="valor_imovel">Valor do Imóvel (R$)*</label>
                    <input type="number" id="valor_imovel" name="valor_imovel" required 
                           min="10000" step="1000" placeholder="Ex: 300000">
                </div>
                
                <div class="form-group">
                    <label for="valor_entrada">Valor de Entrada (R$)</label>
                    <input type="number" id="valor_entrada" name="valor_entrada" 
                           min="0" step="1000" placeholder="Ex: 60000">
                </div>
                
                <div class="form-group">
                    <label for="taxa_juros">Taxa de Juros Anual (%)*</label>
                    <input type="number" id="taxa_juros" name="taxa_juros" required 
                           min="1" max="30" step="0.1" placeholder="Ex: 8.5" value="8.5">
                </div>
                
                <div class="form-group">
                    <label for="prazo_anos">Prazo do Financiamento (anos)*</label>
                    <select id="prazo_anos" name="prazo_anos" required>
                        <option value="">Selecione...</option>
                        <option value="10">10 anos</option>
                        <option value="15">15 anos</option>
                        <option value="20">20 anos</option>
                        <option value="25">25 anos</option>
                        <option value="30">30 anos</option>
                        <option value="35">35 anos</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Período de Obras</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="periodo_obra_meses">Duração da Obra (meses)*</label>
                    <input type="number" id="periodo_obra_meses" name="periodo_obra_meses" required 
                           min="1" max="60" placeholder="Ex: 12">
                </div>
                
                <div class="form-group">
                    <label for="periodicidade_obra">Periodicidade das Parcelas*</label>
                    <select id="periodicidade_obra" name="periodicidade_obra" required>
                        <option value="mensal">Mensal</option>
                        <option value="trimestral">Trimestral</option>
                        <option value="semestral">Semestral</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="data_vencimento">Data do Primeiro Vencimento*</label>
                    <input type="text" id="data_vencimento" name="data_vencimento" required 
                           placeholder="dd/mm/aaaa" class="date-mask">
                </div>
                
                <div class="form-group">
                    <label for="tipo_obra">Tipo de Obra</label>
                    <select id="tipo_obra" name="tipo_obra">
                        <option value="construcao">Construção</option>
                        <option value="reforma">Reforma</option>
                        <option value="ampliacao">Ampliação</option>
                        <option value="acabamento">Acabamento</option>
                    </select>
                </div>
            </div>
            
            <div class="obra-info">
                <div class="info-box">
                    <h4>💡 Como funciona o período de obras?</h4>
                    <p>Durante a construção, você paga apenas os juros do financiamento. 
                    A amortização do valor principal começa apenas após o término da obra.</p>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" id="btn-calcular-obra" class="btn-primary">
                Calcular Simulação
            </button>
            <button type="reset" class="btn-secondary">
                Limpar Campos
            </button>
        </div>
    </form>

    <div id="resultado-obra" class="resultado-container" style="display: none;">
        <!-- Resultados serão carregados aqui via AJAX -->
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Máscara para data
    $('#data_vencimento').mask('00/00/0000');
    
    // Formatação de valores monetários
    $('input[type="number"]').on('blur', function() {
        const value = parseFloat($(this).val());
        if (!isNaN(value) && value >= 0) {
            $(this).val(value.toFixed(2));
        }
    });
    
    // Cálculo da simulação
    $('#btn-calcular-obra').on('click', function() {
        calcularSimulacaoObra();
    });
    
    // Atualiza informações baseado na periodicidade
    $('#periodicidade_obra').on('change', function() {
        atualizarInfoPeriodicidade($(this).val());
    });
    
    function atualizarInfoPeriodicidade(periodicidade) {
        const info = {
            'mensal': 'Parcelas mensais durante todo o período de obra',
            'trimestral': 'Parcelas a cada 3 meses durante o período de obra',
            'semestral': 'Parcelas a cada 6 meses durante o período de obra',
            'anual': 'Parcelas anuais durante o período de obra'
        };
        
        $('.periodicidade-info').remove();
        $('#periodicidade_obra').after('<small class="periodicidade-info">' + info[periodicidade] + '</small>');
    }
    
    function calcularSimulacaoObra() {
        const form = $('#form-simulacao-obra');
        const btn = $('#btn-calcular-obra');
        const resultado = $('#resultado-obra');
        
        // Validação básica
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }
        
        btn.prop('disabled', true).text('Calculando...');
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'calcular_simulacao_obra',
                data: form.serialize()
            },
            success: function(response) {
                if (response.success) {
                    resultado.html(response.data.html).show();
                    $('html, body').animate({
                        scrollTop: resultado.offset().top - 100
                    }, 500);
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function() {
                alert('Erro ao calcular simulação. Tente novamente.');
            },
            complete: function() {
                btn.prop('disabled', false).text('Calcular Simulação');
            }
        });
    }
    
    // Inicializa informações
    atualizarInfoPeriodicidade('mensal');
});
</script>

<style>
.simulador-obra {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem;
}

.obra-header {
    text-align: center;
    margin-bottom: 2rem;
}

.obra-header h2 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.obra-header p {
    color: #7f8c8d;
    font-size: 1.1rem;
}

.form-section {
    background: #fff;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-section h3 {
    color: #34495e;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #3498db;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.form-group input,
.form-group select {
    padding: 0.75rem;
    border: 2px solid #bdc3c7;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #3498db;
}

.obra-info {
    margin-top: 1.5rem;
}

.info-box {
    background: #e8f4fd;
    padding: 1rem;
    border-radius: 6px;
    border-left: 4px solid #3498db;
}

.info-box h4 {
    margin: 0 0 0.5rem 0;
    color: #2980b9;
}

.info-box p {
    margin: 0;
    color: #34495e;
}

.form-actions {
    text-align: center;
    margin-top: 2rem;
}

.btn-primary, .btn-secondary {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 0 0.5rem;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

.periodicidade-info {
    display: block;
    margin-top: 0.25rem;
    color: #7f8c8d;
    font-style: italic;
}

@media (max-width: 768px) {
    .simulador-obra {
        padding: 1rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn-primary, .btn-secondary {
        margin: 0;
    }
}
</style>

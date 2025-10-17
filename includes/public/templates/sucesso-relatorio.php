<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="simulador-sucesso">
    <div class="sucesso-container">
        <div class="sucesso-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z" fill="#4CAF50"/>
            </svg>
        </div>
        
        <h2 class="sucesso-titulo">Simulação Concluída com Sucesso!</h2>
        
        <div class="sucesso-mensagem">
            <p>O relatório da sua simulação foi gerado e enviado para o seu e-mail.</p>
            <p><strong>Verifique sua caixa de entrada e a pasta de spam.</strong></p>
        </div>

        <div class="sucesso-detalhes">
            <div class="detalhe-item">
                <span class="detalhe-label">Número da Simulação:</span>
                <span class="detalhe-valor">#<?php echo esc_html($simulation_id); ?></span>
            </div>
            <div class="detalhe-item">
                <span class="detalhe-label">Data:</span>
                <span class="detalhe-valor"><?php echo date('d/m/Y H:i'); ?></span>
            </div>
            <?php if (!empty($email)): ?>
            <div class="detalhe-item">
                <span class="detalhe-label">E-mail enviado para:</span>
                <span class="detalhe-valor"><?php echo esc_html($email); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="sucesso-acoes">
            <button type="button" class="btn-nova-simulacao" onclick="window.location.href='<?php echo esc_url(home_url()); ?>'">
                Nova Simulação
            </button>
            
            <?php if (!empty($pdf_url)): ?>
            <a href="<?php echo esc_url($pdf_url); ?>" class="btn-download-pdf" download>
                Download do PDF
            </a>
            <?php endif; ?>
            
            <button type="button" class="btn-compartilhar" onclick="compartilharSimulacao()">
                Compartilhar
            </button>
        </div>

        <div class="sucesso-info">
            <div class="info-box">
                <h4>Próximos Passos</h4>
                <ul>
                    <li>Verifique seu e-mail com o relatório completo</li>
                    <li>Entre em contato conosco para mais informações</li>
                    <li>Compare diferentes modalidades de financiamento</li>
                </ul>
            </div>
            
            <div class="info-box">
                <h4>Dúvidas Frequentes</h4>
                <ul>
                    <li><a href="#" onclick="abrirFAQ('prazo')">Qual o prazo máximo de financiamento?</a></li>
                    <li><a href="#" onclick="abrirFAQ('taxas')">Como são calculadas as taxas?</a></li>
                    <li><a href="#" onclick="abrirFAQ('documentos')">Quais documentos são necessários?</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function compartilharSimulacao() {
    if (navigator.share) {
        navigator.share({
            title: 'Minha Simulação de Financiamento',
            text: 'Confira minha simulação de financiamento gerada pelo sistema',
            url: window.location.href
        })
        .then(() => console.log('Compartilhado com sucesso'))
        .catch((error) => console.log('Erro ao compartilhar:', error));
    } else {
        // Fallback para copiar link
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link copiado para a área de transferência!');
        });
    }
}

function abrirFAQ(tipo) {
    // Implementar abertura de modal com FAQ específico
    alert('Funcionalidade FAQ em desenvolvimento para: ' + tipo);
}

// Redirecionamento automático após 30 segundos
setTimeout(() => {
    window.location.href = '<?php echo esc_url(home_url()); ?>';
}, 30000);
</script>

<style>
.simulador-sucesso {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.sucesso-container {
    text-align: center;
}

.sucesso-icon {
    margin-bottom: 1.5rem;
}

.sucesso-titulo {
    color: #2E7D32;
    font-size: 2rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.sucesso-mensagem {
    background: #E8F5E8;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border-left: 4px solid #4CAF50;
}

.sucesso-detalhes {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: left;
}

.detalhe-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.detalhe-item:last-child {
    border-bottom: none;
}

.detalhe-label {
    font-weight: 600;
    color: #495057;
}

.detalhe-valor {
    color: #212529;
    font-weight: 500;
}

.sucesso-acoes {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.btn-nova-simulacao,
.btn-download-pdf,
.btn-compartilhar {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-nova-simulacao {
    background: #4CAF50;
    color: white;
}

.btn-download-pdf {
    background: #2196F3;
    color: white;
}

.btn-compartilhar {
    background: #FF9800;
    color: white;
}

.btn-nova-simulacao:hover {
    background: #45a049;
    transform: translateY(-2px);
}

.btn-download-pdf:hover {
    background: #1976D2;
    transform: translateY(-2px);
}

.btn-compartilhar:hover {
    background: #F57C00;
    transform: translateY(-2px);
}

.sucesso-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-top: 2rem;
}

.info-box {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: left;
}

.info-box h4 {
    color: #495057;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.info-box ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-box li {
    padding: 0.25rem 0;
    color: #6c757d;
}

.info-box a {
    color: #2196F3;
    text-decoration: none;
}

.info-box a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .simulador-sucesso {
        margin: 1rem;
        padding: 1rem;
    }
    
    .sucesso-acoes {
        flex-direction: column;
    }
    
    .sucesso-info {
        grid-template-columns: 1fr;
    }
}
</style>

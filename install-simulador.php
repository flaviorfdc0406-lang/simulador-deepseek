<?php
// install-simulador.php - Coloque na raiz do WordPress e execute uma vez
function criar_estrutura_simulador() {
    $estrutura = [
        'includes/' => [
            'class-database.php',
            'class-calculator.php', 
            'class-admin-dashboard.php',
            'class-pdf-generator.php',
            'class-email-sender.php',
            'class-pdf-handlers.php',
            'class-ajax-handlers.php',
            'class-shortcodes.php'
        ],
        'admin/templates/' => [
            'dashboard.php',
            'modalidades.php', 
            'relatorios.php',
            'personalizacao.php',
            'configuracoes.php'
        ],
        'admin/assets/css/' => ['admin.css'],
        'admin/assets/js/' => ['admin.js'],
        'public/templates/' => [
            'formulario-simulacao.php',
            'resultado-compacto.php',
            'formulario-informacoes.php'
        ],
        'public/css/' => ['simulador.css', 'etapas-3-4.css'],
        'public/js/' => [
            'simulador-frontend.js',
            'simulador-calculator.js', 
            'etapas-3-4.js',
            'relatorios-final.js'
        ]
    ];
    
    // Cria diretórios e arquivos
    $base_path = WP_CONTENT_DIR . '/plugins/simulador-financiamento-living/';
    
    foreach ($estrutura as $pasta => $arquivos) {
        wp_mkdir_p($base_path . $pasta);
        foreach ($arquivos as $arquivo) {
            touch($base_path . $pasta . $arquivo);
        }
    }
    
    echo "Estrutura criada com sucesso!";
}

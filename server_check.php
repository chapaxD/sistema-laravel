<?php
/**
 * Script para verificar requisitos del servidor para Laravel + Inertia
 * Sube este archivo a tu servidor (ej. public_html/check.php) y ábrelo en el navegador.
 */

$requirements = [
    'php_version' => '8.2.0',
    'extensions' => [
        'bcmath',
        'ctype',
        'fileinfo',
        'json',
        'mbstring',
        'openssl',
        'pdo',
        'pdo_pgsql', // O pdo_mysql si usas MySQL
        'tokenizer',
        'xml',
        'curl',
        'zip'
    ]
];

$results = [];

// 1. Verificar Versión de PHP
$currentPhp = phpversion();
$results['php'] = [
    'name' => 'PHP Version',
    'required' => $requirements['php_version'],
    'current' => $currentPhp,
    'status' => version_compare($currentPhp, $requirements['php_version'], '>=')
];

// 2. Verificar Extensiones
foreach ($requirements['extensions'] as $ext) {
    $loaded = extension_loaded($ext);
    $results['extensions'][$ext] = [
        'status' => $loaded,
        'message' => $loaded ? 'Instalada' : 'Falta instalar'
    ];
}

// 3. Verificar Comandos de Shell (Composer, Node, Git)
// Nota: En hosting compartido, shell_exec puede estar deshabilitado
function checkCommand($cmd) {
    if (!function_exists('shell_exec')) {
        return 'No se puede verificar (shell_exec deshabilitado)';
    }
    $output = shell_exec("$cmd --version 2>&1");
    return $output ? trim($output) : 'No encontrado';
}

$results['commands'] = [
    'composer' => checkCommand('composer'),
    'node' => checkCommand('node'),
    'npm' => checkCommand('npm'),
    'git' => checkCommand('git'),
    'psql' => checkCommand('psql'), // Cliente PostgreSQL
];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Servidor</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; line-height: 1.5; }
        h1 { border-bottom: 2px solid #eee; padding-bottom: 0.5rem; }
        .card { background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: 0.5rem; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <h1>Verificación de Requisitos del Servidor</h1>

    <div class="card">
        <h2>PHP</h2>
        <p>
            Versión requerida: <strong><?php echo $requirements['php_version']; ?>+</strong><br>
            Versión actual: <strong><?php echo $results['php']['current']; ?></strong><br>
            Estado: <span class="<?php echo $results['php']['status'] ? 'success' : 'error'; ?>">
                <?php echo $results['php']['status'] ? 'OK' : 'ACTUALIZAR PHP'; ?>
            </span>
        </p>
    </div>

    <div class="card">
        <h2>Extensiones PHP</h2>
        <table>
            <?php foreach ($results['extensions'] as $ext => $info): ?>
            <tr>
                <td><?php echo $ext; ?></td>
                <td class="<?php echo $info['status'] ? 'success' : 'error'; ?>">
                    <?php echo $info['message']; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="card">
        <h2>Herramientas de Línea de Comandos</h2>
        <p><em>Si estas herramientas no están disponibles, deberás subir el proyecto ya compilado (vendor y node_modules/build) desde tu PC.</em></p>
        <table>
            <?php foreach ($results['commands'] as $cmd => $output): ?>
            <tr>
                <td><strong><?php echo ucfirst($cmd); ?></strong></td>
                <td><?php echo htmlspecialchars($output); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>

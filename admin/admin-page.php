<?php
// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Función helper para obtener la ruta del directorio estático
function greenborn_admin_get_static_dir() {
    return function_exists('greenborn_get_static_dir') ? 
        greenborn_get_static_dir() : 
        dirname(ABSPATH) . '/wp-static/';
}

// Obtener información del directorio
$static_dir = greenborn_admin_get_static_dir();
$dir_exists = file_exists($static_dir);
$dir_writable = is_writable($static_dir);
$dir_perms = $dir_exists ? substr(sprintf('%o', fileperms($static_dir)), -4) : 'N/A';
$dir_owner = $dir_exists && function_exists('posix_getpwuid') ? 
    (posix_getpwuid(fileowner($static_dir))['name'] ?? 'unknown') : 
    'unknown';
$current_user = function_exists('posix_getpwuid') ? 
    (posix_getpwuid(posix_geteuid())['name'] ?? 'unknown') : 
    'unknown';
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="card">
        <h2>Generador de Páginas Estáticas</h2>
        <p>Este plugin genera páginas HTML estáticas en el directorio <code>wp-static/</code> para mejorar el rendimiento y la seguridad de tu sitio.</p>
        
        <div class="notice notice-info">
            <p><strong>Directorio estático:</strong> <code><?php echo esc_html($static_dir); ?></code></p>
            <p><strong>⚠️ Importante:</strong> Para que el sitio estático funcione correctamente, debes configurar este directorio como root del dominio. Ve a <strong>Static Pages > Configuración</strong> para ver las instrucciones detalladas.</p>
        </div>
        
        <div class="notice notice-warning">
            <p><strong>⚠️ Advertencia:</strong> Al generar las páginas estáticas, el directorio <code><?php echo esc_html($static_dir); ?></code> será completamente limpiado y todos los archivos existentes serán eliminados.</p>
            <p>Si tienes archivos importantes en este directorio, haz una copia de seguridad antes de continuar.</p>
        </div>
        
        <div class="card">
            <h3>Estado del Directorio Estático</h3>
            <table class="form-table">
                <tr>
                    <th>Directorio existe:</th>
                    <td>
                        <?php if ($dir_exists): ?>
                            <span style="color: green;">✓ Sí</span>
                        <?php else: ?>
                            <span style="color: red;">✗ No</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Permisos de escritura:</th>
                    <td>
                        <?php if ($dir_writable): ?>
                            <span style="color: green;">✓ Sí</span>
                        <?php else: ?>
                            <span style="color: red;">✗ No (permisos: <?php echo esc_html($dir_perms); ?>)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Permisos actuales:</th>
                    <td><code><?php echo esc_html($dir_perms); ?></code></td>
                </tr>
            </table>
            
            <?php if (!$dir_exists || !$dir_writable): ?>
            <div class="notice notice-warning">
                <p><strong>Problema detectado:</strong> El directorio no está listo para generar páginas estáticas.</p>
                <p><strong>Solución:</strong> Ejecuta estos comandos en tu servidor:</p>
                <pre><code>mkdir -p <?php echo esc_html($static_dir); ?>
chmod 755 <?php echo esc_html($static_dir); ?>
chown <?php echo esc_html($dir_owner !== 'unknown' ? $dir_owner : 'www-data'); ?>:<?php echo esc_html($dir_owner !== 'unknown' ? $dir_owner : 'www-data'); ?> <?php echo esc_html($static_dir); ?></code></pre>
                <p><em>Nota: Si el comando chown falla, contacta a tu proveedor de hosting para que ajuste los permisos del directorio.</em></p>
                
                <p>
                    <button type="button" id="fix-directory" class="button button-secondary">
                        Intentar crear directorio automáticamente
                    </button>
                    <span id="fix-result"></span>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="result-message"></div>
        <div id="progress-container"></div>
        
        <p>
            <button type="button" id="generate-static-pages" class="button button-primary">
                Generar Páginas Estáticas
            </button>
        </p>
    </div>
    
    <div class="card">
        <h2>Información del Plugin</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Versión</th>
                <td><?php echo esc_html(GREENBORN_STATIC_PLUGIN_VERSION); ?></td>
            </tr>
            <tr>
                <th scope="row">Estado del directorio</th>
                <td>
                    <?php if (file_exists($static_dir)): ?>
                        <span class="status-indicator success"></span>✓ Directorio creado
                    <?php else: ?>
                        <span class="status-indicator error"></span>✗ Directorio no existe
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Permisos de escritura</th>
                <td>
                    <?php if (is_writable($static_dir)): ?>
                        <span class="status-indicator success"></span>✓ Escritura permitida
                    <?php else: ?>
                        <span class="status-indicator error"></span>✗ Escritura denegada
                        <br><small>Permisos: <?php echo esc_html($dir_perms); ?>, Propietario: <?php echo esc_html($dir_owner); ?>, Usuario actual: <?php echo esc_html($current_user); ?></small>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Archivo .htaccess</th>
                <td>
                    <?php if (file_exists($static_dir . '.htaccess')): ?>
                        <span class="status-indicator success"></span>✓ Archivo creado
                    <?php else: ?>
                        <span class="status-indicator error"></span>✗ Archivo no existe
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=greenborn-static-config')); ?>" class="button">
                Ver Configuración Completa
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=greenborn-static-help')); ?>" class="button">
                Ver Ayuda
            </a>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var currentStep = 0;
    var itemsList = [];
    var processedItems = 0;
    var totalItems = 0;
    
    $('#generate-static-pages').on('click', function(e) {
        e.preventDefault();
        
        // Mostrar confirmación antes de proceder
        if (!confirm('⚠️ ADVERTENCIA: Al continuar, el directorio <?php echo esc_js($static_dir); ?> será completamente limpiado y todos los archivos existentes serán eliminados.\n\n¿Estás seguro de que quieres continuar?')) {
            return;
        }
        
        var button = $(this);
        var originalText = button.text();
        
        // Deshabilitar botón y mostrar loading
        button.prop('disabled', true).text('Iniciando...');
        
        // Limpiar mensajes anteriores
        $('#result-message').html('');
        $('#progress-container').html('');
        
        // Paso 1: Preparar directorio
        prepareDirectory(button, originalText);
    });
    
    function prepareDirectory(button, originalText) {
        button.text('Preparando directorio...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_static_pages',
                nonce: '<?php echo wp_create_nonce('greenborn_static_generation'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#result-message').append(
                        '<div class="notice notice-success"><p>✓ ' + response.data.message + '</p></div>'
                    );
                    // Paso 2: Obtener lista de elementos
                    getItemsList(button, originalText);
                } else {
                    showError(response.data.message, button, originalText);
                }
            },
            error: function() {
                showError('Error de conexión al preparar directorio', button, originalText);
            }
        });
    }
    
    function getItemsList(button, originalText) {
        button.text('Obteniendo lista de elementos...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_items_list',
                nonce: '<?php echo wp_create_nonce('greenborn_static_generation'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    itemsList = response.data.items;
                    totalItems = response.data.total_items;
                    processedItems = 0;
                    
                    $('#result-message').append(
                        '<div class="notice notice-success"><p>✓ ' + response.data.message + '</p>' +
                        '<p><strong>Total de elementos:</strong> ' + totalItems + '</p></div>'
                    );
                    
                    // Mostrar lista de elementos
                    showItemsList();
                    
                    // Paso 3: Procesar elementos uno por uno
                    processNextItem(button, originalText);
                } else {
                    showError(response.data.message, button, originalText);
                }
            },
            error: function() {
                showError('Error de conexión al obtener lista de elementos', button, originalText);
            }
        });
    }
    
    function showItemsList() {
        var listHtml = '<div class="items-list-container">' +
            '<h3>Elementos a procesar:</h3>' +
            '<div class="items-list">';
        
        itemsList.forEach(function(item, index) {
            listHtml += '<div class="item-row" id="item-' + item.id + '-' + item.type + '">' +
                '<span class="item-number">' + (index + 1) + '.</span>' +
                '<span class="item-type">[' + item.type + ']</span>' +
                '<span class="item-title">' + item.title + '</span>' +
                '<span class="item-status pending">Pendiente</span>' +
                '</div>';
        });
        
        listHtml += '</div></div>';
        $('#progress-container').html(listHtml);
    }
    
    function processNextItem(button, originalText) {
        if (processedItems >= totalItems) {
            // Proceso completado
            button.text('Completado').removeClass('button-primary').addClass('button-secondary');
            $('#result-message').append(
                '<div class="notice notice-success"><p>✓ <strong>Proceso completado!</strong> Se procesaron ' + totalItems + ' elementos.</p></div>'
            );
            return;
        }
        
        var currentItem = itemsList[processedItems];
        button.text('Procesando: ' + currentItem.title.substring(0, 30) + '...');
        
        // Actualizar estado en la lista
        $('#item-' + currentItem.id + '-' + currentItem.type + ' .item-status')
            .removeClass('pending')
            .addClass('processing')
            .text('Procesando...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'process_single_item',
                item_id: currentItem.id,
                item_type: currentItem.type,
                nonce: '<?php echo wp_create_nonce('greenborn_static_generation'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar estado en la lista
                    $('#item-' + currentItem.id + '-' + currentItem.type + ' .item-status')
                        .removeClass('processing')
                        .addClass('completed')
                        .text('✓ Completado');
                    
                    processedItems++;
                    
                    // Continuar con el siguiente elemento
                    setTimeout(function() {
                        processNextItem(button, originalText);
                    }, 500); // Pequeña pausa entre elementos
                    
                } else {
                    // Marcar como error
                    $('#item-' + currentItem.id + '-' + currentItem.type + ' .item-status')
                        .removeClass('processing')
                        .addClass('error')
                        .text('✗ Error: ' + response.data.message);
                    
                    processedItems++;
                    
                    // Continuar con el siguiente elemento
                    setTimeout(function() {
                        processNextItem(button, originalText);
                    }, 500);
                }
            },
            error: function() {
                // Marcar como error
                $('#item-' + currentItem.id + '-' + currentItem.type + ' .item-status')
                    .removeClass('processing')
                    .addClass('error')
                    .text('✗ Error de conexión');
                
                processedItems++;
                
                // Continuar con el siguiente elemento
                setTimeout(function() {
                    processNextItem(button, originalText);
                }, 500);
            }
        });
    }
    
    function showError(message, button, originalText) {
        $('#result-message').append(
            '<div class="notice notice-error"><p>✗ ' + message + '</p></div>'
        );
        button.prop('disabled', false).text(originalText);
    }
});
</script>

<script>
jQuery(document).ready(function($) {
    $('#fix-directory').on('click', function() {
        var button = $(this);
        var originalText = button.text();
        
        button.prop('disabled', true).text('Creando directorio...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fix_static_directory',
                nonce: '<?php echo wp_create_nonce('greenborn_static_generation'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#fix-result').html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                    // Recargar la página para mostrar el estado actualizado
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('#fix-result').html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                }
            },
            error: function() {
                $('#fix-result').html('<span style="color: red;">✗ Error de conexión</span>');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>

<style>
.items-list-container {
    margin-top: 20px;
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 15px;
    background: #f9f9f9;
}

.items-list {
    font-family: monospace;
    font-size: 12px;
}

.item-row {
    display: flex;
    align-items: center;
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

.item-row:last-child {
    border-bottom: none;
}

.item-number {
    width: 30px;
    font-weight: bold;
    color: #666;
}

.item-type {
    width: 60px;
    color: #666;
    font-weight: bold;
}

.item-title {
    flex: 1;
    margin: 0 10px;
}

.item-status {
    width: 120px;
    text-align: right;
    font-weight: bold;
}

.item-status.pending {
    color: #666;
}

.item-status.processing {
    color: #0073aa;
}

.item-status.completed {
    color: #46b450;
}

.item-status.error {
    color: #dc3232;
}
</style> 
<?php
// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="card">
        <h2>Generador de Páginas Estáticas</h2>
        <p>Este plugin genera páginas HTML estáticas en el directorio <code>wp-static/</code> para mejorar el rendimiento y la seguridad de tu sitio.</p>
        
        <div class="notice notice-info">
            <p><strong>Directorio estático:</strong> <code><?php echo esc_html(GREENBORN_STATIC_DIR); ?></code></p>
            <p><strong>⚠️ Importante:</strong> Para que el sitio estático funcione correctamente, debes configurar este directorio como root del dominio. Ve a <strong>Static Pages > Configuración</strong> para ver las instrucciones detalladas.</p>
        </div>
        
        <div id="generation-status" style="display: none;">
            <div class="notice notice-warning">
                <p><strong>Generando páginas estáticas...</strong> <span id="progress-text">Iniciando...</span></p>
            </div>
        </div>
        
        <div id="generation-result" style="display: none;">
            <div class="notice notice-success">
                <p id="result-message"></p>
            </div>
        </div>
        
        <p>
            <button type="button" id="generate-static-pages" class="button button-primary">
                Generar Páginas Estáticas
            </button>
            <span class="spinner" id="generation-spinner" style="float: none; margin-left: 10px; display: none;"></span>
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
                    <?php if (file_exists(GREENBORN_STATIC_DIR)): ?>
                        <span class="status-indicator success"></span>✓ Directorio creado
                    <?php else: ?>
                        <span class="status-indicator error"></span>✗ Directorio no existe
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Permisos de escritura</th>
                <td>
                    <?php if (is_writable(GREENBORN_STATIC_DIR)): ?>
                        <span class="status-indicator success"></span>✓ Escritura permitida
                    <?php else: ?>
                        <span class="status-indicator error"></span>✗ Sin permisos de escritura
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">Archivo .htaccess</th>
                <td>
                    <?php if (file_exists(GREENBORN_STATIC_DIR . '.htaccess')): ?>
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

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#generate-static-pages').on('click', function() {
        var button = $(this);
        var spinner = $('#generation-spinner');
        var status = $('#generation-status');
        var result = $('#generation-result');
        
        // Deshabilitar botón y mostrar spinner
        button.prop('disabled', true);
        spinner.show();
        status.show();
        result.hide();
        
        // Realizar petición AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'generate_static_pages',
                nonce: '<?php echo wp_create_nonce('greenborn_static_generation'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#result-message').html(
                        '<strong>¡Éxito!</strong> ' + response.data.message + '<br>' +
                        'Páginas generadas: ' + response.data.pages_generated + '<br>' +
                        'Directorio: ' + response.data.static_dir
                    );
                    result.find('.notice').removeClass('notice-success').addClass('notice-success');
                } else {
                    $('#result-message').html('<strong>Error:</strong> ' + response.data.message);
                    result.find('.notice').removeClass('notice-success').addClass('notice-error');
                }
                result.show();
            },
            error: function() {
                $('#result-message').html('<strong>Error:</strong> No se pudo completar la operación');
                result.find('.notice').removeClass('notice-success').addClass('notice-error');
                result.show();
            },
            complete: function() {
                // Habilitar botón y ocultar spinner
                button.prop('disabled', false);
                spinner.hide();
                status.hide();
            }
        });
    });
});
</script> 
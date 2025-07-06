<?php
/**
 * Archivo de desinstalación del plugin
 * Se ejecuta cuando el plugin es eliminado completamente
 */

// Si no se llama desde WordPress, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Eliminar opciones del plugin
delete_option('greenborn_static_pages_settings');
delete_option('greenborn_static_pages_version');

// Limpiar cualquier tarea programada
wp_clear_scheduled_hook('greenborn_static_generation');

// Opcional: Eliminar el directorio estático
// Descomenta las siguientes líneas si quieres eliminar automáticamente el directorio wp-static
/*
$static_dir = ABSPATH . 'wp-static/';
if (is_dir($static_dir)) {
    // Función recursiva para eliminar directorio
    function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    delete_directory($static_dir);
}
*/

// Limpiar logs si existen
$log_file = WP_CONTENT_DIR . '/greenborn-static-pages.log';
if (file_exists($log_file)) {
    unlink($log_file);
} 
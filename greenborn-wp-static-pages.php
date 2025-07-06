<?php
/**
 * Plugin Name: Greenborn WP Static Pages
 * Plugin URI: 
 * Description: Plugin wordpress que se encarga de generar micrositio estático y optimizado buscando acelerar la carga y cacheado de recursos y elevar el nivel de seguridad al no exponer código ejecutable en backend.
 * Version: 0.1.0
 * Author: luciano.n.vega@gmail.com
 * Author URI: 
 * License: GPL v3
 * Text Domain: greenborn-wp-static-pages
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('GREENBORN_STATIC_PLUGIN_VERSION', '0.1.0');
define('GREENBORN_STATIC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GREENBORN_STATIC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('GREENBORN_STATIC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Definir la ruta del directorio estático usando get_home_path() que es más confiable
// que dirname(ABSPATH) para obtener la ruta real del directorio de WordPress
function greenborn_get_static_dir() {
    if (!function_exists('get_home_path')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $home_path = get_home_path();
    if (empty($home_path)) {
        // Fallback a dirname(ABSPATH) si get_home_path() no funciona
        $home_path = dirname(ABSPATH) . '/';
    }
    return $home_path . 'wp-static/';
}

// Definir la constante después de que WordPress esté cargado
add_action('init', function() {
    if (!defined('GREENBORN_STATIC_DIR')) {
        define('GREENBORN_STATIC_DIR', greenborn_get_static_dir());
    }
});

// Clase principal del plugin
class GreenbornWPStaticPages {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_generate_static_pages', array($this, 'generate_static_pages'));
        add_action('wp_ajax_get_items_list', array($this, 'get_items_list'));
        add_action('wp_ajax_process_single_item', array($this, 'process_single_item'));
        add_action('wp_ajax_fix_static_directory', array($this, 'fix_static_directory'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Inicialización del plugin
        load_plugin_textdomain('greenborn-wp-static-pages', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Cargar archivos adicionales
        require_once GREENBORN_STATIC_PLUGIN_PATH . 'includes/class-static-generator.php';
        require_once GREENBORN_STATIC_PLUGIN_PATH . 'includes/class-page-processor.php';
    }
    
    public function activate() {
        // Solo crear el directorio básico si no existe
        $static_dir = greenborn_get_static_dir();
        if (!file_exists($static_dir)) {
            @wp_mkdir_p($static_dir);
        }
    }
    
    public function deactivate() {
        // Limpiar cualquier tarea programada
        wp_clear_scheduled_hook('greenborn_static_generation');
    }
    
    public function add_admin_menu() {
        // Agregar al menú principal de administración
        add_menu_page(
            'Greenborn Static Pages',
            'Static Pages',
            'manage_options',
            'greenborn-static-pages',
            array($this, 'admin_page'),
            'dashicons-admin-site',
            30
        );
        
        // Agregar submenús
        add_submenu_page(
            'greenborn-static-pages',
            'Generar Páginas',
            'Generar Páginas',
            'manage_options',
            'greenborn-static-pages',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'greenborn-static-pages',
            'Configuración',
            'Configuración',
            'manage_options',
            'greenborn-static-config',
            array($this, 'config_page')
        );
        
        add_submenu_page(
            'greenborn-static-pages',
            'Ayuda',
            'Ayuda',
            'manage_options',
            'greenborn-static-help',
            array($this, 'help_page')
        );
    }
    
    public function admin_page() {
        include GREENBORN_STATIC_PLUGIN_PATH . 'admin/admin-page.php';
    }
    
    public function config_page() {
        include GREENBORN_STATIC_PLUGIN_PATH . 'admin/config-page.php';
    }
    
    public function help_page() {
        include GREENBORN_STATIC_PLUGIN_PATH . 'admin/help-page.php';
    }
    
    public function generate_static_pages() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Acceso denegado');
        }
        
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'greenborn_static_generation')) {
            wp_die('Verificación de seguridad fallida');
        }
        
        try {
            // Solo generar el directorio y archivos básicos
            $generator = new GreenbornStaticGenerator();
            $result = $generator->prepare_static_directory();
            
            wp_send_json_success(array(
                'message' => 'Directorio preparado correctamente',
                'static_dir' => greenborn_get_static_dir(),
                'next_step' => 'get_items_list'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error preparando directorio: ' . $e->getMessage()
            ));
        }
    }
    
    public function get_items_list() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Acceso denegado');
        }
        
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'greenborn_static_generation')) {
            wp_die('Verificación de seguridad fallida');
        }
        
        try {
            $generator = new GreenbornStaticGenerator();
            $items = $generator->get_all_items_list();
            
            wp_send_json_success(array(
                'items' => $items,
                'total_items' => count($items),
                'message' => 'Lista de elementos obtenida correctamente'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error obteniendo lista de elementos: ' . $e->getMessage()
            ));
        }
    }
    
    public function process_single_item() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Acceso denegado');
        }
        
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'greenborn_static_generation')) {
            wp_die('Verificación de seguridad fallida');
        }
        
        try {
            $item_id = intval($_POST['item_id']);
            $item_type = sanitize_text_field($_POST['item_type']);
            
            $generator = new GreenbornStaticGenerator();
            $result = $generator->process_single_item($item_id, $item_type);
            
            wp_send_json_success(array(
                'item_id' => $item_id,
                'item_type' => $item_type,
                'result' => $result,
                'message' => 'Elemento procesado correctamente'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error procesando elemento: ' . $e->getMessage(),
                'item_id' => $item_id,
                'item_type' => $item_type
            ));
        }
    }
    
    public function fix_static_directory() {
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Acceso denegado');
        }
        
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'greenborn_static_generation')) {
            wp_die('Verificación de seguridad fallida');
        }
        
        try {
            $static_dir = greenborn_get_static_dir();
            
            // Intentar crear el directorio
            if (!file_exists($static_dir)) {
                $created = @wp_mkdir_p($static_dir);
                if (!$created) {
                    throw new Exception('No se pudo crear el directorio: ' . $static_dir);
                }
            }
            
            // Verificar permisos y propietario
            if (!is_writable($static_dir)) {
                $current_perms = substr(sprintf('%o', fileperms($static_dir)), -4);
                $owner = function_exists('posix_getpwuid') ? 
                    (posix_getpwuid(fileowner($static_dir))['name'] ?? 'unknown') : 
                    'unknown';
                $current_user = function_exists('posix_getpwuid') ? 
                    (posix_getpwuid(posix_geteuid())['name'] ?? 'unknown') : 
                    'unknown';
                
                // Intentar cambiar permisos automáticamente
                $chmod_result = @chmod($static_dir, 0755);
                
                if (!$chmod_result || !is_writable($static_dir)) {
                    throw new Exception(
                        'El directorio fue creado pero no es escribible. ' .
                        'Permisos: ' . $current_perms . ', Propietario: ' . $owner . ', Usuario actual: ' . $current_user . '. ' .
                        'Ejecuta manualmente: chmod 755 ' . $static_dir . ' && chown www-data:www-data ' . $static_dir . 
                        ' (o el usuario de tu servidor web)'
                    );
                }
            }
            
            wp_send_json_success(array(
                'message' => 'Directorio corregido correctamente',
                'static_dir' => $static_dir,
                'permissions' => substr(sprintf('%o', fileperms($static_dir)), -4),
                'owner' => function_exists('posix_getpwuid') ? 
                    (posix_getpwuid(fileowner($static_dir))['name'] ?? 'unknown') : 
                    'unknown'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error corrigiendo directorio: ' . $e->getMessage()
            ));
        }
    }
}

// Inicializar el plugin
new GreenbornWPStaticPages();
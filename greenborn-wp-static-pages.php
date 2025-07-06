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
define('GREENBORN_STATIC_DIR', ABSPATH . 'wp-static/');

// Clase principal del plugin
class GreenbornWPStaticPages {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_generate_static_pages', array($this, 'generate_static_pages'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Inicialización del plugin
        load_plugin_textdomain('greenborn-wp-static-pages', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        // Crear directorio estático si no existe
        if (!file_exists(GREENBORN_STATIC_DIR)) {
            wp_mkdir_p(GREENBORN_STATIC_DIR);
            
            // Crear archivo .htaccess para el directorio estático
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "RewriteEngine On\n";
            $htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
            $htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
            $htaccess_content .= "RewriteRule . /index.html [L]\n";
            
            file_put_contents(GREENBORN_STATIC_DIR . '.htaccess', $htaccess_content);
        }
        
        // Crear archivo index.html básico
        $this->create_basic_index();
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
            $generator = new GreenbornStaticGenerator();
            $result = $generator->generate_all_pages();
            
            wp_send_json_success(array(
                'message' => 'Páginas estáticas generadas correctamente',
                'pages_generated' => $result['pages_generated'],
                'static_dir' => GREENBORN_STATIC_DIR
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error al generar páginas estáticas: ' . $e->getMessage()
            ));
        }
    }
    
    private function create_basic_index() {
        $index_content = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitio Estático Generado</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #333; }
        p { line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sitio Estático Generado</h1>
        <p>Este es el directorio de páginas estáticas generadas por Greenborn WP Static Pages.</p>
        <p>Las páginas se generan automáticamente desde WordPress para mejorar el rendimiento y la seguridad.</p>
    </div>
</body>
</html>';
        
        file_put_contents(GREENBORN_STATIC_DIR . 'index.html', $index_content);
    }
}

// Inicializar el plugin
new GreenbornWPStaticPages();

// Incluir archivos adicionales
require_once GREENBORN_STATIC_PLUGIN_PATH . 'includes/class-static-generator.php';
require_once GREENBORN_STATIC_PLUGIN_PATH . 'includes/class-page-processor.php'; 
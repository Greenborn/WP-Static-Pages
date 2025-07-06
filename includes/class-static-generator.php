<?php
/**
 * Clase para generar páginas estáticas
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class GreenbornStaticGenerator {
    
    private $static_dir;
    private $processor;
    
    public function __construct() {
        $this->static_dir = GREENBORN_STATIC_DIR;
        $this->processor = new GreenbornPageProcessor();
    }
    
    /**
     * Genera todas las páginas estáticas
     */
    public function generate_all_pages() {
        $pages_generated = 0;
        $errors = array();
        
        // Verificar que el directorio existe y es escribible
        if (!is_dir($this->static_dir)) {
            wp_mkdir_p($this->static_dir);
        }
        
        if (!is_writable($this->static_dir)) {
            throw new Exception('El directorio estático no es escribible: ' . $this->static_dir);
        }
        
        // Generar página principal
        try {
            $this->generate_home_page();
            $pages_generated++;
        } catch (Exception $e) {
            $errors[] = 'Error en página principal: ' . $e->getMessage();
        }
        
        // Generar páginas de posts
        try {
            $posts_count = $this->generate_posts_pages();
            $pages_generated += $posts_count;
        } catch (Exception $e) {
            $errors[] = 'Error en páginas de posts: ' . $e->getMessage();
        }
        
        // Generar páginas de páginas
        try {
            $pages_count = $this->generate_pages_pages();
            $pages_generated += $pages_count;
        } catch (Exception $e) {
            $errors[] = 'Error en páginas de páginas: ' . $e->getMessage();
        }
        
        // Generar archivos de recursos
        try {
            $this->copy_assets();
        } catch (Exception $e) {
            $errors[] = 'Error copiando recursos: ' . $e->getMessage();
        }
        
        // Si hay errores, lanzar excepción
        if (!empty($errors)) {
            throw new Exception('Errores durante la generación: ' . implode(', ', $errors));
        }
        
        return array(
            'pages_generated' => $pages_generated,
            'errors' => $errors
        );
    }
    
    /**
     * Genera la página principal
     */
    private function generate_home_page() {
        $home_url = home_url('/');
        $html_content = $this->processor->get_page_content($home_url);
        
        if ($html_content) {
            $processed_content = $this->processor->process_content($html_content, $home_url);
            $file_path = $this->static_dir . 'index.html';
            file_put_contents($file_path, $processed_content);
        }
    }
    
    /**
     * Genera páginas para todos los posts
     */
    private function generate_posts_pages() {
        $posts = get_posts(array(
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        $count = 0;
        
        foreach ($posts as $post) {
            try {
                $post_url = get_permalink($post->ID);
                $html_content = $this->processor->get_page_content($post_url);
                
                if ($html_content) {
                    $processed_content = $this->processor->process_content($html_content, $post_url);
                    $file_path = $this->get_static_file_path($post_url);
                    
                    // Crear directorio si no existe
                    $dir = dirname($file_path);
                    if (!is_dir($dir)) {
                        wp_mkdir_p($dir);
                    }
                    
                    file_put_contents($file_path, $processed_content);
                    $count++;
                }
            } catch (Exception $e) {
                error_log('Error generando post ' . $post->ID . ': ' . $e->getMessage());
            }
        }
        
        return $count;
    }
    
    /**
     * Genera páginas para todas las páginas
     */
    private function generate_pages_pages() {
        $pages = get_pages(array(
            'number' => -1,
            'post_status' => 'publish'
        ));
        
        $count = 0;
        
        foreach ($pages as $page) {
            try {
                $page_url = get_permalink($page->ID);
                $html_content = $this->processor->get_page_content($page_url);
                
                if ($html_content) {
                    $processed_content = $this->processor->process_content($html_content, $page_url);
                    $file_path = $this->get_static_file_path($page_url);
                    
                    // Crear directorio si no existe
                    $dir = dirname($file_path);
                    if (!is_dir($dir)) {
                        wp_mkdir_p($dir);
                    }
                    
                    file_put_contents($file_path, $processed_content);
                    $count++;
                }
            } catch (Exception $e) {
                error_log('Error generando página ' . $page->ID . ': ' . $e->getMessage());
            }
        }
        
        return $count;
    }
    
    /**
     * Copia los recursos estáticos (CSS, JS, imágenes)
     */
    private function copy_assets() {
        $wp_content_dir = WP_CONTENT_DIR;
        $wp_includes_dir = ABSPATH . 'wp-includes';
        
        // Copiar directorio de uploads
        $uploads_dir = $wp_content_dir . '/uploads';
        if (is_dir($uploads_dir)) {
            $this->copy_directory($uploads_dir, $this->static_dir . 'wp-content/uploads');
        }
        
        // Copiar directorio de themes
        $themes_dir = $wp_content_dir . '/themes';
        if (is_dir($themes_dir)) {
            $this->copy_directory($themes_dir, $this->static_dir . 'wp-content/themes');
        }
        
        // Copiar directorio de plugins (solo assets)
        $plugins_dir = $wp_content_dir . '/plugins';
        if (is_dir($plugins_dir)) {
            $this->copy_plugin_assets($plugins_dir, $this->static_dir . 'wp-content/plugins');
        }
        
        // Copiar wp-includes (solo archivos necesarios)
        if (is_dir($wp_includes_dir)) {
            $this->copy_wp_includes($wp_includes_dir, $this->static_dir . 'wp-includes');
        }
    }
    
    /**
     * Copia un directorio recursivamente
     */
    private function copy_directory($source, $destination) {
        if (!is_dir($source)) {
            return false;
        }
        
        if (!is_dir($destination)) {
            wp_mkdir_p($destination);
        }
        
        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $source_path = $source . '/' . $file;
                $dest_path = $destination . '/' . $file;
                
                if (is_dir($source_path)) {
                    $this->copy_directory($source_path, $dest_path);
                } else {
                    copy($source_path, $dest_path);
                }
            }
        }
        closedir($dir);
        
        return true;
    }
    
    /**
     * Copia solo los assets de los plugins
     */
    private function copy_plugin_assets($source, $destination) {
        if (!is_dir($source)) {
            return false;
        }
        
        if (!is_dir($destination)) {
            wp_mkdir_p($destination);
        }
        
        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $source_path = $source . '/' . $file;
                $dest_path = $destination . '/' . $file;
                
                if (is_dir($source_path)) {
                    // Solo copiar directorios que contengan assets
                    if ($this->has_assets($source_path)) {
                        $this->copy_directory($source_path, $dest_path);
                    }
                }
            }
        }
        closedir($dir);
        
        return true;
    }
    
    /**
     * Verifica si un directorio contiene assets
     */
    private function has_assets($dir) {
        $asset_extensions = array('css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot');
        
        $dir_handle = opendir($dir);
        while (($file = readdir($dir_handle)) !== false) {
            if ($file != '.' && $file != '..') {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                if (in_array(strtolower($extension), $asset_extensions)) {
                    closedir($dir_handle);
                    return true;
                }
            }
        }
        closedir($dir_handle);
        
        return false;
    }
    
    /**
     * Copia archivos necesarios de wp-includes
     */
    private function copy_wp_includes($source, $destination) {
        if (!is_dir($source)) {
            return false;
        }
        
        if (!is_dir($destination)) {
            wp_mkdir_p($destination);
        }
        
        // Solo copiar archivos CSS y JS
        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $source_path = $source . '/' . $file;
                $dest_path = $destination . '/' . $file;
                
                if (is_file($source_path)) {
                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                    if (in_array(strtolower($extension), array('css', 'js'))) {
                        copy($source_path, $dest_path);
                    }
                }
            }
        }
        closedir($dir);
        
        return true;
    }
    
    /**
     * Obtiene la ruta del archivo estático basado en la URL
     */
    private function get_static_file_path($url) {
        $parsed_url = parse_url($url);
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
        
        // Si es la página principal, usar index.html
        if ($path == '/' || $path == '') {
            return $this->static_dir . 'index.html';
        }
        
        // Para otras páginas, crear estructura de directorios
        $path = trim($path, '/');
        
        // Si termina con .html, mantener la extensión
        if (pathinfo($path, PATHINFO_EXTENSION) == 'html') {
            return $this->static_dir . $path;
        }
        
        // Si no tiene extensión, agregar .html
        return $this->static_dir . $path . '.html';
    }
} 
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
        // Usar la función en lugar de la constante para asegurar que se obtiene la ruta correcta
        $this->static_dir = function_exists('greenborn_get_static_dir') ? 
            greenborn_get_static_dir() : 
            dirname(ABSPATH) . '/wp-static/';
        // El processor se inicializará cuando sea necesario
        $this->processor = null;
    }
    
    /**
     * Obtiene el processor de manera lazy
     */
    private function get_processor() {
        if ($this->processor === null) {
            $this->processor = new GreenbornPageProcessor();
        }
        return $this->processor;
    }
    
    /**
     * Prepara el directorio estático
     */
    public function prepare_static_directory() {
        // Verificar que el directorio existe y es escribible
        if (!is_dir($this->static_dir)) {
            $created = @wp_mkdir_p($this->static_dir);
            if (!$created) {
                throw new Exception('No se pudo crear el directorio estático: ' . $this->static_dir . 
                    '. Verifica los permisos del directorio padre.');
            }
        }
        
        // Verificar permisos de escritura
        if (!is_writable($this->static_dir)) {
            $current_perms = substr(sprintf('%o', fileperms($this->static_dir)), -4);
            $owner = posix_getpwuid(fileowner($this->static_dir))['name'] ?? 'unknown';
            $current_user = posix_getpwuid(posix_geteuid())['name'] ?? 'unknown';
            
            // Intentar cambiar permisos automáticamente
            $chmod_result = @chmod($this->static_dir, 0755);
            
            if (!$chmod_result || !is_writable($this->static_dir)) {
                throw new Exception(
                    'El directorio estático no es escribible: ' . $this->static_dir . 
                    ' (permisos: ' . $current_perms . ', propietario: ' . $owner . ', usuario actual: ' . $current_user . '). ' .
                    'Ejecuta en el servidor: chmod 755 ' . $this->static_dir . ' && chown www-data:www-data ' . $this->static_dir . 
                    ' (o el usuario de tu servidor web)'
                );
            }
        }
        
        // Vaciar el directorio antes de comenzar
        $this->clean_static_directory();
        
        // Generar página principal
        $this->generate_home_page();
        
        $this->log_message('Directorio estático preparado correctamente');
        
        return array(
            'static_dir' => $this->static_dir,
            'home_generated' => true
        );
    }
    
    /**
     * Limpia el directorio estático eliminando todos los archivos y subdirectorios
     */
    private function clean_static_directory() {
        if (!is_dir($this->static_dir)) {
            return;
        }
        
        $this->log_message('Limpiando directorio estático...');
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->static_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }
        
        $this->log_message('Directorio estático limpiado correctamente');
    }
    
    /**
     * Obtiene la lista de todos los elementos a procesar
     */
    public function get_all_items_list() {
        $items = array();
        
        // Obtener posts
        $posts = get_posts(array(
            'numberposts' => -1,
            'post_status' => 'publish',
            'post_type' => 'post'
        ));
        
        foreach ($posts as $post) {
            $items[] = array(
                'id' => $post->ID,
                'type' => 'post',
                'title' => $post->post_title,
                'url' => get_permalink($post->ID)
            );
        }
        
        // Obtener páginas
        $pages = get_pages(array(
            'number' => -1,
            'post_status' => 'publish',
            'post_type' => 'page'
        ));
        
        foreach ($pages as $page) {
            $items[] = array(
                'id' => $page->ID,
                'type' => 'page',
                'title' => $page->post_title,
                'url' => get_permalink($page->ID)
            );
        }
        
        $this->log_message('Lista de elementos obtenida: ' . count($items) . ' elementos');
        
        return $items;
    }
    
    /**
     * Procesa un elemento individual
     */
    public function process_single_item($item_id, $item_type) {
        try {
            if ($item_type === 'post') {
                $post = get_post($item_id);
                if (!$post || $post->post_status !== 'publish') {
                    throw new Exception('Post no encontrado o no publicado');
                }
                
                $url = get_permalink($item_id);
                $html_content = $this->get_page_content($url);
                
                if ($html_content) {
                    $file_path = $this->get_static_file_path($url);
                    
                    // Crear directorio si no existe
                    $dir = dirname($file_path);
                    if (!is_dir($dir)) {
                        wp_mkdir_p($dir);
                    }
                    
                    file_put_contents($file_path, $html_content);
                    
                    $this->log_message('Post procesado: ' . $post->post_title . ' (' . $item_id . ')');
                    
                    return array(
                        'success' => true,
                        'file_path' => $file_path,
                        'title' => $post->post_title
                    );
                } else {
                    throw new Exception('No se pudo obtener contenido del post');
                }
                
            } elseif ($item_type === 'page') {
                $page = get_page($item_id);
                if (!$page || $page->post_status !== 'publish') {
                    throw new Exception('Página no encontrada o no publicada');
                }
                
                $url = get_permalink($item_id);
                $html_content = $this->get_page_content($url);
                
                if ($html_content) {
                    $file_path = $this->get_static_file_path($url);
                    
                    // Crear directorio si no existe
                    $dir = dirname($file_path);
                    if (!is_dir($dir)) {
                        wp_mkdir_p($dir);
                    }
                    
                    file_put_contents($file_path, $html_content);
                    
                    $this->log_message('Página procesada: ' . $page->post_title . ' (' . $item_id . ')');
                    
                    return array(
                        'success' => true,
                        'file_path' => $file_path,
                        'title' => $page->post_title
                    );
                } else {
                    throw new Exception('No se pudo obtener contenido de la página');
                }
            } else {
                throw new Exception('Tipo de elemento no válido: ' . $item_type);
            }
            
        } catch (Exception $e) {
            $this->log_message('Error procesando elemento ' . $item_id . ' (' . $item_type . '): ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtiene el contenido de una página
     */
    private function get_page_content($url) {
        $content = @file_get_contents($url);
        
        if ($content === false) {
            $this->log_message('Error obteniendo contenido de: ' . $url);
            return false;
        }
        
        return $content;
    }
    
    /**
     * Obtiene la ruta del archivo estático para una URL
     */
    private function get_static_file_path($url) {
        $parsed_url = parse_url($url);
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
        
        // Si la ruta termina en /, usar index.html
        if ($path === '/' || substr($path, -1) === '/') {
            $file_path = $this->static_dir . $path . 'index.html';
        } else {
            // Para URLs como /post-slug, crear /post-slug/index.html
            $file_path = $this->static_dir . $path . '/index.html';
        }
        
        return $file_path;
    }
    
    /**
     * Genera la página principal
     */
    private function generate_home_page() {
        $home_url = home_url('/');
        
        try {
            // Obtener el contenido exacto del home
            $html_content = $this->get_home_content();
            
            if ($html_content) {
                // Guardar directamente el contenido sin procesar
                $file_path = $this->static_dir . 'index.html';
                $result = file_put_contents($file_path, $html_content);
                
                if ($result === false) {
                    throw new Exception('No se pudo escribir el archivo index.html');
                }
                
                $this->log_message('Página principal generada correctamente: ' . $file_path . ' (' . $result . ' bytes)');
            } else {
                throw new Exception('No se pudo obtener contenido del home');
            }
        } catch (Exception $e) {
            $this->log_message('Error generando página principal: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtiene el contenido exacto del home
     */
    private function get_home_content() {
        $home_url = home_url('/');
        
        // Petición GET simple al home
        $content = @file_get_contents($home_url);
        
        if ($content === false) {
            $this->log_message('Error obteniendo contenido del home: ' . error_get_last()['message']);
            return false;
        }
        
        $this->log_message('Contenido del home obtenido: ' . strlen($content) . ' bytes');
        return $content;
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
                $html_content = $this->get_processor()->get_page_content($post_url);
                
                if ($html_content) {
                    $processed_content = $this->get_processor()->process_content($html_content, $post_url);
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
                $html_content = $this->get_processor()->get_page_content($page_url);
                
                if ($html_content) {
                    $processed_content = $this->get_processor()->process_content($html_content, $page_url);
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
     * Registra mensajes en el log
     */
    private function log_message($message) {
        $log_file = WP_CONTENT_DIR . '/greenborn-static-pages.log';
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}" . PHP_EOL;
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
} 
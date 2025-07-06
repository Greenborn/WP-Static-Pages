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
    
    public function __construct() {
        // Usar la función en lugar de la constante para asegurar que se obtiene la ruta correcta
        $this->static_dir = function_exists('greenborn_get_static_dir') ? 
            greenborn_get_static_dir() : 
            dirname(ABSPATH) . '/wp-static/';
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
        
        // Crear subdirectorio assets si no existe
        $assets_dir = $this->static_dir . 'assets/';
        if (!is_dir($assets_dir)) {
            $assets_created = @wp_mkdir_p($assets_dir);
            if (!$assets_created) {
                throw new Exception('No se pudo crear el subdirectorio assets: ' . $assets_dir);
            }
            $this->log_message('Subdirectorio assets creado: ' . $assets_dir);
        }
        
        // Vaciar el directorio antes de comenzar
        $this->clean_static_directory();
        
        // Generar página principal
        $this->generate_home_page();
        
        $this->log_message('Directorio estático preparado correctamente');
        
        return array(
            'static_dir' => $this->static_dir,
            'assets_dir' => $assets_dir,
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
        
        // Recrear el directorio assets después de la limpieza
        $assets_dir = $this->static_dir . 'assets/';
        if (!is_dir($assets_dir)) {
            @wp_mkdir_p($assets_dir);
            $this->log_message('Directorio assets recreado después de la limpieza');
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
                
                // Extraer y copiar imágenes del post
                $copied_images = $this->extract_and_copy_images($item_id, $post->post_content);
                
                $url = get_permalink($item_id);
                $html_content = $this->get_page_content($url);
                
                if ($html_content) {
                    // Reemplazar URLs de imágenes en el contenido HTML
                    $html_content = $this->replace_image_urls_in_content($html_content, $copied_images);
                    
                    // Reemplazar URLs internas de WordPress con URLs estáticas
                    $html_content = $this->replace_internal_urls($html_content);
                    
                    $file_path = $this->get_static_file_path($url);
                    
                    // Crear directorio si no existe
                    $dir = dirname($file_path);
                    if (!is_dir($dir)) {
                        wp_mkdir_p($dir);
                    }
                    
                    file_put_contents($file_path, $html_content);
                    
                    $this->log_message('Post procesado: ' . $post->post_title . ' (' . $item_id . ') - ' . count($copied_images) . ' imágenes copiadas, URLs de imágenes e internas reemplazadas');
                    
                    return array(
                        'success' => true,
                        'file_path' => $file_path,
                        'title' => $post->post_title,
                        'images_copied' => count($copied_images)
                    );
                } else {
                    throw new Exception('No se pudo obtener contenido del post');
                }
                
            } elseif ($item_type === 'page') {
                $page = get_page($item_id);
                if (!$page || $page->post_status !== 'publish') {
                    throw new Exception('Página no encontrada o no publicada');
                }
                
                // Extraer y copiar imágenes de la página
                $copied_images = $this->extract_and_copy_images($item_id, $page->post_content);
                
                $url = get_permalink($item_id);
                $html_content = $this->get_page_content($url);
                
                if ($html_content) {
                    // Reemplazar URLs de imágenes en el contenido HTML
                    $html_content = $this->replace_image_urls_in_content($html_content, $copied_images);
                    
                    // Reemplazar URLs internas de WordPress con URLs estáticas
                    $html_content = $this->replace_internal_urls($html_content);
                    
                    $file_path = $this->get_static_file_path($url);
                    
                    // Crear directorio si no existe
                    $dir = dirname($file_path);
                    if (!is_dir($dir)) {
                        wp_mkdir_p($dir);
                    }
                    
                    file_put_contents($file_path, $html_content);
                    
                    $this->log_message('Página procesada: ' . $page->post_title . ' (' . $item_id . ') - ' . count($copied_images) . ' imágenes copiadas, URLs de imágenes e internas reemplazadas');
                    
                    return array(
                        'success' => true,
                        'file_path' => $file_path,
                        'title' => $page->post_title,
                        'images_copied' => count($copied_images)
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
        
        // Si la ruta es / o está vacía, usar index.html
        if ($path === '/' || $path === '') {
            $file_path = $this->static_dir . 'index.html';
        } else {
            // Para URLs como /post-slug/, crear post-slug.html
            // Remover la barra inicial y final
            $clean_path = trim($path, '/');
            $file_path = $this->static_dir . $clean_path . '.html';
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
                // Reemplazar URLs internas de WordPress con URLs estáticas
                $html_content = $this->replace_internal_urls($html_content);
                
                // Guardar el contenido procesado
                $file_path = $this->static_dir . 'index.html';
                $result = file_put_contents($file_path, $html_content);
                
                if ($result === false) {
                    throw new Exception('No se pudo escribir el archivo index.html');
                }
                
                $this->log_message('Página principal generada correctamente: ' . $file_path . ' (' . $result . ' bytes) - URLs internas reemplazadas');
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
    
    /**
     * Extrae y copia imágenes de un post o página al directorio assets
     */
    private function extract_and_copy_images($post_id, $post_content) {
        $assets_dir = $this->static_dir . 'assets/';
        $copied_images = array();
        
        // Buscar imágenes en el contenido usando regex
        $image_patterns = array(
            '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i',
            '/background-image:\s*url\(["\']?([^"\')\s]+)["\']?\)/i',
            '/src=["\']([^"\']*\.(jpg|jpeg|png|gif|webp|svg))["\']/i'
        );
        
        $found_images = array();
        foreach ($image_patterns as $pattern) {
            if (preg_match_all($pattern, $post_content, $matches)) {
                foreach ($matches[1] as $image_url) {
                    if (!empty($image_url) && !in_array($image_url, $found_images)) {
                        $found_images[] = $image_url;
                    }
                }
            }
        }
        
        // Procesar cada imagen encontrada
        foreach ($found_images as $image_url) {
            try {
                $result = $this->copy_image_to_assets($image_url, $assets_dir);
                if ($result) {
                    $copied_images[] = $result;
                }
            } catch (Exception $e) {
                $this->log_message('Error copiando imagen: ' . $image_url . ' - ' . $e->getMessage());
            }
        }
        
        if (!empty($copied_images)) {
            $this->log_message('Copiadas ' . count($copied_images) . ' imágenes para post ' . $post_id);
        }
        
        return $copied_images;
    }
    
    /**
     * Copia una imagen específica al directorio assets
     */
    private function copy_image_to_assets($image_url, $assets_dir) {
        // Convertir URL relativa a absoluta si es necesario
        $absolute_url = $this->get_absolute_image_url($image_url);
        
        if (empty($absolute_url)) {
            return false;
        }
        
        // Obtener la ruta del archivo en el servidor
        $server_path = $this->get_server_path_from_url($absolute_url);
        
        if (empty($server_path) || !file_exists($server_path)) {
            return false;
        }
        
        // Generar nombre único para el archivo
        $file_info = pathinfo($server_path);
        $extension = strtolower($file_info['extension'] ?? '');
        
        // Verificar que es una imagen válida
        $valid_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
        if (!in_array($extension, $valid_extensions)) {
            return false;
        }
        
        // Crear nombre único basado en el hash del archivo
        $file_hash = md5_file($server_path);
        $new_filename = $file_hash . '.' . $extension;
        $destination_path = $assets_dir . $new_filename;
        
        // Solo copiar si no existe ya (evitar duplicados)
        if (!file_exists($destination_path)) {
            $copied = @copy($server_path, $destination_path);
            if ($copied) {
                return array(
                    'original_url' => $image_url,
                    'original_path' => $server_path,
                    'new_filename' => $new_filename,
                    'assets_path' => $destination_path
                );
            }
        } else {
            // El archivo ya existe, retornar información
            return array(
                'original_url' => $image_url,
                'original_path' => $server_path,
                'new_filename' => $new_filename,
                'assets_path' => $destination_path,
                'already_exists' => true
            );
        }
        
        return false;
    }
    
    /**
     * Convierte una URL de imagen a URL absoluta
     */
    private function get_absolute_image_url($image_url) {
        // Si ya es una URL absoluta, retornarla
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            return $image_url;
        }
        
        // Si es una ruta relativa, convertirla a absoluta
        if (strpos($image_url, '/') === 0) {
            // Ruta absoluta desde el dominio
            return home_url($image_url);
        }
        
        // Ruta relativa desde el directorio actual
        return home_url('/' . ltrim($image_url, '/'));
    }
    
    /**
     * Obtiene la ruta del servidor desde una URL
     */
    private function get_server_path_from_url($url) {
        // Si es una URL externa, no podemos acceder al archivo
        $home_url = home_url();
        if (strpos($url, $home_url) !== 0) {
            return false;
        }
        
        // Convertir URL a ruta del servidor
        $relative_path = str_replace($home_url, '', $url);
        $server_path = ABSPATH . ltrim($relative_path, '/');
        
        return $server_path;
    }
    
    /**
     * Reemplaza las URLs de imágenes en el contenido HTML con URLs relativas del directorio assets
     */
    private function replace_image_urls_in_content($html_content, $copied_images) {
        if (empty($copied_images)) {
            return $html_content;
        }
        
        // Crear un mapeo de URLs originales a nombres de archivo en assets
        $url_mapping = array();
        foreach ($copied_images as $image_info) {
            $original_url = $image_info['original_url'];
            $new_filename = $image_info['new_filename'];
            
            // Normalizar la URL original para el mapeo
            $normalized_url = $this->normalize_image_url($original_url);
            $url_mapping[$normalized_url] = 'assets/' . $new_filename;
        }
        
        $this->log_message('Mapeo de URLs creado para ' . count($copied_images) . ' imágenes');
        
        // Reemplazar URLs en el contenido HTML
        $modified_content = $html_content;
        
        // 1. Reemplazar en etiquetas <img> - patrón mejorado para manejar múltiples atributos
        $modified_content = preg_replace_callback(
            '/<img([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i',
            function($matches) use ($url_mapping) {
                $before_src = $matches[1];
                $original_url = $matches[2];
                $after_src = $matches[3];
                
                $normalized_url = $this->normalize_image_url($original_url);
                
                if (isset($url_mapping[$normalized_url])) {
                    $new_url = $url_mapping[$normalized_url];
                    $this->log_message('Reemplazando <img>: ' . $original_url . ' -> ' . $new_url);
                    return '<img' . $before_src . 'src="' . $new_url . '"' . $after_src . '>';
                }
                
                return $matches[0]; // Mantener original si no se encuentra en el mapeo
            },
            $modified_content
        );
        
        // 2. Reemplazar en CSS background-image
        $modified_content = preg_replace_callback(
            '/background-image:\s*url\(["\']?([^"\')\s]+)["\']?\)/i',
            function($matches) use ($url_mapping) {
                $original_url = $matches[1];
                $normalized_url = $this->normalize_image_url($original_url);
                
                if (isset($url_mapping[$normalized_url])) {
                    $new_url = $url_mapping[$normalized_url];
                    $this->log_message('Reemplazando background-image: ' . $original_url . ' -> ' . $new_url);
                    return 'background-image: url("' . $new_url . '")';
                }
                
                return $matches[0]; // Mantener original si no se encuentra en el mapeo
            },
            $modified_content
        );
        
        // 3. Reemplazar en otros atributos src (más específico para imágenes)
        $modified_content = preg_replace_callback(
            '/src=["\']([^"\']*\.(jpg|jpeg|png|gif|webp|svg))["\']/i',
            function($matches) use ($url_mapping) {
                $original_url = $matches[1];
                $normalized_url = $this->normalize_image_url($original_url);
                
                if (isset($url_mapping[$normalized_url])) {
                    $new_url = $url_mapping[$normalized_url];
                    $this->log_message('Reemplazando src genérico: ' . $original_url . ' -> ' . $new_url);
                    return 'src="' . $new_url . '"';
                }
                
                return $matches[0]; // Mantener original si no se encuentra en el mapeo
            },
            $modified_content
        );
        
        // 4. Reemplazar en data-src (para lazy loading)
        $modified_content = preg_replace_callback(
            '/data-src=["\']([^"\']*\.(jpg|jpeg|png|gif|webp|svg))["\']/i',
            function($matches) use ($url_mapping) {
                $original_url = $matches[1];
                $normalized_url = $this->normalize_image_url($original_url);
                
                if (isset($url_mapping[$normalized_url])) {
                    $new_url = $url_mapping[$normalized_url];
                    $this->log_message('Reemplazando data-src: ' . $original_url . ' -> ' . $new_url);
                    return 'data-src="' . $new_url . '"';
                }
                
                return $matches[0]; // Mantener original si no se encuentra en el mapeo
            },
            $modified_content
        );
        
        // 5. Reemplazar en data-lazy-src (para otros plugins de lazy loading)
        $modified_content = preg_replace_callback(
            '/data-lazy-src=["\']([^"\']*\.(jpg|jpeg|png|gif|webp|svg))["\']/i',
            function($matches) use ($url_mapping) {
                $original_url = $matches[1];
                $normalized_url = $this->normalize_image_url($original_url);
                
                if (isset($url_mapping[$normalized_url])) {
                    $new_url = $url_mapping[$normalized_url];
                    $this->log_message('Reemplazando data-lazy-src: ' . $original_url . ' -> ' . $new_url);
                    return 'data-lazy-src="' . $new_url . '"';
                }
                
                return $matches[0]; // Mantener original si no se encuentra en el mapeo
            },
            $modified_content
        );
        
        // 6. Reemplazar en URLs dentro de atributos data (para bloques dinámicos)
        $modified_content = preg_replace_callback(
            '/data-[^=]+=["\']([^"\']*\.(jpg|jpeg|png|gif|webp|svg))["\']/i',
            function($matches) use ($url_mapping) {
                $original_url = $matches[1];
                $normalized_url = $this->normalize_image_url($original_url);
                
                if (isset($url_mapping[$normalized_url])) {
                    $new_url = $url_mapping[$normalized_url];
                    $this->log_message('Reemplazando data-attribute: ' . $original_url . ' -> ' . $new_url);
                    return str_replace($original_url, $new_url, $matches[0]);
                }
                
                return $matches[0]; // Mantener original si no se encuentra en el mapeo
            },
            $modified_content
        );
        
        // 7. Reemplazar en URLs dentro de JSON o configuraciones (para bloques complejos)
        $modified_content = preg_replace_callback(
            '/"([^"]*\.(jpg|jpeg|png|gif|webp|svg))"/i',
            function($matches) use ($url_mapping) {
                $original_url = $matches[1];
                $normalized_url = $this->normalize_image_url($original_url);
                
                if (isset($url_mapping[$normalized_url])) {
                    $new_url = $url_mapping[$normalized_url];
                    $this->log_message('Reemplazando JSON URL: ' . $original_url . ' -> ' . $new_url);
                    return '"' . $new_url . '"';
                }
                
                return $matches[0]; // Mantener original si no se encuentra en el mapeo
            },
            $modified_content
        );
        
        $this->log_message('URLs de imágenes reemplazadas en el contenido HTML');
        
        return $modified_content;
    }
    
    /**
     * Normaliza una URL de imagen para el mapeo
     */
    private function normalize_image_url($url) {
        // Si es una URL absoluta del sitio, convertirla a relativa
        $home_url = home_url();
        if (strpos($url, $home_url) === 0) {
            $url = str_replace($home_url, '', $url);
        }
        
        // Asegurar que comience con /
        if (strpos($url, '/') !== 0) {
            $url = '/' . $url;
        }
        
        return $url;
    }
    
    /**
     * Reemplaza las URLs internas de WordPress con URLs estáticas
     */
    private function replace_internal_urls($html_content) {
        $home_url = home_url();
        $modified_content = $html_content;
        
        // Obtener todos los posts y páginas para crear el mapeo
        $items = $this->get_all_items_list();
        $url_mapping = array();
        
        foreach ($items as $item) {
            $original_url = $item['url'];
            $static_url = $this->get_static_url_from_wordpress_url($original_url);
            $url_mapping[$original_url] = $static_url;
        }
        
        // Agregar el home
        $url_mapping[$home_url] = './index.html';
        $url_mapping[$home_url . '/'] = './index.html';
        
        $this->log_message('Mapeo de URLs internas creado para ' . count($url_mapping) . ' elementos');
        
        // Reemplazar en enlaces <a href>
        $modified_content = preg_replace_callback(
            '/<a([^>]*?)href=["\']([^"\']+)["\']([^>]*?)>/i',
            function($matches) use ($url_mapping, $home_url) {
                $before_href = $matches[1];
                $original_url = $matches[2];
                $after_href = $matches[3];
                
                // Solo procesar URLs del mismo sitio
                if (strpos($original_url, $home_url) === 0 || strpos($original_url, '/') === 0) {
                    $normalized_url = $this->normalize_internal_url($original_url, $home_url);
                    
                    if (isset($url_mapping[$normalized_url])) {
                        $new_url = $url_mapping[$normalized_url];
                        $this->log_message('Reemplazando enlace interno: ' . $original_url . ' -> ' . $new_url);
                        return '<a' . $before_href . 'href="' . $new_url . '"' . $after_href . '>';
                    }
                }
                
                return $matches[0]; // Mantener original si no se encuentra en el mapeo
            },
            $modified_content
        );
        
        // Reemplazar en formularios action
        $modified_content = preg_replace_callback(
            '/<form([^>]*?)action=["\']([^"\']+)["\']([^>]*?)>/i',
            function($matches) use ($url_mapping, $home_url) {
                $before_action = $matches[1];
                $original_url = $matches[2];
                $after_action = $matches[3];
                
                // Solo procesar URLs del mismo sitio
                if (strpos($original_url, $home_url) === 0 || strpos($original_url, '/') === 0) {
                    $normalized_url = $this->normalize_internal_url($original_url, $home_url);
                    
                    if (isset($url_mapping[$normalized_url])) {
                        $new_url = $url_mapping[$normalized_url];
                        $this->log_message('Reemplazando form action: ' . $original_url . ' -> ' . $new_url);
                        return '<form' . $before_action . 'action="' . $new_url . '"' . $after_action . '>';
                    }
                }
                
                return $matches[0]; // Mantener original si no se encuentra en el mapeo
            },
            $modified_content
        );
        
        // Reemplazar en meta refresh
        $modified_content = preg_replace_callback(
            '/<meta([^>]*?)content=["\']([^"\']*?url=([^"\']+)[^"\']*?)["\']([^>]*?)>/i',
            function($matches) use ($url_mapping, $home_url) {
                $before_content = $matches[1];
                $content = $matches[2];
                $original_url = $matches[3];
                $after_content = $matches[4];
                
                // Solo procesar URLs del mismo sitio
                if (strpos($original_url, $home_url) === 0 || strpos($original_url, '/') === 0) {
                    $normalized_url = $this->normalize_internal_url($original_url, $home_url);
                    
                    if (isset($url_mapping[$normalized_url])) {
                        $new_url = $url_mapping[$normalized_url];
                        $new_content = str_replace($original_url, $new_url, $content);
                        $this->log_message('Reemplazando meta refresh: ' . $original_url . ' -> ' . $new_url);
                        return '<meta' . $before_content . 'content="' . $new_content . '"' . $after_content . '>';
                    }
                }
                
                return $matches[0]; // Mantener original si no se encuentra en el mapeo
            },
            $modified_content
        );
        
        // Reemplazar en JavaScript (window.location, etc.)
        $modified_content = preg_replace_callback(
            '/(window\.location|location\.href)\s*=\s*["\']([^"\']+)["\']/i',
            function($matches) use ($url_mapping, $home_url) {
                $js_var = $matches[1];
                $original_url = $matches[2];
                
                // Solo procesar URLs del mismo sitio
                if (strpos($original_url, $home_url) === 0 || strpos($original_url, '/') === 0) {
                    $normalized_url = $this->normalize_internal_url($original_url, $home_url);
                    
                    if (isset($url_mapping[$normalized_url])) {
                        $new_url = $url_mapping[$normalized_url];
                        $this->log_message('Reemplazando JavaScript redirect: ' . $original_url . ' -> ' . $new_url);
                        return $js_var . ' = "' . $new_url . '"';
                    }
                }
                
                return $matches[0]; // Mantener original si no se encuentra en el mapeo
            },
            $modified_content
        );
        
        $this->log_message('URLs internas reemplazadas en el contenido HTML');
        
        return $modified_content;
    }
    
    /**
     * Normaliza una URL interna para el mapeo
     */
    private function normalize_internal_url($url, $home_url) {
        // Si es una URL absoluta del sitio, convertirla a completa
        if (strpos($url, $home_url) === 0) {
            return $url;
        }
        
        // Si es una URL relativa, convertirla a absoluta
        if (strpos($url, '/') === 0) {
            return $home_url . $url;
        }
        
        // Si es una URL relativa sin /, agregar /
        if (strpos($url, 'http') !== 0) {
            return $home_url . '/' . $url;
        }
        
        return $url;
    }
    
    /**
     * Obtiene la URL estática correspondiente a una URL de WordPress
     */
    private function get_static_url_from_wordpress_url($wordpress_url) {
        $parsed_url = parse_url($wordpress_url);
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
        
        // Si la ruta es / o está vacía, usar index.html
        if ($path === '/' || $path === '') {
            return './index.html';
        } else {
            // Para URLs como /post-slug/, crear ./post-slug.html
            // Remover la barra inicial y final
            $clean_path = trim($path, '/');
            return './' . $clean_path . '.html';
        }
    }
} 
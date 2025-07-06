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
     * Renderiza un template por defecto si no existe el template del home
     */
    private function render_default_home_template() {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php wp_title('|', true, 'right'); ?></title>
            <meta name="description" content="<?php bloginfo('description'); ?>">
            <link rel="canonical" href="<?php echo esc_url(home_url('/')); ?>">
            <?php wp_head(); ?>
        </head>
        <body <?php body_class(); ?>>
            <?php wp_body_open(); ?>
            
            <div id="page" class="site">
                <header id="masthead" class="site-header">
                    <div class="site-branding">
                        <h1 class="site-title">
                            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                                <?php bloginfo('name'); ?>
                            </a>
                        </h1>
                        <?php if (get_bloginfo('description')) : ?>
                            <p class="site-description"><?php bloginfo('description'); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <nav id="site-navigation" class="main-navigation">
                        <?php
                        wp_nav_menu(array(
                            'theme_location' => 'primary',
                            'menu_id'        => 'primary-menu',
                            'fallback_cb'    => false,
                        ));
                        ?>
                    </nav>
                </header>
                
                <div id="content" class="site-content">
                    <main id="main" class="site-main">
                        <?php if (have_posts()) : ?>
                            <div class="posts-container">
                                <?php while (have_posts()) : the_post(); ?>
                                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                                        <header class="entry-header">
                                            <?php if (has_post_thumbnail()) : ?>
                                                <div class="post-thumbnail">
                                                    <a href="<?php the_permalink(); ?>">
                                                        <?php the_post_thumbnail('medium'); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <h2 class="entry-title">
                                                <a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
                                            </h2>
                                            
                                            <div class="entry-meta">
                                                <span class="posted-on">
                                                    <?php echo get_the_date(); ?>
                                                </span>
                                                <span class="byline">
                                                    <?php echo get_the_author(); ?>
                                                </span>
                                            </div>
                                        </header>
                                        
                                        <div class="entry-content">
                                            <?php the_excerpt(); ?>
                                        </div>
                                        
                                        <footer class="entry-footer">
                                            <a href="<?php the_permalink(); ?>" class="read-more">
                                                <?php _e('Read More', 'greenborn-wp-static-pages'); ?>
                                            </a>
                                        </footer>
                                    </article>
                                <?php endwhile; ?>
                            </div>
                            
                            <?php
                            // Navegación de posts
                            the_posts_pagination(array(
                                'mid_size'  => 2,
                                'prev_text' => __('Previous', 'greenborn-wp-static-pages'),
                                'next_text' => __('Next', 'greenborn-wp-static-pages'),
                            ));
                            ?>
                        <?php else : ?>
                            <div class="no-posts">
                                <h2><?php _e('No posts found', 'greenborn-wp-static-pages'); ?></h2>
                                <p><?php _e('It looks like nothing was found at this location.', 'greenborn-wp-static-pages'); ?></p>
                            </div>
                        <?php endif; ?>
                    </main>
                </div>
                
                <footer id="colophon" class="site-footer">
                    <div class="footer-content">
                        <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php _e('All rights reserved.', 'greenborn-wp-static-pages'); ?></p>
                        <p><?php _e('Generated by Greenborn WP Static Pages', 'greenborn-wp-static-pages'); ?></p>
                    </div>
                </footer>
            </div>
            
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
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
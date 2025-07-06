<?php
/**
 * Clase para procesar el contenido de las páginas
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class GreenbornPageProcessor {
    
    private $site_url;
    private $static_url;
    
    public function __construct() {
        $this->site_url = home_url('/');
        $this->static_url = str_replace(ABSPATH, '', GREENBORN_STATIC_DIR);
    }
    
    /**
     * Obtiene el contenido HTML de una URL
     */
    public function get_page_content($url) {
        // Configurar el contexto de la petición
        $context = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => array(
                    'User-Agent: WordPress Static Generator',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
                    'Accept-Encoding: gzip, deflate',
                    'Connection: keep-alive',
                    'Upgrade-Insecure-Requests: 1',
                ),
                'timeout' => 30,
                'follow_location' => true,
                'max_redirects' => 5
            )
        ));
        
        // Realizar la petición
        $content = @file_get_contents($url, false, $context);
        
        if ($content === false) {
            error_log('Error obteniendo contenido de: ' . $url);
            return false;
        }
        
        return $content;
    }
    
    /**
     * Procesa el contenido HTML para optimizarlo
     */
    public function process_content($html_content, $page_url) {
        // Si el contenido está vacío, retornar
        if (empty($html_content)) {
            return false;
        }
        
        // Para el index.html, mantener el contenido lo más fiel posible
        // Solo procesar URLs y recursos, sin modificar la estructura
        $processed_html = $this->process_content_faithful($html_content, $page_url);
        
        return $processed_html;
    }
    
    /**
     * Procesa el contenido manteniendo fidelidad al original
     */
    private function process_content_faithful($html_content, $page_url) {
        // Procesar URLs usando expresiones regulares para mantener la estructura exacta
        $processed_html = $this->process_urls_regex($html_content, $page_url);
        
        // Procesar formularios (solo deshabilitar, no remover)
        $processed_html = $this->process_forms_regex($processed_html);
        
        return $processed_html;
    }
    
    /**
     * Procesa URLs usando expresiones regulares para mantener la estructura
     */
    private function process_urls_regex($html_content, $page_url) {
        $site_url = $this->site_url;
        $static_url = $this->static_url;
        
        // Procesar enlaces href
        $html_content = preg_replace_callback(
            '/href=["\']([^"\']+)["\']/i',
            function($matches) use ($site_url, $static_url) {
                $url = $matches[1];
                return $this->convert_url_to_static($url, $site_url, $static_url);
            },
            $html_content
        );
        
        // Procesar src de imágenes
        $html_content = preg_replace_callback(
            '/src=["\']([^"\']+)["\']/i',
            function($matches) use ($site_url, $static_url) {
                $url = $matches[1];
                return $this->convert_url_to_static($url, $site_url, $static_url);
            },
            $html_content
        );
        
        // Procesar src de scripts
        $html_content = preg_replace_callback(
            '/<script[^>]*src=["\']([^"\']+)["\'][^>]*>/i',
            function($matches) use ($site_url, $static_url) {
                $url = $matches[1];
                $converted_url = $this->convert_url_to_static($url, $site_url, $static_url);
                return str_replace('src="' . $url . '"', 'src="' . $converted_url . '"', $matches[0]);
            },
            $html_content
        );
        
        // Procesar href de CSS
        $html_content = preg_replace_callback(
            '/<link[^>]*href=["\']([^"\']+)["\'][^>]*>/i',
            function($matches) use ($site_url, $static_url) {
                $url = $matches[1];
                $converted_url = $this->convert_url_to_static($url, $site_url, $static_url);
                return str_replace('href="' . $url . '"', 'href="' . $converted_url . '"', $matches[0]);
            },
            $html_content
        );
        
        // Procesar URLs en atributos data-*
        $html_content = preg_replace_callback(
            '/data-[^=]+=["\']([^"\']+)["\']/i',
            function($matches) use ($site_url, $static_url) {
                $url = $matches[1];
                return $this->convert_url_to_static($url, $site_url, $static_url);
            },
            $html_content
        );
        
        return $html_content;
    }
    
    /**
     * Convierte una URL a su versión estática
     */
    private function convert_url_to_static($url, $site_url, $static_url) {
        // Si es una URL absoluta del sitio
        if (strpos($url, $site_url) === 0) {
            $relative_path = str_replace($site_url, '', $url);
            return $static_url . $relative_path;
        }
        
        // Si es una URL relativa que comienza con /
        if (strpos($url, '/') === 0) {
            return $static_url . $url;
        }
        
        // Si es una URL relativa sin /
        if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0 && strpos($url, '#') !== 0 && strpos($url, 'mailto:') !== 0) {
            return $static_url . '/' . $url;
        }
        
        // Mantener URL original si no es del sitio
        return $url;
    }
    
    /**
     * Procesa formularios usando expresiones regulares
     */
    private function process_forms_regex($html_content) {
        // Agregar mensaje de formulario deshabilitado
        $html_content = preg_replace_callback(
            '/<form([^>]*)>/i',
            function($matches) {
                $form_attrs = $matches[1];
                $message = '<div class="form-disabled-message" style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc; color: #666;">Este formulario está deshabilitado en la versión estática.</div>';
                return '<form' . $form_attrs . ' disabled="disabled" style="opacity: 0.5; pointer-events: none;">' . $message;
            },
            $html_content
        );
        
        return $html_content;
    }
    
    /**
     * Procesa los enlaces para que apunten a archivos estáticos
     */
    private function process_links($dom, $page_url) {
        $links = $dom->getElementsByTagName('a');
        
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            
            if ($href && !$this->is_external_url($href)) {
                $static_href = $this->convert_to_static_url($href, $page_url);
                if ($static_href) {
                    $link->setAttribute('href', $static_href);
                }
            }
        }
    }
    
    /**
     * Procesa los recursos (CSS, JS, imágenes)
     */
    private function process_resources($dom, $page_url) {
        // Procesar CSS
        $css_links = $dom->getElementsByTagName('link');
        foreach ($css_links as $link) {
            $rel = $link->getAttribute('rel');
            if ($rel === 'stylesheet') {
                $href = $link->getAttribute('href');
                if ($href && !$this->is_external_url($href)) {
                    $static_href = $this->convert_to_static_url($href, $page_url);
                    if ($static_href) {
                        $link->setAttribute('href', $static_href);
                    }
                }
            }
        }
        
        // Procesar JS
        $scripts = $dom->getElementsByTagName('script');
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            if ($src && !$this->is_external_url($src)) {
                $static_src = $this->convert_to_static_url($src, $page_url);
                if ($static_src) {
                    $script->setAttribute('src', $static_src);
                }
            }
        }
        
        // Procesar imágenes
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if ($src && !$this->is_external_url($src)) {
                $static_src = $this->convert_to_static_url($src, $page_url);
                if ($static_src) {
                    $img->setAttribute('src', $static_src);
                }
            }
            
            // Procesar srcset si existe
            $srcset = $img->getAttribute('srcset');
            if ($srcset) {
                $static_srcset = $this->process_srcset($srcset, $page_url);
                if ($static_srcset) {
                    $img->setAttribute('srcset', $static_srcset);
                }
            }
        }
    }
    
    /**
     * Procesa formularios para deshabilitar funcionalidad dinámica
     */
    private function process_forms($dom) {
        $forms = $dom->getElementsByTagName('form');
        
        foreach ($forms as $form) {
            // Agregar mensaje de formulario deshabilitado
            $message = $dom->createElement('div');
            $message->setAttribute('class', 'form-disabled-message');
            $message->setAttribute('style', 'background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;');
            $message->textContent = 'Este formulario está deshabilitado en la versión estática.';
            
            $form->insertBefore($message, $form->firstChild);
            
            // Deshabilitar el formulario
            $form->setAttribute('disabled', 'disabled');
            $form->setAttribute('style', 'opacity: 0.5; pointer-events: none;');
        }
    }
    
    /**
     * Procesa scripts para remover funcionalidad dinámica
     */
    private function process_scripts($dom) {
        $scripts = $dom->getElementsByTagName('script');
        
        foreach ($scripts as $script) {
            $src = $script->getAttribute('src');
            
            // Si es un script externo de WordPress, comentarlo
            if ($src && strpos($src, 'wp-includes') !== false) {
                $script->parentNode->removeChild($script);
            }
            
            // Si es un script inline que contiene AJAX o formularios, comentarlo
            if (!$src) {
                $content = $script->textContent;
                if (strpos($content, 'ajax') !== false || 
                    strpos($content, 'form') !== false ||
                    strpos($content, 'submit') !== false) {
                    $script->parentNode->removeChild($script);
                }
            }
        }
    }
    
    /**
     * Procesa el head y meta tags
     */
    private function process_head($dom, $page_url) {
        $head = $dom->getElementsByTagName('head')->item(0);
        if (!$head) {
            return;
        }
        
        // Procesar meta tags
        $meta_tags = $head->getElementsByTagName('meta');
        foreach ($meta_tags as $meta) {
            $content = $meta->getAttribute('content');
            if ($content && strpos($content, 'http') === 0) {
                $static_content = $this->convert_to_static_url($content, $page_url);
                if ($static_content) {
                    $meta->setAttribute('content', $static_content);
                }
            }
        }
        
        // Procesar link tags (favicon, etc.)
        $link_tags = $head->getElementsByTagName('link');
        foreach ($link_tags as $link) {
            $href = $link->getAttribute('href');
            if ($href && !$this->is_external_url($href)) {
                $static_href = $this->convert_to_static_url($href, $page_url);
                if ($static_href) {
                    $link->setAttribute('href', $static_href);
                }
            }
        }
    }
    
    /**
     * Optimiza el HTML final
     */
    private function optimize_html($html) {
        // Remover comentarios HTML innecesarios (excepto los importantes)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>|wp-|WordPress))(?:(?!-->).)*-->/s', '', $html);
        
        // Remover espacios en blanco extra
        $html = preg_replace('/\s+/', ' ', $html);
        
        // Remover espacios antes de cierre de tags
        $html = preg_replace('/\s+>/', '>', $html);
        
        // Remover espacios después de apertura de tags
        $html = preg_replace('/>\s+/', '>', $html);
        
        // Limpiar el encoding XML que agregamos
        $html = str_replace('<?xml encoding="UTF-8">', '', $html);
        
        return $html;
    }
    
    /**
     * Convierte una URL a su versión estática
     */
    private function convert_to_static_url($url, $page_url) {
        // Si es una URL absoluta del sitio
        if (strpos($url, $this->site_url) === 0) {
            $relative_path = str_replace($this->site_url, '', $url);
            return $this->static_url . $relative_path;
        }
        
        // Si es una URL relativa
        if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0) {
            // Resolver la URL relativa
            $resolved_url = $this->resolve_relative_url($url, $page_url);
            if ($resolved_url) {
                return $this->convert_to_static_url($resolved_url, $page_url);
            }
        }
        
        return $url;
    }
    
    /**
     * Resuelve una URL relativa a absoluta
     */
    private function resolve_relative_url($relative_url, $base_url) {
        $base_parts = parse_url($base_url);
        $relative_parts = parse_url($relative_url);
        
        if (isset($relative_parts['scheme'])) {
            return $relative_url; // Ya es absoluta
        }
        
        $result = $base_parts['scheme'] . '://' . $base_parts['host'];
        
        if (isset($base_parts['port'])) {
            $result .= ':' . $base_parts['port'];
        }
        
        if (isset($relative_parts['path'])) {
            if (strpos($relative_parts['path'], '/') === 0) {
                $result .= $relative_parts['path'];
            } else {
                $base_path = isset($base_parts['path']) ? $base_parts['path'] : '/';
                $result .= dirname($base_path) . '/' . $relative_parts['path'];
            }
        } else {
            $result .= isset($base_parts['path']) ? $base_parts['path'] : '/';
        }
        
        if (isset($relative_parts['query'])) {
            $result .= '?' . $relative_parts['query'];
        }
        
        if (isset($relative_parts['fragment'])) {
            $result .= '#' . $relative_parts['fragment'];
        }
        
        return $result;
    }
    
    /**
     * Procesa el atributo srcset de imágenes
     */
    private function process_srcset($srcset, $page_url) {
        $sources = explode(',', $srcset);
        $processed_sources = array();
        
        foreach ($sources as $source) {
            $parts = preg_split('/\s+/', trim($source));
            if (count($parts) >= 1) {
                $url = $parts[0];
                $static_url = $this->convert_to_static_url($url, $page_url);
                
                $parts[0] = $static_url;
                $processed_sources[] = implode(' ', $parts);
            }
        }
        
        return implode(', ', $processed_sources);
    }
    
    /**
     * Verifica si una URL es externa
     */
    private function is_external_url($url) {
        if (strpos($url, 'http') !== 0) {
            return false; // URL relativa
        }
        
        $url_parts = parse_url($url);
        $site_parts = parse_url($this->site_url);
        
        return $url_parts['host'] !== $site_parts['host'];
    }
} 
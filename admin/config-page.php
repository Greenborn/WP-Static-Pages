<?php
// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap greenborn-static-pages-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="card">
        <h2>Configuración del Directorio Estático</h2>
        <p>El plugin genera páginas estáticas en el directorio <code>wp-static/</code> que debe configurarse como root del dominio para su correcto funcionamiento.</p>
        
        <div class="notice notice-info">
            <p><strong>Directorio estático actual:</strong> <code><?php echo esc_html(GREENBORN_STATIC_DIR); ?></code></p>
        </div>
        
        <h3>Estado del Directorio</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Existencia del directorio</th>
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
            <tr>
                <th scope="row">Archivo index.html</th>
                <td>
                    <?php if (file_exists(GREENBORN_STATIC_DIR . 'index.html')): ?>
                        <span class="status-indicator success"></span>✓ Archivo creado
                    <?php else: ?>
                        <span class="status-indicator error"></span>✗ Archivo no existe
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="card">
        <h2>Configuración del Servidor Web</h2>
        <p>Para que el sitio estático funcione correctamente, debes configurar tu servidor web para servir el directorio <code>wp-static/</code> como root del dominio.</p>
        
        <h3>Apache (.htaccess)</h3>
        <p>El plugin genera automáticamente un archivo <code>.htaccess</code> en el directorio estático. Si necesitas configuración adicional, puedes agregar estas reglas:</p>
        <pre><code># Configuración adicional para Apache
RewriteEngine On

# Cache de archivos estáticos
&lt;FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$"&gt;
    ExpiresActive On
    ExpiresDefault "access plus 1 year"
    Header set Cache-Control "public, immutable"
&lt;/FilesMatch&gt;

# Compresión GZIP
&lt;IfModule mod_deflate.c&gt;
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
&lt;/IfModule&gt;</code></pre>
        
        <h3>Nginx</h3>
        <p>Si usas Nginx, agrega esta configuración a tu archivo de configuración del sitio:</p>
        <pre><code>server {
    listen 80;
    server_name tu-dominio.com;
    root /ruta/a/tu/wordpress/wp-static;
    index index.html;
    
    # Configuración para archivos estáticos
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # Cache para archivos estáticos
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # Compresión GZIP
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
}</code></pre>
        
        <h3>Configuración del Dominio</h3>
        <p>Para configurar el directorio estático como root del dominio:</p>
        <ol>
            <li><strong>Apache:</strong> Cambia el DocumentRoot en la configuración del virtual host</li>
            <li><strong>Nginx:</strong> Cambia la directiva root en la configuración del servidor</li>
            <li><strong>Panel de control:</strong> Configura el directorio público en tu panel de hosting</li>
        </ol>
    </div>
    
    <div class="card">
        <h2>Configuración de DNS</h2>
        <p>Si quieres usar un subdominio para el sitio estático (ej: static.tudominio.com), configura los registros DNS correspondientes.</p>
        
        <h3>Ejemplo de configuración:</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Tipo</th>
                <th scope="row">Nombre</th>
                <th scope="row">Valor</th>
            </tr>
            <tr>
                <td>A</td>
                <td>static</td>
                <td>IP_DEL_SERVIDOR</td>
            </tr>
            <tr>
                <td>CNAME</td>
                <td>static</td>
                <td>tudominio.com</td>
            </tr>
        </table>
    </div>
    
    <div class="card">
        <h2>Verificación de Configuración</h2>
        <p>Una vez configurado el servidor, puedes verificar que todo funcione correctamente:</p>
        
        <h3>Pruebas recomendadas:</h3>
        <ul>
            <li>Acceder al sitio estático desde el navegador</li>
            <li>Verificar que las imágenes y recursos se carguen correctamente</li>
            <li>Comprobar que los enlaces internos funcionen</li>
            <li>Verificar la velocidad de carga con herramientas como PageSpeed Insights</li>
        </ul>
        
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=greenborn-static-pages')); ?>" class="button button-primary">
                Ir a Generar Páginas
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=greenborn-static-help')); ?>" class="button">
                Ver Ayuda
            </a>
        </p>
    </div>
</div> 
<?php
// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap greenborn-static-pages-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="card">
        <h2>¿Qué es Greenborn WP Static Pages?</h2>
        <p>Greenborn WP Static Pages es un plugin que genera automáticamente páginas HTML estáticas de tu sitio WordPress, creando un micrositio optimizado en el directorio <code>wp-static/</code>.</p>
        
        <h3>Beneficios principales:</h3>
        <ul>
            <li><strong>🚀 Rendimiento mejorado:</strong> Las páginas estáticas se cargan más rápido</li>
            <li><strong>🔒 Mayor seguridad:</strong> No expone código PHP ejecutable</li>
            <li><strong>⚡ Menor carga del servidor:</strong> No requiere procesamiento dinámico</li>
            <li><strong>📈 Mejor SEO:</strong> Páginas más rápidas mejoran el posicionamiento</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>Guía de Uso Rápida</h2>
        
        <h3>1. Generar Páginas Estáticas</h3>
        <ol>
            <li>Ve a <strong>Static Pages > Generar Páginas</strong> en el menú de administración</li>
            <li>Haz clic en <strong>"Generar Páginas Estáticas"</strong></li>
            <li>Espera a que se complete el proceso</li>
            <li>Verifica que se hayan generado los archivos en <code>wp-static/</code></li>
        </ol>
        
        <h3>2. Configurar el Servidor</h3>
        <ol>
            <li>Ve a <strong>Static Pages > Configuración</strong></li>
            <li>Sigue las instrucciones para tu servidor web (Apache/Nginx)</li>
            <li>Configura el directorio <code>wp-static/</code> como root del dominio</li>
            <li>Verifica que el sitio estático funcione correctamente</li>
        </ol>
        
        <h3>3. Mantener Actualizado</h3>
        <ol>
            <li>Cuando hagas cambios en WordPress, regenera las páginas estáticas</li>
            <li>Puedes programar la generación automática (próximamente)</li>
            <li>Mantén una copia de seguridad del directorio estático</li>
        </ol>
    </div>
    
    <div class="card">
        <h2>Estructura del Directorio Estático</h2>
        <p>El plugin crea la siguiente estructura en el directorio <code>wp-static/</code>:</p>
        
        <pre><code>wp-static/
├── index.html                 # Página principal
├── .htaccess                  # Configuración de Apache
├── wp-content/
│   ├── uploads/              # Imágenes y archivos subidos
│   ├── themes/               # Temas (solo assets)
│   └── plugins/              # Plugins (solo assets)
└── wp-includes/              # Archivos CSS/JS de WordPress</code></pre>
        
        <h3>Archivos generados:</h3>
        <ul>
            <li><strong>index.html:</strong> Página principal del sitio (versión estática del home actual)</li>
            <li><strong>página.html:</strong> Cada página publicada se convierte en un archivo HTML</li>
            <li><strong>post.html:</strong> Cada post publicado se convierte en un archivo HTML</li>
            <li><strong>.htaccess:</strong> Configuración básica para Apache</li>
        </ul>
        
        <h3>Generación del Home:</h3>
        <p>El plugin genera una copia exacta del contenido del home:</p>
        <ul>
            <li><strong>Petición GET simple:</strong> Hace una petición GET directa a la URL del sitio</li>
            <li><strong>Sin procesamiento:</strong> El contenido se guarda tal como se recibe</li>
            <li><strong>Máxima simplicidad:</strong> No se modifica ni optimiza el HTML</li>
            <li><strong>Contenido exacto:</strong> El index.html es idéntico al resultado de la petición GET</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>Limitaciones y Consideraciones</h2>
        
        <h3>Funcionalidades deshabilitadas:</h3>
        <ul>
            <li><strong>Formularios:</strong> Se deshabilitan automáticamente con un mensaje informativo</li>
            <li><strong>AJAX:</strong> Los scripts de AJAX se remueven para evitar errores</li>
            <li><strong>Funcionalidad dinámica:</strong> Cualquier funcionalidad que requiera PHP se deshabilita</li>
            <li><strong>Comentarios:</strong> Los formularios de comentarios no funcionarán</li>
        </ul>
        
        <h3>Consideraciones importantes:</h3>
        <ul>
            <li><strong>Actualizaciones manuales:</strong> Las páginas estáticas deben regenerarse cuando se actualiza el contenido</li>
            <li><strong>Recursos externos:</strong> Los recursos que apunten a URLs externas no se copian</li>
            <li><strong>Tamaño del directorio:</strong> El directorio estático puede crecer considerablemente</li>
            <li><strong>Backup:</strong> Mantén copias de seguridad del directorio estático</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>Solución de Problemas Comunes</h2>
        
        <h3>Error: "Directorio no es escribible"</h3>
        <p><strong>Solución:</strong> Cambia los permisos del directorio:</p>
        <pre><code>chmod 755 wp-static/
chown www-data:www-data wp-static/</code></pre>
        
        <h3>Error: "No se pudo obtener contenido"</h3>
        <p><strong>Posibles causas:</strong></p>
        <ul>
            <li>El sitio no es accesible desde el servidor</li>
            <li><code>allow_url_fopen</code> está deshabilitado en PHP</li>
            <li>Problemas de conectividad del servidor</li>
        </ul>
        
        <h3>Páginas no se generan correctamente</h3>
        <p><strong>Verifica:</strong></p>
        <ul>
            <li>Que las páginas estén publicadas</li>
            <li>Los permisos de archivos</li>
            <li>Que no haya errores en el tema o plugins</li>
            <li>Los logs de error de WordPress</li>
        </ul>
        
        <h3>Recursos no se cargan</h3>
        <p><strong>Solución:</strong> Verifica que:</p>
        <ul>
            <li>Los archivos CSS/JS se copiaron correctamente</li>
            <li>Las rutas en el HTML son correctas</li>
            <li>El servidor web está configurado para servir archivos estáticos</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>Herramientas de Verificación</h2>
        
        <h3>Herramientas recomendadas:</h3>
        <ul>
            <li><strong>PageSpeed Insights:</strong> Para verificar la velocidad de carga</li>
            <li><strong>GTmetrix:</strong> Para análisis de rendimiento</li>
            <li><strong>WebPageTest:</strong> Para pruebas de velocidad detalladas</li>
            <li><strong>Lighthouse:</strong> Para auditorías de rendimiento y SEO</li>
        </ul>
        
        <h3>Verificaciones manuales:</h3>
        <ul>
            <li>Acceder al sitio estático desde diferentes navegadores</li>
            <li>Verificar que todos los enlaces funcionen</li>
            <li>Comprobar que las imágenes se carguen correctamente</li>
            <li>Verificar que el CSS y JavaScript funcionen</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>Próximas Características</h2>
        <p>Estamos trabajando en las siguientes mejoras:</p>
        <ul>
            <li>Generación automática programada (cron jobs)</li>
            <li>Integración con CDN</li>
            <li>Optimización automática de imágenes</li>
            <li>Compresión automática de archivos</li>
            <li>Notificaciones por email cuando se completan las generaciones</li>
            <li>Interfaz para configurar exclusiones de páginas</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>Enlaces Útiles</h2>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=greenborn-static-pages')); ?>" class="button button-primary">
                Generar Páginas Estáticas
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=greenborn-static-config')); ?>" class="button">
                Configuración
            </a>
            <a href="https://wordpress.org/support/" target="_blank" class="button">
                Soporte de WordPress
            </a>
        </p>
    </div>
</div> 
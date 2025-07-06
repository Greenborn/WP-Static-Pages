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
            <li>Confirma que entiendes que el directorio será limpiado completamente</li>
            <li>El proceso seguirá estos pasos automáticamente:
                <ul>
                    <li><strong>Paso 1:</strong> Limpiar el directorio estático (eliminar archivos existentes)</li>
                    <li><strong>Paso 2:</strong> Preparar directorio y generar página principal</li>
                    <li><strong>Paso 3:</strong> Obtener lista de todos los posts y páginas</li>
                    <li><strong>Paso 4:</strong> Mostrar listado de elementos a procesar</li>
                    <li><strong>Paso 5:</strong> Procesar cada elemento individualmente con progreso en tiempo real</li>
                </ul>
            </li>
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
        <h2>📁 Ubicación del Directorio Estático</h2>
        <p>El plugin crea automáticamente un directorio <code>wp-static/</code> en la raíz de tu instalación de WordPress.</p>
        
        <h3>¿Cómo se determina la ruta?</h3>
        <p>El plugin usa <code>get_home_path()</code>, una función nativa de WordPress que es más confiable que <code>dirname(ABSPATH)</code> para obtener la ruta real del directorio de WordPress. Esto asegura que funcione correctamente en diferentes configuraciones de servidor.</p>
        
        <h3>Ubicaciones típicas:</h3>
        <ul>
            <li><strong>Servidor Linux:</strong> <code>/var/www/html/wp-static/</code></li>
            <li><strong>XAMPP Windows:</strong> <code>C:\xampp\htdocs\wp-static\</code></li>
            <li><strong>MAMP Mac:</strong> <code>/Applications/MAMP/htdocs/wp-static/</code></li>
            <li><strong>Servidor compartido:</strong> <code>/home/usuario/public_html/wp-static/</code></li>
        </ul>
        
        <h3>Verificación de ruta:</h3>
        <p>Si tienes problemas con la ruta del directorio, verifica:</p>
        <ol>
            <li>Que WordPress esté correctamente instalado</li>
            <li>Que el archivo <code>wp-config.php</code> esté en la ubicación correcta</li>
            <li>Que las constantes <code>ABSPATH</code> y <code>WP_HOME</code> estén bien definidas</li>
        </ol>
    </div>
    
    <div class="card">
        <h2>Estructura del Directorio Estático</h2>
        <p>El plugin crea la siguiente estructura en el directorio <code>wp-static/</code>:</p>
        
        <pre><code>wp-static/
├── index.html                 # Página principal
├── post-slug/
│   └── index.html            # Páginas individuales de posts
├── page-slug/
│   └── index.html            # Páginas individuales de páginas
├── .htaccess                  # Configuración de Apache
├── wp-content/
│   ├── uploads/              # Imágenes y archivos subidos
│   ├── themes/               # Temas (solo assets)
│   └── plugins/              # Plugins (solo assets)
└── wp-includes/              # Archivos CSS/JS de WordPress</code></pre>
        
        <h3>Archivos generados:</h3>
        <ul>
            <li><strong>index.html:</strong> Página principal del sitio (versión estática del home actual)</li>
            <li><strong>post-slug/index.html:</strong> Páginas individuales de cada post</li>
            <li><strong>page-slug/index.html:</strong> Páginas individuales de cada página</li>
            <li><strong>.htaccess:</strong> Configuración básica para Apache</li>
        </ul>
        
        <p><em>Cada post y página se genera como un archivo index.html en su propio directorio para mantener URLs limpias.</em></p>
        
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
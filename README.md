# Greenborn WP Static Pages

Plugin de WordPress para generar versiones estáticas de todas las páginas y posts del sitio, mejorando significativamente la velocidad de carga, cacheo y seguridad al no exponer código ejecutable del backend.

## Características

- **Generación completa**: Crea versiones estáticas de todos los posts y páginas publicadas
- **Procesamiento individual**: Procesa cada elemento uno a uno con progreso visual en tiempo real
- **Limpieza automática**: Limpia completamente el directorio antes de generar contenido nuevo
- **Manejo de permisos**: Verifica y ayuda a corregir permisos del directorio
- **Interfaz intuitiva**: Panel de administración con estados visuales y confirmaciones
- **Ruta inteligente**: Usa `get_home_path()` para obtener la ruta correcta del directorio de WordPress
- **Copia automática de imágenes**: Extrae y copia todas las imágenes de posts y páginas al directorio `assets/`

## Instalación

1. Sube el plugin a `/wp-content/plugins/greenborn-wp-static-pages/`
2. Activa el plugin desde el panel de administración
3. Ve a **Static Pages > Generar Páginas** para comenzar

## Configuración

### Directorio Estático

El plugin crea automáticamente un directorio `wp-static/` en la raíz de tu instalación de WordPress. La ruta se determina usando `get_home_path()`, que es más confiable que `dirname(ABSPATH)` para obtener la ruta real del directorio de WordPress.

**Ubicación típica:**
- `/var/www/html/wp-static/` (servidor Linux)
- `C:\xampp\htdocs\wp-static\` (XAMPP Windows)
- `/Applications/MAMP/htdocs/wp-static/` (MAMP Mac)

### Configuración del Servidor Web

Para que el sitio estático funcione correctamente, debes configurar tu servidor web para servir el directorio `wp-static/` como root del dominio:

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/wp-static/
RewriteRule ^(.*)$ /wp-static/$1 [L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /wp-static$uri /wp-static$uri/ /wp-static/index.html;
}
```

## Uso

### Generación de Páginas Estáticas

1. **Preparación**: El plugin verifica que el directorio `wp-static/` existe y es escribible
2. **Limpieza**: Se eliminan todos los archivos y subdirectorios existentes
3. **Procesamiento**: Cada post y página se procesa individualmente:
   - **Pendiente**: Elemento en cola para procesamiento
   - **Procesando**: Elemento siendo generado actualmente
   - **Completado**: Elemento generado exitosamente
   - **Error**: Error en la generación del elemento

### Estados Visuales

- 🟡 **Pendiente**: Elemento en cola
- 🔵 **Procesando**: Elemento siendo generado
- 🟢 **Completado**: Elemento generado exitosamente
- 🔴 **Error**: Error en la generación

## Estructura de Archivos Generados

```
wp-static/
├── index.html              # Página principal
├── .htaccess              # Configuración Apache
├── assets/                # Directorio centralizado con todas las imágenes del sitio
│   ├── [hash1].jpg        # Imágenes extraídas de posts y páginas
│   ├── [hash2].png        # Nombres únicos basados en hash MD5
│   └── [hash3].gif        # Evita duplicados automáticamente
├── post-slug/
│   └── index.html         # Post individual
└── page-slug/
    └── index.html         # Página individual
```

### Gestión de Imágenes

El plugin extrae automáticamente todas las imágenes de posts y páginas y las copia al directorio `assets/`:

- **Detección automática**: Busca imágenes en etiquetas `<img>`, `background-image` y atributos `src`
- **Nombres únicos**: Usa hash MD5 del archivo para evitar duplicados
- **Formatos soportados**: JPG, JPEG, PNG, GIF, WebP, SVG
- **URLs relativas y absolutas**: Convierte automáticamente URLs relativas a absolutas
- **Centralización**: Todas las imágenes del sitio en un solo directorio
- **Reemplazo automático de URLs**: Las URLs en el HTML se actualizan para usar rutas relativas al directorio `assets/`

### Reemplazo Automático de URLs

El plugin reemplaza automáticamente todas las URLs de imágenes en el contenido HTML generado:

- **URLs relativas**: `assets/[hash].jpg` en lugar de rutas absolutas
- **Compatibilidad**: Funciona con etiquetas `<img>`, CSS `background-image` y otros atributos `src`
- **Mapeo inteligente**: Mantiene la relación entre URLs originales y archivos copiados
- **Preservación**: Las URLs que no corresponden a imágenes copiadas se mantienen sin cambios

## Solución de Problemas

### Permisos del Directorio

Si el plugin no puede escribir en el directorio `wp-static/`, ejecuta estos comandos en tu servidor:

```bash
mkdir -p /ruta/a/tu/wordpress/wp-static/
chmod 755 /ruta/a/tu/wordpress/wp-static/
chown www-data:www-data /ruta/a/tu/wordpress/wp-static/
```

*Nota: Reemplaza "www-data" con el usuario de tu servidor web si es diferente.*

### Verificación de Ruta

El plugin usa `get_home_path()` para determinar la ruta correcta del directorio de WordPress. Si tienes problemas con la ruta, verifica:

1. Que WordPress esté correctamente instalado
2. Que el archivo `wp-config.php` esté en la ubicación correcta
3. Que las constantes `ABSPATH` y `WP_HOME` estén bien definidas

## Seguridad

- **Sin código ejecutable**: El sitio estático no contiene código PHP
- **Sin base de datos**: No requiere conexión a base de datos
- **Archivos estáticos**: Solo HTML, CSS y JavaScript
- **Mejor rendimiento**: Carga más rápida y menor uso de recursos

## Compatibilidad

- WordPress 5.0+
- PHP 7.4+
- Apache/Nginx
- Múltiples configuraciones de servidor

## Licencia

GPL v2 o posterior

## Desarrollo

### Estructura del plugin

```
greenborn-wp-static-pages/
├── greenborn-wp-static-pages.php    # Archivo principal
├── admin/
│   └── admin-page.php               # Interfaz de administración
├── includes/
│   ├── class-static-generator.php   # Generador de páginas
│   └── class-page-processor.php     # Procesador de contenido
├── assets/
│   └── css/
│       └── admin-style.css          # Estilos de admin
├── languages/
│   └── greenborn-wp-static-pages.pot # Archivo de traducción
├── readme.txt                       # Información del plugin
├── uninstall.php                    # Script de desinstalación
└── README.md                        # Esta documentación
```

### Hooks disponibles

```php
// Antes de generar páginas
do_action('greenborn_before_static_generation');

// Después de generar páginas
do_action('greenborn_after_static_generation', $result);

// Antes de procesar una página
do_action('greenborn_before_page_processing', $url);

// Después de procesar una página
do_action('greenborn_after_page_processing', $url, $file_path);
```

### Filtros disponibles

```php
// Modificar la lista de páginas a generar
$pages = apply_filters('greenborn_pages_to_generate', $pages);

// Modificar la configuración del procesador
$config = apply_filters('greenborn_processor_config', $config);

// Modificar el contenido HTML antes de guardar
$html = apply_filters('greenborn_before_save_html', $html, $url);
```

## Soporte

Para reportar bugs o solicitar características, por favor crea un issue en el repositorio del proyecto.

## Changelog

### 0.1.0
- Versión inicial
- Generación básica de páginas estáticas
- Interfaz de administración
- Optimización de recursos
- Copia automática de assets 
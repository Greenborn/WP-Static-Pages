# Arquitectura del Plugin Greenborn WP Static Pages

## Visión General

El plugin **Greenborn WP Static Pages** es una solución completa para generar versiones estáticas de sitios WordPress, mejorando significativamente el rendimiento, cacheo y seguridad al eliminar la necesidad de procesamiento dinámico en el frontend.

## Arquitectura del Sistema

### 1. Estructura de Archivos

```
greenborn-wp-static-pages/
├── greenborn-wp-static-pages.php    # Archivo principal del plugin
├── includes/
│   ├── class-static-generator.php   # Generador principal de páginas estáticas
│   └── class-page-processor.php     # Procesador de contenido HTML
├── admin/
│   ├── admin-page.php               # Página principal de administración
│   ├── config-page.php              # Página de configuración
│   └── help-page.php                # Página de ayuda
├── assets/                          # Recursos del plugin (CSS, JS, imágenes)
├── languages/                       # Archivos de internacionalización
├── README.md                        # Documentación principal
└── arquitectura.md                  # Este archivo
```

### 2. Componentes Principales

#### 2.1 Clase Principal (`GreenbornWPStaticPages`)
- **Responsabilidad**: Punto de entrada del plugin, gestión de hooks y menús
- **Funciones clave**:
  - Inicialización del plugin
  - Registro de hooks de WordPress
  - Gestión de menús de administración
  - Manejo de endpoints AJAX

#### 2.2 Generador Estático (`GreenbornStaticGenerator`)
- **Responsabilidad**: Lógica principal de generación de páginas estáticas
- **Funciones clave**:
  - Preparación del directorio estático
  - Procesamiento de posts y páginas
  - Extracción y copia de imágenes
  - Limpieza de directorios
  - Generación de archivos HTML

#### 2.3 Procesador de Páginas (`GreenbornPageProcessor`)
- **Responsabilidad**: Procesamiento y optimización de contenido HTML
- **Funciones clave**:
  - Obtención de contenido de páginas
  - Procesamiento de HTML
  - Aplicación de filtros y hooks
  - Optimización de contenido

### 3. Flujo de Procesamiento

#### 3.1 Inicialización
```
1. Plugin se activa
2. Se definen constantes y funciones helper
3. Se registran hooks de WordPress
4. Se cargan archivos de clases
5. Se inicializa la interfaz de administración
```

#### 3.2 Generación de Páginas Estáticas
```
1. Usuario inicia el proceso desde la interfaz
2. Se verifica y prepara el directorio wp-static/
3. Se crea el subdirectorio assets/
4. Se obtiene la lista completa de posts y páginas
5. Se procesa cada elemento individualmente:
   a. Se extrae el contenido HTML
   b. Se detectan y copian imágenes al directorio assets/
   c. Se genera el archivo HTML estático
   d. Se actualiza el progreso en tiempo real
6. Se completa el proceso y se muestra el resumen
```

#### 3.3 Procesamiento de Imágenes
```
1. Se analiza el contenido HTML del post/página
2. Se buscan imágenes usando patrones regex:
   - Etiquetas <img> con atributo src
   - CSS background-image
   - URLs de imágenes en atributos src
3. Se convierten URLs relativas a absolutas
4. Se verifica que los archivos existan en el servidor
5. Se generan nombres únicos basados en hash MD5
6. Se copian las imágenes al directorio assets/
7. Se evitan duplicados automáticamente
8. Se reemplazan las URLs en el contenido HTML con rutas relativas al directorio assets/
```

### 4. Gestión de Rutas y Permisos

#### 4.1 Detección de Rutas
- **Función principal**: `greenborn_get_static_dir()`
- **Método preferido**: `get_home_path()` (función nativa de WordPress)
- **Fallback**: `dirname(ABSPATH)` si `get_home_path()` no está disponible
- **Inclusión automática**: Se incluye `wp-admin/includes/file.php` si es necesario

#### 4.2 Manejo de Permisos
- **Verificación automática**: Permisos de escritura del directorio
- **Información detallada**: Propietario, permisos actuales, usuario del servidor
- **Corrección automática**: Intento de cambio de permisos
- **Comandos específicos**: Sugerencias personalizadas para cada sistema

### 5. Interfaz de Usuario

#### 5.1 Página Principal de Administración
- **Estado del directorio**: Existencia, permisos, propietario
- **Progreso visual**: Estados pendiente, procesando, completado, error
- **Información detallada**: Número de imágenes copiadas por elemento
- **Confirmaciones**: Advertencias sobre limpieza de directorio

#### 5.2 Página de Configuración
- **Información del sistema**: Rutas, permisos, archivos generados
- **Estado de componentes**: Directorio, .htaccess, index.html, assets
- **Comandos de corrección**: Sugerencias específicas para problemas

#### 5.3 Página de Ayuda
- **Documentación completa**: Instrucciones de uso y configuración
- **Solución de problemas**: Guías para errores comunes
- **Configuración del servidor**: Ejemplos para Apache y Nginx

### 6. Gestión de Datos

#### 6.1 Estructura de Datos
```php
// Elemento individual (post o página)
$item = [
    'id' => 123,
    'type' => 'post|page',
    'title' => 'Título del elemento',
    'url' => 'https://ejemplo.com/post-slug/'
];

// Resultado de procesamiento
$result = [
    'success' => true,
    'file_path' => '/ruta/al/archivo.html',
    'title' => 'Título del elemento',
    'images_copied' => 3
];

// Información de imagen copiada
$image_info = [
    'original_url' => 'https://ejemplo.com/imagen.jpg',
    'original_path' => '/ruta/original/imagen.jpg',
    'new_filename' => 'abc123def456.jpg',
    'assets_path' => '/wp-static/assets/abc123def456.jpg'
];

// Mapeo de URLs para reemplazo
$url_mapping = [
    '/wp-content/uploads/imagen.jpg' => 'assets/abc123def456.jpg',
    'https://ejemplo.com/imagen.png' => 'assets/def456ghi789.png'
];
```

#### 6.2 Logging y Monitoreo
- **Archivo de log**: Registro detallado de operaciones
- **Mensajes informativos**: Progreso y estado de operaciones
- **Manejo de errores**: Captura y registro de excepciones
- **Información de depuración**: Detalles técnicos para troubleshooting

### 7. Seguridad y Validación

#### 7.1 Validaciones de Seguridad
- **Verificación de permisos**: Solo usuarios con `manage_options`
- **Nonces**: Protección CSRF en todas las operaciones AJAX
- **Sanitización**: Limpieza de datos de entrada
- **Validación de tipos**: Verificación de tipos de datos

#### 7.2 Validaciones de Contenido
- **Verificación de archivos**: Existencia y accesibilidad
- **Validación de formatos**: Solo formatos de imagen permitidos
- **Verificación de URLs**: URLs internas vs externas
- **Control de duplicados**: Evita copias innecesarias

#### 7.3 Reemplazo de URLs de Imágenes
- **Mapeo inteligente**: Relación entre URLs originales y archivos copiados
- **Normalización de URLs**: Conversión de URLs absolutas a relativas
- **Patrones de búsqueda**: Regex para etiquetas `<img>`, CSS `background-image` y atributos `src`
- **Preservación de contenido**: URLs no mapeadas se mantienen sin cambios
- **URLs relativas**: Todas las imágenes apuntan a `assets/[hash].[ext]`

### 8. Optimizaciones

#### 8.1 Rendimiento
- **Procesamiento asíncrono**: Operaciones AJAX no bloqueantes
- **Progreso en tiempo real**: Actualización continua del estado
- **Manejo de memoria**: Procesamiento por elementos individuales
- **Cacheo de rutas**: Evita recálculos innecesarios

#### 8.2 Gestión de Recursos
- **Nombres únicos**: Hash MD5 para evitar conflictos
- **Eliminación de duplicados**: Una sola copia por imagen
- **Limpieza automática**: Eliminación de archivos antiguos
- **Optimización de espacio**: Solo archivos necesarios

### 9. Compatibilidad

#### 9.1 WordPress
- **Versiones soportadas**: WordPress 5.0+
- **Hooks utilizados**: `init`, `admin_menu`, `wp_ajax_*`
- **Funciones nativas**: `get_home_path()`, `wp_mkdir_p()`, etc.
- **Compatibilidad con temas**: Funciona con cualquier tema

#### 9.2 Servidores Web
- **Apache**: Configuración .htaccess automática
- **Nginx**: Instrucciones de configuración proporcionadas
- **Permisos**: Manejo automático de permisos de directorios
- **Usuarios**: Compatibilidad con diferentes usuarios de servidor

### 10. Extensibilidad

#### 10.1 Hooks y Filtros
```php
// Filtro para modificar HTML antes de guardar
apply_filters('greenborn_before_save_html', $html, $url);

// Hook para acciones personalizadas
do_action('greenborn_after_page_generated', $file_path, $post_id);
```

#### 10.2 Estructura Modular
- **Clases independientes**: Fácil extensión y modificación
- **Métodos públicos**: API clara para desarrolladores
- **Configuración flexible**: Constantes y opciones configurables
- **Documentación completa**: Guías para desarrolladores

## Conclusión

La arquitectura del plugin está diseñada para ser robusta, escalable y fácil de mantener. Utiliza las mejores prácticas de WordPress y proporciona una base sólida para la generación de sitios estáticos con funcionalidades avanzadas como la gestión automática de imágenes.

El sistema es modular, bien documentado y preparado para futuras extensiones y mejoras. 
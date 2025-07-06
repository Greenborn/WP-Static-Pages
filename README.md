# Greenborn WP Static Pages

Plugin de WordPress que genera automáticamente páginas HTML estáticas para mejorar el rendimiento y la seguridad de tu sitio.

## Características

- ✅ Generación automática de páginas estáticas HTML
- ✅ Optimización de recursos (CSS, JS, imágenes)
- ✅ Conversión automática de URLs a rutas estáticas
- ✅ Deshabilitación de formularios y funcionalidad dinámica
- ✅ Copia automática de recursos estáticos
- ✅ Interfaz de administración simple
- ✅ Compatibilidad con la última versión de WordPress

## Instalación

1. **Subir el plugin**: Copia la carpeta `greenborn-wp-static-pages` a `/wp-content/plugins/`
2. **Activar**: Ve a Plugins > Plugins instalados y activa "Greenborn WP Static Pages"
3. **Configurar**: Ve a Ajustes > Static Pages para generar las páginas estáticas

## Uso

### Generación de páginas estáticas

1. Ve a **Ajustes > Static Pages** en el panel de administración
2. Haz clic en **"Generar Páginas Estáticas"**
3. El plugin generará automáticamente:
   - Página principal (`index.html`)
   - Todas las páginas publicadas
   - Todos los posts publicados
   - Recursos estáticos (CSS, JS, imágenes)

### Directorio generado

El plugin crea el directorio `wp-static/` en la raíz de WordPress con la siguiente estructura:

```
wp-static/
├── index.html                 # Página principal
├── .htaccess                  # Configuración de Apache
├── wp-content/
│   ├── uploads/              # Imágenes y archivos subidos
│   ├── themes/               # Temas (solo assets)
│   └── plugins/              # Plugins (solo assets)
└── wp-includes/              # Archivos CSS/JS de WordPress
```

## Configuración del servidor

### Apache

El plugin genera automáticamente un archivo `.htaccess` en el directorio `wp-static/` con la configuración básica.

### Nginx

Agrega esta configuración a tu servidor Nginx:

```nginx
location /wp-static/ {
    try_files $uri $uri/ /wp-static/index.html;
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

## Beneficios

### Rendimiento
- **Carga más rápida**: Las páginas estáticas se sirven directamente sin procesamiento PHP
- **Menor uso de CPU**: No requiere ejecución de código dinámico
- **Mejor cacheo**: Los navegadores pueden cachear las páginas más eficientemente

### Seguridad
- **Sin código ejecutable**: No expone archivos PHP en el frontend
- **Menor superficie de ataque**: Reduce los vectores de ataque
- **Aislamiento**: El sitio dinámico puede estar en un directorio protegido

### SEO
- **Páginas más rápidas**: Mejora el Core Web Vitals
- **Mejor indexación**: Los motores de búsqueda pueden indexar más eficientemente
- **Menor tiempo de carga**: Factor de ranking positivo

## Limitaciones

- **Formularios deshabilitados**: Los formularios se deshabilitan automáticamente
- **Funcionalidad dinámica**: Los scripts de AJAX y formularios se remueven
- **Actualizaciones manuales**: Las páginas estáticas deben regenerarse cuando se actualiza el contenido

## Solución de problemas

### Error: "Directorio no es escribible"

```bash
# Dar permisos de escritura al directorio
chmod 755 wp-static/
chown www-data:www-data wp-static/
```

### Error: "No se pudo obtener contenido"

- Verifica que el sitio sea accesible desde el servidor
- Revisa los logs de error de WordPress
- Asegúrate de que `allow_url_fopen` esté habilitado en PHP

### Páginas no se generan correctamente

- Verifica que las páginas estén publicadas
- Revisa los permisos de archivos
- Comprueba que no haya errores en el tema o plugins

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

## Licencia

Este plugin está licenciado bajo GPL v3. Ver el archivo [LICENSE](LICENSE) para más detalles.

## Soporte

Para reportar bugs o solicitar características, por favor crea un issue en el repositorio del proyecto.

## Changelog

### 0.1.0
- Versión inicial
- Generación básica de páginas estáticas
- Interfaz de administración
- Optimización de recursos
- Copia automática de assets 
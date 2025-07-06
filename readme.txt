=== Greenborn WP Static Pages ===
Contributors: luciano.n.vega
Tags: static, performance, security, optimization, cache
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Plugin wordpress que se encarga de generar micrositio estático y optimizado buscando acelerar la carga y cacheado de recursos y elevar el nivel de seguridad al no exponer código ejecutable en backend.

== Description ==

Greenborn WP Static Pages es un plugin que genera automáticamente páginas HTML estáticas de tu sitio WordPress, creando un micrositio optimizado en el directorio `wp-static/`.

**Características principales:**

* Generación automática de páginas estáticas HTML
* Optimización de recursos (CSS, JS, imágenes)
* Conversión automática de URLs a rutas estáticas
* Deshabilitación de formularios y funcionalidad dinámica
* Copia automática de recursos estáticos
* Interfaz de administración simple
* Compatibilidad con la última versión de WordPress

**Beneficios:**

* **Rendimiento mejorado**: Las páginas estáticas se cargan más rápido
* **Mayor seguridad**: No expone código PHP ejecutable
* **Menor carga del servidor**: No requiere procesamiento dinámico
* **Mejor SEO**: Páginas más rápidas mejoran el posicionamiento

== Installation ==

1. Sube el plugin a la carpeta `/wp-content/plugins/`
2. Activa el plugin a través del menú 'Plugins' en WordPress
3. Ve a 'Static Pages > Generar Páginas' en el menú de administración
4. Configura el directorio `wp-static/` como root del dominio (ver 'Static Pages > Configuración')

== Frequently Asked Questions ==

= ¿Qué directorio se crea para las páginas estáticas? =

El plugin crea automáticamente el directorio `wp-static/` en la raíz de tu instalación de WordPress. Este directorio debe configurarse como root del dominio para que el sitio estático funcione correctamente.

= ¿Se pueden seguir editando las páginas en WordPress? =

Sí, puedes seguir editando normalmente en WordPress. Para actualizar las páginas estáticas, simplemente ejecuta la generación nuevamente desde el panel de administración.

= ¿Qué pasa con los formularios? =

Los formularios se deshabilitan automáticamente en las páginas estáticas y se muestra un mensaje informativo.

= ¿Se copian todos los recursos? =

Sí, el plugin copia automáticamente CSS, JavaScript, imágenes y otros recursos necesarios para que las páginas estáticas funcionen correctamente.

== Screenshots ==

1. Panel de administración del plugin
2. Generación de páginas estáticas
3. Información del estado del directorio

== Changelog ==

= 0.1.0 =
* Versión inicial
* Generación básica de páginas estáticas
* Interfaz de administración
* Optimización de recursos
* Copia automática de assets

== Upgrade Notice ==

= 0.1.0 =
Versión inicial del plugin. Incluye funcionalidad básica de generación de páginas estáticas. 
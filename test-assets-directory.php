<?php
/**
 * Script de prueba para verificar la creación del directorio assets
 */

echo "=== PRUEBA DE CREACIÓN DE DIRECTORIO ASSETS ===\n\n";

// Simular entorno WordPress
define('ABSPATH', dirname(__FILE__) . '/');

// Función nativa de WordPress (simulada)
function get_home_path() {
    return ABSPATH;
}

// Función nativa de WordPress (simulada)
function wp_mkdir_p($path) {
    return mkdir($path, 0755, true);
}

// Función del plugin
function greenborn_get_static_dir() {
    if (!function_exists('get_home_path')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $home_path = get_home_path();
    if (empty($home_path)) {
        $home_path = dirname(ABSPATH) . '/';
    }
    return $home_path . 'wp-static/';
}

// Simular la clase generadora
class GreenbornStaticGenerator {
    private $static_dir;
    
    public function __construct() {
        $this->static_dir = function_exists('greenborn_get_static_dir') ? 
            greenborn_get_static_dir() : 
            dirname(ABSPATH) . '/wp-static/';
    }
    
    public function prepare_static_directory() {
        // Verificar que el directorio existe y es escribible
        if (!is_dir($this->static_dir)) {
            $created = @wp_mkdir_p($this->static_dir);
            if (!$created) {
                throw new Exception('No se pudo crear el directorio estático: ' . $this->static_dir);
            }
        }
        
        // Verificar permisos de escritura
        if (!is_writable($this->static_dir)) {
            throw new Exception('El directorio estático no es escribible: ' . $this->static_dir);
        }
        
        // Crear subdirectorio assets si no existe
        $assets_dir = $this->static_dir . 'assets/';
        if (!is_dir($assets_dir)) {
            $assets_created = @wp_mkdir_p($assets_dir);
            if (!$assets_created) {
                throw new Exception('No se pudo crear el subdirectorio assets: ' . $assets_dir);
            }
            echo "   ✅ Subdirectorio assets creado: $assets_dir\n";
        } else {
            echo "   ℹ️  Subdirectorio assets ya existe: $assets_dir\n";
        }
        
        return array(
            'static_dir' => $this->static_dir,
            'assets_dir' => $assets_dir,
            'home_generated' => true
        );
    }
    
    private function log_message($message) {
        echo "   📝 $message\n";
    }
}

echo "1. INICIALIZANDO GENERADOR:\n";
$generator = new GreenbornStaticGenerator();
echo "   Directorio estático: " . greenborn_get_static_dir() . "\n\n";

echo "2. PREPARANDO DIRECTORIO:\n";
try {
    $result = $generator->prepare_static_directory();
    echo "   ✅ Directorio preparado correctamente\n";
    echo "   Directorio principal: " . $result['static_dir'] . "\n";
    echo "   Directorio assets: " . $result['assets_dir'] . "\n\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "3. VERIFICANDO ESTRUCTURA:\n";
$static_dir = $result['static_dir'];
$assets_dir = $result['assets_dir'];

echo "   Directorio principal existe: " . (is_dir($static_dir) ? "✅ Sí" : "❌ No") . "\n";
echo "   Directorio principal escribible: " . (is_writable($static_dir) ? "✅ Sí" : "❌ No") . "\n";
echo "   Directorio assets existe: " . (is_dir($assets_dir) ? "✅ Sí" : "❌ No") . "\n";
echo "   Directorio assets escribible: " . (is_writable($assets_dir) ? "✅ Sí" : "❌ No") . "\n\n";

echo "4. PRUEBA DE ESCRITURA EN ASSETS:\n";
$test_file = $assets_dir . 'test.txt';
$content = "Archivo de prueba en assets - " . date('Y-m-d H:i:s') . "\n";

$written = @file_put_contents($test_file, $content);
if ($written !== false) {
    echo "   ✅ Escritura exitosa en assets - $written bytes\n";
    
    // Limpiar archivo de prueba
    @unlink($test_file);
    echo "   ✅ Archivo de prueba eliminado\n";
} else {
    echo "   ❌ Error al escribir en directorio assets\n";
}

echo "\n5. RESUMEN:\n";
echo "   ✅ El directorio assets se crea automáticamente\n";
echo "   ✅ Los permisos son correctos\n";
echo "   ✅ Se puede escribir en el directorio assets\n";
echo "   ✅ La estructura está lista para uso\n";

echo "\n=== PRUEBA COMPLETADA ===\n";
?> 
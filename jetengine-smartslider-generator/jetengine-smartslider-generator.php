<?php
/**
 * Plugin Name: Smart Slider Pro Fix
 * Description: Einfacher Fix für SmartSlider Pro Erkennung
 * Version: 1.0.0
 * Author: Support
 * 
 * Speichern Sie diese Datei als smartslider-pro-fix.php im Verzeichnis /wp-content/mu-plugins/
 * (Erstellen Sie den mu-plugins Ordner, falls er nicht existiert)
 */

// Wenn wir nicht in WordPress sind, abbrechen
if (!defined('ABSPATH')) exit;

// Smart Slider Pro Konstante definieren
if (!defined('NEXTEND_SMARTSLIDER_3_PRO')) {
    define('NEXTEND_SMARTSLIDER_3_PRO', '1');
}

// Filter für Pro-Status hinzufügen
add_filter('smartslider3_is_pro', '__return_true', 999999);

// JetEngine SmartSlider Generator Plugin deaktivieren (temporär)
add_action('muplugins_loaded', function() {
    $active_plugins = get_option('active_plugins', []);
    $plugin_to_deactivate = 'jetengine-smartslider-generator/jetengine-smartslider-generator.php';
    
    if (in_array($plugin_to_deactivate, $active_plugins)) {
        // Temporär aus der Liste der aktiven Plugins entfernen
        $key = array_search($plugin_to_deactivate, $active_plugins);
        unset($active_plugins[$key]);
        update_option('active_plugins', $active_plugins);
        
        // Speichere Information zur späteren Wiederaktivierung
        update_option('jetengine_smartslider_deactivated', true);
        
        // Zeige Admin-Hinweis
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>JetEngine SmartSlider Generator wurde temporär deaktiviert</strong> wegen eines Kompatibilitätsproblems.</p>';
            echo '<p>Bitte wenden Sie sich an den Support, um Hilfe bei der Behebung des Problems zu erhalten.</p>';
            echo '</div>';
        });
    }
}, 1);

// Admin-Hinweis anzeigen, dass das Plugin aktiv ist
add_action('admin_notices', function() {
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p><strong>Smart Slider Pro Fix</strong> ist aktiv und erzwingt die Pro-Erkennung für Smart Slider 3.</p>';
    echo '</div>';
});

<?php
/**
 * Plugin Name: JetEngine SmartSlider Field Enhancer
 * Description: Verbessert die JetEngine SmartSlider Integration mit Dropdown-Menüs für Meta-Felder
 * Version: 1.0.0
 * Author: Support
 * Text Domain: jetengine-smartslider-enhancer
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) exit;

/**
 * Hauptklasse des Plugins
 */
class JetEngine_SmartSlider_Field_Enhancer {
    
    /**
     * Plugin-Version
     */
    public $version = '1.0.0';
    
    /**
     * Plugin-Pfad
     */
    public $plugin_path;
    
    /**
     * Plugin-URL
     */
    public $plugin_url;
    
    /**
     * Singleton-Instanz
     */
    private static $instance = null;
    
    /**
     * Singleton-Pattern: Instanz zurückgeben
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Konstruktor
     */
    private function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        
        // Admin-Assets laden
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // AJAX-Endpunkte registrieren
        add_action('wp_ajax_jetengine_smartslider_get_all_meta_fields', [$this, 'ajax_get_all_meta_fields']);
        
        // Plugin initialisieren
        $this->init();
    }
    
    /**
     * Plugin initialisieren
     */
    private function init() {
        // Dateien laden
        $this->load_files();
        
        // Log-Nachricht
        $this->log('Plugin initialisiert');
    }
    
    /**
     * Dateien laden
     */
    private function load_files() {
        // Hilfsfunktionen laden
        if (file_exists($this->plugin_path . 'includes/helpers.php')) {
            require_once $this->plugin_path . 'includes/helpers.php';
        }
    }
    
    /**
     * Admin-Assets laden
     * 
     * @param string $hook Hook-Name
     */
    public function enqueue_admin_assets($hook) {
        // Nur auf Smart Slider-Seiten laden
        if (strpos($hook, 'smart-slider') === false) {
            return;
        }
        
        // CSS laden
        wp_enqueue_style(
            'jetengine-smartslider-enhancer',
            $this->plugin_url . 'assets/css/admin.css',
            [],
            $this->version
        );
        
        // JavaScript laden
        wp_enqueue_script(
            'jetengine-smartslider-enhancer',
            $this->plugin_url . 'assets/js/admin.js',
            ['jquery'],
            $this->version,
            true
        );
        
        // JavaScript-Daten
        wp_localize_script(
            'jetengine-smartslider-enhancer', 
            'JetEngineSmartSliderEnhancer', 
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('jetengine_smartslider_enhancer_nonce'),
                'strings' => [
                    'select_field' => __('-- Meta-Feld auswählen --', 'jetengine-smartslider-enhancer'),
                    'loading' => __('Lade Meta-Felder...', 'jetengine-smartslider-enhancer'),
                    'no_fields' => __('Keine Meta-Felder gefunden', 'jetengine-smartslider-enhancer')
                ]
            ]
        );
    }
    
    /**
     * AJAX-Handler zum Abrufen aller Meta-Felder
     */
    public function ajax_get_all_meta_fields() {
        // Nonce überprüfen
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jetengine_smartslider_enhancer_nonce')) {
            wp_send_json_error(['message' => 'Sicherheitsüberprüfung fehlgeschlagen.']);
        }
        
        // Post-Typ prüfen
        if (!isset($_POST['post_type']) || empty($_POST['post_type'])) {
            wp_send_json_error(['message' => 'Post-Typ nicht angegeben.']);
        }
        
        $post_type = sanitize_text_field($_POST['post_type']);
        
        // Meta-Felder abrufen
        $meta_fields = $this->get_all_meta_fields($post_type);
        
        // Antwort senden
        wp_send_json_success(['meta_fields' => $meta_fields]);
    }
    
    /**
     * Holt alle verfügbaren Meta-Felder für einen Post-Typ
     * 
     * @param string $post_type Post-Typ
     * @return array Array mit Meta-Feldern
     */
    public function get_all_meta_fields($post_type) {
        global $wpdb;
        
        // Meta-Schlüssel aus der Datenbank abrufen
        $query = $wpdb->prepare(
            "SELECT DISTINCT pm.meta_key 
             FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE p.post_type = %s
             ORDER BY pm.meta_key",
            $post_type
        );
        
        $meta_keys = $wpdb->get_results($query);
        
        $fields = [];
        
        // Meta-Felder für die Ausgabe aufbereiten
        foreach ($meta_keys as $meta_key) {
            // Systeminterne Felder filtern (optional)
            $key = $meta_key->meta_key;
            
            // Zeige JetEngine-Felder oder Felder, die nicht mit Unterstrich beginnen
            if (substr($key, 0, 5) === '_jet_' || substr($key, 0, 1) !== '_') {
                $fields[] = [
                    'key' => $key,
                    'label' => $key
                ];
            }
        }
        
        // JetEngine-Felder gruppieren
        $jet_fields = [];
        $other_fields = [];
        
        foreach ($fields as $field) {
            if (substr($field['key'], 0, 5) === '_jet_' || substr($field['key'], 0, 4) === 'jet_') {
                $jet_fields[] = $field;
            } else {
                $other_fields[] = $field;
            }
        }
        
        // Sortierte Felder zurückgeben
        return [
            'jet_fields' => $jet_fields,
            'other_fields' => $other_fields
        ];
    }
    
    /**
     * Log-Nachricht
     * 
     * @param string $message Nachricht
     */
    public function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[JetEngine SmartSlider Enhancer] ' . $message);
        }
    }
}

/**
 * Plugin-Instanz abrufen
 * 
 * @return JetEngine_SmartSlider_Field_Enhancer Plugin-Instanz
 */
function jetengine_smartslider_enhancer() {
    return JetEngine_SmartSlider_Field_Enhancer::get_instance();
}

// Plugin initialisieren
add_action('plugins_loaded', 'jetengine_smartslider_enhancer');

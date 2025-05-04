<?php
/**
 * Plugin Name: JetEngine Advanced Smart Slider 3 Generator (Optimiert)
 * Description: Eine fortschrittliche Integration zwischen JetEngine und Smart Slider 3 Pro, die die volle Leistungsfähigkeit der dynamischen Inhalte von JetEngine nutzt.
 * Version: 1.1.0
 * Author: Support
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) exit;

/**
 * Hauptklasse des Plugins
 */
class JetEngine_SmartSlider_Generator {
    
    /**
     * Plugin-Version
     */
    public $version = '1.1.0';
    
    /**
     * Plugin-Pfad
     */
    public $plugin_path;
    
    /**
     * Plugin-URL
     */
    public $plugin_url;
    
    /**
     * Abhängigkeiten geladen
     */
    private $dependencies_loaded = false;
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        
        // Sehr späte Initialisierung, um Timing-Probleme zu vermeiden
        add_action('wp_loaded', [$this, 'check_dependencies']);
        
        // Admin-Ressourcen laden
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Admin-Hinweise anzeigen
        add_action('admin_notices', [$this, 'admin_notices']);
    }
    
    /**
     * Abhängigkeiten prüfen und Plugin initialisieren
     */
    public function check_dependencies() {
        // JetEngine prüfen
        if (!class_exists('Jet_Engine')) {
            $this->log('JetEngine nicht aktiviert');
            return;
        }
        
        // Smart Slider Klassen prüfen
        if (!$this->check_smartslider_classes()) {
            $this->log('Smart Slider Klassen nicht gefunden');
            return;
        }
        
        // Abhängigkeiten sind erfüllt, Plugin initialisieren
        $this->dependencies_loaded = true;
        $this->init_plugin();
        
        $this->log('Plugin erfolgreich initialisiert');
    }
    
    /**
     * Plugin initialisieren
     */
    private function init_plugin() {
        // Dateien laden
        $this->load_files();
        
        // Generator initialisieren
        add_action('admin_init', [$this, 'init_generator'], 999);
    }
    
    /**
     * Erforderliche Dateien laden
     */
    private function load_files() {
        // Helper laden
        if (file_exists($this->plugin_path . 'includes/helper.php')) {
            require_once $this->plugin_path . 'includes/helper.php';
            $this->log('Helper geladen');
        }
        
        // Generator Sources laden
        if (file_exists($this->plugin_path . 'includes/generator-sources.php')) {
            require_once $this->plugin_path . 'includes/generator-sources.php';
            $this->log('Generator Sources geladen');
        }
    }
    
    /**
     * Generator initialisieren
     */
    public function init_generator() {
        // Prüfen, ob die Generator-Factory verfügbar ist
        if (!class_exists('Nextend\SmartSlider3\Generator\GeneratorFactory')) {
            $this->log('Generator Factory nicht verfügbar');
            return;
        }
        
        // Generator Gruppe laden
        $this->load_generator_group();
        
        // Generator Gruppe bei der Factory registrieren
        $this->register_generator_group();
    }
    
    /**
     * Generator Gruppe laden
     */
    private function load_generator_group() {
        // Optimierte Generator-Gruppe Datei
        $optimized_file = __DIR__ . '/includes/generator-group-optimized.php';
        $original_file = __DIR__ . '/includes/generator-group.php';
        
        // Optimierte Version bevorzugen, falls vorhanden
        if (file_exists($optimized_file)) {
            require_once $optimized_file;
            $this->log('Optimierte Generator-Gruppe geladen');
        } 
        // Ansonsten Original-Version versuchen
        else if (file_exists($original_file)) {
            require_once $original_file;
            $this->log('Original Generator-Gruppe geladen');
        } 
        // Falls beides fehlt, Inline-Version verwenden
        else {
            $this->define_inline_generator_group();
            $this->log('Inline Generator-Gruppe definiert');
        }
    }
    
    /**
     * Inline Generator-Gruppe definieren
     */
    private function define_inline_generator_group() {
        // Prüfen, ob die Klasse bereits existiert
        if (class_exists('JetEngine_SmartSlider_Generator_Group')) {
            return;
        }
        
        // Basisklasse existiert?
        if (!class_exists('Nextend\SmartSlider3\Generator\AbstractGeneratorGroup')) {
            $this->log('AbstractGeneratorGroup nicht gefunden');
            return;
        }
        
        // Trait existiert?
        if (!trait_exists('Nextend\Framework\Pattern\GetAssetsPathTrait')) {
            $this->log('GetAssetsPathTrait nicht gefunden');
            return;
        }
        
        // Minimale Generator-Gruppe definieren
        eval('
        class JetEngine_SmartSlider_Generator_Group extends Nextend\SmartSlider3\Generator\AbstractGeneratorGroup {
            protected $name = "jetengine";
            protected $url = "";
            protected $displayName = "JetEngine";
            
            public function __construct() {
                parent::__construct();
                $this->url = plugin_dir_url(__FILE__);
            }
            
            public function getLabel() {
                return "JetEngine";
            }
            
            public function getDescription() {
                return "Creates slides from JetEngine custom post types, meta fields, taxonomies, and relations.";
            }
            
            public function getIcon() {
                return plugin_dir_url(__FILE__) . "assets/images/jetengine-icon.svg";
            }
            
            protected function loadSources() {
                // Minimale Implementierung
            }
        }
        ');
    }
    
    /**
     * Generator Gruppe bei der Factory registrieren
     */
    private function register_generator_group() {
        // Klasse existiert?
        if (!class_exists('JetEngine_SmartSlider_Generator_Group')) {
            $this->log('Generator Group Klasse nicht gefunden');
            return;
        }
        
        try {
            // Factory abrufen und Generator registrieren
            $factory = \Nextend\SmartSlider3\Generator\GeneratorFactory::getInstance();
            
            if (method_exists($factory, 'addGenerator')) {
                $generator = new JetEngine_SmartSlider_Generator_Group();
                $factory->addGenerator($generator);
                $this->log('Generator erfolgreich registriert');
            } else {
                $this->log('addGenerator Methode nicht gefunden');
            }
        } catch (\Exception $e) {
            $this->log('Fehler beim Registrieren: ' . $e->getMessage());
        }
    }
    
    /**
     * Admin-Ressourcen laden
     */
    public function enqueue_admin_assets($hook) {
        // Nur auf Smart Slider-Seiten laden
        if (strpos($hook, 'smart-slider') === false) {
            return;
        }
        
        // CSS laden
        if (file_exists($this->plugin_path . 'assets/css/admin.css')) {
            wp_enqueue_style(
                'jetengine-smartslider-admin',
                $this->plugin_url . 'assets/css/admin.css',
                [],
                $this->version
            );
        }
        
        // JS laden
        if (file_exists($this->plugin_path . 'assets/js/admin.js')) {
            wp_enqueue_script(
                'jetengine-smartslider-admin',
                $this->plugin_url . 'assets/js/admin.js',
                ['jquery'],
                $this->version,
                true
            );
            
            // JS-Daten
            wp_localize_script('jetengine-smartslider-admin', 'jetengineSmartSliderData', [
                'nonce' => wp_create_nonce('jetengine_smartslider_nonce'),
                'select_field' => __('Select field', 'jetengine-smartslider'),
                'select_meta_key' => __('Select meta key', 'jetengine-smartslider'),
                'select_taxonomies' => __('Select taxonomies', 'jetengine-smartslider'),
                'meta_field_tip' => __('Enter the name of a JetEngine meta field to filter by.', 'jetengine-smartslider'),
                'image_field_tip' => __('Select a field that contains image ID or URL.', 'jetengine-smartslider')
            ]);
        }
    }
    
    /**
     * Admin-Hinweise anzeigen
     */
    public function admin_notices() {
        // Nur für Administratoren anzeigen
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Nur anzeigen, wenn Abhängigkeiten fehlen
        if ($this->dependencies_loaded) {
            return;
        }
        
        echo '<div class="notice notice-error">';
        echo '<p><strong>JetEngine Advanced Smart Slider 3 Generator</strong></p>';
        
        if (!class_exists('Jet_Engine')) {
            echo '<p>JetEngine ist nicht aktiviert. Dieses Plugin benötigt JetEngine, um zu funktionieren.</p>';
        } else if (!$this->check_smartslider_classes()) {
            echo '<p>Smart Slider 3 Pro ist nicht korrekt installiert oder aktiviert.</p>';
            echo '<p>Bitte aktivieren Sie Smart Slider 3 Pro und stellen Sie sicher, dass es korrekt funktioniert.</p>';
            
            // Debug-Informationen anzeigen
            echo '<p><a href="#" onclick="jQuery(\'#jetengine-smartslider-debug\').toggle(); return false;">Debug-Informationen anzeigen</a></p>';
            echo '<div id="jetengine-smartslider-debug" style="display:none; padding: 10px; background: #f8f8f8; border: 1px solid #ccc;">';
            
            echo '<h4>Klassenprüfung:</h4>';
            echo '<ul>';
            echo '<li>AbstractGeneratorGroup: ' . (class_exists('Nextend\SmartSlider3\Generator\AbstractGeneratorGroup') ? 'Gefunden' : 'Fehlt') . '</li>';
            echo '<li>GetAssetsPathTrait: ' . (trait_exists('Nextend\Framework\Pattern\GetAssetsPathTrait') ? 'Gefunden' : 'Fehlt') . '</li>';
            echo '<li>GeneratorFactory: ' . (class_exists('Nextend\SmartSlider3\Generator\GeneratorFactory') ? 'Gefunden' : 'Fehlt') . '</li>';
            echo '<li>Autoloader.php existiert: ' . (defined('SMARTSLIDER3_LIBRARY_PATH') && file_exists(SMARTSLIDER3_LIBRARY_PATH . '/Autoloader.php') ? 'Ja' : 'Nein') . '</li>';
            echo '</ul>';
            
            echo '<h4>Konstanten:</h4>';
            echo '<ul>';
            echo '<li>NEXTEND_SMARTSLIDER_3: ' . (defined('NEXTEND_SMARTSLIDER_3') ? 'Definiert (' . NEXTEND_SMARTSLIDER_3 . ')' : 'Nicht definiert') . '</li>';
            echo '<li>NEXTEND_SMARTSLIDER_3_PRO: ' . (defined('NEXTEND_SMARTSLIDER_3_PRO') ? 'Definiert' : 'Nicht definiert') . '</li>';
            echo '<li>SMARTSLIDER3_LIBRARY_PATH: ' . (defined('SMARTSLIDER3_LIBRARY_PATH') ? 'Definiert (' . SMARTSLIDER3_LIBRARY_PATH . ')' : 'Nicht definiert') . '</li>';
            echo '</ul>';
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Prüfen, ob die erforderlichen Smart Slider Klassen verfügbar sind
     */
    private function check_smartslider_classes() {
        // Erforderliche Klassen prüfen
        $required_classes = [
            'Nextend\SmartSlider3\Generator\AbstractGeneratorGroup',
            'Nextend\Framework\Pattern\GetAssetsPathTrait',
            'Nextend\SmartSlider3\Generator\GeneratorFactory'
        ];
        
        foreach ($required_classes as $class) {
            if (strpos($class, '\Pattern\\') !== false) {
                // Für Traits
                if (!trait_exists($class)) {
                    return false;
                }
            } else {
                // Für reguläre Klassen
                if (!class_exists($class)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Nachricht protokollieren
     */
    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[JetEngine SmartSlider] ' . $message);
        }
    }
}

/**
 * Gibt die Hauptinstanz des Plugins zurück
 */
function jetengine_smartslider_generator() {
    static $instance = null;
    
    if (is_null($instance)) {
        $instance = new JetEngine_SmartSlider_Generator();
    }
    
    return $instance;
}

// Plugin initialisieren
jetengine_smartslider_generator();

<?php
/**
 * JetEngine Generator Group - Optimierte Version
 * 
 * Diese optimierte Version verhindert Timing-Probleme bei der Klassenladung
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Prüfen, ob die erforderlichen Klassen bereits verfügbar sind
if (!class_exists('Nextend\SmartSlider3\Generator\AbstractGeneratorGroup')) {
    return;
}

/**
 * JetEngine Generator Group
 */
class JetEngine_SmartSlider_Generator_Group extends \Nextend\SmartSlider3\Generator\AbstractGeneratorGroup {
    
    protected $name = 'jetengine';
    protected $url = '';
    protected $displayName = 'JetEngine';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Wichtig: Wir rufen parent::__construct() nicht auf, um zu verhindern, dass der Generator 
        // während der Konstruktion registriert wird. Die Registrierung erfolgt manuell später.
        // parent::__construct();
        
        $this->url = plugin_dir_url(dirname(__FILE__));
    }
    
    /**
     * Returns label for the generator group
     * 
     * @return string Generator group label
     */
    public function getLabel() {
        return 'JetEngine';
    }
    
    /**
     * Returns group description
     * 
     * @return string Group description
     */
    public function getDescription() {
        return n2_('Creates slides from JetEngine custom post types, meta fields, taxonomies, and relations.');
    }
    
    /**
     * Returns icon for the generator
     * 
     * @return string Icon URL
     */
    public function getIcon() {
        $icon_path = 'assets/images/jetengine-icon.svg';
        
        // Prüfen, ob die Icon-Datei existiert
        if (file_exists(plugin_dir_path(dirname(__FILE__)) . $icon_path)) {
            return plugin_dir_url(dirname(__FILE__)) . $icon_path;
        }
        
        // Fallback auf ein allgemeines Icon
        return 'https://img.icons8.com/color/48/000000/database.png';
    }
    
    /**
     * Load generator sources
     */
    protected function loadSources() {
        try {
            // Post-Types laden
            $this->loadPostTypes();
            
            // Custom Content Types laden, falls verfügbar
            if (function_exists('jet_engine') && method_exists(jet_engine(), 'modules') && 
                jet_engine()->modules->is_module_active('custom-content-types')) {
                $this->loadContentTypes();
            }
            
            // Relations laden, falls verfügbar
            if (function_exists('jet_engine') && method_exists(jet_engine(), 'relations')) {
                $this->loadRelations();
            }
        } catch (\Exception $e) {
            // Fehlerbehandlung
            if (function_exists('jetengine_smartslider_generator')) {
                jetengine_smartslider_generator()->log('Error loading sources: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Load post types
     */
    private function loadPostTypes() {
        // Prüfen, ob die Quell-Datei existiert
        $source_file = plugin_dir_path(dirname(__FILE__)) . 'includes/sources/post-type.php';
        if (!file_exists($source_file)) {
            return;
        }
        
        // Klasse laden, falls noch nicht geladen
        if (!class_exists('JetEngine_SmartSlider_Source_PostType')) {
            require_once $source_file;
        }
        
        // Post-Types laden
        $post_types = $this->getJetEnginePostTypes();
        
        // Bestimmte Post-Types überspringen
        $skip_types = ['attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset'];
        
        foreach ($post_types as $post_type) {
            // Post-Type überspringen, falls in der Skip-Liste
            if (in_array($post_type->name, $skip_types)) {
                continue;
            }
            
            // Generator-Quelle für Post-Type erstellen
            if (isset($post_type->name) && isset($post_type->labels->singular_name)) {
                try {
                    $this->sources[$post_type->name] = new JetEngine_SmartSlider_Source_PostType(
                        $this, 
                        $post_type->name, 
                        $post_type->labels->singular_name, 
                        $post_type
                    );
                } catch (\Exception $e) {
                    // Fehlerbehandlung
                }
            }
        }
    }
    
    /**
     * Load custom content types
     */
    private function loadContentTypes() {
        // Prüfen, ob die Quell-Datei existiert
        $source_file = plugin_dir_path(dirname(__FILE__)) . 'includes/sources/content-type.php';
        if (!file_exists($source_file)) {
            return;
        }
        
        // Klasse laden, falls noch nicht geladen
        if (!class_exists('JetEngine_SmartSlider_Source_ContentType')) {
            require_once $source_file;
        }
        
        // CCT-Modul abrufen
        $cct_module = jet_engine()->modules->get_module('custom-content-types');
        
        if (!$cct_module || !method_exists($cct_module, 'get_content_types')) {
            return;
        }
        
        // Content-Types abrufen
        $content_types = $cct_module->get_content_types();
        
        if (empty($content_types)) {
            return;
        }
        
        foreach ($content_types as $content_type) {
            // Content-Type überspringen, falls kein Slug oder Name vorhanden
            if (!isset($content_type->slug) || !isset($content_type->labels['name'])) {
                continue;
            }
            
            try {
                // Generator-Quelle für Content-Type erstellen
                $this->sources['cct_' . $content_type->slug] = new JetEngine_SmartSlider_Source_ContentType(
                    $this,
                    'cct_' . $content_type->slug,
                    $content_type->labels['name'],
                    $content_type
                );
            } catch (\Exception $e) {
                // Fehlerbehandlung
            }
        }
    }
    
    /**
     * Load relations
     */
    private function loadRelations() {
        // Prüfen, ob die Quell-Datei existiert
        $source_file = plugin_dir_path(dirname(__FILE__)) . 'includes/sources/relation.php';
        if (!file_exists($source_file)) {
            return;
        }
        
        // Klasse laden, falls noch nicht geladen
        if (!class_exists('JetEngine_SmartSlider_Source_Relation')) {
            require_once $source_file;
        }
        
        // Relations-Manager abrufen
        $relations_manager = jet_engine()->relations;
        
        if (!$relations_manager || !method_exists($relations_manager, 'get_active_relations')) {
            return;
        }
        
        // Relations abrufen
        $relations = $relations_manager->get_active_relations();
        
        if (empty($relations)) {
            return;
        }
        
        foreach ($relations as $relation) {
            // Relation überspringen, falls keine ID oder kein Name vorhanden
            if (!isset($relation['id']) || !isset($relation['name'])) {
                continue;
            }
            
            try {
                // Generator-Quelle für Relation erstellen
                $this->sources['relation_' . $relation['id']] = new JetEngine_SmartSlider_Source_Relation(
                    $this,
                    'relation_' . $relation['id'],
                    $relation['name'],
                    $relation
                );
            } catch (\Exception $e) {
                // Fehlerbehandlung
            }
        }
    }
    
    /**
     * Get JetEngine post types
     * 
     * @return array Array of post type objects
     */
    private function getJetEnginePostTypes() {
        $post_types = [];
        
        // JetEngine-Post-Types abrufen
        if (function_exists('jet_engine') && isset(jet_engine()->cpt)) {
            if (method_exists(jet_engine()->cpt, 'get_items')) {
                $jet_post_types = jet_engine()->cpt->get_items();
                
                if (!empty($jet_post_types)) {
                    foreach ($jet_post_types as $post_type) {
                        $slug = isset($post_type['slug']) ? $post_type['slug'] : false;
                        $name = isset($post_type['labels']['name']) ? $post_type['labels']['name'] : false;
                        $singular = isset($post_type['labels']['singular_name']) ? $post_type['labels']['singular_name'] : $name;
                        
                        if ($slug && $name) {
                            // Post-Type-Objekt erstellen
                            $post_type_obj = new stdClass();
                            $post_type_obj->name = $slug;
                            $post_type_obj->labels = new stdClass();
                            $post_type_obj->labels->name = $name;
                            $post_type_obj->labels->singular_name = $singular;
                            
                            $post_types[$slug] = $post_type_obj;
                        }
                    }
                }
            }
        }
        
        // WordPress-Post-Types abrufen
        $builtin_post_types = get_post_types(['_builtin' => true, 'public' => true], 'objects');
        $custom_post_types = get_post_types(['_builtin' => false, 'public' => true], 'objects');
        
        // Arrays zusammenführen
        $post_types = array_merge($post_types, $builtin_post_types, $custom_post_types);
        
        return $post_types;
    }
}

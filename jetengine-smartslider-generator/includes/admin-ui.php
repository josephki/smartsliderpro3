<?php
/**
 * Verbesserte Admin-Benutzeroberfläche für JetEngine SmartSlider Generator
 * 
 * Diese Datei enthält verbesserte UI-Komponenten und Hilfe-Funktionen
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) exit;

class JetEngine_SmartSlider_Admin_UI {
    
    /**
     * Plugin-Instanz
     */
    private $plugin;
    
    /**
     * Konstruktor
     * 
     * @param object $plugin Plugin-Hauptinstanz
     */
    public function __construct($plugin) {
        $this->plugin = $plugin;
        
        // Admin-Menü hinzufügen
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Admin-Initialisierung
        add_action('admin_init', [$this, 'admin_init']);
        
        // Hilfe-Tabs hinzufügen
        add_action('admin_head', [$this, 'add_help_tabs']);
        
        // Admin-Hinweise
        add_action('admin_notices', [$this, 'admin_notices']);
        
        // Ajax-Handler für Vorschau registrieren
        add_action('wp_ajax_jetengine_smartslider_preview', [$this, 'handle_preview_ajax']);
    }
    
    /**
     * Admin-Menü hinzufügen
     */
    public function add_admin_menu() {
        // Untermenü-Punkt zu Smart Slider hinzufügen
        add_submenu_page(
            'smart-slider3',
            __('JetEngine Integration', 'jetengine-smartslider'),
            __('JetEngine Integration', 'jetengine-smartslider'),
            'manage_options',
            'jetengine-smartslider',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Admin-Initialisierung
     */
    public function admin_init() {
        // Einstellungen registrieren
        register_setting('jetengine_smartslider_settings', 'jetengine_smartslider_settings', [$this, 'validate_settings']);
        
        // Allgemeine Einstellungen
        add_settings_section(
            'jetengine_smartslider_general',
            __('Allgemeine Einstellungen', 'jetengine-smartslider'),
            [$this, 'render_general_section'],
            'jetengine_smartslider_settings'
        );
        
        // Debug-Einstellungen
        add_settings_section(
            'jetengine_smartslider_debug',
            __('Debug-Einstellungen', 'jetengine-smartslider'),
            [$this, 'render_debug_section'],
            'jetengine_smartslider_settings'
        );
        
        // Einstellungsfelder hinzufügen
        $this->add_settings_fields();
        
        // Styles und Skripte registrieren
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Plugin-Aktionslinks hinzufügen
        add_filter('plugin_action_links_' . plugin_basename($this->plugin->plugin_path . 'jetengine-smartslider-generator.php'), 
            [$this, 'add_plugin_action_links']
        );
    }
    
    /**
     * Einstellungsfelder hinzufügen
     */
    private function add_settings_fields() {
        // Allgemeine Einstellungen
        add_settings_field(
            'enabled_sources',
            __('Aktivierte Quellen', 'jetengine-smartslider'),
            [$this, 'render_enabled_sources_field'],
            'jetengine_smartslider_settings',
            'jetengine_smartslider_general'
        );
        
        add_settings_field(
            'default_per_page',
            __('Standard-Einträge pro Seite', 'jetengine-smartslider'),
            [$this, 'render_default_per_page_field'],
            'jetengine_smartslider_settings',
            'jetengine_smartslider_general'
        );
        
        // Debug-Einstellungen
        add_settings_field(
            'debug_mode',
            __('Debug-Modus', 'jetengine-smartslider'),
            [$this, 'render_debug_mode_field'],
            'jetengine_smartslider_settings',
            'jetengine_smartslider_debug'
        );
        
        add_settings_field(
            'log_level',
            __('Log-Level', 'jetengine-smartslider'),
            [$this, 'render_log_level_field'],
            'jetengine_smartslider_settings',
            'jetengine_smartslider_debug'
        );
        
        add_settings_field(
            'view_logs',
            __('Log-Dateien', 'jetengine-smartslider'),
            [$this, 'render_view_logs_field'],
            'jetengine_smartslider_settings',
            'jetengine_smartslider_debug'
        );
    }
    
    /**
     * Rendert die allgemeine Einstellungssektion
     */
    public function render_general_section() {
        echo '<p>' . __('Konfigurieren Sie die grundlegenden Optionen für die JetEngine-Integration mit Smart Slider 3.', 'jetengine-smartslider') . '</p>';
    }
    
    /**
     * Rendert die Debug-Einstellungssektion
     */
    public function render_debug_section() {
        echo '<p>' . __('Diese Einstellungen helfen bei der Fehlersuche und Problemlösung.', 'jetengine-smartslider') . '</p>';
    }
    
    /**
     * Rendert das Feld für aktivierte Quellen
     */
    public function render_enabled_sources_field() {
        $options = get_option('jetengine_smartslider_settings', []);
        $enabled_sources = isset($options['enabled_sources']) ? $options['enabled_sources'] : [
            'post_types' => 'on',
            'content_types' => 'on',
            'relations' => 'on',
            'queries' => 'on'
        ];
        
        ?>
        <fieldset>
            <label for="jetengine-post-types">
                <input type="checkbox" id="jetengine-post-types" name="jetengine_smartslider_settings[enabled_sources][post_types]" <?php checked(isset($enabled_sources['post_types'])); ?> />
                <?php _e('Post Types', 'jetengine-smartslider'); ?>
            </label><br>
            
            <label for="jetengine-content-types">
                <input type="checkbox" id="jetengine-content-types" name="jetengine_smartslider_settings[enabled_sources][content_types]" <?php checked(isset($enabled_sources['content_types'])); ?> />
                <?php _e('Custom Content Types', 'jetengine-smartslider'); ?>
            </label><br>
            
            <label for="jetengine-relations">
                <input type="checkbox" id="jetengine-relations" name="jetengine_smartslider_settings[enabled_sources][relations]" <?php checked(isset($enabled_sources['relations'])); ?> />
                <?php _e('Relations', 'jetengine-smartslider'); ?>
            </label><br>
            
            <label for="jetengine-queries">
                <input type="checkbox" id="jetengine-queries" name="jetengine_smartslider_settings[enabled_sources][queries]" <?php checked(isset($enabled_sources['queries'])); ?> />
                <?php _e('Query Builder', 'jetengine-smartslider'); ?>
            </label>
        </fieldset>
        <p class="description"><?php _e('Wählen Sie die JetEngine-Quellen aus, die in Smart Slider 3 verfügbar sein sollen.', 'jetengine-smartslider'); ?></p>
        <?php
    }
    
    /**
     * Rendert das Feld für Standard-Einträge pro Seite
     */
    public function render_default_per_page_field() {
        $options = get_option('jetengine_smartslider_settings', []);
        $default_per_page = isset($options['default_per_page']) ? $options['default_per_page'] : 10;
        
        ?>
        <input type="number" min="1" max="100" id="default-per-page" name="jetengine_smartslider_settings[default_per_page]" value="<?php echo esc_attr($default_per_page); ?>" />
        <p class="description"><?php _e('Die Standardanzahl der Elemente, die pro Slider angezeigt werden sollen.', 'jetengine-smartslider'); ?></p>
        <?php
    }
    
    /**
     * Rendert das Feld für den Debug-Modus
     */
    public function render_debug_mode_field() {
        $options = get_option('jetengine_smartslider_settings', []);
        $debug_mode = isset($options['debug_mode']) ? $options['debug_mode'] : false;
        
        ?>
        <label for="debug-mode">
            <input type="checkbox" id="debug-mode" name="jetengine_smartslider_settings[debug_mode]" <?php checked($debug_mode); ?> />
            <?php _e('Debug-Modus aktivieren', 'jetengine-smartslider'); ?>
        </label>
        <p class="description"><?php _e('Aktiviert erweiterte Protokollierung und Debugging-Funktionen.', 'jetengine-smartslider'); ?></p>
        <?php
    }
    
    /**
     * Rendert das Feld für das Log-Level
     */
    public function render_log_level_field() {
        $options = get_option('jetengine_smartslider_settings', []);
        $log_level = isset($options['log_level']) ? $options['log_level'] : 1;
        
        ?>
        <select id="log-level" name="jetengine_smartslider_settings[log_level]">
            <option value="0" <?php selected($log_level, 0); ?>><?php _e('Fehler', 'jetengine-smartslider'); ?></option>
            <option value="1" <?php selected($log_level, 1); ?>><?php _e('Warnungen', 'jetengine-smartslider'); ?></option>
            <option value="2" <?php selected($log_level, 2); ?>><?php _e('Info', 'jetengine-smartslider'); ?></option>
            <option value="3" <?php selected($log_level, 3); ?>><?php _e('Debug', 'jetengine-smartslider'); ?></option>
        </select>
        <p class="description"><?php _e('Legt fest, welche Meldungen protokolliert werden sollen.', 'jetengine-smartslider'); ?></p>
        <?php
    }
    
    /**
     * Rendert das Feld für die Log-Dateien
     */
    public function render_view_logs_field() {
        ?>
        <button type="button" id="view-logs-button" class="button button-secondary"><?php _e('Log-Dateien anzeigen', 'jetengine-smartslider'); ?></button>
        <button type="button" id="clear-logs-button" class="button button-secondary"><?php _e('Log-Dateien leeren', 'jetengine-smartslider'); ?></button>
        
        <div id="logs-viewer" style="display: none; margin-top: 10px;">
            <textarea id="logs-content" style="width: 100%; min-height: 300px; font-family: monospace;" readonly></textarea>
        </div>
        <p class="description"><?php _e('Hier können Sie die Log-Dateien einsehen und verwalten.', 'jetengine-smartslider'); ?></p>
        <?php
    }
    
    /**
     * Validiert die Einstellungen
     * 
     * @param array $input Einstellungswerte
     * @return array Validierte Einstellungswerte
     */
    public function validate_settings($input) {
        $output = [];
        
        // Aktivierte Quellen
        if (isset($input['enabled_sources']) && is_array($input['enabled_sources'])) {
            $output['enabled_sources'] = $input['enabled_sources'];
        } else {
            $output['enabled_sources'] = [];
        }
        
        // Standard-Einträge pro Seite
        if (isset($input['default_per_page'])) {
            $output['default_per_page'] = intval($input['default_per_page']);
            if ($output['default_per_page'] < 1) {
                $output['default_per_page'] = 1;
            } elseif ($output['default_per_page'] > 100) {
                $output['default_per_page'] = 100;
            }
        } else {
            $output['default_per_page'] = 10;
        }
        
        // Debug-Modus
        $output['debug_mode'] = isset($input['debug_mode']) ? true : false;
        
        // Log-Level
        if (isset($input['log_level'])) {
            $output['log_level'] = intval($input['log_level']);
            if ($output['log_level'] < 0) {
                $output['log_level'] = 0;
            } elseif ($output['log_level'] > 3) {
                $output['log_level'] = 3;
            }
        } else {
            $output['log_level'] = 1;
        }
        
        // Error Handler-Einstellungen aktualisieren
        if (function_exists('jetengine_smartslider_error_handler')) {
            jetengine_smartslider_error_handler()->set_debug_mode($output['debug_mode']);
            jetengine_smartslider_error_handler()->set_log_level($output['log_level']);
        }
        
        return $output;
    }
    
    /**
     * Rendert die Einstellungsseite
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('JetEngine SmartSlider Generator', 'jetengine-smartslider'); ?></h1>
            
            <div class="jetengine-smartslider-header">
                <div class="jetengine-smartslider-logo">
                    <img src="<?php echo esc_url($this->plugin->plugin_url . 'assets/images/jetengine-icon.svg'); ?>" alt="JetEngine Logo" width="50" height="50">
                </div>
                <div class="jetengine-smartslider-version">
                    <span><?php echo sprintf(__('Version: %s', 'jetengine-smartslider'), $this->plugin->version); ?></span>
                </div>
            </div>
            
            <div class="jetengine-smartslider-nav">
                <ul class="jetengine-smartslider-tabs">
                    <li><a href="#settings" class="active"><?php _e('Einstellungen', 'jetengine-smartslider'); ?></a></li>
                    <li><a href="#help"><?php _e('Hilfe', 'jetengine-smartslider'); ?></a></li>
                    <li><a href="#template-guide"><?php _e('Vorlagen-Guide', 'jetengine-smartslider'); ?></a></li>
                    <li><a href="#troubleshooting"><?php _e('Fehlerbehebung', 'jetengine-smartslider'); ?></a></li>
                </ul>
            </div>
            
            <div class="jetengine-smartslider-content">
                <!-- Einstellungs-Tab -->
                <div id="settings" class="jetengine-smartslider-tab-content active">
                    <form method="post" action="options.php">
                        <?php 
                        settings_fields('jetengine_smartslider_settings');
                        do_settings_sections('jetengine_smartslider_settings');
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <!-- Hilfe-Tab -->
                <div id="help" class="jetengine-smartslider-tab-content">
                    <h2><?php _e('JetEngine SmartSlider Integration - Hilfe', 'jetengine-smartslider'); ?></h2>
                    
                    <div class="jetengine-smartslider-help-section">
                        <h3><?php _e('Was ist JetEngine SmartSlider Generator?', 'jetengine-smartslider'); ?></h3>
                        <p><?php _e('JetEngine SmartSlider Generator ist eine fortschrittliche Integration zwischen JetEngine und Smart Slider 3 Pro, die die volle Leistungsfähigkeit der dynamischen Inhalte von JetEngine nutzt.', 'jetengine-smartslider'); ?></p>
                        
                        <h3><?php _e('Hauptfunktionen', 'jetengine-smartslider'); ?></h3>
                        <ul>
                            <li><?php _e('Vollständige JetEngine-Integration: Generiere dynamische Slides aus jedem JetEngine Custom Post Type, Custom Content Type oder jeder Relation', 'jetengine-smartslider'); ?></li>
                            <li><?php _e('Erweiterte Meta-Feld-Unterstützung: Nutze alle JetEngine-Meta-Feldtypen', 'jetengine-smartslider'); ?></li>
                            <li><?php _e('Dynamische Bildquellen: Mehrere Optionen für Bildquellen', 'jetengine-smartslider'); ?></li>
                            <li><?php _e('Intelligente Taxonomie-Verarbeitung: Filtere Beiträge nach Taxonomien', 'jetengine-smartslider'); ?></li>
                            <li><?php _e('Flexible Sortierung: Sortiere Inhalte nach verschiedenen Kriterien', 'jetengine-smartslider'); ?></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Vorlagen-Guide-Tab -->
                <div id="template-guide" class="jetengine-smartslider-tab-content">
                    <h2><?php _e('Vorlagen-Guide für dynamische Slides', 'jetengine-smartslider'); ?></h2>
                    
                    <div class="jetengine-smartslider-template-section">
                        <h3><?php _e('Verfügbare Variablen in Slides', 'jetengine-smartslider'); ?></h3>
                        
                        <h4><?php _e('Allgemeine Variablen', 'jetengine-smartslider'); ?></h4>
                        <table class="widefat jetengine-smartslider-variables">
                            <thead>
                                <tr>
                                    <th><?php _e('Variable', 'jetengine-smartslider'); ?></th>
                                    <th><?php _e('Beschreibung', 'jetengine-smartslider'); ?></th>
                                    <th><?php _e('Beispiel', 'jetengine-smartslider'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>{id}</code></td>
                                    <td><?php _e('Element-ID', 'jetengine-smartslider'); ?></td>
                                    <td><code>42</code></td>
                                </tr>
                                <tr>
                                    <td><code>{title}</code></td>
                                    <td><?php _e('Elementtitel', 'jetengine-smartslider'); ?></td>
                                    <td><code>Mein Beitragstitel</code></td>
                                </tr>
                                <tr>
                                    <td><code>{url}</code></td>
                                    <td><?php _e('Element-URL', 'jetengine-smartslider'); ?></td>
                                    <td><code>https://example.com/post</code></td>
                                </tr>
                                <tr>
                                    <td><code>{date}</code></td>
                                    <td><?php _e('Erstellungsdatum', 'jetengine-smartslider'); ?></td>
                                    <td><code>01.01.2023</code></td>
                                </tr>
                                <tr>
                                    <td><code>{image}</code></td>
                                    <td><?php _e('Haupt-Bild-URL', 'jetengine-smartslider'); ?></td>
                                    <td><code>https://example.com/image.jpg</code></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h4><?php _e('Meta-Feld-Variablen', 'jetengine-smartslider'); ?></h4>
                        <table class="widefat jetengine-smartslider-variables">
                            <thead>
                                <tr>
                                    <th><?php _e('Variable', 'jetengine-smartslider'); ?></th>
                                    <th><?php _e('Beschreibung', 'jetengine-smartslider'); ?></th>
                                    <th><?php _e('Beispiel', 'jetengine-smartslider'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>{meta_FELDNAME}</code></td>
                                    <td><?php _e('Roher Meta-Feldwert', 'jetengine-smartslider'); ?></td>
                                    <td><code>42</code></td>
                                </tr>
                                <tr>
                                    <td><code>{meta_FELDNAME_formatted}</code></td>
                                    <td><?php _e('Formatierter Meta-Feldwert', 'jetengine-smartslider'); ?></td>
                                    <td><code>42 €</code></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <h3><?php _e('Beispiel-Templates', 'jetengine-smartslider'); ?></h3>
                        
                        <div class="jetengine-smartslider-example">
                            <h4><?php _e('Einfacher Produkt-Slider', 'jetengine-smartslider'); ?></h4>
                            <pre>
&lt;div class="product-slide"&gt;
  &lt;div class="product-image"&gt;
    &lt;img src="{image}" alt="{title}"&gt;
  &lt;/div&gt;
  &lt;div class="product-info"&gt;
    &lt;h3&gt;{title}&lt;/h3&gt;
    &lt;p class="price"&gt;{meta_price} €&lt;/p&gt;
    &lt;a href="{url}" class="btn"&gt;Details&lt;/a&gt;
  &lt;/div&gt;
&lt;/div&gt;
                            </pre>
                        </div>
                        
                        <div class="jetengine-smartslider-example">
                            <h4><?php _e('Immobilien-Slider mit Meta-Feldern', 'jetengine-smartslider'); ?></h4>
                            <pre>
&lt;div class="property-slide"&gt;
  &lt;img src="{image}" alt="{title}"&gt;
  &lt;div class="property-details"&gt;
    &lt;h3&gt;{title}&lt;/h3&gt;
    &lt;div class="features"&gt;
      &lt;span&gt;{meta_beds} Zimmer&lt;/span&gt;
      &lt;span&gt;{meta_baths} Bäder&lt;/span&gt;
      &lt;span&gt;{meta_area} m²&lt;/span&gt;
    &lt;/div&gt;
    &lt;p class="price"&gt;{meta_price_formatted}&lt;/p&gt;
    &lt;a href="{url}"&gt;Details ansehen&lt;/a&gt;
  &lt;/div&gt;
&lt;/div&gt;
                            </pre>
                        </div>
                    </div>
                </div>
                
                <!-- Fehlerbehebungs-Tab -->
                <div id="troubleshooting" class="jetengine-smartslider-tab-content">
                    <h2><?php _e('Fehlerbehebung', 'jetengine-smartslider'); ?></h2>
                    
                    <div class="jetengine-smartslider-troubleshooting-section">
                        <h3><?php _e('Häufige Probleme und Lösungen', 'jetengine-smartslider'); ?></h3>
                        
                        <div class="jetengine-smartslider-troubleshooting-item">
                            <h4><?php _e('JetEngine-Quellen werden nicht angezeigt', 'jetengine-smartslider'); ?></h4>
                            <p><?php _e('Stellen Sie sicher, dass JetEngine aktiviert ist und die gewünschten Quellen in den Einstellungen aktiviert sind.', 'jetengine-smartslider'); ?></p>
                            <p><?php _e('Überprüfen Sie auch, ob Sie die Pro-Version von Smart Slider 3 verwenden, da diese für die Integration erforderlich ist.', 'jetengine-smartslider'); ?></p>
                        </div>
                        
                        <div class="jetengine-smartslider-troubleshooting-item">
                            <h4><?php _e('Meta-Feld-Werte werden nicht korrekt angezeigt', 'jetengine-smartslider'); ?></h4>
                            <p><?php _e('Stellen Sie sicher, dass Sie die richtigen Feldnamen in Ihren Vorlagen verwenden. Für JetEngine-Meta-Felder sollten Sie das Format {meta_FELDNAME} verwenden.', 'jetengine-smartslider'); ?></p>
                            <p><?php _e('Einige komplexe Feldtypen wie Repeater benötigen möglicherweise spezielle Formatierung. Verwenden Sie {meta_FELDNAME_formatted} für formatierte Werte.', 'jetengine-smartslider'); ?></p>
                        </div>
                        
                        <h3><?php _e('Kompatibilitätsprüfung', 'jetengine-smartslider'); ?></h3>
                        <div class="jetengine-smartslider-compatibility">
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Anforderung', 'jetengine-smartslider'); ?></th>
                                        <th><?php _e('Status', 'jetengine-smartslider'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><?php _e('WordPress Version 5.6+', 'jetengine-smartslider'); ?></td>
                                        <td><?php echo version_compare(get_bloginfo('version'), '5.6', '>=') ? '<span class="jetengine-smartslider-success">✓</span>' : '<span class="jetengine-smartslider-error">✗</span>'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php _e('JetEngine 2.8.0+', 'jetengine-smartslider'); ?></td>
                                        <td><?php 
                                            $jet_engine_version = defined('JET_ENGINE_VERSION') ? JET_ENGINE_VERSION : false;
                                            echo $jet_engine_version && version_compare($jet_engine_version, '2.8.0', '>=') ? 
                                                '<span class="jetengine-smartslider-success">✓</span>' : 
                                                '<span class="jetengine-smartslider-error">✗</span>';
                                        ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php _e('Smart Slider 3 Pro 3.5.0+', 'jetengine-smartslider'); ?></td>
                                        <td><?php
                                            $ss3_version = defined('NEXTEND_SMARTSLIDER_3_PRO') ? 
                                                (defined('NEXTEND_SMARTSLIDER_3_VERSION') ? NEXTEND_SMARTSLIDER_3_VERSION : 'Unbekannt') : 
                                                false;
                                            echo $ss3_version && $ss3_version !== 'Unbekannt' ? 
                                                '<span class="jetengine-smartslider-success">✓</span>' : 
                                                '<span class="jetengine-smartslider-error">✗</span>';
                                        ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php _e('PHP 7.2+', 'jetengine-smartslider'); ?></td>
                                        <td><?php echo version_compare(PHP_VERSION, '7.2', '>=') ? '<span class="jetengine-smartslider-success">✓</span>' : '<span class="jetengine-smartslider-error">✗</span>'; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <h3><?php _e('Support erhalten', 'jetengine-smartslider'); ?></h3>
                        <p><?php _e('Wenn Sie weiterhin Probleme haben, besuchen Sie bitte unsere Support-Seite oder kontaktieren Sie uns.', 'jetengine-smartslider'); ?></p>
                        <p><a href="#" class="button button-primary"><?php _e('Support kontaktieren', 'jetengine-smartslider'); ?></a></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Admin-Assets einbinden
     * 
     * @param string $hook Hook-Name
     */
    public function enqueue_admin_assets($hook) {
        // Nur auf unserer Einstellungsseite und Smart Slider-Seiten laden
        if (strpos($hook, 'page_jetengine-smartslider') !== false || strpos($hook, 'smart-slider3') !== false) {
            // CSS laden
            wp_enqueue_style(
                'jetengine-smartslider-admin',
                $this->plugin->plugin_url . 'assets/css/admin.css',
                [],
                $this->plugin->version
            );
            
            // JavaScript laden
            wp_enqueue_script(
                'jetengine-smartslider-admin',
                $this->plugin->plugin_url . 'assets/js/admin.js',
                ['jquery'],
                $this->plugin->version,
                true
            );
            
            // Einstellungen für JavaScript
            wp_localize_script('jetengine-smartslider-admin', 'jetengineSmartSliderData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('jetengine_smartslider_nonce'),
                'strings' => [
                    'errorLoading' => __('Fehler beim Laden der Daten.', 'jetengine-smartslider'),
                    'logsCleared' => __('Log-Dateien wurden geleert.', 'jetengine-smartslider'),
                    'previewGenerated' => __('Vorschau wurde generiert.', 'jetengine-smartslider'),
                    'loadingLogs' => __('Lade Log-Dateien...', 'jetengine-smartslider'),
                    'noLogsFound' => __('Keine Log-Dateien gefunden.', 'jetengine-smartslider')
                ]
            ]);
        }
    }
    
    /**
     * Hilfe-Tabs hinzufügen
     */
    public function add_help_tabs() {
        $screen = get_current_screen();
        
        // Nur auf unserer Einstellungsseite hinzufügen
        if ($screen->id !== 'smart-slider3_page_jetengine-smartslider') {
            return;
        }
        
        // Übersichts-Tab
        $screen->add_help_tab([
            'id'      => 'jetengine-smartslider-overview',
            'title'   => __('Übersicht', 'jetengine-smartslider'),
            'content' => '<h2>' . __('JetEngine SmartSlider Generator', 'jetengine-smartslider') . '</h2>' .
                         '<p>' . __('Eine fortschrittliche Integration zwischen JetEngine und Smart Slider 3 Pro, die die volle Leistungsfähigkeit der dynamischen Inhalte von JetEngine nutzt.', 'jetengine-smartslider') . '</p>' .
                         '<p>' . __('Mit diesem Plugin können Sie dynamische Sliders aus JetEngine-Inhalten erstellen, einschließlich Custom Post Types, Custom Content Types und Relations.', 'jetengine-smartslider') . '</p>'
        ]);
        
        // Erste Schritte-Tab
        $screen->add_help_tab([
            'id'      => 'jetengine-smartslider-getting-started',
            'title'   => __('Erste Schritte', 'jetengine-smartslider'),
            'content' => '<h2>' . __('Erste Schritte', 'jetengine-smartslider') . '</h2>' .
                         '<p>' . __('Um einen dynamischen Slider zu erstellen:', 'jetengine-smartslider') . '</p>' .
                         '<ol>' .
                         '<li>' . __('Gehen Sie zu Smart Slider 3 > Slider erstellen > Dynamischer Slide', 'jetengine-smartslider') . '</li>' .
                         '<li>' . __('Wählen Sie "JetEngine" aus der Generator-Liste', 'jetengine-smartslider') . '</li>' .
                         '<li>' . __('Wählen Sie die spezifische JetEngine-Quelle aus', 'jetengine-smartslider') . '</li>' .
                         '<li>' . __('Konfigurieren Sie die Filter-Einstellungen nach Bedarf', 'jetengine-smartslider') . '</li>' .
                         '<li>' . __('Klicken Sie auf "Slider erstellen", um Ihren dynamischen Slider zu generieren', 'jetengine-smartslider') . '</li>' .
                         '</ol>'
        ]);
        
        // Seitenhilfe hinzufügen
        $screen->set_help_sidebar(
            '<p><strong>' . __('Weitere Informationen:', 'jetengine-smartslider') . '</strong></p>' .
            '<p><a href="#">' . __('Dokumentation', 'jetengine-smartslider') . '</a></p>' .
            '<p><a href="#">' . __('Support', 'jetengine-smartslider') . '</a></p>' .
            '<p><a href="#">' . __('Über JetEngine', 'jetengine-smartslider') . '</a></p>'
        );
    }
    
    /**
     * Admin-Hinweise anzeigen
     */
    public function admin_notices() {
        // Prüfen, ob JetEngine aktiviert ist
        if (!function_exists('jet_engine')) {
            ?>
            <div class="notice notice-error">
                <p><?php _e('JetEngine Advanced Smart Slider Generator benötigt JetEngine, um zu funktionieren. Bitte installieren und aktivieren Sie JetEngine.', 'jetengine-smartslider'); ?></p>
            </div>
            <?php
        }
        
        // Prüfen, ob Smart Slider 3 Pro aktiviert ist
        if (!defined('NEXTEND_SMARTSLIDER_3_PRO')) {
            ?>
            <div class="notice notice-error">
                <p><?php _e('JetEngine Advanced Smart Slider Generator benötigt Smart Slider 3 Pro, um zu funktionieren. Bitte installieren und aktivieren Sie Smart Slider 3 Pro.', 'jetengine-smartslider'); ?></p>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX-Handler für die Vorschau
     */
    public function handle_preview_ajax() {
        // Nonce überprüfen
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jetengine_smartslider_nonce')) {
            wp_send_json_error(['message' => __('Sicherheitsüberprüfung fehlgeschlagen.', 'jetengine-smartslider')]);
        }
        
        // Berechtigungen prüfen
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unzureichende Berechtigungen.', 'jetengine-smartslider')]);
        }
        
        // Vorschaudaten verarbeiten
        if (!isset($_POST['generator_type']) || !isset($_POST['generator_id'])) {
            wp_send_json_error(['message' => __('Fehlende Parameter.', 'jetengine-smartslider')]);
        }
        
        $generator_type = sanitize_text_field($_POST['generator_type']);
        $generator_id = sanitize_text_field($_POST['generator_id']);
        
        // Hier können Sie den entsprechenden Generator-Code aufrufen, um Vorschaudaten zu generieren
        $preview_data = $this->generate_preview_data($generator_type, $generator_id);
        
        wp_send_json_success([
            'message' => __('Vorschau erfolgreich generiert.', 'jetengine-smartslider'),
            'data' => $preview_data
        ]);
    }
    
    /**
     * Generiert Vorschaudaten für den Generator
     * 
     * @param string $generator_type Generator-Typ
     * @param string $generator_id Generator-ID
     * @return array Vorschaudaten
     */
    private function generate_preview_data($generator_type, $generator_id) {
        $preview_data = [];
        
        // Je nach Generator-Typ unterschiedlich verarbeiten
        switch ($generator_type) {
            case 'post_type':
                // Vorschaudaten für Post Type generieren
                $preview_data = $this->generate_post_type_preview($generator_id);
                break;
                
            case 'content_type':
                // Vorschaudaten für Content Type generieren
                $preview_data = $this->generate_content_type_preview($generator_id);
                break;
                
            case 'relation':
                // Vorschaudaten für Relation generieren
                $preview_data = $this->generate_relation_preview($generator_id);
                break;
                
            case 'query':
                // Vorschaudaten für Query generieren
                $preview_data = $this->generate_query_preview($generator_id);
                break;
                
            default:
                $preview_data = [
                    'error' => __('Nicht unterstützter Generator-Typ.', 'jetengine-smartslider')
                ];
                break;
        }
        
        return $preview_data;
    }
    
    /**
     * Generiert Vorschaudaten für einen Post Type
     * 
     * @param string $post_type Post Type-Name
     * @return array Vorschaudaten
     */
    private function generate_post_type_preview($post_type) {
        $preview_data = [];
        
        // Beispiel-Posts holen
        $args = [
            'post_type' => $post_type,
            'posts_per_page' => 5,
            'post_status' => 'publish'
        ];
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                $preview_item = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'date' => get_the_date(),
                    'excerpt' => get_the_excerpt()
                ];
                
                // Beitragsbild hinzufügen
                if (has_post_thumbnail()) {
                    $preview_item['image'] = get_the_post_thumbnail_url(null, 'medium');
                }
                
                $preview_data[] = $preview_item;
            }
            
            wp_reset_postdata();
        }
        
        return [
            'items' => $preview_data,
            'count' => count($preview_data),
            'post_type' => $post_type
        ];
    }
    
    /**
     * Generiert Vorschaudaten für einen Content Type
     * 
     * @param string $content_type Content Type-ID
     * @return array Vorschaudaten
     */
    private function generate_content_type_preview($content_type) {
        $preview_data = [];
        
        // CCT-Modul prüfen
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'modules') || 
            !jet_engine()->modules->is_module_active('custom-content-types')) {
            return [
                'items' => [],
                'count' => 0,
                'error' => __('Custom Content Types-Modul ist nicht aktiviert.', 'jetengine-smartslider')
            ];
        }
        
        // CCT-Slug aus der ID extrahieren
        $cct_slug = str_replace('cct_', '', $content_type);
        
        // CCT-Modul abrufen
        $cct_module = jet_engine()->modules->get_module('custom-content-types');
        
        if (!$cct_module || !method_exists($cct_module, 'instance') || !property_exists($cct_module, 'instance')) {
            return [
                'items' => [],
                'count' => 0,
                'error' => __('CCT-Modul ist nicht verfügbar.', 'jetengine-smartslider')
            ];
        }
        
        // CCT-Manager abrufen
        $cct_manager = $cct_module->instance->manager;
        
        if (!$cct_manager || !method_exists($cct_manager, 'get_items')) {
            return [
                'items' => [],
                'count' => 0,
                'error' => __('CCT-Manager ist nicht verfügbar.', 'jetengine-smartslider')
            ];
        }
        
        // CCT-Items abrufen
        $items = $cct_manager->get_items($cct_slug, [
            'limit' => 5
        ]);
        
        if (!empty($items)) {
            foreach ($items as $item) {
                $preview_item = [
                    'id' => isset($item['_ID']) ? $item['_ID'] : '',
                    'date' => isset($item['cct_created']) ? $item['cct_created'] : '',
                    'modified' => isset($item['cct_modified']) ? $item['cct_modified'] : ''
                ];
                
                // Titel bestimmen
                if (isset($item['title'])) {
                    $preview_item['title'] = $item['title'];
                } elseif (isset($item['name'])) {
                    $preview_item['title'] = $item['name'];
                } else {
                    $preview_item['title'] = __('Item #', 'jetengine-smartslider') . $preview_item['id'];
                }
                
                // Felder hinzufügen
                foreach ($item as $key => $value) {
                    // Interne Felder überspringen
                    if (substr($key, 0, 1) === '_' || in_array($key, ['cct_status', 'cct_created', 'cct_modified'])) {
                        continue;
                    }
                    
                    // Feldwert formatieren
                    if (is_array($value)) {
                        $preview_item[$key] = json_encode($value);
                    } else {
                        $preview_item[$key] = $value;
                    }
                }
                
                $preview_data[] = $preview_item;
            }
        }
        
        return [
            'items' => $preview_data,
            'count' => count($preview_data),
            'content_type' => $cct_slug
        ];
    }
    
    /**
     * Generiert Vorschaudaten für eine Relation
     * 
     * @param string $relation_id Relation-ID
     * @return array Vorschaudaten
     */
    private function generate_relation_preview($relation_id) {
        $preview_data = [];
        
        // Relations-Modul prüfen
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'relations')) {
            return [
                'items' => [],
                'count' => 0,
                'error' => __('Relations-Modul ist nicht verfügbar.', 'jetengine-smartslider')
            ];
        }
        
        // Relation-ID extrahieren
        $relation_id = str_replace('relation_', '', $relation_id);
        
        // Relations-Manager abrufen
        $relations_manager = jet_engine()->relations;
        
        if (!$relations_manager || !method_exists($relations_manager, 'get_relation_instances')) {
            return [
                'items' => [],
                'count' => 0,
                'error' => __('Relations-Manager ist nicht verfügbar.', 'jetengine-smartslider')
            ];
        }
        
        // Relation-Instanzen abrufen
        $relation_instances = $relations_manager->get_relation_instances();
        
        // Ziel-Relation finden
        $relation_instance = null;
        foreach ($relation_instances as $instance) {
            if (method_exists($instance, 'get_id') && $instance->get_id() == $relation_id) {
                $relation_instance = $instance;
                break;
            }
        }
        
        if (!$relation_instance) {
            return [
                'items' => [],
                'count' => 0,
                'error' => __('Relation nicht gefunden.', 'jetengine-smartslider')
            ];
        }
        
        // Beispiel-Elternbeitrag finden
        $parent_posts = get_posts([
            'post_type' => $relation_instance->get_args('parent_object'),
            'posts_per_page' => 1
        ]);
        
        if (empty($parent_posts)) {
            return [
                'items' => [],
                'count' => 0,
                'error' => sprintf(
                    __('Keine Beiträge für den Eltern-Objekttyp %s gefunden.', 'jetengine-smartslider'),
                    $relation_instance->get_args('parent_object')
                )
            ];
        }
        
        $parent_id = $parent_posts[0]->ID;
        
        // Kindobjekte für Elternobjekt abrufen
        $related_items = [];
        
        try {
            if (method_exists($relation_instance, 'get_children')) {
                $related_items = $relation_instance->get_children($parent_id, true, [
                    'posts_per_page' => 5
                ]);
            }
        } catch (Exception $e) {
            return [
                'items' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
        
        // Elemente verarbeiten
        if (!empty($related_items) && is_array($related_items)) {
            foreach ($related_items as $item) {
                // Überspringen, wenn kein Post-Objekt
                if (!is_a($item, 'WP_Post')) {
                    continue;
                }
                
                $preview_item = [
                    'id' => $item->ID,
                    'title' => $item->post_title,
                    'url' => get_permalink($item->ID),
                    'date' => get_the_date('', $item->ID),
                    'excerpt' => has_excerpt($item->ID) ? get_the_excerpt($item->ID) : wp_trim_words($item->post_content, 20),
                    'post_type' => $item->post_type
                ];
                
                // Beitragsbild hinzufügen
                if (has_post_thumbnail($item->ID)) {
                    $preview_item['image'] = get_the_post_thumbnail_url($item->ID, 'medium');
                }
                
                $preview_data[] = $preview_item;
            }
        }
        
        return [
            'items' => $preview_data,
            'count' => count($preview_data),
            'relation_id' => $relation_id,
            'parent_id' => $parent_id,
            'parent_type' => $relation_instance->get_args('parent_object'),
            'child_type' => $relation_instance->get_args('child_object')
        ];
    }
    
    /**
     * Generiert Vorschaudaten für eine Query
     * 
     * @param string $query_id Query-ID
     * @return array Vorschaudaten
     */
    private function generate_query_preview($query_id) {
        $preview_data = [];
        
        // Query-Builder-Modul prüfen
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'modules') || 
            !jet_engine()->modules->is_module_active('query-builder')) {
            return [
                'items' => [],
                'count' => 0,
                'error' => __('Query Builder-Modul ist nicht aktiviert.', 'jetengine-smartslider')
            ];
        }
        
        // Query-ID extrahieren
        $query_id = str_replace('query_', '', $query_id);
        
        // Query-Builder-Modul abrufen
        $query_builder = jet_engine()->modules->get_module('query-builder');
        
        if (!$query_builder || !isset($query_builder->instance) || !method_exists($query_builder->instance, 'get_query_by_id')) {
            return [
                'items' => [],
                'count' => 0,
                'error' => __('Query Builder-Modul ist nicht verfügbar.', 'jetengine-smartslider')
            ];
        }
        
        // Query anhand der ID abrufen
        $query_instance = $query_builder->instance->get_query_by_id($query_id);
        
        if (!$query_instance) {
            return [
                'items' => [],
                'count' => 0,
                'error' => sprintf(__('Query mit ID %s nicht gefunden.', 'jetengine-smartslider'), $query_id)
            ];
        }
        
        try {
            // Query ausführen
            $query_instance->setup_query([
                'limit' => 5
            ]);
            $items = $query_instance->get_items();
            
            // Query-Typ bestimmen
            $query_type = isset($query_instance->query_type) ? $query_instance->query_type : '';
            
            // Elemente verarbeiten
            if (!empty($items)) {
                foreach ($items as $item) {
                    $preview_item = [];
                    
                    // Je nach Query-Typ unterschiedlich verarbeiten
                    switch ($query_type) {
                        case 'posts':
                            if (is_a($item, 'WP_Post')) {
                                $preview_item = [
                                    'id' => $item->ID,
                                    'title' => $item->post_title,
                                    'url' => get_permalink($item->ID),
                                    'date' => get_the_date('', $item->ID),
                                    'post_type' => $item->post_type
                                ];
                                
                                // Beitragsbild hinzufügen
                                if (has_post_thumbnail($item->ID)) {
                                    $preview_item['image'] = get_the_post_thumbnail_url($item->ID, 'medium');
                                }
                            }
                            break;
                            
                        case 'terms':
                            if (is_a($item, 'WP_Term')) {
                                $preview_item = [
                                    'id' => $item->term_id,
                                    'title' => $item->name,
                                    'url' => get_term_link($item),
                                    'taxonomy' => $item->taxonomy,
                                    'count' => $item->count
                                ];
                            }
                            break;
                            
                        case 'users':
                            if (is_a($item, 'WP_User')) {
                                $preview_item = [
                                    'id' => $item->ID,
                                    'title' => $item->display_name,
                                    'url' => get_author_posts_url($item->ID),
                                    'email' => $item->user_email,
                                    'roles' => implode(', ', $item->roles)
                                ];
                                
                                // Avatar hinzufügen
                                $preview_item['image'] = get_avatar_url($item->ID);
                            }
                            break;
                            
                        default:
                            // Generische Verarbeitung
                            if (is_object($item)) {
                                foreach (get_object_vars($item) as $key => $value) {
                                    if (is_scalar($value)) {
                                        $preview_item[$key] = $value;
                                    } elseif (is_array($value)) {
                                        $preview_item[$key] = json_encode($value);
                                    }
                                }
                            } elseif (is_array($item)) {
                                foreach ($item as $key => $value) {
                                    if (is_scalar($value)) {
                                        $preview_item[$key] = $value;
                                    } elseif (is_array($value)) {
                                        $preview_item[$key] = json_encode($value);
                                    }
                                }
                            }
                            
                            // ID und Titel bestimmen
                            if (!isset($preview_item['id']) && isset($preview_item['ID'])) {
                                $preview_item['id'] = $preview_item['ID'];
                            }
                            
                            if (!isset($preview_item['title'])) {
                                if (isset($preview_item['name'])) {
                                    $preview_item['title'] = $preview_item['name'];
                                } elseif (isset($preview_item['label'])) {
                                    $preview_item['title'] = $preview_item['label'];
                                } else {
                                    $preview_item['title'] = isset($preview_item['id']) ? 
                                        __('Item #', 'jetengine-smartslider') . $preview_item['id'] : 
                                        __('Item', 'jetengine-smartslider');
                                }
                            }
                            break;
                    }
                    
                    $preview_data[] = $preview_item;
                }
            }
        } catch (Exception $e) {
            return [
                'items' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
        
        return [
            'items' => $preview_data,
            'count' => count($preview_data),
            'query_id' => $query_id,
            'query_type' => $query_type
        ];
    }
    
    /**
     * Plugin-Aktionslinks hinzufügen
     * 
     * @param array $links Bestehende Links
     * @return array Modifizierte Links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=jetengine-smartslider') . '">' . __('Einstellungen', 'jetengine-smartslider') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
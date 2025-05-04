<?php
/**
 * JetEngine Query Builder Source
 * 
 * Diese Klasse verarbeitet die Query Builder-Generator-Quelle
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) exit;

use Nextend\SmartSlider3\Generator\AbstractGenerator;
use Nextend\Framework\Form\Container\ContainerTable;
use Nextend\Framework\Form\Element\OnOff;
use Nextend\Framework\Form\Element\Select;
use Nextend\Framework\Form\Element\Text;
use Nextend\Framework\Form\Element\Textarea;
// Hier als MixedElement importieren um PHP 8+ Kompatibilität zu gewährleisten
use Nextend\Framework\Form\Element\Mixed as MixedElement;
use Nextend\Framework\Form\Element\Group;

class JetEngine_SmartSlider_Source_Query extends AbstractGenerator {
    
    protected $layout = 'article';
    private $query_id;
    private $query;
    
    /**
     * Konstruktor
     */
    public function __construct($group, $name, $label, $query) {
        $this->query_id = str_replace('query_', '', $name);
        $this->query = $query;
        
        parent::__construct($group, $name, $label);
        
        jetengine_smartslider_generator()->log('Query Source konstruiert für: ' . $name);
    }
    
    /**
     * Gibt die Beschreibung des Generators zurück
     */
    public function getDescription() {
        return sprintf(n2_('Erstellt Slides aus der JetEngine Query "%s".'), $this->query['name']);
    }
    
    /**
     * Rendert die Formularfelder des Generators im Admin-Bereich
     * 
     * @param ContainerTable $container
     */
    public function renderFields($container) {
        // Filter-Gruppe
        $filterGroup = new ContainerTable($container, 'filter-group', n2_('Filter'));
        
        // Abfrage-Optionen
        $queryRow = $filterGroup->createRow('query-options');
        
        // Limit (Anzahl der Einträge)
        new Text($queryRow, 'limit', n2_('Limit'), '20', [
            'tipLabel'       => n2_('Limit'),
            'tipDescription' => n2_('Maximale Anzahl an Elementen, die zurückgegeben werden sollen.')
        ]);
        
        // Offset (Startpunkt)
        new Text($queryRow, 'offset', n2_('Offset'), '0', [
            'tipLabel'       => n2_('Offset'),
            'tipDescription' => n2_('Anzahl der zu überspringenden Elemente.')
        ]);
        
        // Zusätzliche Parameter
        $paramsRow = $filterGroup->createRow('additional-params');
        
        // Zusätzliche Query-Parameter als JSON
        new Textarea($paramsRow, 'custom_args', n2_('Zusätzliche Parameter (JSON)'), '', [
            'tipLabel'       => n2_('Zusätzliche Parameter'),
            'tipDescription' => n2_('Zusätzliche Parameter für die Query im JSON-Format, z.B. {"post__in": [1, 2, 3]}')
        ]);
        
        // Sortier-Gruppe
        $orderGroup = new ContainerTable($container, 'order-group', n2_('Sortierung'));
        $orderRow = $orderGroup->createRow('order-row');
        
        // Dynamische Sortieroptionen basierend auf dem Query-Typ
        $orderby_options = [
            'default' => n2_('Standard (wie in der Query definiert)'),
            'ID'      => n2_('ID'),
            'date'    => n2_('Datum'),
            'title'   => n2_('Titel'),
            'rand'    => n2_('Zufällig')
        ];
        
        // Meta-Sortierung hinzufügen, falls unterstützt
        if ($this->supportsMetaFieldSorting()) {
            $orderby_options['meta_value'] = n2_('Meta-Feld');
            $orderby_options['meta_value_num'] = n2_('Meta-Feld (numerisch)');
        }
        
        // Sortieren nach
        new Select($orderRow, 'orderby', n2_('Sortieren nach'), 'default', [
            'options' => $orderby_options
        ]);
        
        // Meta-Feld für Sortierung
        if ($this->supportsMetaFieldSorting()) {
            new Text($orderRow, 'meta_key', n2_('Meta-Feld für Sortierung'), '', [
                'tipLabel'       => n2_('Meta-Feld für Sortierung'),
                'tipDescription' => n2_('Wenn nach einem Meta-Wert sortiert wird, geben Sie hier den Meta-Key ein.')
            ]);
        }
        
        // Sortierreihenfolge
        new Select($orderRow, 'order', n2_('Reihenfolge'), 'DESC', [
            'options' => [
                'DESC' => n2_('Absteigend'),
                'ASC'  => n2_('Aufsteigend')
            ]
        ]);
        
        // Bildquellen-Optionen
        $this->renderImageOptions($container);
    }
    
    /**
     * Prüft, ob der Query-Typ die Sortierung nach Meta-Feldern unterstützt
     * 
     * @return bool True, wenn Meta-Feld-Sortierung unterstützt wird
     */
    private function supportsMetaFieldSorting() {
        if (!isset($this->query['type'])) {
            return false;
        }
        
        // Liste der Query-Typen, die Meta-Feld-Sortierung unterstützen
        $supported_types = [
            'posts',
            'users',
            'terms',
            'comments',
            'custom-content-type'
        ];
        
        return in_array($this->query['type'], $supported_types);
    }
    
    /**
     * Rendert die Bildquellen-Optionen
     */
    private function renderImageOptions($container) {
        $imageGroup = new ContainerTable($container, 'image-options', n2_('Bildoptionen'));
        $imageRow = $imageGroup->createRow('image-row');
        
        // Bildquelle
        $image_options = [
            'featured'    => n2_('Beitragsbild'),
            'first'       => n2_('Erstes Bild aus dem Inhalt'),
            'meta'        => n2_('Meta-Feld'),
            'content'     => n2_('Inhaltsbilder'),
            'jet_gallery' => n2_('JetEngine Galerie-Feld')
        ];
        
        // Optionen basierend auf Query-Typ anpassen
        if (isset($this->query['type'])) {
            if ($this->query['type'] === 'users') {
                $image_options = [
                    'avatar'      => n2_('Benutzer-Avatar'),
                    'meta'        => n2_('Meta-Feld'),
                    'jet_gallery' => n2_('JetEngine Galerie-Feld')
                ];
            } elseif ($this->query['type'] === 'terms') {
                $image_options = [
                    'meta'        => n2_('Meta-Feld'),
                    'jet_gallery' => n2_('JetEngine Galerie-Feld')
                ];
            }
        }
        
        new Select($imageRow, 'image_source', n2_('Bildquelle'), key($image_options), [
            'options' => $image_options
        ]);
        
        // Meta-Feld für Bild
        new Text($imageRow, 'image_meta', n2_('Bild-Meta-Feld'), '', [
            'tipLabel'       => n2_('Bild-Meta-Feld'),
            'tipDescription' => n2_('Meta-Feld, das die Bild-ID, URL oder ein JetEngine Galerie-Feld enthält.')
        ]);
    }
    
    /**
     * Holt die Daten für den Generator
     * 
     * @param int $count Anzahl der zurückzugebenden Elemente
     * @param int $startIndex Startindex
     * @return array Generierte Daten
     */
    protected function _getData($count, $startIndex) {
        jetengine_smartslider_generator()->log('Hole Daten für Query: ' . $this->query_id);
        
        $data = [];
        
        // Prüfen, ob JetEngine Query Builder-Modul aktiv ist
        if (!function_exists('jet_engine') || !method_exists(jet_engine(), 'modules') || 
            !jet_engine()->modules->is_module_active('query-builder')) {
            return $data;
        }
        
        // Query Builder-Modul abrufen
        $query_builder = jet_engine()->modules->get_module('query-builder');
        
        if (!$query_builder || !isset($query_builder->instance) || !method_exists($query_builder->instance, 'get_query_by_id')) {
            return $data;
        }
        
        // Query anhand der ID abrufen
        $query_instance = $query_builder->instance->get_query_by_id($this->query_id);
        
        if (!$query_instance) {
            jetengine_smartslider_generator()->log('Query nicht gefunden: ' . $this->query_id);
            return $data;
        }
        
        try {
            // Query-Argumente erstellen
            $args = $this->buildQueryArgs($count, $startIndex);
            
            // Query ausführen
            $query_instance->setup_query($args);
            $items = $query_instance->get_items();
            
            // Elemente verarbeiten
            if (!empty($items)) {
                foreach ($items as $item) {
                    $record = $this->getItemData($item, $query_instance);
                    
                    // Zu Ergebnisdaten hinzufügen
                    $data[] = $record;
                }
            }
        } catch (\Exception $e) {
            jetengine_smartslider_generator()->log('Fehler beim Abrufen der Query-Daten: ' . $e->getMessage());
        }
        
        return $data;
    }
    
    /**
     * Erstellt die Query-Argumente basierend auf den Formulardaten
     * 
     * @param int $count Anzahl der zurückzugebenden Elemente
     * @param int $startIndex Startindex
     * @return array Query-Argumente
     */
    private function buildQueryArgs($count, $startIndex) {
        $args = [
            'limit'  => $this->data->get('limit', $count),
            'offset' => $this->data->get('offset', $startIndex)
        ];
        
        // Sortieroptionen
        $orderby = $this->data->get('orderby', 'default');
        if ($orderby !== 'default') {
            $args['orderby'] = $orderby;
            $args['order'] = $this->data->get('order', 'DESC');
            
            // Meta-Schlüssel für Sortierung
            if (in_array($orderby, ['meta_value', 'meta_value_num'])) {
                $meta_key = $this->data->get('meta_key', '');
                if (!empty($meta_key)) {
                    $args['meta_key'] = $meta_key;
                }
            }
        }
        
        // Benutzerdefinierte Argumente aus JSON-Eingabe
        $custom_args_json = $this->data->get('custom_args', '');
        if (!empty($custom_args_json)) {
            try {
                $custom_args = json_decode($custom_args_json, true);
                if (is_array($custom_args)) {
                    $args = array_merge($args, $custom_args);
                }
            } catch (\Exception $e) {
                jetengine_smartslider_generator()->log('Fehler beim Parsen der benutzerdefinierten Argumente: ' . $e->getMessage());
            }
        }
        
        return $args;
    }
    
    /**
     * Verarbeitet die Daten eines einzelnen Elements
     * 
     * @param mixed $item Element-Daten
     * @param object $query_instance Query-Instanz
     * @return array Verarbeitete Daten
     */
    private function getItemData($item, $query_instance) {
        $record = [];
        $query_type = isset($this->query['type']) ? $this->query['type'] : '';
        
        // Je nach Query-Typ unterschiedlich verarbeiten
        switch ($query_type) {
            case 'posts':
                $record = $this->getPostItemData($item);
                break;
                
            case 'terms':
                $record = $this->getTermItemData($item);
                break;
                
            case 'users':
                $record = $this->getUserItemData($item);
                break;
                
            case 'custom-content-type':
                $record = $this->getCCTItemData($item);
                break;
                
            default:
                // Generische Verarbeitung für andere Typen
                $record = $this->getGenericItemData($item, $query_instance);
                break;
        }
        
        // Bilddaten hinzufügen
        $this->addImageData($record, $item, $query_type);
        
        return $record;
    }
    
    /**
     * Holt die Daten für ein Post-Item
     * 
     * @param WP_Post $post Post-Objekt
     * @return array Post-Daten
     */
    private function getPostItemData($post) {
        $record = [];
        
        // Grundlegende Post-Daten
        $record['id'] = $post->ID;
        $record['title'] = $post->post_title;
        $record['content'] = $post->post_content;
        $record['url'] = get_permalink($post->ID);
        $record['author_name'] = get_the_author_meta('display_name', $post->post_author);
        $record['author_url'] = get_author_posts_url($post->post_author);
        $record['date'] = get_the_date('', $post->ID);
        $record['modified'] = get_the_modified_date('', $post->ID);
        $record['excerpt'] = has_excerpt($post->ID) ? get_the_excerpt($post->ID) : JetEngine_SmartSlider_Helper::generate_excerpt($post->post_content);
        $record['post_type'] = $post->post_type;
        
        // Kommentare
        $record['comment_count'] = $post->comment_count;
        $record['comment_status'] = $post->comment_status;
        
        // Taxonomien hinzufügen
        $this->addTaxonomiesData($record, $post);
        
        // Meta-Felder hinzufügen
        $this->addMetaFieldsData($record, $post);
        
        return $record;
    }
    
    /**
     * Holt die Daten für ein Term-Item
     * 
     * @param WP_Term $term Term-Objekt
     * @return array Term-Daten
     */
    private function getTermItemData($term) {
        $record = [];
        
        // Grundlegende Term-Daten
        $record['id'] = $term->term_id;
        $record['title'] = $term->name;
        $record['content'] = $term->description;
        $record['url'] = get_term_link($term);
        $record['slug'] = $term->slug;
        $record['taxonomy'] = $term->taxonomy;
        $record['count'] = $term->count;
        
        // Meta-Felder für Terms hinzufügen (wenn verfügbar)
        if (function_exists('get_term_meta')) {
            $term_meta = get_term_meta($term->term_id);
            if (!empty($term_meta)) {
                foreach ($term_meta as $key => $values) {
                    if (isset($values[0])) {
                        $record['meta_' . $key] = $values[0];
                    }
                }
            }
        }
        
        return $record;
    }
    
    /**
     * Holt die Daten für ein User-Item
     * 
     * @param WP_User $user User-Objekt
     * @return array User-Daten
     */
    private function getUserItemData($user) {
        $record = [];
        
        // Grundlegende User-Daten
        $record['id'] = $user->ID;
        $record['title'] = $user->display_name;
        $record['content'] = isset($user->description) ? $user->description : '';
        $record['url'] = get_author_posts_url($user->ID);
        $record['email'] = $user->user_email;
        $record['login'] = $user->user_login;
        $record['registered'] = $user->user_registered;
        $record['roles'] = implode(', ', $user->roles);
        $record['posts_count'] = count_user_posts($user->ID);
        
        // Avatar-URL
        $avatar_url = get_avatar_url($user->ID);
        if ($avatar_url) {
            $record['avatar'] = $avatar_url;
        }
        
        // Meta-Felder für Benutzer hinzufügen
        $user_meta = get_user_meta($user->ID);
        if (!empty($user_meta)) {
            foreach ($user_meta as $key => $values) {
                // Interne Felder überspringen
                if (in_array($key, ['session_tokens', 'capabilities', 'user_level', 'user-settings', 'user-settings-time'])) {
                    continue;
                }
                
                if (isset($values[0])) {
                    $record['meta_' . $key] = $values[0];
                }
            }
        }
        
        return $record;
    }
    
    /**
     * Holt die Daten für ein Custom Content Type-Item
     * 
     * @param array $item CCT-Item
     * @return array CCT-Item-Daten
     */
    private function getCCTItemData($item) {
        $record = [];
        
        // ID des Items
        $record['id'] = isset($item['_ID']) ? $item['_ID'] : '';
        
        // Alle CCT-Felder durchlaufen
        foreach ($item as $key => $value) {
            // Interne Felder überspringen
            if (substr($key, 0, 1) === '_') {
                continue;
            }
            
            // Feld hinzufügen
            $record[$key] = $value;
        }
        
        // CCT-spezifische Felder
        $record['cct_type'] = isset($item['cct_type']) ? $item['cct_type'] : '';
        $record['cct_status'] = isset($item['cct_status']) ? $item['cct_status'] : '';
        $record['date'] = isset($item['cct_created']) ? $item['cct_created'] : '';
        $record['modified'] = isset($item['cct_modified']) ? $item['cct_modified'] : '';
        
        return $record;
    }
    
    /**
     * Verarbeitet Daten von jedem anderen Query-Typ
     * 
     * @param mixed $item Item-Daten
     * @param object $query_instance Query-Instanz
     * @return array Verarbeitete Daten
     */
    private function getGenericItemData($item, $query_instance) {
        $record = [];
        
        // Versuchen, eine ID zu bestimmen
        if (is_object($item) && isset($item->ID)) {
            $record['id'] = $item->ID;
        } elseif (is_array($item) && isset($item['ID'])) {
            $record['id'] = $item['ID'];
        } elseif (is_array($item) && isset($item['id'])) {
            $record['id'] = $item['id'];
        } else {
            $record['id'] = md5(serialize($item));
        }
        
        // Bei Objekten alle öffentlichen Eigenschaften hinzufügen
        if (is_object($item)) {
            foreach (get_object_vars($item) as $key => $value) {
                if (is_scalar($value) || is_null($value)) {
                    $record[$key] = $value;
                } elseif (is_array($value)) {
                    $record[$key] = json_encode($value);
                }
            }
        } 
        // Bei Arrays alle Elemente hinzufügen
        elseif (is_array($item)) {
            foreach ($item as $key => $value) {
                if (is_scalar($value) || is_null($value)) {
                    $record[$key] = $value;
                } elseif (is_array($value)) {
                    $record[$key] = json_encode($value);
                }
            }
        }
        
        // Titel und Inhalt bestimmen
        if (!isset($record['title'])) {
            if (isset($record['name'])) {
                $record['title'] = $record['name'];
            } elseif (isset($record['label'])) {
                $record['title'] = $record['label'];
            } else {
                $record['title'] = 'Item #' . $record['id'];
            }
        }
        
        if (!isset($record['content'])) {
            if (isset($record['description'])) {
                $record['content'] = $record['description'];
            } elseif (isset($record['text'])) {
                $record['content'] = $record['text'];
            } else {
                $record['content'] = '';
            }
        }
        
        return $record;
    }
    
    /**
     * Fügt Taxonomie-Daten zum Datensatz hinzu
     * 
     * @param array $record Datensatz (per Referenz)
     * @param WP_Post $post Post-Objekt
     */
    private function addTaxonomiesData(&$record, $post) {
        $taxonomies = get_object_taxonomies($post->post_type);
        
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post->ID, $taxonomy, ['fields' => 'all']);
            
            if (!empty($terms) && !is_wp_error($terms)) {
                // Term-Namen als Liste
                $term_names = [];
                $term_slugs = [];
                $term_urls = [];
                
                foreach ($terms as $term) {
                    $term_names[] = $term->name;
                    $term_slugs[] = $term->slug;
                    $term_urls[] = get_term_link($term);
                }
                
                $record[$taxonomy] = implode(', ', $term_names);
                $record[$taxonomy . '_slugs'] = implode(', ', $term_slugs);
                $record[$taxonomy . '_urls'] = implode(', ', $term_urls);
                
                // Erstes Term-Feld separat hinzufügen
                if (isset($terms[0])) {
                    $record[$taxonomy . '_name'] = $terms[0]->name;
                    $record[$taxonomy . '_slug'] = $terms[0]->slug;
                    $record[$taxonomy . '_url'] = get_term_link($terms[0]);
                }
            }
        }
    }
    
    /**
     * Fügt Meta-Feld-Daten zum Datensatz hinzu
     * 
     * @param array $record Datensatz (per Referenz)
     * @param WP_Post $post Post-Objekt
     */
    private function addMetaFieldsData(&$record, $post) {
        $meta_fields = get_post_meta($post->ID);
        
        foreach ($meta_fields as $key => $values) {
            // Interne Felder überspringen
            if (substr($key, 0, 1) === '_') {
                continue;
            }
            
            if (isset($values[0])) {
                // Feldwert holen
                $value = $values[0];
                
                // Prüfen, ob Wert serialisiert ist
                if (is_serialized($value)) {
                    $value = maybe_unserialize($value);
                }
                
                // Arrays/Objekte behandeln
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                
                // Zum Datensatz hinzufügen
                $record['meta_' . $key] = $value;
                
                // Spezielle Behandlung für Bildfelder
                if (is_numeric($value) && wp_attachment_is_image($value)) {
                    $image_url = wp_get_attachment_image_url($value, 'full');
                    if ($image_url) {
                        $record['meta_image_' . $key] = $image_url;
                    }
                }
            }
        }
        
        // JetEngine-Meta-Felder verarbeiten, falls verfügbar
        if (function_exists('jet_engine') && method_exists(jet_engine(), 'meta_boxes')) {
            $jet_meta_fields = JetEngine_SmartSlider_Helper::get_jet_meta_fields($post->post_type);
            
            if (!empty($jet_meta_fields)) {
                foreach ($jet_meta_fields as $field_name => $field) {
                    // Überspringen, falls bereits verarbeitet
                    if (isset($record['meta_' . $field_name . '_formatted'])) {
                        continue;
                    }
                    
                    // Feldwert holen
                    $value = get_post_meta($post->ID, $field_name, true);
                    
                    if (empty($value)) {
                        continue;
                    }
                    
                    // Wert basierend auf Feldtyp verarbeiten
                    $processed = JetEngine_SmartSlider_Helper::process_jet_field_value(
                        $value, 
                        $field['type'], 
                        $field_name
                    );
                    
                    // Formatierten Wert hinzufügen
                    $record['meta_' . $field_name . '_formatted'] = $processed['formatted'];
                    
                    // Zusätzliche Daten hinzufügen
                    foreach ($processed['additional'] as $additional_key => $additional_value) {
                        $record['meta_' . $field_name . '_' . $additional_key] = $additional_value;
                    }
                }
            }
        }
    }
    
    /**
     * Fügt Bilddaten zum Datensatz hinzu
     * 
     * @param array $record Datensatz (per Referenz)
     * @param mixed $item Item-Daten
     * @param string $query_type Query-Typ
     */
    private function addImageData(&$record, $item, $query_type) {
        $image_source = $this->data->get('image_source', 'featured');
        
        // Standard-Bildwerte
        $record['image'] = '';
        $record['thumbnail'] = '';
        $record['image_alt'] = '';
        $record['image_title'] = '';
        
        // Je nach Item-Typ und Bildquelle unterschiedlich verarbeiten
        switch ($query_type) {
            case 'posts':
                $this->addPostImageData($record, $item, $image_source);
                break;
                
            case 'terms':
                $this->addTermImageData($record, $item, $image_source);
                break;
                
            case 'users':
                $this->addUserImageData($record, $item, $image_source);
                break;
                
            case 'custom-content-type':
                $this->addCCTImageData($record, $item, $image_source);
                break;
                
            default:
                // Generische Bildverarbeitung
                $this->addGenericImageData($record, $item, $image_source);
                break;
        }
    }
    
    /**
     * Fügt Bilddaten für Posts hinzu
     * 
     * @param array $record Datensatz (per Referenz)
     * @param WP_Post $post Post-Objekt
     * @param string $image_source Bildquelle
     */
    private function addPostImageData(&$record, $post, $image_source) {
        switch ($image_source) {
            case 'featured':
                // Beitragsbild holen
                $image_id = get_post_thumbnail_id($post->ID);
                if ($image_id) {
                    $this->addImageDataFromID($record, $image_id);
                }
                break;
                
            case 'first':
                // Erstes Bild aus dem Inhalt holen
                $first_img = JetEngine_SmartSlider_Helper::get_first_image_from_content($post->post_content);
                if (!empty($first_img)) {
                    $record['image'] = $first_img;
                    $record['thumbnail'] = $first_img;
                }
                break;
                
            case 'meta':
                // Bild aus Meta-Feld holen
                $meta_field = $this->data->get('image_meta', '');
                if (!empty($meta_field)) {
                    $meta_value = get_post_meta($post->ID, $meta_field, true);
                    if (!empty($meta_value)) {
                        // Prüfen, ob es eine Bild-ID ist
                        if (is_numeric($meta_value) && wp_attachment_is_image($meta_value)) {
                            $this->addImageDataFromID($record, $meta_value);
                        } 
                        // Prüfen, ob es eine URL ist
                        elseif (filter_var($meta_value, FILTER_VALIDATE_URL)) {
                            $record['image'] = $meta_value;
                            $record['thumbnail'] = $meta_value;
                        }
                    }
                }
                break;
                
            case 'jet_gallery':
                // JetEngine Galerie-Feld holen
                $gallery_field = $this->data->get('image_meta', '');
                if (!empty($gallery_field)) {
                    $gallery_images = get_post_meta($post->ID, $gallery_field, true);
                    if (!empty($gallery_images) && is_array($gallery_images)) {
                        // Erstes Bild aus der Galerie verwenden
                        $first_image = reset($gallery_images);
                        if (is_numeric($first_image) && wp_attachment_is_image($first_image)) {
                            $this->addImageDataFromID($record, $first_image);
                        }
                        
                        // Alle Galerie-Bilder als separate Einträge hinzufügen
                        $i = 2;
                        foreach ($gallery_images as $image_id) {
                            if ($i === 2) { // Erstes Bild überspringen, da es bereits hinzugefügt wurde
                                $i++;
                                continue;
                            }
                            
                            if (is_numeric($image_id) && wp_attachment_is_image($image_id)) {
                                $image_url = wp_get_attachment_image_url($image_id, 'full');
                                $record['image_' . $i] = $image_url;
                                $record['thumbnail_' . $i] = $image_url;
                                $record['image_alt_' . $i] = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                                
                                // Bild-Metadaten holen
                                $attachment = get_post($image_id);
                                if ($attachment) {
                                    $record['image_title_' . $i] = $attachment->post_title;
                                }
                                
                                $i++;
                            }
                        }
                    }
                }
                break;
                
            case 'content':
                // Alle Bilder aus dem Inhalt holen
                $content_images = JetEngine_SmartSlider_Helper::get_all_images_from_content($post->post_content);
                if (!empty($content_images)) {
                    // Erstes Bild für Hauptbild verwenden
                    $record['image'] = $content_images[0];
                    $record['thumbnail'] = $content_images[0];
                    
                    // Zusätzliche Bilder hinzufügen
                    for ($i = 1; $i < count($content_images); $i++) {
                        $idx = $i + 1;
                        $record['image_' . $idx] = $content_images[$i];
                        $record['thumbnail_' . $idx] = $content_images[$i];
                    }
                }
                break;
        }
    }
    
    /**
     * Fügt Bilddaten für Terms hinzu
     * 
     * @param array $record Datensatz (per Referenz)
     * @param WP_Term $term Term-Objekt
     * @param string $image_source Bildquelle
     */
    private function addTermImageData(&$record, $term, $image_source) {
        switch ($image_source) {
            case 'meta':
                // Bild aus Meta-Feld holen
                $meta_field = $this->data->get('image_meta', '');
                if (!empty($meta_field) && function_exists('get_term_meta')) {
                    $meta_value = get_term_meta($term->term_id, $meta_field, true);
                    if (!empty($meta_value)) {
                        // Prüfen, ob es eine Bild-ID ist
                        if (is_numeric($meta_value) && wp_attachment_is_image($meta_value)) {
                            $this->addImageDataFromID($record, $meta_value);
                        } 
                        // Prüfen, ob es eine URL ist
                        elseif (filter_var($meta_value, FILTER_VALIDATE_URL)) {
                            $record['image'] = $meta_value;
                            $record['thumbnail'] = $meta_value;
                        }
                    }
                }
                break;
                
            case 'jet_gallery':
                // JetEngine Galerie-Feld holen
                $gallery_field = $this->data->get('image_meta', '');
                if (!empty($gallery_field) && function_exists('get_term_meta')) {
                    $gallery_images = get_term_meta($term->term_id, $gallery_field, true);
                    if (!empty($gallery_images) && is_array($gallery_images)) {
                        // Erstes Bild aus der Galerie verwenden
                        $first_image = reset($gallery_images);
                        if (is_numeric($first_image) && wp_attachment_is_image($first_image)) {
                            $this->addImageDataFromID($record, $first_image);
                        }
                        
                        // Alle Galerie-Bilder als separate Einträge hinzufügen
                        $i = 2;
                        foreach ($gallery_images as $image_id) {
                            if ($i === 2) { // Erstes Bild überspringen, da es bereits hinzugefügt wurde
                                $i++;
                                continue;
                            }
                            
                            if (is_numeric($image_id) && wp_attachment_is_image($image_id)) {
                                $image_url = wp_get_attachment_image_url($image_id, 'full');
                                $record['image_' . $i] = $image_url;
                                $record['thumbnail_' . $i] = $image_url;
                                $record['image_alt_' . $i] = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                                
                                // Bild-Metadaten holen
                                $attachment = get_post($image_id);
                                if ($attachment) {
                                    $record['image_title_' . $i] = $attachment->post_title;
                                }
                                
                                $i++;
                            }
                        }
                    }
                }
                break;
        }
    }
    
    /**
     * Fügt Bilddaten für Benutzer hinzu
     * 
     * @param array $record Datensatz (per Referenz)
     * @param WP_User $user User-Objekt
     * @param string $image_source Bildquelle
     */
    private function addUserImageData(&$record, $user, $image_source) {
        switch ($image_source) {
            case 'avatar':
                // Avatar-URL holen
                $avatar_url = get_avatar_url($user->ID);
                if ($avatar_url) {
                    $record['image'] = $avatar_url;
                    $record['thumbnail'] = $avatar_url;
                }
                break;
                
            case 'meta':
                // Bild aus Meta-Feld holen
                $meta_field = $this->data->get('image_meta', '');
                if (!empty($meta_field)) {
                    $meta_value = get_user_meta($user->ID, $meta_field, true);
                    if (!empty($meta_value)) {
                        // Prüfen, ob es eine Bild-ID ist
                        if (is_numeric($meta_value) && wp_attachment_is_image($meta_value)) {
                            $this->addImageDataFromID($record, $meta_value);
                        } 
                        // Prüfen, ob es eine URL ist
                        elseif (filter_var($meta_value, FILTER_VALIDATE_URL)) {
                            $record['image'] = $meta_value;
                            $record['thumbnail'] = $meta_value;
                        }
                    }
                }
                break;
                
            case 'jet_gallery':
                // JetEngine Galerie-Feld holen
                $gallery_field = $this->data->get('image_meta', '');
                if (!empty($gallery_field)) {
                    $gallery_images = get_user_meta($user->ID, $gallery_field, true);
                    if (!empty($gallery_images) && is_array($gallery_images)) {
                        // Erstes Bild aus der Galerie verwenden
                        $first_image = reset($gallery_images);
                        if (is_numeric($first_image) && wp_attachment_is_image($first_image)) {
                            $this->addImageDataFromID($record, $first_image);
                        }
                    }
                }
                break;
        }
    }
    
    /**
     * Fügt Bilddaten für Custom Content Type Items hinzu
     * 
     * @param array $record Datensatz (per Referenz)
     * @param array $item CCT-Item
     * @param string $image_source Bildquelle
     */
    private function addCCTImageData(&$record, $item, $image_source) {
        switch ($image_source) {
            case 'meta':
                // Bild aus CCT-Feld holen
                $meta_field = $this->data->get('image_meta', '');
                if (!empty($meta_field) && isset($item[$meta_field])) {
                    $meta_value = $item[$meta_field];
                    if (!empty($meta_value)) {
                        // Prüfen, ob es eine Bild-ID ist
                        if (is_numeric($meta_value) && wp_attachment_is_image($meta_value)) {
                            $this->addImageDataFromID($record, $meta_value);
                        } 
                        // Prüfen, ob es eine URL ist
                        elseif (filter_var($meta_value, FILTER_VALIDATE_URL)) {
                            $record['image'] = $meta_value;
                            $record['thumbnail'] = $meta_value;
                        }
                    }
                }
                break;
                
            case 'jet_gallery':
                // JetEngine Galerie-Feld holen
                $gallery_field = $this->data->get('image_meta', '');
                if (!empty($gallery_field) && isset($item[$gallery_field])) {
                    $gallery_images = $item[$gallery_field];
                    if (!empty($gallery_images) && is_array($gallery_images)) {
                        // Erstes Bild aus der Galerie verwenden
                        $first_image = reset($gallery_images);
                        if (is_numeric($first_image) && wp_attachment_is_image($first_image)) {
                            $this->addImageDataFromID($record, $first_image);
                        }
                    }
                }
                break;
        }
    }
    
    /**
     * Fügt Bilddaten für generische Items hinzu
     * 
     * @param array $record Datensatz (per Referenz)
     * @param mixed $item Item-Daten
     * @param string $image_source Bildquelle
     */
    private function addGenericImageData(&$record, $item, $image_source) {
        // Versuchen, eine Bild-URL oder ID aus dem Item zu extrahieren
        if (is_object($item) || is_array($item)) {
            $image_fields = ['image', 'thumbnail', 'img', 'picture', 'photo', 'featured_image', 'thumbnail_url', 'image_url'];
            
            foreach ($image_fields as $field) {
                if (is_object($item) && isset($item->$field)) {
                    $image_value = $item->$field;
                } elseif (is_array($item) && isset($item[$field])) {
                    $image_value = $item[$field];
                } else {
                    continue;
                }
                
                if (!empty($image_value)) {
                    // Prüfen, ob es eine Bild-ID ist
                    if (is_numeric($image_value) && wp_attachment_is_image($image_value)) {
                        $this->addImageDataFromID($record, $image_value);
                        break;
                    } 
                    // Prüfen, ob es eine URL ist
                    elseif (filter_var($image_value, FILTER_VALIDATE_URL)) {
                        $record['image'] = $image_value;
                        $record['thumbnail'] = $image_value;
                        break;
                    }
                }
            }
        }
    }
    
    /**
     * Fügt Bilddaten aus einer Attachment-ID hinzu
     * 
     * @param array $record Datensatz (per Referenz)
     * @param int $image_id Attachment-ID
     */
    private function addImageDataFromID(&$record, $image_id) {
        $image_url = wp_get_attachment_image_url($image_id, 'full');
        $image_data = wp_get_attachment_metadata($image_id);
        
        $record['image'] = $image_url;
        $record['thumbnail'] = $image_url;
        $record['image_alt'] = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        
        // Bildtitel holen
        $attachment = get_post($image_id);
        if ($attachment) {
            $record['image_title'] = $attachment->post_title;
        }
        
        // Bilddimensionen hinzufügen
        if (!empty($image_data) && isset($image_data['width']) && isset($image_data['height'])) {
            $record['image_width'] = $image_data['width'];
            $record['image_height'] = $image_data['height'];
        }
    }
}
<?php
/**
 * AJAX-Handler für Meta-Feld-Dropdowns
 * 
 * Fügen Sie diesen Code in Ihre jetengine-smartslider-generator.php Datei ein
 */

/**
 * AJAX-Handler zum Abrufen der Meta-Felder für einen Post-Typ
 */
add_action('wp_ajax_jetengine_smartslider_get_meta_fields', 'jetengine_smartslider_get_meta_fields_callback');
/**
 * Verbesserter AJAX-Handler für Meta-Felder
 * Diese Funktion sollte den existierenden AJAX-Handler ersetzen
 */
function jetengine_smartslider_get_meta_fields_callback() {
    // Nonce prüfen
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jetengine_smartslider_nonce')) {
        wp_send_json_error(['message' => 'Sicherheitsüberprüfung fehlgeschlagen.']);
    }
    
    // Post-Typ prüfen
    $post_type = '';
    
    if (isset($_POST['post_type'])) {
        $post_type = sanitize_text_field($_POST['post_type']);
    } elseif (isset($_POST['generator_type'])) {
        // Generator-Typ parsen
        $generator_type = sanitize_text_field($_POST['generator_type']);
        if (preg_match('/^post_type_([a-zA-Z0-9_-]+)$/', $generator_type, $matches)) {
            $post_type = $matches[1];
        } elseif (preg_match('/^([a-zA-Z0-9_-]+)$/', $generator_type, $matches)) {
            // Fallback: Versuche den Generator-Typ direkt als Post-Typ zu verwenden
            $post_type = $matches[1];
        }
    }
    
    if (empty($post_type)) {
        // Versuche, den Post-Typ aus der Referer-URL zu extrahieren
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            if (preg_match('/type=([a-zA-Z0-9_-]+)/', $referer, $matches)) {
                $post_type = $matches[1];
            }
        }
        
        // Wenn immer noch kein Post-Typ, verwende Standard
        if (empty($post_type)) {
            $post_type = 'post';
        }
    }
    
    // Debug-Informationen
    error_log('JetEngine SmartSlider: Meta-Felder für Post-Typ ' . $post_type . ' abgerufen');
    
    // Meta-Felder abrufen
    $fields = jetengine_smartslider_get_all_meta_fields($post_type);
    
    // Erfolg zurückgeben
    wp_send_json_success(['fields' => $fields]);
}

/**
 * Holt alle Meta-Felder für einen Post-Typ
 * Verbesserte Version mit Cache und besserer Erkennung
 * 
 * @param string $post_type Post-Typ-Name
 * @return array Meta-Felder
 */
function jetengine_smartslider_get_all_meta_fields($post_type) {
    global $wpdb;
    
    // Cache-Schlüssel für diesen Post-Typ
    $cache_key = 'jetengine_smartslider_meta_fields_' . $post_type;
    
    // Versuche, aus dem Cache zu laden
    $cached_fields = get_transient($cache_key);
    if ($cached_fields !== false) {
        return $cached_fields;
    }
    
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
        // Zeige JetEngine-Felder oder Felder, die nicht mit Unterstrich beginnen
        $key = $meta_key->meta_key;
        
        if (substr($key, 0, 1) !== '_' || substr($key, 0, 5) === '_jet_') {
            $fields[] = [
                'name' => $key,
                'type' => guess_field_type($key, $post_type),
                'title' => formatize_meta_key($key)
            ];
        }
    }
    
    // JetEngine Meta-Felder hinzufügen, falls verfügbar
    if (function_exists('jet_engine')) {
        $jet_fields = get_jet_meta_fields($post_type);
        if (!empty($jet_fields)) {
            foreach ($jet_fields as $field) {
                // Prüfen, ob das Feld bereits existiert
                $exists = false;
                foreach ($fields as $existing_field) {
                    if ($existing_field['name'] === $field['name']) {
                        $exists = true;
                        break;
                    }
                }
                
                // Nur hinzufügen, wenn es noch nicht existiert
                if (!$exists) {
                    $fields[] = $field;
                }
            }
        }
    }
    
    // Mit ACF integrieren, falls verfügbar
    if (function_exists('acf_get_field_groups')) {
        $acf_fields = get_acf_fields($post_type);
        if (!empty($acf_fields)) {
            foreach ($acf_fields as $field) {
                // Prüfen, ob das Feld bereits existiert
                $exists = false;
                foreach ($fields as $existing_field) {
                    if ($existing_field['name'] === $field['name']) {
                        $exists = true;
                        break;
                    }
                }
                
                // Nur hinzufügen, wenn es noch nicht existiert
                if (!$exists) {
                    $fields[] = $field;
                }
            }
        }
    }
    
    // Cache für 1 Stunde speichern
    set_transient($cache_key, $fields, HOUR_IN_SECONDS);
    
    return $fields;
}

/**
 * Formatiert einen Meta-Key in einen lesbaren Titel
 * 
 * @param string $key Meta-Key
 * @return string Formatierter Titel
 */
function formatize_meta_key($key) {
    // Entferne führende Unterstriche
    $key = ltrim($key, '_');
    
    // Ersetze Unterstriche durch Leerzeichen
    $key = str_replace('_', ' ', $key);
    
    // Ersetze Bindestriche durch Leerzeichen
    $key = str_replace('-', ' ', $key);
    
    // Ersten Buchstaben groß schreiben
    $key = ucfirst($key);
    
    return $key;
}

/**
 * Versucht, den Feldtyp anhand des Namens und der Werte zu erraten
 * 
 * @param string $key Feldschlüssel
 * @param string $post_type Post-Typ
 * @return string Vermuteter Feldtyp
 */
function guess_field_type($key, $post_type) {
    // Standard-Typ
    $type = 'text';
    
    // Prüfen anhand des Namens
    $key_lower = strtolower($key);
    
    $image_keywords = ['image', 'img', 'picture', 'photo', 'thumbnail', 'gallery', 'logo', 'icon', 'avatar'];
    $date_keywords = ['date', 'time', 'datetime', 'day', 'month', 'year', 'schedule'];
    $number_keywords = ['price', 'cost', 'fee', 'rating', 'score', 'count', 'total', 'sum', 'quantity', 'number', 'amount'];
    
    foreach ($image_keywords as $keyword) {
        if (strpos($key_lower, $keyword) !== false) {
            return 'image';
        }
    }
    
    foreach ($date_keywords as $keyword) {
        if (strpos($key_lower, $keyword) !== false) {
            return 'date';
        }
    }
    
    foreach ($number_keywords as $keyword) {
        if (strpos($key_lower, $keyword) !== false) {
            return 'number';
        }
    }
    
    return $type;
}

/**
 * Holt JetEngine Meta-Felder für einen Post-Typ
 * 
 * @param string $post_type Post-Typ-Name
 * @return array JetEngine Meta-Felder
 */
function get_jet_meta_fields($post_type) {
    $fields = [];
    
    // Prüfen, ob JetEngine aktiv ist
    if (!function_exists('jet_engine')) {
        return $fields;
    }
    
    // JetEngine Meta-Boxen abrufen, wenn verfügbar
    if (isset(jet_engine()->meta_boxes) && method_exists(jet_engine()->meta_boxes, 'get_meta_boxes')) {
        $meta_boxes = jet_engine()->meta_boxes->get_meta_boxes();
        
        if (!empty($meta_boxes)) {
            foreach ($meta_boxes as $meta_box) {
                // Nur Meta-Boxen für Post-Typen berücksichtigen
                if (!isset($meta_box['args']['object_type']) || $meta_box['args']['object_type'] !== 'post') {
                    continue;
                }
                
                // Prüfen, ob Meta-Box für diesen Post-Typ gilt
                if (!isset($meta_box['args']['allowed_post_type']) || !in_array($post_type, $meta_box['args']['allowed_post_type'])) {
                    continue;
                }
                
                // Meta-Felder verarbeiten
                if (isset($meta_box['meta_fields']) && !empty($meta_box['meta_fields'])) {
                    foreach ($meta_box['meta_fields'] as $field) {
                        if (!isset($field['name'])) {
                            continue;
                        }
                        
                        $field_name = $field['name'];
                        $field_type = isset($field['type']) ? $field['type'] : 'text';
                        $field_title = isset($field['title']) ? $field['title'] : formatize_meta_key($field_name);
                        
                        $fields[] = [
                            'name' => $field_name,
                            'type' => $field_type,
                            'title' => $field_title
                        ];
                    }
                }
            }
        }
    }
    
    return $fields;
}

/**
 * Holt ACF-Felder für einen Post-Typ
 * 
 * @param string $post_type Post-Typ-Name
 * @return array ACF-Felder
 */
function get_acf_fields($post_type) {
    $fields = [];
    
    // Prüfen, ob ACF aktiviert ist
    if (!function_exists('acf_get_field_groups')) {
        return $fields;
    }
    
    // ACF-Feldgruppen abrufen
    $field_groups = acf_get_field_groups(['post_type' => $post_type]);
    
    if (empty($field_groups)) {
        return $fields;
    }
    
    // Durch Feldgruppen iterieren
    foreach ($field_groups as $field_group) {
        $group_fields = acf_get_fields($field_group);
        
        if (!empty($group_fields)) {
            foreach ($group_fields as $field) {
                if (!isset($field['name'])) {
                    continue;
                }
                
                $fields[] = [
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'title' => $field['label']
                ];
            }
        }
    }
    
    return $fields;
}

/** --------------------------------------------- vorher ----------------------------------
 * Holt alle Meta-Felder für einen Post-Typ
 * 
 * @param string $post_type Post-Typ-Name
 * @return array Meta-Felder
 */


/**
 * Holt JetEngine Meta-Felder für einen Post-Typ
 * 
 * @param string $post_type Post-Typ-Name
 * @return array JetEngine Meta-Felder
 */
function jetengine_smartslider_get_jet_meta_fields($post_type) {
    $fields = [];
    
    // Prüfen, ob JetEngine aktiv ist
    if (!function_exists('jet_engine')) {
        return $fields;
    }
    
    // JetEngine Meta-Boxen abrufen, wenn verfügbar
    if (isset(jet_engine()->meta_boxes) && method_exists(jet_engine()->meta_boxes, 'get_meta_boxes')) {
        $meta_boxes = jet_engine()->meta_boxes->get_meta_boxes();
        
        if (!empty($meta_boxes)) {
            foreach ($meta_boxes as $meta_box) {
                // Nur Meta-Boxen für Post-Typen berücksichtigen
                if (!isset($meta_box['args']['object_type']) || $meta_box['args']['object_type'] !== 'post') {
                    continue;
                }
                
                // Prüfen, ob Meta-Box für diesen Post-Typ gilt
                if (!isset($meta_box['args']['allowed_post_type']) || !in_array($post_type, $meta_box['args']['allowed_post_type'])) {
                    continue;
                }
                
                // Meta-Felder verarbeiten
                if (isset($meta_box['meta_fields']) && !empty($meta_box['meta_fields'])) {
                    foreach ($meta_box['meta_fields'] as $field) {
                        if (!isset($field['name'])) {
                            continue;
                        }
                        
                        $fields[] = [
                            'name' => $field['name'],
                            'type' => isset($field['type']) ? $field['type'] : 'text',
                            'title' => isset($field['title']) ? $field['title'] : $field['name']
                        ];
                    }
                }
            }
        }
    }
    
    return $fields;
}

/**
 * Lokalisiert die Admin-Skripte für den JetEngine SmartSlider Generator
 * 
 * Fügen Sie diesen Code in Ihre Funktion zum Einbinden der Skripte ein (enqueue_admin_assets)
 */
function jetengine_smartslider_add_localized_data() {
    wp_localize_script('jetengine-smartslider-admin', 'jetengineSmartSliderData', [
        'nonce' => wp_create_nonce('jetengine_smartslider_nonce'),
        'select_field' => __('Feld auswählen', 'jetengine-smartslider'),
        'select_meta_key' => __('Meta-Schlüssel auswählen', 'jetengine-smartslider'),
        'select_taxonomies' => __('Taxonomien auswählen', 'jetengine-smartslider'),
        'meta_field_tip' => __('Geben Sie den Namen eines JetEngine-Meta-Felds ein, nach dem gefiltert werden soll.', 'jetengine-smartslider'),
        'image_field_tip' => __('Wählen Sie ein Feld, das eine Bild-ID oder URL enthält.', 'jetengine-smartslider')
    ]);
}

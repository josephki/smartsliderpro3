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
        if (preg_match('/post_type_([a-zA-Z0-9_-]+)/', $generator_type, $matches)) {
            $post_type = $matches[1];
        }
    }
    
    if (empty($post_type)) {
        wp_send_json_error(['message' => 'Post-Typ nicht angegeben.']);
    }
    
    // Meta-Felder abrufen
    $fields = jetengine_smartslider_get_all_meta_fields($post_type);
    
    // Erfolg zurückgeben
    wp_send_json_success(['fields' => $fields]);
}

/**
 * Holt alle Meta-Felder für einen Post-Typ
 * 
 * @param string $post_type Post-Typ-Name
 * @return array Meta-Felder
 */
function jetengine_smartslider_get_all_meta_fields($post_type) {
    global $wpdb;
    
    // Meta-Felder aus der Datenbank abrufen
    $meta_keys = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT pm.meta_key 
         FROM {$wpdb->postmeta} pm
         JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE p.post_type = %s
         ORDER BY pm.meta_key",
        $post_type
    ));
    
    $fields = [];
    
    // Normale Meta-Felder verarbeiten
    foreach ($meta_keys as $meta_key) {
        // Interne Felder überspringen (optional)
        $key = $meta_key->meta_key;
        if (substr($key, 0, 1) !== '_' || substr($key, 0, 5) === '_jet_') {
            $fields[] = [
                'name' => $key,
                'type' => 'text', // Standardtyp
                'title' => $key
            ];
        }
    }
    
    // JetEngine Meta-Felder hinzufügen, falls verfügbar
    $jet_fields = jetengine_smartslider_get_jet_meta_fields($post_type);
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
    
    return $fields;
}

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

<?php
/**
 * JetEngine SmartSlider Field Enhancer - Hilfsfunktionen
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) exit;

/**
 * Holt JetEngine-Meta-Felder für einen Post-Typ
 * 
 * @param string $post_type Post-Typ
 * @return array Meta-Felder
 */
function jetengine_smartslider_get_jet_fields($post_type) {
    $fields = [];
    
    // Prüfen, ob JetEngine aktiviert ist
    if (!function_exists('jet_engine')) {
        return $fields;
    }
    
    // JetEngine Meta-Box-Modul prüfen
    if (!isset(jet_engine()->meta_boxes) || !method_exists(jet_engine()->meta_boxes, 'get_meta_boxes')) {
        return $fields;
    }
    
    // Alle Meta-Boxen abrufen
    $meta_boxes = jet_engine()->meta_boxes->get_meta_boxes();
    
    if (empty($meta_boxes)) {
        return $fields;
    }
    
    // Durch Meta-Boxen iterieren
    foreach ($meta_boxes as $meta_box) {
        // Prüfen, ob Meta-Box für Posts ist
        if (!isset($meta_box['args']['object_type']) || $meta_box['args']['object_type'] !== 'post') {
            continue;
        }
        
        // Prüfen, ob Meta-Box für diesen Post-Typ ist
        if (!isset($meta_box['args']['allowed_post_type']) || !in_array($post_type, $meta_box['args']['allowed_post_type'])) {
            continue;
        }
        
        // Meta-Felder verarbeiten
        if (isset($meta_box['meta_fields']) && !empty($meta_box['meta_fields'])) {
            foreach ($meta_box['meta_fields'] as $field) {
                // Überspringen, wenn kein Name vorhanden
                if (!isset($field['name'])) {
                    continue;
                }
                
                $field_name = $field['name'];
                $field_type = isset($field['type']) ? $field['type'] : 'text';
                $field_title = isset($field['title']) ? $field['title'] : $field_name;
                
                // Feld hinzufügen
                $fields[$field_name] = [
                    'name' => $field_name,
                    'type' => $field_type,
                    'title' => $field_title
                ];
            }
        }
    }
    
    return $fields;
}

/**
 * Holt alle Post-Meta-Felder für einen bestimmten Post-Typ
 * 
 * @param string $post_type Post-Typ
 * @return array Meta-Felder
 */
function jetengine_smartslider_get_post_meta_fields($post_type) {
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
    
    // Meta-Felder verarbeiten
    foreach ($meta_keys as $meta_key) {
        $key = $meta_key->meta_key;
        
        // Systeminterne Felder filtern (optional)
        if (substr($key, 0, 1) !== '_' || substr($key, 0, 5) === '_jet_') {
            $fields[$key] = [
                'name' => $key,
                'type' => 'text', // Standardtyp
                'title' => $key
            ];
        }
    }
    
    return $fields;
}

/**
 * Holt alle ACF-Felder für einen Post-Typ
 * 
 * @param string $post_type Post-Typ
 * @return array ACF-Felder
 */
function jetengine_smartslider_get_acf_fields($post_type) {
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
                $fields[$field['name']] = [
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'title' => $field['label']
                ];
            }
        }
    }
    
    return $fields;
}

/**
 * Gruppiert Meta-Felder nach Typ
 * 
 * @param array $fields Meta-Felder
 * @return array Gruppierte Meta-Felder
 */
function jetengine_smartslider_group_meta_fields($fields) {
    $grouped = [
        'text' => [],
        'number' => [],
        'date' => [],
        'select' => [],
        'checkbox' => [],
        'radio' => [],
        'media' => [],
        'gallery' => [],
        'other' => []
    ];
    
    // Felder nach Typ gruppieren
    foreach ($fields as $field) {
        $type = isset($field['type']) ? $field['type'] : 'text';
        
        switch ($type) {
            case 'text':
            case 'textarea':
            case 'wysiwyg':
                $grouped['text'][] = $field;
                break;
                
            case 'number':
            case 'range':
                $grouped['number'][] = $field;
                break;
                
            case 'date':
            case 'datetime':
            case 'time':
                $grouped['date'][] = $field;
                break;
                
            case 'select':
                $grouped['select'][] = $field;
                break;
                
            case 'checkbox':
                $grouped['checkbox'][] = $field;
                break;
                
            case 'radio':
                $grouped['radio'][] = $field;
                break;
                
            case 'media':
            case 'image':
            case 'file':
                $grouped['media'][] = $field;
                break;
                
            case 'gallery':
                $grouped['gallery'][] = $field;
                break;
                
            default:
                $grouped['other'][] = $field;
                break;
        }
    }
    
    return $grouped;
}

/**
 * Bestimmt den Post-Typ aus einem Smart Slider Generator-Typ
 * 
 * @param string $generator_type Generator-Typ
 * @return string|null Post-Typ oder null
 */
function jetengine_smartslider_get_post_type_from_generator($generator_type) {
    // Format: post_type_NAME oder cct_NAME
    if (preg_match('/^post_type_([a-zA-Z0-9_-]+)$/', $generator_type, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Überprüft, ob ein Feld ein Bildfeld ist
 * 
 * @param array $field Felddetails
 * @return bool True, wenn es ein Bildfeld ist
 */
function jetengine_smartslider_is_image_field($field) {
    if (!isset($field['type'])) {
        return false;
    }
    
    $image_field_types = [
        'image',
        'media',
        'gallery',
        'file'
    ];
    
    return in_array($field['type'], $image_field_types);
}

/**
 * Gibt alle Meta-Felder für einen Post-Typ zurück (JetEngine, ACF und Custom)
 * 
 * @param string $post_type Post-Typ
 * @return array Meta-Felder
 */
function jetengine_smartslider_get_all_fields_for_post_type($post_type) {
    // JetEngine-Felder abrufen
    $jet_fields = jetengine_smartslider_get_jet_fields($post_type);
    
    // ACF-Felder abrufen
    $acf_fields = jetengine_smartslider_get_acf_fields($post_type);
    
    // Post-Meta-Felder abrufen
    $meta_fields = jetengine_smartslider_get_post_meta_fields($post_type);
    
    // Felder zusammenführen (JetEngine-Felder haben Priorität)
    $all_fields = array_merge($meta_fields, $acf_fields, $jet_fields);
    
    return $all_fields;
}

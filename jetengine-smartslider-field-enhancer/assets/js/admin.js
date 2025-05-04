/**
 * JetEngine SmartSlider Field Enhancer - Admin JavaScript
 * 
 * Erweitert die Smart Slider UI mit Meta-Feld-Dropdown-Menüs
 */
(function($) {
    'use strict';

    // Initialisieren, sobald das DOM geladen ist
    $(document).ready(function() {
        // Initialisiere den Field Enhancer
        initFieldEnhancer();
    });

    /**
     * Hauptfunktion zur Initialisierung des Field Enhancers
     */
    function initFieldEnhancer() {
        // Nur auf den relevanten Admin-Seiten ausführen
        if (!isSmartSliderPage()) {
            return;
        }

        console.log('JetEngine SmartSlider Field Enhancer geladen');
        
        // Event-Listener hinzufügen
        attachEventListeners();
    }

    /**
     * Überprüft, ob wir uns auf einer Smart Slider-Seite befinden
     * @return {boolean} True, wenn auf einer Smart Slider-Seite
     */
    function isSmartSliderPage() {
        // Prüfe URL-Parameter für Smart Slider
        return window.location.href.indexOf('smart-slider') > -1 || 
               $('body').hasClass('toplevel_page_smart-slider3') ||
               $('body').hasClass('smart-slider3_page_smart-slider3');
    }

    /**
     * Event-Listener hinzufügen
     */
    function attachEventListeners() {
        // Smart Slider Generator-Auswahl-Event abfangen
        $(document).on('SmartSlider3GeneratorSelected', function(e, generatorType) {
            if (generatorType && generatorType.indexOf('jetengine') === 0) {
                console.log('JetEngine Generator ausgewählt:', generatorType);
                
                // Generator-Typ extrahieren
                const postTypeMatch = generatorType.match(/post_type_([a-zA-Z0-9_-]+)/);
                if (postTypeMatch && postTypeMatch[1]) {
                    const postType = postTypeMatch[1];
                    console.log('Post-Typ erkannt:', postType);
                    
                    // Meta-Felder laden und Dropdown-Menüs erstellen
                    loadMetaFieldsAndCreateDropdowns(postType);
                }
            }
        });
        
        // Auch bei Generator-Einstellungsänderungen prüfen
        $(document).on('SmartSlider3GeneratorSettingsChanged', function(e, settings) {
            // Verzögerung hinzufügen, um sicherzustellen, dass die UI aktualisiert wurde
            setTimeout(checkAndEnhanceFields, 500);
        });
    }
    
    /**
     * Meta-Felder laden und Dropdown-Menüs erstellen
     * 
     * @param {string} postType Post-Typ
     */
    function loadMetaFieldsAndCreateDropdowns(postType) {
        // Meta-Felder über AJAX laden
        $.ajax({
            url: JetEngineSmartSliderEnhancer.ajaxurl,
            type: 'POST',
            data: {
                action: 'jetengine_smartslider_get_all_meta_fields',
                nonce: JetEngineSmartSliderEnhancer.nonce,
                post_type: postType
            },
            success: function(response) {
                if (response.success && response.data.meta_fields) {
                    // Meta-Felder in globaler Variable speichern
                    window.jetEngineMetaFields = response.data.meta_fields;
                    
                    // Dropdown-Menüs erstellen
                    enhanceMetaFields();
                }
            },
            error: function() {
                console.error('Fehler beim Laden der Meta-Felder');
            }
        });
    }
    
    /**
     * Prüft und verbessert die Felder, wenn die UI aktualisiert wird
     */
    function checkAndEnhanceFields() {
        if (window.jetEngineMetaFields) {
            enhanceMetaFields();
        }
    }
    
    /**
     * Verbessert die Meta-Felder mit Dropdown-Menüs
     */
    function enhanceMetaFields() {
        // Textfelder in Dropdown-Menüs umwandeln
        createMetaFieldDropdown('generatormeta_filter_name', 'filter', 'Meta-Feld für Filter');
        createMetaFieldDropdown('generatormeta_image_meta', 'image', 'Meta-Feld für Bild');
        createMetaFieldDropdown('generatormeta_order_key', 'order', 'Meta-Feld für Sortierung');
    }
    
    /**
     * Erstellt ein Dropdown-Menü für ein Meta-Feld
     * 
     * @param {string} fieldId ID des Textfelds
     * @param {string} fieldType Feldtyp (filter, image, order)
     * @param {string} label Beschriftung
     */
    function createMetaFieldDropdown(fieldId, fieldType, label) {
        // Das originale Textfeld suchen
        var $originalField = $('#' + fieldId);
        
        // Nur fortfahren, wenn das Feld existiert und noch kein Dropdown erstellt wurde
        if ($originalField.length && !$('#' + fieldId + '_select').length) {
            // Den aktuellen Wert speichern
            var currentValue = $originalField.val();
            
            // Container-Element erstellen
            var $container = $('<div class="jetengine-field-dropdown-container"></div>');
            
            // Select-Element erstellen
            var $select = $('<select id="' + fieldId + '_select" class="jetengine-field-dropdown"></select>');
            
            // Platzhalter-Option hinzufügen
            $select.append('<option value="">' + JetEngineSmartSliderEnhancer.strings.select_field + '</option>');
            
            // Optionsgruppen erstellen
            var $jetGroup = $('<optgroup label="JetEngine Felder"></optgroup>');
            var $otherGroup = $('<optgroup label="Andere Felder"></optgroup>');
            
            // JetEngine-Felder hinzufügen
            if (window.jetEngineMetaFields.jet_fields.length > 0) {
                $.each(window.jetEngineMetaFields.jet_fields, function(i, field) {
                    var $option = $('<option value="' + field.key + '">' + field.label + '</option>');
                    if (field.key === currentValue) {
                        $option.prop('selected', true);
                    }
                    $jetGroup.append($option);
                });
                $select.append($jetGroup);
            }
            
            // Andere Felder hinzufügen
            if (window.jetEngineMetaFields.other_fields.length > 0) {
                $.each(window.jetEngineMetaFields.other_fields, function(i, field) {
                    var $option = $('<option value="' + field.key + '">' + field.label + '</option>');
                    if (field.key === currentValue) {
                        $option.prop('selected', true);
                    }
                    $otherGroup.append($option);
                });
                $select.append($otherGroup);
            }
            
            // Dropdown nach dem originalen Feld einfügen
            $originalField.after($container);
            $container.append($select);
            
            // Stil für das originale Feld anpassen
            $originalField.css({
                'position': 'absolute',
                'left': '-9999px',
                'width': '1px',
                'height': '1px',
                'opacity': '0'
            });
            
            // Event-Handler für Änderungen am Dropdown
            $select.on('change', function() {
                // Den Wert des originalen Felds aktualisieren
                $originalField.val($(this).val());
                
                // Ein change-Event auf dem originalen Feld auslösen
                $originalField.trigger('change');
            });
            
            // Hinweistext hinzufügen
            if (fieldType === 'filter') {
                $container.after('<p class="jetengine-field-hint">Wählen Sie ein Meta-Feld für den Filter aus.</p>');
            } else if (fieldType === 'image') {
                $container.after('<p class="jetengine-field-hint">Wählen Sie ein Meta-Feld mit Bild-ID oder URL aus.</p>');
            } else if (fieldType === 'order') {
                $container.after('<p class="jetengine-field-hint">Wählen Sie ein Meta-Feld für die Sortierung aus.</p>');
            }
            
            console.log('Dropdown-Menü erstellt für', fieldId);
        } else {
            console.log('Feld nicht gefunden oder Dropdown bereits erstellt:', fieldId);
        }
    }
    
})(jQuery);

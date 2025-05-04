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
        console.log('Initialisiere Field Enhancer mit Dokumentbereit-Status:', document.readyState);
        console.log('DOM-Struktur für Smart Slider Fields:', $('[id^="generatormeta_"]').length, 'Felder gefunden');
        
        // Nur auf den relevanten Admin-Seiten ausführen
        if (!isSmartSliderPage()) {
            console.log('Keine Smart Slider Seite erkannt, breche Initialisierung ab');
            return;
        }

        console.log('JetEngine SmartSlider Field Enhancer geladen');
        
        // Event-Listener hinzufügen
        attachEventListeners();
        
        // DIREKTE META-FELD-VERBESSERUNG: Sofort Meta-Felder laden und Dropdowns erstellen
        var postType = getPostTypeFromUrl();
        if (postType) {
            console.log('Post-Typ aus URL erkannt:', postType);
            loadMetaFieldsAndCreateDropdowns(postType);
        } else {
            console.log('Kein Post-Typ in URL gefunden, versuche Generator-Typ zu ermitteln');
            var generatorType = getCurrentGeneratorType();
            if (generatorType && generatorType.indexOf('jetengine') === 0) {
                const postTypeMatch = generatorType.match(/post_type_([a-zA-Z0-9_-]+)/);
                if (postTypeMatch && postTypeMatch[1]) {
                    postType = postTypeMatch[1];
                    console.log('Post-Typ aus Generator-Typ erkannt:', postType);
                    loadMetaFieldsAndCreateDropdowns(postType);
                } else {
                    console.log('Verwende Fallback Post-Typ');
                    loadMetaFieldsAndCreateDropdowns('post'); // Fallback: Standard-Post-Typ verwenden
                }
            } else {
                console.log('Verwende Fallback Post-Typ');
                loadMetaFieldsAndCreateDropdowns('post'); // Fallback: Standard-Post-Typ verwenden
            }
        }
    }

    /**
     * Extrahiert den Post-Typ aus der URL
     * @return {string|null} Post-Typ oder null
     */
    function getPostTypeFromUrl() {
        var match = window.location.href.match(/post_type_([a-zA-Z0-9_-]+)/);
        if (match && match[1]) {
            return match[1];
        }
        
        // Alternative Methode: Versuche Generator-Parameter zu extrahieren
        match = window.location.href.match(/generator=post_type_([a-zA-Z0-9_-]+)/);
        if (match && match[1]) {
            return match[1];
        }
        
        return null;
    }
    
    /**
     * Gibt den aktuellen Generator-Typ zurück
     * @return {string|null} Generator-Typ oder null
     */
    function getCurrentGeneratorType() {
        // Versuche, den Generator-Typ aus der URL oder anderen Quellen zu ermitteln
        var match = window.location.href.match(/generator=([^&]+)/);
        if (match && match[1]) {
            return match[1];
        }
        
        // Fallback: Prüfe auf ausgewählten Generator
        var $selected = $('.n2_generator_items__item--selected');
        if ($selected.length) {
            return $selected.data('generatortype');
        }
        
        // Zweiter Fallback: Versuche aus dem DOM zu extrahieren
        var generatorTypeField = $('input[name="generator_type"]');
        if (generatorTypeField.length) {
            return generatorTypeField.val();
        }
        
        return null;
    }

    /**
     * Überprüft, ob wir uns auf einer Smart Slider-Seite befinden
     * @return {boolean} True, wenn auf einer Smart Slider-Seite
     */
    function isSmartSliderPage() {
        // Prüfe URL-Parameter für Smart Slider
        var isSmartSliderPage = window.location.href.indexOf('smart-slider') > -1 || 
               $('body').hasClass('toplevel_page_smart-slider3') ||
               $('body').hasClass('smart-slider3_page_smart-slider3');
        
        console.log('Smart Slider Seite erkannt:', isSmartSliderPage);
        return isSmartSliderPage;
    }

    /**
     * Event-Listener hinzufügen
     */
    function attachEventListeners() {
        console.log('Füge Event-Listener hinzu');
        
        // Smart Slider Generator-Auswahl-Event abfangen
        $(document).on('SmartSlider3GeneratorSelected', function(e, generatorType) {
            console.log('SmartSlider3GeneratorSelected Event empfangen mit Typ:', generatorType);
            
            if (generatorType && generatorType.indexOf('jetengine') === 0) {
                console.log('JetEngine Generator ausgewählt:', generatorType);
                
                // Generator-Typ extrahieren
                const postTypeMatch = generatorType.match(/post_type_([a-zA-Z0-9_-]+)/);
                if (postTypeMatch && postTypeMatch[1]) {
                    const postType = postTypeMatch[1];
                    console.log('Post-Typ erkannt:', postType);
                    
                    // Meta-Felder laden und Dropdown-Menüs erstellen
                    loadMetaFieldsAndCreateDropdowns(postType);
                } else {
                    console.log('Konnte keinen Post-Typ aus generatorType extrahieren:', generatorType);
                }
            } else {
                console.log('Ausgewählter Generator ist kein JetEngine Generator');
            }
        });
        
        // Auch bei Generator-Einstellungsänderungen prüfen
        $(document).on('SmartSlider3GeneratorSettingsChanged', function(e, settings) {
            console.log('SmartSlider3GeneratorSettingsChanged Event empfangen mit Einstellungen:', settings);
            
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
        console.log('Lade Meta-Felder für Post-Typ:', postType);
        
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
                console.log('Meta-Felder AJAX-Antwort erhalten:', response);
                
                if (response.success && response.data.meta_fields) {
                    // Meta-Felder in globaler Variable speichern
                    window.jetEngineMetaFields = response.data.meta_fields;
                    console.log('Verfügbare Meta-Felder:', window.jetEngineMetaFields);
                    
                    // Dropdown-Menüs erstellen
                    enhanceMetaFields();
                } else {
                    console.error('Ungültige oder leere AJAX-Antwort:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Fehler beim Laden der Meta-Felder:', status, error);
                console.log('AJAX-Anfrage-Informationen:', {
                    url: JetEngineSmartSliderEnhancer.ajaxurl,
                    action: 'jetengine_smartslider_get_all_meta_fields',
                    nonce: JetEngineSmartSliderEnhancer.nonce,
                    post_type: postType
                });
            }
        });
    }
    
    /**
     * Prüft und verbessert die Felder, wenn die UI aktualisiert wird
     */
    function checkAndEnhanceFields() {
        console.log('Prüfe und verbessere Felder. jetEngineMetaFields verfügbar:', !!window.jetEngineMetaFields);
        
        if (window.jetEngineMetaFields) {
            enhanceMetaFields();
        } else {
            console.log('Keine Meta-Felder geladen, überspringe Feldverbesserung');
        }
    }
    
    /**
     * Verbessert die Meta-Felder mit Dropdown-Menüs
     */
    function enhanceMetaFields() {
        console.log('Starte Verbesserung der Meta-Felder');
        
        // Aktualisierte Feldnamen basierend auf der DOM-Analyse
        createMetaFieldDropdown('generatormeta_name', 'filter', 'Meta-Feld Name');
        createMetaFieldDropdown('generatormeta_key', 'order', 'Meta-Key für Sortierung');
        createMetaFieldDropdown('generatorimage_meta', 'image', 'Bild-Meta-Feld');
        
        // Zusätzliche Debug-Infos für alle potenziellen Metafelder
        console.log('Potenzielle Meta-Feldnamen im DOM:');
        $('input[id^="generatormeta_"]').each(function() {
            console.log('- Gefunden:', this.id, 'mit Wert:', $(this).val());
        });
    }
    
    /**
     * Erstellt ein Dropdown-Menü für ein Meta-Feld
     * 
     * @param {string} fieldId ID des Textfelds
     * @param {string} fieldType Feldtyp (filter, image, order)
     * @param {string} label Beschriftung
     */
    function createMetaFieldDropdown(fieldId, fieldType, label) {
        console.log('Versuche Dropdown zu erstellen für Feld:', fieldId, 'mit Typ:', fieldType);
        
        // Das originale Textfeld suchen
        var $originalField = $('#' + fieldId);
        console.log('Original-Feld gefunden:', $originalField.length > 0, 'mit Wert:', $originalField.val());
        
        // Nur fortfahren, wenn das Feld existiert und noch kein Dropdown erstellt wurde
        if ($originalField.length && !$('#' + fieldId + '_select').length) {
            // Den aktuellen Wert speichern
            var currentValue = $originalField.val();
            console.log('Aktueller Wert des Originalfelds:', currentValue);
            
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
            var jetFieldsAdded = 0;
            if (window.jetEngineMetaFields && window.jetEngineMetaFields.jet_fields && window.jetEngineMetaFields.jet_fields.length > 0) {
                $.each(window.jetEngineMetaFields.jet_fields, function(i, field) {
                    // Spezielle Filterung für Bildfelder
                    if (fieldType === 'image' && !isImageField(field)) {
                        return; // Überspringen
                    }
                    
                    var $option = $('<option value="' + field.key + '">' + field.label + '</option>');
                    if (field.key === currentValue) {
                        $option.prop('selected', true);
                    }
                    $jetGroup.append($option);
                    jetFieldsAdded++;
                });
                
                if (jetFieldsAdded > 0) {
                    $select.append($jetGroup);
                }
                
                console.log('JetEngine-Felder hinzugefügt:', jetFieldsAdded);
            } else {
                console.log('Keine JetEngine-Felder verfügbar');
            }
            
            // Andere Felder hinzufügen
            var otherFieldsAdded = 0;
            if (window.jetEngineMetaFields && window.jetEngineMetaFields.other_fields && window.jetEngineMetaFields.other_fields.length > 0) {
                $.each(window.jetEngineMetaFields.other_fields, function(i, field) {
                    // Spezielle Filterung für Bildfelder
                    if (fieldType === 'image' && !isImageField(field)) {
                        return; // Überspringen
                    }
                    
                    var $option = $('<option value="' + field.key + '">' + field.label + '</option>');
                    if (field.key === currentValue) {
                        $option.prop('selected', true);
                    }
                    $otherGroup.append($option);
                    otherFieldsAdded++;
                });
                
                if (otherFieldsAdded > 0) {
                    $select.append($otherGroup);
                }
                
                console.log('Andere Felder hinzugefügt:', otherFieldsAdded);
            } else {
                console.log('Keine anderen Felder verfügbar');
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
                var newValue = $(this).val();
                console.log('Dropdown-Änderung:', fieldId, 'Neuer Wert:', newValue);
                
                $originalField.val(newValue);
                
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
            
            console.log('Dropdown-Menü erfolgreich erstellt für', fieldId);
        } else {
            console.log('Feld nicht gefunden oder Dropdown bereits erstellt:', fieldId, 'Feld existiert:', $originalField.length > 0, 'Select existiert:', $('#' + fieldId + '_select').length > 0);
        }
    }
    
    /**
     * Prüft, ob ein Feld ein Bildfeld ist
     * 
     * @param {Object} field Feldinformationen
     * @return {boolean} True wenn es ein Bildfeld ist
     */
    function isImageField(field) {
        // Standard ist immer zulassen
        if (!field.type) {
            return true;
        }
        
        var imageFieldTypes = ['image', 'media', 'gallery', 'file'];
        return imageFieldTypes.indexOf(field.type) !== -1;
    }
    
})(jQuery);
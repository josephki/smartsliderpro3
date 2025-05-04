/**
 * Admin JavaScript für JetEngine SmartSlider 3 Generator
 */
(function($) {
    'use strict';

    // Initialisieren, sobald das DOM geladen ist
    $(document).ready(function() {
        // Initialisiere JetEngine Generator Funktionalität
        initJetEngineGenerator();
    });

    /**
     * Hauptfunktion zur Initialisierung des JetEngine Generators
     */
    function initJetEngineGenerator() {
        console.log('Initialisiere JetEngine Generator mit Dokumentbereit-Status:', document.readyState);
        
        // Nur auf den relevanten Admin-Seiten ausführen
        if (!isSmartSliderPage()) {
            console.log('Keine Smart Slider Seite erkannt, breche Initialisierung ab');
            return;
        }

        console.log('JetEngine SmartSlider Generator geladen');
        
        // Verfügbare Smart Slider DOM-Elemente prüfen
        console.log('Verfügbare Smart Slider DOM-Elemente:', {
            metaName: $('#generatormeta_name').length,
            metaValue: $('#generatormeta_value').length,
            metaKey: $('#generatormeta_key').length,
            imageMetaField: $('#generatorimage_meta').length
        });
        
        // Event-Listener hinzufügen
        attachEventListeners();
        
        // DIREKTE META-FELD-VERBESSERUNG: Sofort starten ohne auf Events zu warten
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
            url: jetengineSmartSliderData.ajaxurl || ajaxurl,
            type: 'POST',
            data: {
                action: 'jetengine_smartslider_get_meta_fields',
                nonce: jetengineSmartSliderData.nonce,
                post_type: postType,
                generator_type: 'post_type_' + postType
            },
            success: function(response) {
                console.log('Meta-Felder AJAX-Antwort erhalten:', response);
                
                if (response.success && response.data && response.data.fields) {
                    // Felder verfügbar machen
                    window.jetEngineAvailableFields = response.data.fields;
                    console.log('Verfügbare Felder geladen:', window.jetEngineAvailableFields);
                    
                    // Dropdown-Menüs erstellen
                    enhanceMetaFields();
                } else {
                    console.error('Ungültige oder leere AJAX-Antwort:', response);
                    
                    // Fallback: Versuche mit Dummy-Daten
                    console.log('Verwende Fallback-Daten für Meta-Felder');
                    window.jetEngineAvailableFields = getFallbackFields();
                    enhanceMetaFields();
                }
            },
            error: function(xhr, status, error) {
                console.error('Fehler beim Laden der Meta-Felder:', status, error);
                console.log('AJAX-Anfrage-Informationen:', {
                    url: jetengineSmartSliderData.ajaxurl || ajaxurl,
                    action: 'jetengine_smartslider_get_meta_fields',
                    nonce: jetengineSmartSliderData.nonce,
                    post_type: postType
                });
                
                // Fallback: Versuche mit Dummy-Daten
                console.log('Verwende Fallback-Daten für Meta-Felder');
                window.jetEngineAvailableFields = getFallbackFields();
                enhanceMetaFields();
            }
        });
    }
    
    /**
     * Liefert Fallback-Felder, falls AJAX-Anfrage fehlschlägt
     * @return {array} Fallback-Felder
     */
    function getFallbackFields() {
        return [
            { name: 'tour_price', title: 'Tour Preis', type: 'number' },
            { name: 'tour_date', title: 'Tour Datum', type: 'date' },
            { name: 'tour_image', title: 'Tour Bild', type: 'image' },
            { name: 'tour_gallery', title: 'Tour Galerie', type: 'gallery' },
            { name: 'tour_description', title: 'Tour Beschreibung', type: 'textarea' }
        ];
    }
    
    /**
     * Prüft und verbessert die Felder, wenn die UI aktualisiert wird
     */
    function checkAndEnhanceFields() {
        console.log('Prüfe und verbessere Felder. jetEngineAvailableFields verfügbar:', !!window.jetEngineAvailableFields);
        
        if (window.jetEngineAvailableFields) {
            enhanceMetaFields();
        } else {
            console.log('Keine Meta-Felder geladen, überspringe Feldverbesserung');
            
            // Versuche dennoch die Feldstruktur zu analysieren
            analyzeFieldStructure();
        }
    }
    
    /**
     * Analysiert die Feldstruktur zur Fehlerbehebung
     */
    function analyzeFieldStructure() {
        console.log('Analysiere Feldstruktur zur Fehlerbehebung');
        
        // Liste aller Meta-Eingabefelder
        var metaFields = $('[id^="generatormeta_"], [id="generatorimage_meta"]');
        console.log('Gefundene Meta-Felder:', metaFields.length);
        
        metaFields.each(function() {
            var $field = $(this);
            console.log('Meta-Feld gefunden:', {
                id: $field.attr('id'),
                name: $field.attr('name'),
                value: $field.val(),
                type: $field.attr('type')
            });
        });
    }
    
    /**
     * Verbessert die Meta-Felder mit Dropdown-Menüs
     */
    function enhanceMetaFields() {
        console.log('Starte Verbesserung der Meta-Felder');
        
        // Aktualisierte Feldnamen basierend auf der DOM-Analyse
        var $metaNameField = $('#generatormeta_name');
        console.log('Meta-Name-Feld gefunden:', $metaNameField.length, 'mit Wert:', $metaNameField.val());
        
        var $metaKeyField = $('#generatormeta_key');
        console.log('Meta-Key-Feld gefunden:', $metaKeyField.length, 'mit Wert:', $metaKeyField.val());
        
        var $imageMetaField = $('#generatorimage_meta');
        console.log('Bild-Meta-Feld gefunden:', $imageMetaField.length, 'mit Wert:', $imageMetaField.val());
        
        // Meta-Feld für Filter
        if ($metaNameField.length && !$metaNameField.data('enhanced')) {
            createMetaFieldDropdown($metaNameField, 'filter');
            $metaNameField.data('enhanced', true);
        }
        
        // Meta-Feld für Sortierung
        if ($metaKeyField.length && !$metaKeyField.data('enhanced')) {
            createMetaFieldDropdown($metaKeyField, 'order');
            $metaKeyField.data('enhanced', true);
        }
        
        // Meta-Feld für Bilder
        if ($imageMetaField.length && !$imageMetaField.data('enhanced')) {
            createMetaFieldDropdown($imageMetaField, 'image');
            $imageMetaField.data('enhanced', true);
        }
        
        console.log('Meta-Feldverbesserung abgeschlossen');
    }
    
    /**
     * Erstellt ein Dropdown-Menü für Meta-Felder
     * 
     * @param {jQuery} $field Das zu verbessernde Eingabefeld
     * @param {string} fieldType Feldtyp (filter, image, order)
     */
    function createMetaFieldDropdown($field, fieldType) {
        console.log('Erstelle Dropdown für Feld:', $field.attr('id'), 'mit Typ:', fieldType);
        
        // Felder vorbereiten
        var fields = window.jetEngineAvailableFields || [];
        
        // Aktuellen Wert speichern
        var currentValue = $field.val();
        console.log('Aktueller Feld-Wert:', currentValue);
        
        // Container erstellen
        var $container = $('<div class="jetengine-meta-dropdown-container"></div>');
        
        // Select-Element erstellen
        var $select = $('<select class="jetengine-meta-dropdown"></select>');
        
        // Platzhalter-Option
        $select.append('<option value="">-- Meta-Feld auswählen --</option>');
        
        // Optionsgruppen erstellen
        var $jetGroup = $('<optgroup label="JetEngine Felder"></optgroup>');
        var $otherGroup = $('<optgroup label="Andere Felder"></optgroup>');
        
        // Felder sortieren
        var jetFields = [];
        var otherFields = [];
        
        $.each(fields, function(i, field) {
            var name = field.name || field.key;
            var title = field.title || field.label || name;
            
            // Nur Bildfelder für Bild-Dropdown anzeigen
            if (fieldType === 'image' && !isImageField(field)) {
                return; // Überspringen
            }
            
            // JetEngine-Felder oder andere
            if (name.indexOf('_jet_') === 0 || name.indexOf('jet_') === 0) {
                jetFields.push({name: name, title: title});
            } else {
                otherFields.push({name: name, title: title});
            }
        });
        
        console.log('Sortierte Felder:', {
            jetFields: jetFields.length,
            otherFields: otherFields.length
        });
        
        // JetEngine-Felder zum Dropdown hinzufügen
        if (jetFields.length > 0) {
            $.each(jetFields, function(i, field) {
                var $option = $('<option value="' + field.name + '">' + field.title + '</option>');
                if (field.name === currentValue) {
                    $option.prop('selected', true);
                }
                $jetGroup.append($option);
            });
            $select.append($jetGroup);
        }
        
        // Andere Felder zum Dropdown hinzufügen
        if (otherFields.length > 0) {
            $.each(otherFields, function(i, field) {
                var $option = $('<option value="' + field.name + '">' + field.title + '</option>');
                if (field.name === currentValue) {
                    $option.prop('selected', true);
                }
                $otherGroup.append($option);
            });
            $select.append($otherGroup);
        }
        
        // Dropdown einfügen und Original-Feld verstecken
        $field.after($container);
        $container.append($select);
        
        $field.css({
            'position': 'absolute',
            'left': '-9999px',
            'width': '1px',
            'height': '1px',
            'opacity': '0'
        });
        
        // Event-Handler für Änderungen
        $select.on('change', function() {
            var newValue = $(this).val();
            console.log('Dropdown-Änderung:', $field.attr('id'), 'Neuer Wert:', newValue);
            
            $field.val(newValue);
            $field.trigger('change');
        });
        
        // Hinweistext hinzufügen
        var hintText = "";
        if (fieldType === 'filter') {
            hintText = "Wählen Sie ein Meta-Feld für den Filter aus.";
        } else if (fieldType === 'image') {
            hintText = "Wählen Sie ein Bild-Meta-Feld aus (unterstützt Media, Gallery, etc.).";
        } else if (fieldType === 'order') {
            hintText = "Wählen Sie ein Meta-Feld für die Sortierung aus.";
        }
        
        if (hintText) {
            $container.after('<p class="jetengine-meta-hint" style="font-size: 12px; color: #666; margin-top: 3px;">' + hintText + '</p>');
        }
        
        console.log('Dropdown erfolgreich erstellt für:', $field.attr('id'));
    }
    
    /**
     * Prüft, ob ein Feld ein Bildfeld ist
     * 
     * @param {Object} field Feldinformationen
     * @return {boolean} True wenn es ein Bildfeld ist
     */
    function isImageField(field) {
        if (!field.type) {
            return true; // Bei Unsicherheit durchlassen
        }
        
        var imageTypes = ['image', 'media', 'gallery', 'file'];
        return imageTypes.indexOf(field.type) !== -1;
    }
    
    /**
     * Gibt den aktuellen Generator-Typ zurück
     * 
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

})(jQuery);
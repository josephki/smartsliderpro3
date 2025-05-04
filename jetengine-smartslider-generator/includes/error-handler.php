<?php
/**
 * Verbesserte Fehlerbehandlung und Logging für JetEngine SmartSlider Generator
 * 
 * Diese Klasse stellt erweiterte Logging- und Fehlerbehanlungsfunktionen bereit
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) exit;

class JetEngine_SmartSlider_Error_Handler {
    
    /**
     * Log-Datei-Pfad
     */
    private $log_file;
    
    /**
     * Aktivierter Debug-Modus
     */
    private $debug_mode = false;
    
    /**
     * Log-Level
     * 
     * 0 = Fehler, 1 = Warnungen, 2 = Info, 3 = Debug
     */
    private $log_level = 1;
    
    /**
     * Maximale Log-Dateigröße in Bytes (standardmäßig 5 MB)
     */
    private $max_log_size = 5242880;
    
    /**
     * Singleton-Instanz
     */
    private static $instance = null;
    
    /**
     * Konstruktor
     */
    private function __construct() {
        // Debug-Modus aus den Plugin-Einstellungen holen
        $this->debug_mode = get_option('jetengine_smartslider_debug_mode', false);
        
        // Log-Level aus den Einstellungen holen
        $this->log_level = get_option('jetengine_smartslider_log_level', 1);
        
        // Log-Datei-Pfad festlegen
        $upload_dir = wp_upload_dir();
        $this->log_file = trailingslashit($upload_dir['basedir']) . 'jetengine-smartslider-logs/debug.log';
        
        // Log-Verzeichnis erstellen, falls nicht vorhanden
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Schütze das Verzeichnis mit einer .htaccess-Datei
            $htaccess_file = trailingslashit($log_dir) . '.htaccess';
            if (!file_exists($htaccess_file)) {
                $htaccess_content = "# Verbiete den direkten Zugriff auf Log-Dateien\n";
                $htaccess_content .= "<Files \"*.log\">\n";
                $htaccess_content .= "Order Allow,Deny\n";
                $htaccess_content .= "Deny from all\n";
                $htaccess_content .= "</Files>\n";
                
                @file_put_contents($htaccess_file, $htaccess_content);
            }
            
            // Füge eine leere index.php hinzu, um Verzeichnislistings zu verhindern
            $index_file = trailingslashit($log_dir) . 'index.php';
            if (!file_exists($index_file)) {
                @file_put_contents($index_file, "<?php\n// Silence is golden.");
            }
        }
        
        // Rolliere die Log-Datei, falls notwendig
        $this->maybe_rotate_logs();
    }
    
    /**
     * Holt die Singleton-Instanz der Klasse
     * 
     * @return JetEngine_SmartSlider_Error_Handler Instanz
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Schreibt einen Fehler in die Log-Datei
     * 
     * @param string $message Fehlermeldung
     * @param array $context Zusätzlicher Kontext
     */
    public function error($message, $context = []) {
        if ($this->log_level >= 0) {
            $this->log('ERROR', $message, $context);
        }
    }
    
    /**
     * Schreibt eine Warnung in die Log-Datei
     * 
     * @param string $message Warnungsmeldung
     * @param array $context Zusätzlicher Kontext
     */
    public function warning($message, $context = []) {
        if ($this->log_level >= 1) {
            $this->log('WARNING', $message, $context);
        }
    }
    
    /**
     * Schreibt eine Info-Meldung in die Log-Datei
     * 
     * @param string $message Info-Meldung
     * @param array $context Zusätzlicher Kontext
     */
    public function info($message, $context = []) {
        if ($this->log_level >= 2) {
            $this->log('INFO', $message, $context);
        }
    }
    
    /**
     * Schreibt eine Debug-Meldung in die Log-Datei
     * 
     * @param string $message Debug-Meldung
     * @param array $context Zusätzlicher Kontext
     */
    public function debug($message, $context = []) {
        if ($this->log_level >= 3) {
            $this->log('DEBUG', $message, $context);
        }
    }
    
    /**
     * Rückwärtskompatibilität mit dem alten Logging-System
     * 
     * @param string $message Meldung
     */
    public function log_message($message) {
        $this->info($message);
    }
    
    /**
     * Kernfunktion für das Logging
     * 
     * @param string $level Log-Level
     * @param string $message Meldung
     * @param array $context Zusätzlicher Kontext
     */
    private function log($level, $message, $context = []) {
        // Zeit und Level formatieren
        $time = current_time('mysql');
        $formatted_message = "[{$time}] [{$level}] {$message}";
        
        // Kontext hinzufügen, falls vorhanden
        if (!empty($context)) {
            $formatted_message .= ' ' . json_encode($context);
        }
        
        // Zeilenumbruch hinzufügen
        $formatted_message .= PHP_EOL;
        
        // In Log-Datei schreiben
        @file_put_contents($this->log_file, $formatted_message, FILE_APPEND);
        
        // Wenn Debug-Modus aktiv ist, auch in PHP-Fehlerlog schreiben
        if ($this->debug_mode && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[JetEngine SmartSlider] ' . $message);
        }
    }
    
    /**
     * Rolliert die Log-Datei, falls sie die maximale Größe überschreitet
     */
    private function maybe_rotate_logs() {
        if (file_exists($this->log_file) && filesize($this->log_file) > $this->max_log_size) {
            $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
            
            // Aktuelle Log-Datei sichern
            @rename($this->log_file, $backup_file);
            
            // Archivierte Log-Dateien aufräumen (nur die letzten 5 behalten)
            $this->cleanup_old_logs();
            
            // Neue Log-Datei erstellen mit einem Header
            $header = "[" . current_time('mysql') . "] [INFO] Log-Datei rolliert. Vorherige Logs in {$backup_file}\n";
            @file_put_contents($this->log_file, $header);
        }
    }
    
    /**
     * Bereinigt alte Log-Dateien (behält nur die neuesten 5)
     */
    private function cleanup_old_logs() {
        $log_dir = dirname($this->log_file);
        $files = glob($log_dir . '/*.bak');
        
        if (count($files) <= 5) {
            return;
        }
        
        // Nach Modifikationsdatum sortieren (neuste zuerst)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Älteste Logs löschen
        $files_to_delete = array_slice($files, 5);
        foreach ($files_to_delete as $file) {
            @unlink($file);
        }
    }
    
    /**
     * Zeichnet eine Ausnahme auf
     * 
     * @param Exception $exception Die aufzuzeichnende Ausnahme
     * @param string $context Zusätzlicher Kontext
     */
    public function log_exception($exception, $context = '') {
        $message = 'Exception: ' . $exception->getMessage();
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context
        ];
        
        $this->error($message, $context);
    }
    
    /**
     * Gibt den Inhalt der Log-Datei zurück
     * 
     * @param int $lines Anzahl der zurückzugebenden Zeilen (0 = alle)
     * @return string Log-Datei-Inhalt
     */
    public function get_log_content($lines = 100) {
        if (!file_exists($this->log_file)) {
            return __('Keine Log-Datei gefunden.', 'jetengine-smartslider');
        }
        
        if ($lines === 0) {
            return @file_get_contents($this->log_file);
        }
        
        // Letzte X Zeilen lesen
        $content = @file($this->log_file);
        if (!$content) {
            return __('Log-Datei konnte nicht gelesen werden.', 'jetengine-smartslider');
        }
        
        // Nur die letzten X Zeilen zurückgeben
        $content = array_slice($content, -$lines);
        
        return implode('', $content);
    }
    
    /**
     * Leert die Log-Datei
     * 
     * @return bool True bei Erfolg, andernfalls False
     */
    public function clear_log() {
        $this->log('INFO', 'Log-Datei manuell geleert.');
        return @file_put_contents($this->log_file, '');
    }
    
    /**
     * Aktiviert oder deaktiviert den Debug-Modus
     * 
     * @param bool $enable True zum Aktivieren, False zum Deaktivieren
     */
    public function set_debug_mode($enable) {
        $this->debug_mode = (bool) $enable;
        update_option('jetengine_smartslider_debug_mode', $this->debug_mode);
        
        $status = $this->debug_mode ? 'aktiviert' : 'deaktiviert';
        $this->log('INFO', "Debug-Modus {$status}.");
    }
    
    /**
     * Setzt das Log-Level
     * 
     * @param int $level Log-Level (0-3)
     */
    public function set_log_level($level) {
        $level = intval($level);
        if ($level >= 0 && $level <= 3) {
            $this->log_level = $level;
            update_option('jetengine_smartslider_log_level', $this->log_level);
            
            $levels = [
                0 => 'ERROR',
                1 => 'WARNING',
                2 => 'INFO',
                3 => 'DEBUG'
            ];
            
            $this->log('INFO', "Log-Level auf {$levels[$level]} gesetzt.");
        }
    }
}

/**
 * Hilfsfunktion für den Zugriff auf den Error Handler
 * 
 * @return JetEngine_SmartSlider_Error_Handler Error Handler-Instanz
 */
function jetengine_smartslider_error_handler() {
    return JetEngine_SmartSlider_Error_Handler::get_instance();
}

/**
 * Diese Funktion sollte in der Hauptklasse des Plugins aufgerufen werden,
 * um die alte log()-Methode zu ersetzen
 */
function jetengine_smartslider_initialize_error_handler() {
    // Fehlerbehandlung für ungefangene Ausnahmen
    set_exception_handler(function($exception) {
        jetengine_smartslider_error_handler()->log_exception($exception);
    });
    
    // Verwenden Sie diese Funktion in Ihrem Plugin, um auf den Error Handler zuzugreifen
    add_action('admin_init', function() {
        // Beispiel für die Verwendung des Error Handlers
        jetengine_smartslider_error_handler()->info('JetEngine SmartSlider Generator initialisiert.');
    });
}

/**
 * Integration mit PHP-Fehlerberichterstattung
 */
function jetengine_smartslider_error_reporting_integration() {
    // Nur im Debug-Modus aktivieren
    if (!get_option('jetengine_smartslider_debug_mode', false)) {
        return;
    }
    
    // Benutzerdefinierten Fehlerhandler festlegen
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        // Ignorieren, wenn Fehlerberichterstattung deaktiviert oder Fehler unterdrückt ist
        if (!(error_reporting() & $errno) || $errno == E_STRICT) {
            return false;
        }
        
        // Nur Fehler innerhalb des Plugin-Verzeichnisses protokollieren
        $plugin_dir = plugin_dir_path(dirname(__FILE__));
        if (strpos($errfile, $plugin_dir) === false) {
            return false;
        }
        
        switch ($errno) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                jetengine_smartslider_error_handler()->error($errstr, [
                    'file' => $errfile,
                    'line' => $errline,
                    'type' => 'PHP Error'
                ]);
                break;
                
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                jetengine_smartslider_error_handler()->warning($errstr, [
                    'file' => $errfile,
                    'line' => $errline,
                    'type' => 'PHP Warning'
                ]);
                break;
                
            case E_NOTICE:
            case E_USER_NOTICE:
                jetengine_smartslider_error_handler()->info($errstr, [
                    'file' => $errfile,
                    'line' => $errline,
                    'type' => 'PHP Notice'
                ]);
                break;
                
            default:
                jetengine_smartslider_error_handler()->debug($errstr, [
                    'file' => $errfile,
                    'line' => $errline,
                    'type' => 'PHP Debug'
                ]);
                break;
        }
        
        // True zurückgeben, um zu verhindern, dass der Standard-PHP-Fehlerhandler ausgeführt wird
        return true;
    });
}

/**
 * Fügt Einstellungen für den Error Handler zu den Plugin-Einstellungen hinzu
 */
function jetengine_smartslider_add_error_handler_settings($settings_section) {
    add_settings_field(
        'jetengine_smartslider_debug_mode',
        __('Debug-Modus', 'jetengine-smartslider'),
        'jetengine_smartslider_debug_mode_callback',
        'jetengine_smartslider_settings',
        $settings_section
    );
    
    add_settings_field(
        'jetengine_smartslider_log_level',
        __('Log-Level', 'jetengine-smartslider'),
        'jetengine_smartslider_log_level_callback',
        'jetengine_smartslider_settings',
        $settings_section
    );
    
    register_setting('jetengine_smartslider_settings', 'jetengine_smartslider_debug_mode');
    register_setting('jetengine_smartslider_settings', 'jetengine_smartslider_log_level');
}

/**
 * Callback für das Debug-Modus-Einstellungsfeld
 */
function jetengine_smartslider_debug_mode_callback() {
    $debug_mode = get_option('jetengine_smartslider_debug_mode', false);
    ?>
    <input type="checkbox" name="jetengine_smartslider_debug_mode" value="1" <?php checked(1, $debug_mode); ?> />
    <p class="description"><?php _e('Aktiviert erweiterte Protokollierung für Fehlersuche und Entwicklung.', 'jetengine-smartslider'); ?></p>
    <?php
}

/**
 * Callback für das Log-Level-Einstellungsfeld
 */
function jetengine_smartslider_log_level_callback() {
    $log_level = get_option('jetengine_smartslider_log_level', 1);
    ?>
    <select name="jetengine_smartslider_log_level">
        <option value="0" <?php selected(0, $log_level); ?>><?php _e('Fehler', 'jetengine-smartslider'); ?></option>
        <option value="1" <?php selected(1, $log_level); ?>><?php _e('Warnungen', 'jetengine-smartslider'); ?></option>
        <option value="2" <?php selected(2, $log_level); ?>><?php _e('Info', 'jetengine-smartslider'); ?></option>
        <option value="3" <?php selected(3, $log_level); ?>><?php _e('Debug', 'jetengine-smartslider'); ?></option>
    </select>
    <p class="description"><?php _e('Wählen Sie die Detailstufe für die Protokollierung.', 'jetengine-smartslider'); ?></p>
    <?php
}
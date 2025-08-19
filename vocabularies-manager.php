<?php
defined('ABSPATH') or die('Zugriff verboten');

/**
 * Vokabular-Manager für lokale Speicherung
 */
class AMB_Vocabularies_Manager {
    
    private $vocabularies_dir;
    private $vocabulary_configs;
    
    public function __construct() {
        $this->vocabularies_dir = plugin_dir_path(__FILE__) . 'vocabularies/';
        $this->vocabulary_configs = $this->get_vocabulary_configs();
        
        // Verzeichnis erstellen falls nicht vorhanden
        if (!file_exists($this->vocabularies_dir)) {
            wp_mkdir_p($this->vocabularies_dir);
        }
    }
    
    /**
     * Konfiguration aller Vokabulare
     */
    private function get_vocabulary_configs() {
        return [
            'amb_license' => [
                'url' => 'https://skohub.io/dini-ag-kim/license/heads/main/w3id.org/kim/license/index.json',
                'amb_key' => 'license',
                'filename' => 'license.json',
                'name' => 'Lizenzen'
            ],
            'amb_area' => [
                'url' => 'https://hof-halle-wittenberg.github.io/vocabs/area/index.json',
                'amb_key' => 'area',
                'filename' => 'area.json',
                'name' => 'Fachbereiche'
            ],
            'amb_type' => [
                'url' => 'https://hof-halle-wittenberg.github.io/vocabs/type/index.json',
                'amb_key' => 'type',
                'filename' => 'type.json',
                'name' => 'Ressourcentypen'
            ],
            'amb_organisationalContext' => [
                'url' => 'https://hof-halle-wittenberg.github.io/vocabs/organisationalContext/index.json',
                'amb_key' => 'about',
                'filename' => 'organisationalContext.json',
                'name' => 'Organisationskontext'
            ],
            'amb_didacticUseCase' => [
                'url' => 'https://hof-halle-wittenberg.github.io/vocabs/didacticUseCase/index.json',
                'amb_key' => 'about',
                'filename' => 'didacticUseCase.json',
                'name' => 'Didaktische Anwendungsfälle'
            ],
            'amb_learningResourceType' => [
                'url' => 'https://skohub.io/dini-ag-kim/hcrt/heads/master/w3id.org/kim/hcrt/scheme.json',
                'amb_key' => 'learningResourceType',
                'filename' => 'learningResourceType.json',
                'name' => 'Lernressourcentypen'
            ],
            'amb_audience' => [
                'url' => 'https://hof-halle-wittenberg.github.io/vocabs/audience/index.json',
                'amb_key' => 'audience',
                'filename' => 'audience.json',
                'name' => 'Zielgruppen'
            ],
            'amb_hochschulfaechersystematik' => [
                'url' => 'https://skohub.io/dini-ag-kim/hochschulfaechersystematik/heads/master/w3id.org/kim/hochschulfaechersystematik/scheme.json',
                'amb_key' => 'about',
                'filename' => 'hochschulfaechersystematik.json',
                'name' => 'Hochschulfächersystematik'
            ]
        ];
    }
    
    /**
     * Einzelnes Vokabular herunterladen und speichern
     */
    public function download_vocabulary($key) {
        if (!isset($this->vocabulary_configs[$key])) {
            return new WP_Error('invalid_key', 'Unbekannter Vokabular-Schlüssel');
        }
        
        $config = $this->vocabulary_configs[$key];
        $local_path = $this->vocabularies_dir . $config['filename'];
        
        amb_dido_log("Lade Vokabular: $key von " . $config['url']);
        
        // Download mit erweiterten Optionen
        $response = wp_remote_get($config['url'], [
            'timeout' => 30,
            'user-agent' => 'AMB-DidO Plugin/0.8.5',
            'sslverify' => true
        ]);
        
        if (is_wp_error($response)) {
            amb_dido_log("Fehler beim Download von $key: " . $response->get_error_message());
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_msg = "HTTP $response_code für $key";
            amb_dido_log($error_msg);
            return new WP_Error('http_error', $error_msg);
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data) {
            $error_msg = "Ungültige JSON-Daten für $key";
            amb_dido_log($error_msg);
            return new WP_Error('invalid_json', $error_msg);
        }
        
        // Speichere in lokaler Datei
        $json_content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($local_path, $json_content) === false) {
            $error_msg = "Fehler beim Speichern von $key";
            amb_dido_log($error_msg);
            return new WP_Error('save_error', $error_msg);
        }
        
        // Metadaten speichern
        $metadata = [
            'downloaded' => current_time('timestamp'),
            'url' => $config['url'],
            'size' => filesize($local_path),
            'entries' => count($data['hasTopConcept'] ?? [])
        ];
        update_option("amb_vocab_meta_$key", $metadata);
        
        amb_dido_log("Vokabular $key erfolgreich gespeichert (" . $metadata['entries'] . " Einträge)");
        
        return true;
    }
    
    /**
     * Alle Vokabulare herunterladen
     */
    public function download_all_vocabularies() {
        $results = [];
        
        foreach ($this->vocabulary_configs as $key => $config) {
            $result = $this->download_vocabulary($key);
            $results[$key] = [
                'success' => !is_wp_error($result),
                'message' => is_wp_error($result) ? $result->get_error_message() : 'Erfolgreich'
            ];
            
            // Kurze Pause zwischen Downloads
            sleep(1);
        }
        
        return $results;
    }
    
    /**
     * Vokabular aus lokaler Datei laden
     */
    public function load_local_vocabulary($key) {
        if (!isset($this->vocabulary_configs[$key])) {
            return [];
        }
        
        $config = $this->vocabulary_configs[$key];
        $local_path = $this->vocabularies_dir . $config['filename'];
        
        if (!file_exists($local_path)) {
            amb_dido_log("Lokale Datei nicht gefunden: $local_path");
            return [];
        }
        
        $content = file_get_contents($local_path);
        if ($content === false) {
            amb_dido_log("Fehler beim Lesen von: $local_path");
            return [];
        }
        
        $data = json_decode($content, true);
        if (!$data) {
            amb_dido_log("Ungültige JSON in: $local_path");
            return [];
        }
        
        return $this->parse_vocabulary_data($data, $config['amb_key']);
    }
    
    /**
     * Alle lokalen Vokabulare laden
     */
    public function load_all_local_vocabularies() {
        $all_values = [];
        
        foreach ($this->vocabulary_configs as $key => $config) {
            $values = $this->load_local_vocabulary($key);
            if (!empty($values)) {
                $all_values[$key] = $values;
            }
        }
        
        return $all_values;
    }
    
    /**
     * Vokabular-Daten parsen
     */
    private function parse_vocabulary_data($data, $amb_key) {
        $field_label = $data['title']['de'] ?? 'Standard-Titel';
        $concepts = $data['hasTopConcept'] ?? [];
        $options = amb_parse_concepts($concepts);

        return [
            'field_label' => $field_label,
            'options' => $options,
            'amb_key' => $amb_key
        ];
    }
    
    /**
     * Vokabular-Status abrufen
     */
    public function get_vocabulary_status($key) {
        $config = $this->vocabulary_configs[$key] ?? null;
        if (!$config) {
            return null;
        }
        
        $local_path = $this->vocabularies_dir . $config['filename'];
        $exists = file_exists($local_path);
        $metadata = get_option("amb_vocab_meta_$key", []);
        
        return [
            'key' => $key,
            'name' => $config['name'],
            'exists' => $exists,
            'size' => $exists ? filesize($local_path) : 0,
            'downloaded' => $metadata['downloaded'] ?? null,
            'entries' => $metadata['entries'] ?? 0,
            'url' => $config['url']
        ];
    }
    
    /**
     * Status aller Vokabulare
     */
    public function get_all_vocabulary_status() {
        $status = [];
        foreach ($this->vocabulary_configs as $key => $config) {
            $status[$key] = $this->get_vocabulary_status($key);
        }
        return $status;
    }
    
    /**
     * Vokabular löschen
     */
    public function delete_vocabulary($key) {
        if (!isset($this->vocabulary_configs[$key])) {
            return false;
        }
        
        $config = $this->vocabulary_configs[$key];
        $local_path = $this->vocabularies_dir . $config['filename'];
        
        if (file_exists($local_path)) {
            unlink($local_path);
        }
        
        delete_option("amb_vocab_meta_$key");
        amb_dido_log("Vokabular $key gelöscht");
        
        return true;
    }
    

    public function save_vocabulary_data($key, $values) {
        if (!isset($this->vocabulary_configs[$key])) {
            return false;
        }
        
        $config = $this->vocabulary_configs[$key];
        $local_path = $this->vocabularies_dir . $config['filename'];
        
        // Erstelle Dummy-JSON-Struktur aus verarbeiteten Daten
        $json_structure = [
            'title' => ['de' => $values['field_label']],
            'hasTopConcept' => $this->reverse_parse_concepts($values['options'])
        ];
        
        $json_content = json_encode($json_structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (file_put_contents($local_path, $json_content) !== false) {
            $metadata = [
                'downloaded' => current_time('timestamp'),
                'size' => filesize($local_path),
                'entries' => count($values['options'])
            ];
            update_option("amb_vocab_meta_$key", $metadata);
            return true;
        }
        
        return false;
    }
    
    private function reverse_parse_concepts($options) {
        $concepts = [];
        foreach ($options as $option) {
            foreach ($option as $id => $label) {
                if (!is_array($label)) {
                    $concepts[] = [
                        'id' => $id,
                        'prefLabel' => ['de' => $label]
                    ];
                }
            }
        }
        return $concepts;
    }
}

// Globale Instanz erstellen
$GLOBALS['amb_vocabularies_manager'] = new AMB_Vocabularies_Manager();

/**
 * Helper-Funktionen für Backward-Compatibility
 */
function amb_vocabularies_manager() {
    return $GLOBALS['amb_vocabularies_manager'];
}

/**
 * Erweiterte Funktion für lokale Vokabulare
 * Diese ersetzt amb_get_all_external_values() wenn lokaler Modus aktiv ist
 */
function amb_get_all_local_vocabularies() {
    return amb_vocabularies_manager()->load_all_local_vocabularies();
}

/**
* Erweiterte Logging-Funktion
*/
function amb_dido_log_with_context($message, $context = []) {
   $contextStr = empty($context) ? '' : ' [' . json_encode($context) . ']';
   amb_dido_log($message . $contextStr);
}
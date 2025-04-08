<?php
/**
 * Plugin Name: AMB-DidO Plugin 
 * Description: Erstellt Metadaten gemäß AMB-Standard im JSON-Format für didaktische und Organisationsressourcen
 * Version: 0.8.5
 * Author Justus Henke, Manuel Oellers
 */




/** 
 *  Features und Funktionen laden 
 * */
include_once(plugin_dir_path( __FILE__ ) . 'metabox.php');  // Weitere Metaboxen
include_once(plugin_dir_path( __FILE__ ) . 'frontend.php'); // Frontend-Darstellung
include_once(plugin_dir_path( __FILE__ ) . 'search.php');   // Suchfunktionen
require_once(plugin_dir_path( __FILE__ ) . 'options.php');  // Plugin-Einstellungen


/**
 * Abhängige Dateien laden
 */

function amb_dido_enqueue_styles() {
    wp_enqueue_style('amb-dido-styles', plugin_dir_url(__FILE__) . 'styles.css');
}
function amb_dido_enqueue_frontend_styles() {
    wp_register_style('amb_dido_styles_frontend', plugins_url('styles-frontend.css', __FILE__));
    wp_enqueue_style('amb_dido_styles_frontend');
}

function amb_enqueue_scripts() {
    wp_enqueue_script('amb-keywords-js', plugin_dir_url(__FILE__) . 'scripts.js', [], null, true);
}

function amb_enqueue_admin_scripts() {
    wp_enqueue_script('post'); // This script is needed for tag input and other post editing features.
}
add_action('admin_enqueue_scripts', 'amb_enqueue_admin_scripts');

/**
 * Funktionen aufrufen
 */

add_action('admin_enqueue_scripts', 'amb_enqueue_scripts');     // JS laden
add_action('admin_enqueue_scripts', 'amb_dido_enqueue_styles'); // CSS Styles
add_action('wp_enqueue_scripts', 'amb_dido_enqueue_frontend_styles');    // CSS Styles für das Frontend
add_action('admin_menu', 'amb_dido_create_settings_page');      // Optionsseite erstellen
add_action('admin_init', 'amb_dido_register_settings');         // Optionen registrieren

add_action('add_meta_boxes', 'amb_dido_add_custom_box');    // Felder erzeugen
add_action('save_post', 'amb_dido_save_post_meta');         // Werte speichern
add_action('wp_head', 'amb_dido_add_json_ld_to_header');    // JSON schreiben





// Logfile erstellen
function amb_dido_log($message) {
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    $log_file = plugin_dir_path(__FILE__) . 'amb_dido.log';
    file_put_contents($log_file, current_time('mysql') . " " . $message . "\n", FILE_APPEND);
}




/**
 *  AMB Metabox
 * --------------
 */

/**
 * Registriert die Metabox für die AMB-Attribute.
 */
function amb_dido_add_custom_box() {
    $selected_post_types = get_option('amb_dido_post_types', []);
    foreach ($selected_post_types as $post_type) {
        add_meta_box(
            'amb_dido_meta_box',
            __('AMB Metadaten', 'amb_dido'),
            'amb_dido_meta_box_callback',
            $post_type,
            'normal',
            'high'
        );
    }
}


// Hartkodierte Wertelisten
function amb_get_other_fields() {
    return [
        
        'amb_inLanguage' => [
            'field_label' => 'Sprache des Inhalts',
            'options' => [
                ['de' => 'Deutsch'],
                ['en' => 'English'],
                ['fr' => 'Français']
            ],
            'amb_key' => 'inLanguage'
        ],
        'amb_isAccessibleForFree' => [
            'field_label' => 'Kostenfreier Zugang',
            'options' => [
                ['true' => 'Ja'],
                ['false' => 'Nein']
            ],
            'amb_key' => 'isAccessibleForFree'
        ],
        'amb_conditionsOfAccess' => [
            'field_label' => 'Zugangsbedingungen',
            'options' => [
                ['http://w3id.org/kim/conditionsOfAccess/no_login' => 'Keine Anmeldung erforderlich'],
                ['http://w3id.org/kim/conditionsOfAccess/login' => 'Anmeldung erforderlich']
            ],
            'amb_key' => 'conditionsOfAccess'
        ],
        'amb_interactivityType' => [
            'field_label' => 'Lehr-/Lernform',
            'options' => [
                ['http://purl.org/dcx/lrmi-vocabs/interactivityType/active' => 'aktiv (eher selbstgesteuert)'],
                ['http://purl.org/dcx/lrmi-vocabs/interactivityType/expositive' => 'erklärend (eher fremdgesteuert)'],
                ['http://purl.org/dcx/lrmi-vocabs/interactivityType/mixed' => 'gemischt']
            ],
            'amb_key' => 'interactivityType'
        ]
        
    ];
}


/**
 * Funktion zur Rückgabe der JSON-URLs für Wertelisten.
 *
 * @return array Array mit JSON-URLs und AMB-Schlüsseln
 */
function amb_get_json_urls() {
    $predefined_json_urls = [
        'amb_license' => [
            'url' => 'https://skohub.io/dini-ag-kim/license/heads/main/w3id.org/kim/license/index.json',
            'amb_key' => 'license'
        ],
        'amb_area' => [
            'url' => 'https://hof-halle-wittenberg.github.io/vocabs/area/index.json',
            'amb_key' => 'area'
        ],
        'amb_type' => [
            'url' => 'https://hof-halle-wittenberg.github.io/vocabs/type/index.json',
            'amb_key' => 'type'
        ],
        'amb_organisationalContext' => [
            'url' => 'https://hof-halle-wittenberg.github.io/vocabs/organisationalContext/index.json',
            'amb_key' => 'about'
        ],
        'amb_didacticUseCase' => [
            'url' => 'https://hof-halle-wittenberg.github.io/vocabs/didacticUseCase/index.json',
            'amb_key' => 'about'
        ],
        'amb_learningResourceType' => [
            'url' => 'https://skohub.io/dini-ag-kim/hcrt/heads/master/w3id.org/kim/hcrt/scheme.json',
            'amb_key' => 'learningResourceType'
        ],
        'amb_audience' => [
            'url' => 'https://hof-halle-wittenberg.github.io/vocabs/audience/index.json',
            'amb_key' => 'audience'
        ],
        'amb_hochschulfaechersystematik' => [
            'url' => 'https://skohub.io/dini-ag-kim/hochschulfaechersystematik/heads/master/w3id.org/kim/hochschulfaechersystematik/scheme.json',
            'amb_key' => 'about'
        ]        
    ];

    $custom_json_urls = get_option('amb_dido_custom_fields', []);

    $json_urls = $predefined_json_urls;

    foreach ($custom_json_urls as $key => $custom_json_url) {
        $json_urls[$key] = [
            'url' => $custom_json_url['url'],
            'amb_key' => $custom_json_url['key']
        ];
    }

    return $json_urls;
}


function amb_get_all_external_values() {
    $urls = amb_get_json_urls();
    $all_values = [];

    foreach ($urls as $key => $url_data) {
        $values = amb_fetch_external_values($url_data['url'], $key, $url_data['amb_key']);
        if (!empty($values)) {
            $all_values[$key] = $values;
        }
    }

    return $all_values;
}

function amb_fetch_external_values($url, $key, $amb_key) {
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    $field_label = $data['title']['de'] ?? 'Standard-Titel';
    $concepts = $data['hasTopConcept'] ?? [];
    $options = amb_parse_concepts($concepts);

    return [
        'field_label' => $field_label,
        'options' => $options,
        'amb_key' => $amb_key
    ];
}

function amb_parse_concepts($concepts) {
    $options = [];
    foreach ($concepts as $concept) {
        if (isset($concept['id']) && isset($concept['prefLabel']['de'])) {
            $entry = [
                $concept['id'] => $concept['prefLabel']['de']
            ];
            if (isset($concept['narrower'])) {
                $entry['narrower'] = amb_parse_concepts($concept['narrower']);
            }
            $options[] = $entry;
        }
    }
    return $options;
}


// veraltet: Alle Wertelisten abrufen, nur für erste Ebene
function amb_get_all_external_values_broader() {
    $urls = amb_get_json_urls();
    $all_values = [];

    foreach ($urls as $key => $url) {
        $values = amb_get_external_values($key);
        if (!empty($values)) {
            $all_values[$key] = $values;
        }
    }

    return $all_values;
}

// Externe Wertelisten, verallgemeinert für erste Ebene
function amb_get_external_values($key, $field_label = null) {
    $urls = amb_get_json_urls();

    if (!isset($urls[$key])) {
        return []; // Keine Daten, wenn keine URL gefunden wird
    }

    $response = wp_remote_get($urls[$key]);
    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Setzen des Feldetiketts, falls nicht explizit übergeben
    if (is_null($field_label)) {
        $field_label = $data['title']['de'] ?? 'Standard-Titel';
    }

    $concepts = $data['hasTopConcept'] ?? [];
    $options = [];

    foreach ($concepts as $concept) {
        if (isset($concept['id']) && isset($concept['prefLabel']['de'])) {
            $options[] = [$concept['id'] => $concept['prefLabel']['de']];
        }
    }

    return [
        'field_label' => $field_label,
        'options' => $options
    ];
}


// Zugriff auf die Defaults und die entsprechenden Labels aus der Optionen-Konfiguration
function amb_dido_display_defaults($field, $options) {
    $defaults = get_option('amb_dido_defaults');

    // Mapping von field auf field_label
    //$all_fields = amb_get_other_fields();
    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values());
    $field_label = isset($all_fields[$field]['field_label']) ? $all_fields[$field]['field_label'] : $field;

    // Ausgabe der Default-Werte oder "Keine Auswahl"
    if (isset($defaults[$field])) {
        if ($defaults[$field] === 'deactivate') {
          echo "<p>{$field_label}: <strong>Feld ausgeblendet</strong></p>";
        } else {
          $default_value = $defaults[$field];
          $label = 'Unbekannte Auswahl';

          // Überprüfe die Optionen und finde das passende Label
          if (isset($all_fields[$field]['options'])) {
            foreach ($all_fields[$field]['options'] as $option) {
              if (isset($option[$default_value])) {
                $label = $option[$default_value];
                break;
              }
            }
          }

          echo "<p>{$field_label}: <strong>" . esc_html($label) . "</strong></p>";
        }
      } else {
        echo "<p>{$field_label}: <strong>Keine Auswahl getroffen</strong></p>";
      }
}

/**
 * Generiert eine Checkbox-Gruppe mit SVG-Icons basierend auf den gegebenen Parametern.
 *
 * @param string $title Der Titel für die Gruppe.
 * @param string $name Der Name des Feldes im Formular.
 * @param array $options Die Optionen als Array von IDs zu Labels.
 * @param array $stored_values Die gespeicherten Werte für die Checkboxen.
 */

// Zeigt erste und zweite Ebene eines Vokabulars an
function amb_generate_checkbox_group_any($name, $options, $stored_values, $title = null) {
    // Falls kein Titel übergeben wurde, versuchen, den Titel aus den Optionen zu extrahieren
    if ($title === null && isset($options['field_label'])) {
        $title = $options['field_label'];
    }
    
    $svg_check = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>';

    echo '<label class="amb-field">' . esc_html($title) . '</label><br />';
    echo '<div class="grid-container">';
    
    foreach ($options['options'] as $option) {
        foreach ($option as $id => $label) {
            if (!isset($option['narrower'])) {
                // Hauptoption ohne "narrower"-Unteroptionen oder falsche Struktur
                $checked = in_array($id, $stored_values) ? 'checked' : '';
                echo '<div class="grid-item components-base-control__field tbroad">';
                echo '<span class="components-checkbox-control__input-container amb-control">';
                echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($id) . '" ' . $checked . ' id="type_' . esc_attr($id) . '" class="components-checkbox-control__input" onchange="toggleSVG(this)">';
                if ($checked) {
                    echo $svg_check;
                }
                echo '</span>';
                echo '<label for="type_' . esc_attr($id) . '" class="label amb-control-label">' . esc_html($label) . '</label>';
                echo '</div>';
            } elseif (isset($option['narrower']) && !is_array($label)) {
                // Option mit "narrower"-Unteroptionen
                $checked = in_array($id, $stored_values) ? 'checked' : '';
                $collapse_class = $checked ? '' : 'collapsed';
                $expanded = $checked ? 'true' : 'false';

                echo '<div class="grid-item components-base-control__field tnarrow">';
                echo '<span class="components-checkbox-control__input-container amb-control">';
                echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($id) . '" ' . $checked . ' id="type_' . esc_attr($id) . '" class="components-checkbox-control__input" onchange="toggleSVG(this)">';
                if ($checked) {
                    echo $svg_check;
                }
                echo '</span>';
                echo '<label for="type_' . esc_attr($id) . '" class="label amb-control-label">' . esc_html($label) . '</label>';
                
                // Erste Ebene von "narrower"-Unteroptionen anzeigen
                echo '<button type="button" onclick="toggleNarrower(this);" class="amb-narrower ' . $collapse_class . '" aria-expanded="' . $expanded . '"></button>';
                echo '<div class="narrower-container grid-container" style="display: ' . ($checked ? 'block' : 'none') . ';">';

                foreach ($option['narrower'] as $narrower_option) {
                    if (is_array($narrower_option)) {
                        foreach ($narrower_option as $narrower_id => $narrower_label) {
                            if (!is_array($narrower_label)) {
                                $checked = in_array($narrower_id, $stored_values) ? 'checked' : '';
                                echo '<div class="grid-item components-base-control__field tsub">';
                                echo '<span class="components-checkbox-control__input-container amb-control">';
                                echo '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($narrower_id) . '" ' . $checked . ' id="type_' . esc_attr($narrower_id) . '" class="components-checkbox-control__input" onchange="toggleSVG(this)">';
                                if ($checked) {
                                    echo $svg_check;
                                }
                                echo '</span>';
                                echo '<label for="type_' . esc_attr($narrower_id) . '" class="label amb-control-label">' . esc_html($narrower_label) . '</label>';
                                echo '</div>';
                            }
                        }
                    }
                }
                echo '</div>'; // Ende der narrower-container
                echo '</div>'; // Ende der grid-item tnarrow
            }
        }
    }
    
    echo '</div>';
}


/* Hilfsfunktion um ids aus Arrays zu extrahieren */ 
function amb_get_selected_ids($meta_field) {
    $stored_values = get_post_meta(get_the_ID(), $meta_field, true);
    $stored_values = is_array($stored_values) ? $stored_values : [];
    $ids = [];

    foreach ($stored_values as $value) {
        if (isset($value['id'])) {
            $ids[] = $value['id'];
        }
    }

    return $ids;
}


/**
 * Generiert das HTML für die AMB Metabox.
 */
function amb_dido_meta_box_callback($post) {
    // Sicherheit: Einfügen eines Nonce-Feldes für Verifizierung
    wp_nonce_field('amb_dido_save_meta_box_data', 'amb_dido_meta_box_nonce');

    $custom_labels = get_option('amb_dido_custom_labels', array());

    // Aktueller Wert von disable Meta field
    $disable_json_value = get_post_meta($post->ID, 'amb_disable_json_for_entry', true);




    echo '<label for="amb_disable_json_for_entry">' .
    '<input type="checkbox" name="amb_disable_json_for_entry" id="amb_disable_json_for_entry" value="true"' . 
    ( $disable_json_value ? ' checked' : '' ) .
    ' /> JSON Ausgabe unterdrücken' .
    '</label>';


    // Generierung Description
    $description = '';
    $use_excerpt_for_description = get_option('use_excerpt_for_description', 'no');
    if ($use_excerpt_for_description !== 'yes') {
        $description = get_post_meta($post->ID, 'amb_description', true);
        echo '<label for="amb_description" class="amb-field">Beschreibung des Inhalts</label><br />';
        echo '<p class="components-form-token-field__help">In zwei bis drei Sätzen den Inhalt beschreiben.</p>';
        echo '<textarea name="amb_description" class="components-textarea-control__input amb-textarea" rows="4" cols="50">' . esc_textarea($description) . '</textarea><br />';
    }
    

    // Generierung Autoren
    $creator = get_post_meta($post->ID, 'amb_creator', true);
    echo '<label for="amb_creator" class="amb-field">Autor:innen</label><br />';
    echo '<p class="components-form-token-field__help">Namen mit Kommas trennen.</p>';
    echo '<input type="text" name="amb_creator" size="80" value="' . esc_attr($creator) . '" class="amb-textinput" /><br />';


    // Generierung der Checkbox-Felder
    $defaults = get_option('amb_dido_defaults');
    $mapping = get_option('amb_dido_taxonomy_mapping', array());
    $checkbox_options = array_merge(amb_get_other_fields(), amb_get_all_external_values());
    // Zieht die Feldstruktur korrekt mit allen verfügbaren Ebenen

    foreach ($checkbox_options as $field => $data) {
        if (isset($defaults[$field]) && !empty($defaults[$field])) {
            amb_dido_display_defaults($field, $data);
        } elseif (isset($mapping[$field])) {
            // Field is mapped to a taxonomy, don't display it
            continue;
        } else {
            $stored_ids = amb_get_selected_ids($field);  
            $field_label = isset($custom_labels[$field]) && !empty($custom_labels[$field]) ? $custom_labels[$field] : $data['field_label'];
            amb_generate_checkbox_group_any($field, ['field_label' => $field_label, 'options' => $data['options']], $stored_ids);
        }
    }
    
}

/**
 * Speichert die Post-Metadaten.
 */

function amb_dido_save_post_meta($post_id) {
    // Überprüfen der Berechtigungen
    if (!isset($_POST['amb_dido_meta_box_nonce']) || !wp_verify_nonce($_POST['amb_dido_meta_box_nonce'], 'amb_dido_save_meta_box_data')) {
        return;
    }

    // Überprüfen, ob dies ein Autosave ist. Falls ja, abbrechen.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Überprüfen, ob der Benutzer die Berechtigung zum Bearbeiten des Beitrags hat.
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Speicherung der offenen Felder
        $open_fields = [
            'amb_description',
            'amb_creator'
        ];
        foreach ($open_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            } else {
                delete_post_meta($post_id, $field);
            }
        }
        //Speicherung von einzlenen Checkboxes für Ja/Nein zustände
        $boolean_fields = [
            'amb_disable_json_for_entry'
        ];
        foreach ($boolean_fields as $bolfield) {
            // Check and sanitize the input
            $new_value = isset($_POST[$bolfield]) ? 'true' : 'false';
            // Update the meta field in the database
            update_post_meta($post_id, $bolfield, $new_value);
        }

    // Alle Wertelisten speichern
    amb_save_all_checkbox_data($post_id);
}

function amb_save_all_checkbox_data($post_id) {
    // Alle verfügbaren externen Werte abrufen
    // $all_options = amb_get_all_external_values();
    $all_options = array_merge(amb_get_other_fields(), amb_get_all_external_values());

    if (empty($all_options)) {
        return;
    }

    // Rekursive Funktion zur Suche und Hinzufügung von Labels
    function find_label_and_add($type_id, $options, &$to_save) {
        foreach ($options as $option) {
            foreach ($option as $id => $label) {
                if ($id == $type_id && !is_array($label)) {
                    $to_save[] = [
                        'id' => $type_id,
                        'prefLabel' => ['de' => $label],
                        'type' => 'Concept'
                    ];
                    return true;
                } elseif (isset($option['narrower']) && is_array($option['narrower'])) {
                    if (find_label_and_add($type_id, $option['narrower'], $to_save)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    // Durch alle Felder iterieren und die Werte speichern
    foreach ($all_options as $field_key => $field_data) {
        if (isset($_POST[$field_key])) {
            $selected_options = $_POST[$field_key];
            $to_save = [];

            foreach ($selected_options as $type_id) {
                find_label_and_add($type_id, $field_data['options'], $to_save);
            }

            // Prüfen, ob `to_save` nicht leer ist, bevor es gespeichert wird
            if (!empty($to_save)) {
                update_post_meta($post_id, $field_key, $to_save);
            } else {
                delete_post_meta($post_id, $field_key);
            }
        } else {
            delete_post_meta($post_id, $field_key);
        }
    }
}

// creator-Objekte vorbereiten

function amb_generate_creator_objects($post_id) {
    $creators = array_filter(explode(',', get_post_meta($post_id, 'amb_creator', true)), function($value) {
        return trim($value) !== '';
    });

    $creator_objects = [];
    foreach ($creators as $creator) {
        $creator_objects[] = [
            'type' => 'Person',
            'name' => $creator,
        ];
    }
    
    return $creator_objects;
}

function amb_get_keywords($post_id) {
    $override_taxonomy = get_option('override_ambkeyword_taxonomy', '');
    if (!empty($override_taxonomy)) {
        $terms = wp_get_post_terms($post_id, $override_taxonomy, ['fields' => 'names']);
    } else {
        $terms = wp_get_post_terms($post_id, 'ambkeywords', ['fields' => 'names']);
    }
    return is_wp_error($terms) ? [] : $terms;
}

/**
 * Gets the description from excerpt or post meta
 *
 * @param WP_Post $post
 * @return string
 */
function amb_get_description($post) {
    $use_excerpt_for_description = get_option('use_excerpt_for_description', 'no');
    if ($use_excerpt_for_description === 'yes') {
        return get_the_excerpt($post);
    } else {
        return get_post_meta($post->ID, 'amb_description', true);
    }
}

/**
 * Gets the language from post meta
 *
 * @param WP_Post $post
 * @return array
 */
function amb_get_language($post): array {
    $field = 'amb_inLanguage';
    $defaults = get_option('amb_dido_defaults');
    $post_languages = get_post_meta($post->ID, $field, true) ?: $defaults[$field];
    $amb_languages = [];
    
    if(is_array($post_languages)) {
        foreach($post_languages as $lang) {
            if(isset($lang['id'])) {
                $amb_languages[] = $lang['id'];
            } 
        }
    } elseif (is_string($post_languages) && !empty($post_languages)) {
        $amb_languages[] = $post_languages;
    }
    
    return !empty($amb_languages) ? $amb_languages : [''];
}

/**
 * Gets isAccessibleForFree
 *
 * @param WP_Post $post
 * @return bool
 */
function amb_get_isaccessibleforfree($post): bool {
    $field = 'amb_isAccessibleForFree';
    $defaults = get_option('amb_dido_defaults');
    $post_values = get_post_meta($post->ID, $field, true) ?: $defaults[$field];
    $amb_value = true;

    if(is_array($post_values)) {
        if(isset($post_values[0]['id'])) {
            $amb_value = $post_values[0]['id'];
        } 
    } elseif (is_string($post_values) && !empty($post_values)) {
        $amb_value = $post_values;
    }
    return filter_var($amb_value, FILTER_VALIDATE_BOOLEAN);
}

function amb_get_disablejson($post): bool {
    $field = 'amb_disable_json_for_entry';

    $meta_value = get_post_meta($post->ID, $field, true);

    return filter_var($meta_value, FILTER_VALIDATE_BOOLEAN);
}

/**
 * Bindet Custom-Fields in das JSON-LD-Format ein.
 */
function amb_dido_add_json_ld_to_header() {
    if (is_singular(get_option('amb_dido_post_types', []))) {
        global $post;

        // Voreinstellungen aus Optionseite rufen
        $defaults = get_option('amb_dido_defaults');
        $json_disabled = amb_get_disablejson($post);
        if(!$json_disabled){
        // Mit Taxonomien überbrückte Felder rufen 
        $mapping = get_option('amb_dido_taxonomy_mapping', array());

        // Alle Felder (hartkodiert und extern) abrufen
        $all_options = array_merge(amb_get_other_fields(), amb_get_all_external_values());

        // JSON Elemente zusammenstellen
        $json_ld_data = [
            "@context" => ["https://w3id.org/kim/amb/context.jsonld", "https://schema.org", ["@language" => "de"]],
            "id" => get_permalink($post->ID),
            "dateCreated" => get_the_date('c', $post),
            "datePublished" => get_the_date('c', $post),
            "dateModified" => get_the_modified_date('c', $post),
            "publisher" => [["type" => "Organization", "name" => get_bloginfo('name')]],
            "creator" => amb_generate_creator_objects($post->ID),
            "name" => get_the_title($post),
            "description" => amb_get_description($post),
            "inLanguage" => amb_get_language($post),
            "isAccessibleForFree" => amb_get_isaccessibleforfree($post),
        ];

        // Keywords auslesen
        $keywords = amb_get_keywords($post->ID) ?: '';
        if(!empty($keywords)) $json_ld_data['keywords'] = $keywords;

        // Thumbnail
        $image = get_the_post_thumbnail_url($post, 'full');
        if($image !== false) $json_ld_data['image'] = $image;

        foreach ($all_options as $field => $data) {
            // Skip already handled fields
            if(in_array($field, ['amb_inLanguage','amb_isAccessibleForFree'])) {
                continue;
            } 

            if (isset($mapping[$field])) {
                // Field is mapped to a taxonomy
                $terms = wp_get_post_terms($post->ID, $mapping[$field], array('fields' => 'all'));
                $value = array();
                foreach ($terms as $term) {
                    $value[] = array(
                        'id' => $term->slug,
                        'prefLabel' => array('de' => $term->name),
                        'type' => 'Concept'
                    );
                }
            } else {
                $value = get_post_meta($post->ID, $field, true);
                if (empty($value)) {
                    $value = $defaults[$field] ?? null;
                }
            }

            $amb_key = $data['amb_key'] ?? 'about';

            if (!is_null($value) && !empty($value)) {
                $formatted_value = is_array($value) ? array_map(function ($item) use ($data) {
                    return [
                        'id' => $item['id'],
                        'prefLabel' => ['de' => $item['prefLabel']['de']],
                        'type' => 'Concept'
                    ];
                }, $value) : [
                    'id' => $value,
                    'prefLabel' => ['de' => $data['options'][0][$value]],
                    'type' => 'Concept'
                ];

                if(in_array($amb_key, ['license', 'conditionsOfAccess', 'interactivityType'])) {
                    $formatted_value = $formatted_value[0] ?? $formatted_value;
                }

                if (isset($json_ld_data[$amb_key])) {
                    $json_ld_data[$amb_key] = array_merge($json_ld_data[$amb_key], $formatted_value);
                } else {
                    $json_ld_data[$amb_key] = $formatted_value;
                }
            }
        }

        // JSON-LD-Ausgabe im Header
        echo '<script type="application/ld+json">' . json_encode($json_ld_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        }
    }
}



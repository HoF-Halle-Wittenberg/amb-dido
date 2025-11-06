<?php

/**
 *  OPTIONEN
 * --------------
 */

/**
 * Erstellt eine Optionsseite für das Plugin.
 */
function amb_dido_create_settings_page() {
    add_options_page(
        'AMB-DidO Einstellungen',
        'AMB-DidO Einstellungen',
        'manage_options',
        'amb_dido',
        'amb_dido_settings_page'
    );
}

/**
 * Ausgabe der Optionsseite.
 */
function amb_dido_settings_page() {
    // Holen Sie sich die Plugin-Daten, einschließlich der Version
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/amb-dido/amb-dido.php');
    $version = $plugin_data['Version'];
    ?>
    <div class="wrap">
        <h1>AMB-DidO Einstellungen <span class="version">Version <?php echo esc_html($version); ?></span></h1>
        <h2 class="nav-tab-wrapper">
            <?php
            $sections = [
                'amb_dido_main_section' => 'Allgemeine Einstellungen',
                'amb_dido_cache_section' => 'Cache-Verwaltung',
                'amb_dido_taxonomy_section' => 'Taxonomie-Zuordnung',
                'amb_dido_default_section' => 'Voreinstellungen',
                'amb_dido_metadata_section' => 'Frontend-Anzeige',
                'amb_dido_custom_fields_section' => 'Benutzerdefinierte Felder'
            ];
            foreach ($sections as $section_id => $section_title) {
                $active = $section_id === 'amb_dido_main_section' ? 'nav-tab-active' : '';
                echo "<a class='nav-tab $active' href='#$section_id'>$section_title</a>";
            }
            ?>
        </h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('amb_dido_settings_group');
            foreach ($sections as $section_id => $section_title) {
                $style = $section_id === 'amb_dido_main_section' ? '' : 'style="display:none;"';
                echo "<div id='$section_id-content' class='amb-dido-section-content' $style>";
                do_settings_sections($section_id);
                echo "</div>";
            }
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Registriert die Einstellungen und Sektionen.
 */
function amb_dido_register_settings() {
    // Einstellungen registrieren
    register_setting('amb_dido_settings_group', 'amb_dido_post_types', 'amb_dido_sanitize_post_types');
    register_setting('amb_dido_settings_group', 'amb_dido_taxonomy_mapping', 'amb_dido_sanitize_taxonomy_mapping');
    register_setting('amb_dido_settings_group', 'amb_dido_defaults', 'amb_dido_sanitize_defaults');
    register_setting('amb_dido_settings_group', 'amb_dido_custom_labels', 'amb_dido_sanitize_custom_labels');
    register_setting('amb_dido_settings_group', 'amb_dido_metadata_display_options', 'amb_dido_sanitize_options');
    register_setting('amb_dido_settings_group', 'amb_dido_custom_fields', 'amb_dido_sanitize_custom_fields');
    register_setting('amb_dido_settings_group', 'override_ambkeyword_taxonomy', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting('amb_dido_settings_group', 'show_ambkeywords_in_menu', [
        'default' => 'yes',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting('amb_dido_settings_group', 'use_excerpt_for_description', [
        'default' => 'no',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    // Cache-Einstellungen hinzufügen
    register_setting('amb_dido_settings_group', 'amb_cache_mode', [
        'default' => 'auto',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting('amb_dido_settings_group', 'amb_storage_mode', [
        'default' => 'hybrid',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    // Hauptsektion
    add_settings_section('amb_dido_main_section', '', null, 'amb_dido_main_section');
    add_settings_field('amb_dido_post_types_field', 'Aktivierte Post-Typen', 'amb_dido_post_types_field_html', 'amb_dido_main_section', 'amb_dido_main_section');
    add_settings_field('override_ambkeyword_taxonomy', 'AMB Keywords Taxonomie überschreiben', 'render_override_ambkeyword_taxonomy_field', 'amb_dido_main_section', 'amb_dido_main_section');
    add_settings_field('show_ambkeywords_in_menu', 'AMB Keywords im Backend-Menü anzeigen', 'render_show_ambkeywords_in_menu_field', 'amb_dido_main_section', 'amb_dido_main_section');
    add_settings_field('use_excerpt_for_description', 'Textauszug (Exzerpt) für Beschreibung verwenden', 'render_use_excerpt_for_description_field', 'amb_dido_main_section', 'amb_dido_main_section');

    // Cache-Sektion
    add_settings_section('amb_dido_cache_section', '', 'amb_dido_cache_section_callback', 'amb_dido_cache_section');
    
    add_settings_field(
        'amb_storage_mode',
        'Speicher-Modus',
        'amb_dido_storage_mode_callback',
        'amb_dido_cache_section',
        'amb_dido_cache_section'
    );
    
    add_settings_field(
        'amb_cache_mode',
        'Cache-Modus',
        'amb_dido_cache_mode_callback',
        'amb_dido_cache_section',
        'amb_dido_cache_section'
    );
    
    add_settings_field(
        'amb_dido_cache_management',
        'Cache-Verwaltung',
        'amb_dido_cache_management_callback',
        'amb_dido_cache_section',
        'amb_dido_cache_section'
    );

    // Taxonomie-Sektion
    add_settings_section('amb_dido_taxonomy_section', '', 'amb_dido_taxonomy_section_callback', 'amb_dido_taxonomy_section');
    add_settings_field('amb_dido_taxonomy_mapping', '', 'amb_dido_taxonomy_mapping_callback', 'amb_dido_taxonomy_section', 'amb_dido_taxonomy_section');

    // Default-Sektion
    add_settings_section('amb_dido_default_section', '', 'amb_dido_default_section_description', 'amb_dido_default_section');
    
    // SICHERER Ansatz: Nur lokale Felder verwenden für Admin-Setup
    $all_fields = amb_get_other_fields(); // Erstmal nur lokale Felder
    
    // Externe Felder nur hinzufügen wenn Funktion verfügbar UND wir uns im korrekten Context befinden
    if (function_exists('amb_get_all_external_values_with_mode') && !wp_doing_ajax() && is_admin()) {
        try {
            $external_fields = amb_get_all_external_values_with_mode();
            if (is_array($external_fields) && !empty($external_fields)) {
                $all_fields = array_merge($all_fields, $external_fields);
            }
        } catch (Exception $e) {
            // Bei Fehlern einfach ohne externe Felder weitermachen
            error_log('AMB-DidO: Externe Felder konnten nicht geladen werden: ' . $e->getMessage());
        }
    }
    
    // Sicherheitsprüfung
    if (!is_array($all_fields) || empty($all_fields)) {
        $all_fields = amb_get_other_fields();
    }

    // Default-Sektion Felder hinzufügen
    foreach ($all_fields as $key => $value) {
        if (isset($value['field_label']) && isset($value['options'])) {
            add_settings_field($key, $value['field_label'], 'amb_dido_default_field_callback', 'amb_dido_default_section', 'amb_dido_default_section', ['id' => $key, 'options' => $value['options']]);
        }
    }

    // Metadata-Sektion
    add_settings_section('amb_dido_metadata_section', '', 'amb_dido_metadata_section_callback', 'amb_dido_metadata_section');
    foreach ($all_fields as $key => $info) {
        if (isset($info['field_label'])) {
            add_settings_field($key, $info['field_label'], 'amb_dido_checkbox_field_callback', 'amb_dido_metadata_section', 'amb_dido_metadata_section', ['id' => $key]);
        }
    }

    // Custom Fields Sektion
    add_settings_section('amb_dido_custom_fields_section', '', 'amb_dido_custom_fields_section_callback', 'amb_dido_custom_fields_section');
    add_settings_field(
        'amb_dido_custom_fields_field',
        'Benutzerdefinierte Wertelisten',
        'amb_dido_custom_fields_field_callback',
        'amb_dido_custom_fields_section',
        'amb_dido_custom_fields_section'
    );
}

function amb_dido_storage_mode_callback() {
    $mode = get_option('amb_storage_mode', 'local');
    echo '<select name="amb_storage_mode">';
    echo '<option value="hybrid"' . selected($mode, 'hybrid', false) . '>Hybrid (empfohlen)</option>';
    echo '<option value="local"' . selected($mode, 'local', false) . '>Nur lokale Dateien</option>';
    echo '<option value="external"' . selected($mode, 'external', false) . '>Nur externe Quellen</option>';
    echo '</select>';
    echo '<p class="description">';
    echo '<strong>Hybrid:</strong> Externe Quellen mit lokalem Fallback<br>';
    echo '<strong>Lokal:</strong> Verwendet nur lokale Vokabular-Dateien<br>';
    echo '<strong>Extern:</strong> Verwendet nur externe Quellen (alter Modus)';
    echo '</p>';
}

function amb_dido_metadata_section_callback() {
    echo '<p>Wählen Sie die Metadatenfelder, die im Frontend angezeigt werden sollen.</p>';
    echo '<p>Alternativ können Metadatenfelder im Editor per Shortcodes aufgerufen werden: <span class="amb-code">[show_amb_metadata field="amb_audience"]</span> oder <span class="amb-code">[show_amb_metadata]</span> für alle aktivierten Felder.</p>';
    echo '<p>Sie können auch beliebige Felder in Ihrem Theme mit <span class="amb-code">show_amb_metadata("NAME_DES_FELDS")</span> aufrufen.</p>';
    echo '<p>Folgende Felder können Sie dafür verwenden:</p>';

    if (function_exists('amb_get_all_external_values')) {
        $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values());
    } else {
        $all_fields = amb_get_other_fields();
    }
    foreach ($all_fields as $field => $data) {
        echo $all_fields[$field]['field_label'] . ": <span class='amb-code'>" . $field . "</span> | ";
    }
}

function amb_dido_checkbox_field_callback($args) {
    $options = get_option('amb_dido_metadata_display_options');
    $checked = isset($options[$args['id']]) ? checked(1, $options[$args['id']], false) : '';
    echo '<input type="checkbox" id="'. esc_attr($args['id']) .'" name="amb_dido_metadata_display_options['. esc_attr($args['id']) .']" value="1" '. $checked .' />';
}

function amb_dido_sanitize_options($input) {
    // Sichere Prüfung ob $input ein Array ist
    if (!is_array($input)) {
        return array();
    }
    
    $new_input = array();
    foreach($input as $key => $value) {
        if (isset($input[$key])) {
            $new_input[$key] = $value ? 1 : 0;
        }
    }
    return $new_input;
}

function amb_dido_default_section_description() {
    echo '<p>Die Voreinstellungen hier vornehmen, wenn sie für alle Ressourcen gesetzt werden sollen. Diese Felder werden dann im Editor nicht mehr angezeigt. Sie können Felder auch ausblenden, ohne einen Standardwert zu setzen.</p>';
}

/**
 * Defaults-Renderer: Radio-Buttons für Single-Value, Checkboxen für Multi-Value.
 * Spezieller Wert "deactivate" blendet das Feld im Editor aus.
 */
function amb_dido_default_field_callback($args) {
    $options = get_option('amb_dido_defaults', []);
    $custom_labels = get_option('amb_dido_custom_labels', array());
    $id = $args['id'];
    $opts = $args['options'];

    // Check if this is a single-value field
    $is_single_value = function_exists('amb_is_single_value_field') ? amb_is_single_value_field($id) : false;

    echo '<div class="amb-default-field-row">';
    echo '<div class="amb-default-field-dropdown">';

    if ($is_single_value) {
        // Single-value field: Radio buttons
        $stored = isset($options[$id]) ? $options[$id] : '';

        // Normalisieren für Single-Value
        if (is_array($stored)) {
            if (in_array('deactivate', $stored, true)) {
                $stored = 'deactivate';
            } elseif (!empty($stored)) {
                $stored = $stored[0];
            } else {
                $stored = '';
            }
        }

        // "Feld ausblenden" Option
        echo '<label style="display:block;margin-bottom:6px;">';
        echo '<input type="radio" name="amb_dido_defaults[' . esc_attr($id) . ']" value="deactivate" ' . checked($stored, 'deactivate', false) . ' />';
        echo ' Feld ausblenden';
        echo '</label>';

        // "Keine Vorauswahl" Option
        echo '<label style="display:block;margin-bottom:6px;">';
        echo '<input type="radio" name="amb_dido_defaults[' . esc_attr($id) . ']" value="" ' . checked($stored, '', false) . ' />';
        echo ' Keine Vorauswahl';
        echo '</label>';

        // Radio-Optionen
        foreach ($opts as $option_array) {
            foreach ($option_array as $value => $label) {
                if (is_array($label)) {
                    continue;
                }
                $checked_val = ($stored === $value);
                echo '<label style="display:block;">';
                echo '<input type="radio" name="amb_dido_defaults[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" ' . checked($checked_val, true, false) . ' />';
                echo ' ' . esc_html($label);
                echo '</label>';
            }
        }
        echo '<p class="description">Einzelauswahl möglich. Wenn "Feld ausblenden" aktiv ist, wird das Feld im Editor nicht angezeigt.</p>';

    } else {
        // Multi-value field: Checkboxes (original code)
        $stored = isset($options[$id]) ? $options[$id] : [];
        if ($stored === 'deactivate') {
            $stored = ['deactivate'];
        } elseif (!is_array($stored)) {
            $stored = $stored !== '' ? [$stored] : [];
        }

        // "Feld ausblenden" (deactivate) exklusiv zu anderen Werten
        echo '<label style="display:block;margin-bottom:6px;">';
        echo '<input type="checkbox" name="amb_dido_defaults[' . esc_attr($id) . '][]" value="deactivate" ' . checked(in_array('deactivate', $stored, true), true, false) . ' />';
        echo ' Feld ausblenden';
        echo '</label>';

        // Top-Level-Optionen als Checkboxen
        foreach ($opts as $option_array) {
            foreach ($option_array as $value => $label) {
                if (is_array($label)) {
                    continue; // verschachtelte Strukturen hier nicht anbieten
                }
                $checked = in_array($value, $stored, true);
                echo '<label style="display:block;">';
                echo '<input type="checkbox" name="amb_dido_defaults[' . esc_attr($id) . '][]" value="' . esc_attr($value) . '" ' . checked($checked, true, false) . ' />';
                echo ' ' . esc_html($label);
                echo '</label>';
            }
        }
        echo '<p class="description">Mehrere Werte möglich. Wenn "Feld ausblenden" aktiv ist, werden andere Auswahl(en) ignoriert.</p>';
    }

    echo '</div>';

    echo '<div class="amb-default-field-label">';
    $custom_label = isset($custom_labels[$id]) ? $custom_labels[$id] : '';
    echo "<input type='text' name='amb_dido_custom_labels[" . esc_attr($id) . "]' value='" . esc_attr($custom_label) . "' placeholder='Benutzerdefiniertes Label'>";
    echo '</div>';

    echo '</div>';
}

function amb_dido_sanitize_custom_labels($input) {
    // Sichere Prüfung
    if (!is_array($input)) {
        return array();
    }
    
    $sanitized_input = array();
    foreach ($input as $key => $value) {
        $sanitized_input[$key] = sanitize_text_field($value);
    }
    return $sanitized_input;
}

/**
 * Sanitize für Defaults: Arrays (Mehrfachauswahl) werden unterstützt.
 * 'deactivate' bleibt als String erhalten und ist exklusiv.
 */
function amb_dido_sanitize_defaults($input) {
    // Prüfen ob $input ein Array ist
    if (!is_array($input)) {
        return array();
    }
    
    $sanitized_input = array();
    foreach ($input as $key => $value) {
        if (is_array($value)) {
            // Leere Einträge entfernen
            $value = array_values(array_filter($value, function ($v) {
                return $v !== '' && $v !== null;
            }));

            // Wenn 'deactivate' gewählt wurde, nur das speichern
            if (in_array('deactivate', $value, true)) {
                $sanitized_input[$key] = 'deactivate';
                continue;
            }

            // Ansonsten als Array von IDs speichern
            $sanitized_input[$key] = array_map('sanitize_text_field', $value);
        } else {
            // Rückwärtskompatibilität (alter Single-Select)
            if (!isset($value) || $value === '') {
                $sanitized_input[$key] = '';
            } elseif ($value === 'deactivate') {
                $sanitized_input[$key] = 'deactivate';
            } else {
                $sanitized_input[$key] = sanitize_text_field($value);
            }
        }
    }
    return $sanitized_input;
}

function amb_dido_post_types_field_html() {
    $selected_post_types = get_option('amb_dido_post_types', []);
    $all_post_types = get_post_types(['public' => true], 'objects');

    foreach ($all_post_types as $post_type) {
        $is_checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
        echo '<input type="checkbox" name="amb_dido_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $is_checked . '> ' . esc_html($post_type->label) . '<br>';
    }
}

function amb_dido_sanitize_post_types($input) {
    $valid_post_types = get_post_types(['public' => true]);
    return array_intersect($valid_post_types, $input);
}

function amb_dido_custom_fields_section_callback() {
    echo '<p>Fügen Sie hier benutzerdefinierte Wertelisten hinzu, indem Sie eine URL und ein AMB-Attribut angeben.</p>';
}

function amb_dido_custom_fields_field_callback() {
    $options = get_option('amb_dido_custom_fields', []);

    echo '<div class="amb_dido_custom_fields_header">';
    echo '<span>URL der Werteliste</span>';
    echo '<span>Angewendetes Attribut</span>';
    echo '<span></span>';
    echo '</div>';
    echo '<div id="amb_dido_custom_fields_wrapper">';
    echo '<div id="amb_dido_custom_fields_container">';
    foreach ($options as $custom_field) {
        amb_dido_render_custom_field($custom_field['url'], $custom_field['key'], substr($custom_field['meta_key'], 10));
    }
    echo '</div>';
    echo '<button type="button" id="amb_dido_add_custom_field" class="button">Mehr hinzufügen</button>';
    echo '</div>';
}

function amb_dido_render_custom_field($url, $key, $counter) {
    $amb_keys = ['about', 'teaches', 'assesses', 'audience', 'interactivityType', 'competencyRequired', 'educationalLevel'];
    $meta_key = 'amb_custom' . $counter;

    echo '<div class="amb_dido_custom_field_container">';
    
    echo '<div class="amb_dido_custom_field_url">';
    echo '<input type="url" name="amb_dido_custom_fields[' . esc_attr($meta_key) . '][url]" value="' . esc_attr($url) . '" placeholder="JSON-URL der Wertliste" />';
    echo '</div>';
    
    echo '<div class="amb_dido_custom_field_key">';
    echo '<select name="amb_dido_custom_fields[' . esc_attr($meta_key) . '][key]">';
    foreach ($amb_keys as $amb_key) {
        $selected = ($key === $amb_key) ? 'selected' : '';
        echo '<option value="' . esc_attr($amb_key) . '" ' . $selected . '>' . esc_html($amb_key) . '</option>';
    }
    echo '</select>';
    echo '</div>';
    
    echo '<div class="amb_dido_custom_field_remove">';
    echo '<button type="button" class="button remove-custom-field">Entfernen</button>';
    echo '</div>';
    
    echo '<input type="hidden" name="amb_dido_custom_fields[' . esc_attr($meta_key) . '][meta_key]" value="' . esc_attr($meta_key) . '" />';
    
    echo '</div>';
}

function amb_dido_sanitize_custom_fields($input) {
    // Sichere Prüfung ob $input ein Array ist
    if (!is_array($input)) {
        return array();
    }
    
    $sanitized_input = array();
    foreach ($input as $custom_field) {
        // Zusätzliche Prüfung ob $custom_field ein Array ist
        if (!is_array($custom_field)) {
            continue;
        }
        
        // Sichere Zugriffe mit isset()
        $url = isset($custom_field['url']) ? $custom_field['url'] : '';
        $key = isset($custom_field['key']) ? $custom_field['key'] : '';
        $meta_key = isset($custom_field['meta_key']) ? $custom_field['meta_key'] : '';
        
        $sanitized_url = esc_url_raw($url);
        $sanitized_key = in_array($key, ['about', 'teaches', 'assesses', 'audience', 'interactivityType']) ? $key : '';

        if (!empty($sanitized_url) && !empty($sanitized_key) && !empty($meta_key)) {
            $sanitized_input[$meta_key] = [
                'url' => $sanitized_url,
                'key' => $sanitized_key,
                'meta_key' => $meta_key,
            ];
        }
    }

    return $sanitized_input;
}


/**
 * Funktion zum Überbrücken der Metafelder mit vorhandenen Wordpress-Taxonomien.
 */

// Callback for the new section
function amb_dido_taxonomy_section_callback() {
    echo '<p>Wenn Sie statt der eingebauten Metafelder bereits Kategorien, Tags oder andere Taxonomien eingerichtet haben und diese für die Metadaten nutzen möchten, können Sie diese hier aktivieren.</p>';
    echo '<p>Bitte beachten Sie dabei folgendes: <br>1.) Ihre Taxonomie sollte kanonisch mit vorhandenen Schemata sein, d.h. die gleichen Werte und idealerweise Wertelabels nutzen. Ggf. sind dafür Anpassungen von "slug" (Wert) und "name" (Label) notwendig, um Gleichheit der Werte sicherzustellen. <br> 2.) Schemata sind feste Wertelisten, bitte vermeiden Sie hinzufügen neuer Kategorien, die nicht Teil des Schemas sind. Diese sind dann nicht nämlich nicht kanonisch und damit nicht interoperabel. <br> 3.) Eigene Taxonomien sind für die Darstellung und Funktionalität der Webseite zwar sehr praktisch, hier führt deren Nutzung aber dazu, dass die gesetzten Werte nicht auf publizierte Wertelisten verlinken. Daher bitte Punkt 1 und 2 beherzigen.</p>';
}

function amb_dido_taxonomy_mapping_callback() {
    // Sichere Initialisierung
    $all_fields = amb_get_other_fields();
    
    if (function_exists('amb_get_all_external_values')) {
        try {
            $external_fields = amb_get_all_external_values();
            if (is_array($external_fields)) {
                $all_fields = array_merge($all_fields, $external_fields);
            }
        } catch (Exception $e) {
            // Fehler ignorieren, mit lokalen Feldern fortfahren
        }
    }
    
    $taxonomies = get_taxonomies(array('public' => true), 'objects');
    $mapping = get_option('amb_dido_taxonomy_mapping', array());

    if (is_array($all_fields)) {
        foreach ($all_fields as $field_key => $field_data) {
            if (isset($field_data['field_label'])) {
                echo '<tr>';
                echo '<th scope="row">' . esc_html($field_data['field_label']) . '</th>';
                echo '<td>';
                echo '<select name="amb_dido_taxonomy_mapping[' . esc_attr($field_key) . ']">';
                echo '<option value="">--Keine Auswahl--</option>';
                foreach ($taxonomies as $taxonomy) {
                    $selected = (isset($mapping[$field_key]) && $mapping[$field_key] === $taxonomy->name) ? 'selected' : '';
                    echo '<option value="' . esc_attr($taxonomy->name) . '" ' . $selected . '>' . esc_html($taxonomy->label) . '</option>';
                }
                echo '</select>';
                echo '</td>';
                echo '</tr>';
            }
        }
    }
}

function amb_dido_sanitize_taxonomy_mapping($input) {
    // Sichere Prüfung
    if (!is_array($input)) {
        return array();
    }
    
    $sanitized_input = array();
    foreach ($input as $field_key => $taxonomy) {
        if (!empty($taxonomy)) {
            $sanitized_input[$field_key] = sanitize_text_field($taxonomy);
        }
    }
    return $sanitized_input;
}

function render_override_ambkeyword_taxonomy_field() {
    $options = get_option('override_ambkeyword_taxonomy');
    $taxonomies = get_taxonomies(array('public' => true), 'objects');

    echo '<select name="override_ambkeyword_taxonomy">';
    echo '<option value="">--Keine Auswahl--</option>';
    foreach ($taxonomies as $taxonomy) {
        $selected = (isset($options) && $options === $taxonomy->name) ? 'selected' : '';
            echo '<option value="' . esc_attr($taxonomy->name) . '" ' . $selected . '>' . esc_html($taxonomy->label) . '</option>';
    }
    echo '</select>';
}

function render_show_ambkeywords_in_menu_field() {
    $option = get_option('show_ambkeywords_in_menu', 'yes');
    echo '<input type="radio" name="show_ambkeywords_in_menu" value="yes" ' . checked('yes', $option, false) . '> Ja ';
    echo '<input type="radio" name="show_ambkeywords_in_menu" value="no" ' . checked('no', $option, false) . '> Nein';
}

function render_use_excerpt_for_description_field() {
    $option = get_option('use_excerpt_for_description', 'no');
    echo '<input type="radio" name="use_excerpt_for_description" value="no" ' . checked('no', $option, false) . '> Nein ';
    echo '<input type="radio" name="use_excerpt_for_description" value="yes" ' . checked('yes', $option, false) . '> Ja';
}

function amb_dido_enqueue_admin_scripts($hook) {
    if ('settings_page_amb_dido' !== $hook) {
        return;
    }
    wp_enqueue_script('amb-dido-admin-js', plugins_url('scripts.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'amb_dido_enqueue_admin_scripts');

function amb_dido_custom_fields_js() {
    ?>
    <script>
        (function($) {
            var counter = <?php echo count(get_option('amb_dido_custom_fields', [])) + 1; ?>;

            function amb_dido_render_custom_field(url, key, counter) {
                var ambKeys = ['about', 'teaches', 'assesses', 'audience', 'interactivityType', 'competencyRequired', 'educationalLevel'];
                var metaKey = 'amb_custom' + counter;

                var $container = $('<div>', { 'class': 'amb_dido_custom_field_container' });

                var $urlDiv = $('<div>', { 'class': 'amb_dido_custom_field_url' });
                var $urlInput = $('<input>', {
                    type: 'url',
                    name: 'amb_dido_custom_fields[' + metaKey + '][url]',
                    value: url,
                    placeholder: 'JSON-URL der Wertliste'
                });
                $urlDiv.append($urlInput);

                var $keyDiv = $('<div>', { 'class': 'amb_dido_custom_field_key' });
                var $keySelect = $('<select>', { name: 'amb_dido_custom_fields[' + metaKey + '][key]' });
                $.each(ambKeys, function(index, ambKey) {
                    var $option = $('<option>', { value: ambKey, text: ambKey });
                    if (ambKey === key) {
                        $option.attr('selected', 'selected');
                    }
                    $keySelect.append($option);
                });
                $keyDiv.append($keySelect);

                var $removeDiv = $('<div>', { 'class': 'amb_dido_custom_field_remove' });
                var $removeButton = $('<button>', {
                    type: 'button',
                    class: 'button remove-custom-field',
                    text: 'Entfernen'
                });
                $removeDiv.append($removeButton);

                var $metaKeyInput = $('<input>', {
                    type: 'hidden',
                    name: 'amb_dido_custom_fields[' + metaKey + '][meta_key]',
                    value: metaKey
                });

                $container.append($urlDiv, $keyDiv, $removeDiv, $metaKeyInput);

                return $container;
            }

            $('#amb_dido_add_custom_field').on('click', function() {
                var newField = amb_dido_render_custom_field('', '', counter);
                $('#amb_dido_custom_fields_container').append(newField);
                counter++;
            });

            $(document).on('click', '.remove-custom-field', function() {
                $(this).closest('.amb_dido_custom_field_container').remove();
            });

            // UI-Helfer: "Feld ausblenden" exklusiv zu anderen Checkboxen im Defaults-Bereich
            document.addEventListener('change', function(e){
              var input = e.target;
              if (input.name && input.name.startsWith('amb_dido_defaults[') && input.type === 'checkbox') {
                var container = input.closest('.amb-default-field-row');
                if (!container) return;
                var deactivate = container.querySelector('input[value="deactivate"]');
                var valueBoxes = container.querySelectorAll('input[type="checkbox"]:not([value="deactivate"])');

                if (deactivate && input === deactivate) {
                  if (deactivate.checked) {
                    valueBoxes.forEach(function(cb){ cb.checked = false; });
                  }
                } else if (deactivate && input.value !== 'deactivate' && input.checked) {
                  deactivate.checked = false;
                }
              }
            });
        })(jQuery);
    </script>
    <?php
}
add_action('admin_footer', 'amb_dido_custom_fields_js');

// Hook to add the settings page
add_action('admin_menu', 'amb_dido_create_settings_page');

// Hook to register settings
add_action('admin_init', 'amb_dido_register_settings');


/**
 * Cache-Verwaltung zur Optionsseite hinzufügen
 */
function amb_dido_register_cache_settings() {
    // Neue Sektion für Cache-Verwaltung
    add_settings_section(
        'amb_dido_cache_section', 
        '', 
        'amb_dido_cache_section_callback', 
        'amb_dido_cache_section'
    );
    
    add_settings_field(
        'amb_dido_cache_management',
        'Cache-Verwaltung',
        'amb_dido_cache_management_callback',
        'amb_dido_cache_section',
        'amb_dido_cache_section'
    );
    
    // Cache-Modus Setting
    register_setting('amb_dido_settings_group', 'amb_cache_mode', [
        'default' => 'auto',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    
    add_settings_field(
        'amb_cache_mode',
        'Cache-Modus',
        'amb_dido_cache_mode_callback',
        'amb_dido_cache_section',
        'amb_dido_cache_section'
    );
}

/**
 * Cache-Sektion Callbacks
 */
function amb_dido_cache_section_callback() {
    echo '<p>Verwalten Sie hier den Cache für externe Wertelisten und lokale Vokabulare. Der Cache verbessert die Performance erheblich.</p>';
    
    // Cache-Status anzeigen
    $cache = get_transient('amb_external_values_cache');
    $cache_status = $cache ? 'Aktiv' : 'Leer';
    
    echo "<p><strong>Cache-Status:</strong> $cache_status</p>";
    if ($cache) {
        $timeout = get_option('_transient_timeout_amb_external_values_cache', 0);
        if ($timeout > 0) {
            $expires = date('d.m.Y H:i', $timeout);
            echo "<p><strong>Läuft ab:</strong> $expires</p>";
        }
    }
    
    // Backup-Status
    $backup_count = 0;
    $urls = amb_get_json_urls();
    foreach ($urls as $key => $url_data) {
        if (get_option('amb_local_backup_' . $key)) {
            $backup_count++;
        }
    }
    echo "<p><strong>Lokale Backups:</strong> $backup_count von " . count($urls) . " verfügbar</p>";
    
    // Vokabular-Manager Status
    if (function_exists('amb_vocabularies_manager')) {
        $manager = amb_vocabularies_manager();
        $status = $manager->get_all_vocabulary_status();
        $local_count = 0;
        foreach ($status as $vocab_status) {
            if ($vocab_status['exists']) {
                $local_count++;
            }
        }
        echo "<p><strong>Lokale Vokabular-Dateien:</strong> $local_count von " . count($status) . " verfügbar</p>";
    }
}

/**
 * AJAX-basierte Cache-Management Callback
 */
function amb_dido_cache_management_callback() {
    // Nonce für AJAX-Sicherheit
    $nonce = wp_create_nonce('amb_cache_nonce');
    
    echo '<div style="margin-bottom: 15px;">';
    echo '<h4>Cache-Aktionen</h4>';
    echo '<button type="button" id="amb-refresh-cache" class="button-primary" data-nonce="' . $nonce . '">Cache aktualisieren</button> ';
    echo '<button type="button" id="amb-clear-cache" class="button-secondary" data-nonce="' . $nonce . '">Cache leeren</button>';
    echo '<p class="description">Cache aktualisieren lädt alle externen Listen neu. Cache leeren erzwingt Neuladen beim nächsten Zugriff.</p>';
    echo '</div>';
    
    echo '<div>';
    echo '<h4>Lokale Vokabulare</h4>';
    echo '<button type="button" id="amb-download-vocab" class="button-secondary" data-nonce="' . $nonce . '">Alle Vokabulare herunterladen</button>';
    echo '<p class="description">Lädt alle Vokabulare als lokale Dateien herunter für bessere Performance und Offline-Verfügbarkeit.</p>';
    echo '</div>';
    
    echo '<div id="amb-cache-message" style="margin-top: 15px;"></div>';
    
    // Status-Informationen
    echo '<div style="margin-top: 20px; padding: 10px; background: #f1f1f1; border-radius: 3px;">';
    echo '<h4>Aktueller Status</h4>';
    
    $last_refresh = get_option('amb_last_cache_refresh', 0);
    if ($last_refresh) {
        echo '<p><strong>Letzter Cache-Refresh:</strong> ' . date('d.m.Y H:i:s', $last_refresh) . '</p>';
    }
    
    $storage_mode = get_option('amb_storage_mode', 'hybrid');
    $cache_mode = get_option('amb_cache_mode', 'auto');
    echo '<p><strong>Aktueller Modus:</strong> ' . ucfirst($storage_mode) . ' / ' . ucfirst($cache_mode) . '</p>';
    echo '</div>';
    
    // JavaScript für AJAX-Aufrufe
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        function showMessage(message, isSuccess) {
            var messageDiv = $('#amb-cache-message');
            var className = isSuccess ? 'notice-success' : 'notice-error';
            messageDiv.html('<div class="notice ' + className + ' inline"><p>' + message + '</p></div>');
            
            // Nach 5 Sekunden ausblenden
            setTimeout(function() {
                messageDiv.fadeOut();
            }, 5000);
        }
        
        function performCacheAction(action, button) {
            var nonce = button.data('nonce');
            var originalText = button.text();
            
            button.prop('disabled', true).text('Wird verarbeitet...');
            
            $.post(ajaxurl, {
                action: 'amb_cache_refresh',
                cache_action: action,
                nonce: nonce
            }, function(response) {
                showMessage(response.message, response.success);
                button.prop('disabled', false).text(originalText);
                
                if (response.success && (action === 'refresh' || action === 'download')) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                }
            }).fail(function() {
                showMessage('Fehler bei der Kommunikation mit dem Server', false);
                button.prop('disabled', false).text(originalText);
            });
        }
        
        $('#amb-refresh-cache').click(function() {
            performCacheAction('refresh', $(this));
        });
        
        $('#amb-clear-cache').click(function() {
            performCacheAction('clear', $(this));
        });
        
        $('#amb-download-vocab').click(function() {
            performCacheAction('download', $(this));
        });
    });
    </script>
    <?php
}

function amb_dido_cache_mode_callback() {
    $mode = get_option('amb_cache_mode', 'auto');
    echo '<select name="amb_cache_mode">';
    echo '<option value="auto"' . selected($mode, 'auto', false) . '>Automatisch (empfohlen)</option>';
    echo '<option value="manual"' . selected($mode, 'manual', false) . '>Nur manuell</option>';
    echo '<option value="offline"' . selected($mode, 'offline', false) . '>Offline-Modus</option>';
    echo '</select>';
    echo '<p class="description">';
    echo '<strong>Automatisch:</strong> Cache wird automatisch erneuert<br>';
    echo '<strong>Manuell:</strong> Cache wird nur bei manueller Aktualisierung erneuert<br>';
    echo '<strong>Offline:</strong> Verwendet nur lokale Backups, keine externen Aufrufe';
    echo '</p>';
}

/**
 * Verarbeitet Cache-Aktionen vor der Seitenausgabe
 */
function amb_dido_process_cache_actions() {
    // Nur auf der Plugin-Optionsseite und bei POST-Requests
    if (!isset($_GET['page']) || $_GET['page'] !== 'amb_dido' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    
    // Sicherheitscheck
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $redirect_url = admin_url('options-general.php?page=amb_dido');
    $message = '';
    $status = '';
    
    // Cache-Refresh verarbeiten
    if (isset($_POST['amb_refresh_cache_manual'])) {
        if (function_exists('amb_refresh_external_cache')) {
            // Output-Buffering starten um Logs zu unterdrücken
            ob_start();
            $success = amb_refresh_external_cache();
            ob_end_clean(); // Buffer verwerfen
            
            if ($success) {
                $message = 'cache_updated';
                $status = 'success';
            } else {
                $message = 'cache_failed';
                $status = 'error';
            }
        } else {
            $message = 'function_missing';
            $status = 'error';
        }
        
        $redirect_url = add_query_arg(array(
            'cache_action' => $message,
            'cache_status' => $status
        ), $redirect_url);
        
        wp_redirect($redirect_url);
        exit;
    }
    
    // Cache leeren verarbeiten
    if (isset($_POST['amb_clear_cache'])) {
        delete_transient('amb_external_values_cache');
        
        $redirect_url = add_query_arg(array(
            'cache_action' => 'cache_cleared',
            'cache_status' => 'success'
        ), $redirect_url);
        
        wp_redirect($redirect_url);
        exit;
    }
    
    // Vokabulare herunterladen verarbeiten
    if (isset($_POST['amb_download_vocabularies'])) {
        if (function_exists('amb_vocabularies_manager')) {
            // Output-Buffering starten
            ob_start();
            $manager = amb_vocabularies_manager();
            $results = $manager->download_all_vocabularies();
            ob_end_clean(); // Buffer verwerfen
            
            $success_count = 0;
            foreach ($results as $result) {
                if ($result['success']) {
                    $success_count++;
                }
            }
            
            $redirect_url = add_query_arg(array(
                'cache_action' => 'vocabularies_downloaded',
                'cache_status' => 'success',
                'success_count' => $success_count,
                'total_count' => count($results)
            ), $redirect_url);
        } else {
            $redirect_url = add_query_arg(array(
                'cache_action' => 'vocabularies_failed',
                'cache_status' => 'error'
            ), $redirect_url);
        }
        
        wp_redirect($redirect_url);
        exit;
    }
}

// Hook für die Verarbeitung vor der Seitenausgabe
add_action('admin_init', 'amb_dido_process_cache_actions');


/**
 * Zeigt Benachrichtigungen basierend auf URL-Parametern an
 */
function amb_dido_show_cache_notices() {
    if (!isset($_GET['page']) || $_GET['page'] !== 'amb_dido') {
        return;
    }
    
    if (isset($_GET['cache_action']) && isset($_GET['cache_status'])) {
        $action = sanitize_text_field($_GET['cache_action']);
        $status = sanitize_text_field($_GET['cache_status']);
        $notice_class = $status === 'success' ? 'notice-success' : 'notice-error';
        
        switch ($action) {
            case 'cache_updated':
                echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>Cache erfolgreich aktualisiert!</p></div>';
                break;
            case 'cache_failed':
                echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>Cache-Aktualisierung fehlgeschlagen! Prüfen Sie die Logs.</p></div>';
                break;
            case 'cache_cleared':
                echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>Cache erfolgreich geleert!</p></div>';
                break;
            case 'vocabularies_downloaded':
                $success = isset($_GET['success_count']) ? intval($_GET['success_count']) : 0;
                $total = isset($_GET['total_count']) ? intval($_GET['total_count']) : 0;
                echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>Vokabulare heruntergeladen: ' . $success . ' von ' . $total . ' erfolgreich!</p></div>';
                break;
            case 'vocabularies_failed':
                echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>Vokabular-Download fehlgeschlagen!</p></div>';
                break;
            case 'function_missing':
                echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>Cache-Funktion nicht verfügbar!</p></div>';
                break;
        }
    }
}

/**
 * AJAX Handler für Cache-Aktionen
 */
function amb_dido_ajax_cache_refresh() {
    // Sicherheitscheck
    if (!current_user_can('manage_options')) {
        wp_die('Keine Berechtigung');
    }
    
    check_ajax_referer('amb_cache_nonce', 'nonce');
    
    $action = sanitize_text_field($_POST['cache_action']);
    $success = false;
    $message = '';
    
    switch ($action) {
        case 'refresh':
            delete_transient('amb_external_values_cache');
            if (function_exists('amb_get_all_external_values_with_mode')) {
                $values = amb_get_all_external_values_with_mode();
                if (!empty($values)) {
                    set_transient('amb_external_values_cache', $values, 24 * HOUR_IN_SECONDS);
                    update_option('amb_last_cache_refresh', time());
                    $success = true;
                    $message = 'Cache erfolgreich aktualisiert!';
                } else {
                    $message = 'Cache-Aktualisierung fehlgeschlagen!';
                }
            } else {
                $message = 'Cache-Funktion nicht verfügbar!';
            }
            break;
            
        case 'clear':
            delete_transient('amb_external_values_cache');
            $success = true;
            $message = 'Cache erfolgreich geleert!';
            break;
            
        case 'download':
            if (function_exists('amb_vocabularies_manager')) {
                $manager = amb_vocabularies_manager();
                $results = $manager->download_all_vocabularies();
                $success_count = 0;
                foreach ($results as $result) {
                    if ($result['success']) {
                        $success_count++;
                    }
                }
                $success = true;
                $message = "Vokabulare heruntergeladen: $success_count von " . count($results) . " erfolgreich!";
            } else {
                $message = 'Vokabular-Manager nicht verfügbar!';
            }
            break;
    }
    
    wp_send_json(array(
        'success' => $success,
        'message' => $message
    ));
}

add_action('wp_ajax_amb_cache_refresh', 'amb_dido_ajax_cache_refresh');
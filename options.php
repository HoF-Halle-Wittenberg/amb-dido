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

    // Abschnitte und Felder hinzufügen
    add_settings_section('amb_dido_main_section', '', null, 'amb_dido_main_section');
    add_settings_field('amb_dido_post_types_field', 'Aktivierte Post-Typen', 'amb_dido_post_types_field_html', 'amb_dido_main_section', 'amb_dido_main_section');
    add_settings_field('override_ambkeyword_taxonomy', 'AMB Keywords Taxonomie überschreiben', 'render_override_ambkeyword_taxonomy_field', 'amb_dido_main_section', 'amb_dido_main_section');
    add_settings_field('show_ambkeywords_in_menu', 'AMB Keywords im Backend-Menü anzeigen', 'render_show_ambkeywords_in_menu_field', 'amb_dido_main_section', 'amb_dido_main_section');
    add_settings_field('use_excerpt_for_description', 'Textauszug (Exzerpt) für Beschreibung verwenden', 'render_use_excerpt_for_description_field', 'amb_dido_main_section', 'amb_dido_main_section');

    add_settings_section('amb_dido_taxonomy_section', '', 'amb_dido_taxonomy_section_callback', 'amb_dido_taxonomy_section');
    add_settings_field('amb_dido_taxonomy_mapping', '', 'amb_dido_taxonomy_mapping_callback', 'amb_dido_taxonomy_section', 'amb_dido_taxonomy_section');

    add_settings_section('amb_dido_default_section', '', 'amb_dido_default_section_description', 'amb_dido_default_section');
    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values());
    foreach ($all_fields as $key => $value) {
        add_settings_field($key, $value['field_label'], 'amb_dido_default_field_callback', 'amb_dido_default_section', 'amb_dido_default_section', ['id' => $key, 'options' => $value['options']]);
    }

    add_settings_section('amb_dido_metadata_section', '', 'amb_dido_metadata_section_callback', 'amb_dido_metadata_section');
    foreach ($all_fields as $key => $info) {
        add_settings_field($key, $info['field_label'], 'amb_dido_checkbox_field_callback', 'amb_dido_metadata_section', 'amb_dido_metadata_section', ['id' => $key]);
    }

    add_settings_section('amb_dido_custom_fields_section', '', 'amb_dido_custom_fields_section_callback', 'amb_dido_custom_fields_section');
    add_settings_field(
        'amb_dido_custom_fields_field',
        'Benutzerdefinierte Wertelisten',
        'amb_dido_custom_fields_field_callback',
        'amb_dido_custom_fields_section',
        'amb_dido_custom_fields_section'
    );
}

function amb_dido_metadata_section_callback() {
    echo '<p>Wählen Sie die Metadatenfelder, die im Frontend angezeigt werden sollen.</p>';
    echo '<p>Alternativ können Metadatenfelder im Editor per Shortcodes aufgerufen werden: <span class="amb-code">[show_amb_metadata field="amb_audience"]</span> oder <span class="amb-code">[show_amb_metadata]</span> für alle aktivierten Felder.</p>';
    echo '<p>Sie können auch beliebige Felder in Ihrem Theme mit <span class="amb-code">show_amb_metadata("NAME_DES_FELDS")</span> aufrufen.</p>';
    echo '<p>Folgende Felder können Sie dafür verwenden:</p>';

    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values());
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
    $new_input = [];
    foreach($input as $key => $value) {
        if (isset($input[$key])) {
            $new_input[$key] = $value ? 1 : 0;
        }
    }
    return $new_input;
}

function amb_dido_default_section_description() {
    echo '<p>Die Voreinstellungen hier vornehmen, wenn sie für alle Ressourcen gesetzt werden sollen. Diese Felder werden dann im Editor nicht mehr angezeigt. Sie können Felder auch ausblenden, ohne einen Standardwert zu setzen. </p>';
}

function amb_dido_default_field_callback($args) {
  $options = get_option('amb_dido_defaults');
  $custom_labels = get_option('amb_dido_custom_labels', array());

  echo '<div class="amb-default-field-row">';
  
  echo '<div class="amb-default-field-dropdown">';
  echo "<select name='amb_dido_defaults[{$args['id']}]'>";
  echo "<option value=''>--Keine Auswahl--</option>";
  echo "<option value='deactivate'" . ($options[$args['id']] === 'deactivate' ? ' selected="selected"' : '') . ">--Feld ausblenden--</option>";
  foreach ($args['options'] as $option_array) {
    foreach ($option_array as $id => $label) {
      if(!is_array($label)) {
        $selected = isset($options[$args['id']]) && $options[$args['id']] == $id ? 'selected="selected"' : '';
        echo "<option value='$id' $selected>$label</option>";
      }
    }
  }
  echo "</select>";
  echo '</div>';

  echo '<div class="amb-default-field-label">';
  $custom_label = isset($custom_labels[$args['id']]) ? $custom_labels[$args['id']] : '';
  echo "<input type='text' name='amb_dido_custom_labels[{$args['id']}]' value='" . esc_attr($custom_label) . "' placeholder='Benutzerdefiniertes Label'>";
  echo '</div>';

  echo '</div>';
}

function amb_dido_sanitize_custom_labels($input) {
  $sanitized_input = array();
  foreach ($input as $key => $value) {
    $sanitized_input[$key] = sanitize_text_field($value);
  }
  return $sanitized_input;
}

function amb_dido_sanitize_defaults($value) {
    if (!isset($value) || empty($value)) {
        return '';
    } elseif ($value === 'deactivate') {
        return 'deactivate';
    } else {
        return $value;
    }
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
    $sanitized_input = [];
    foreach ($input as $custom_field) {
        $sanitized_url = esc_url_raw($custom_field['url']);
        $sanitized_key = in_array($custom_field['key'], ['about', 'teaches', 'assesses', 'audience', 'interactivityType']) ? $custom_field['key'] : '';
        $meta_key = isset($custom_field['meta_key']) ? $custom_field['meta_key'] : '';

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
    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values());
    $taxonomies = get_taxonomies(array('public' => true), 'objects');
    $mapping = get_option('amb_dido_taxonomy_mapping', array());

    foreach ($all_fields as $field_key => $field_data) {
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

function amb_dido_sanitize_taxonomy_mapping($input) {
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
    $mapping = get_option('amb_dido_taxonomy_mapping', array());

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

        })(jQuery);
    </script>
    <?php
}
add_action('admin_footer', 'amb_dido_custom_fields_js');

// Hook to add the settings page
add_action('admin_menu', 'amb_dido_create_settings_page');

// Hook to register settings
add_action('admin_init', 'amb_dido_register_settings');
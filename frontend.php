<?php

defined('ABSPATH') or die('Zugriff verboten');


function amb_get_field_value($post_id, $field) {
    $mapping = get_option('amb_dido_taxonomy_mapping', array());
    $defaults = get_option('amb_dido_defaults');

    if (isset($mapping[$field])) {
        // Field is mapped to a taxonomy
        $terms = wp_get_post_terms($post_id, $mapping[$field], array('fields' => 'all'));
        $value = array();
        foreach ($terms as $term) {
            $value[] = array(
                'id' => $term->slug,
                'prefLabel' => array('de' => $term->name),
                'type' => 'Concept'
            );
        }
    } else {
        // Field is a regular meta field
        $value = get_post_meta($post_id, $field, true);
        if (empty($value)) {
            $value = $defaults[$field] ?? null;
        }
    }

    return $value;
}


/**
 * Metadaten-Box in das Frontend einspielen
 **/

function add_metadata_to_content($content) {
    $options = get_option('amb_dido_display_metadata');

    // Anpassung, um is_single oder is_page zu berücksichtigen
    $is_post_or_page = is_single() || is_page();
    $in_the_loop = in_the_loop();
    $is_main_query = is_main_query();

    if ($options && $is_post_or_page && $in_the_loop && $is_main_query) {
        if (function_exists('show_post_metadata')) {
            $metadata_content = show_post_metadata();
            $content .= $metadata_content;
        } else {
            // do nothing
        }
    } else {
        // do nothing
    }
    return $content;
}

add_filter('the_content', 'add_metadata_to_content', 20); 


/**
* Anzeigen der ausgewählten Metadaten im Frontend
**/ 

function show_post_metadata() {
    global $post;
    if (empty($post)) return '';

    $output = '<div class="amb-metadata-box">';
    $options = get_option('amb_dido_metadata_display_options');
    $custom_labels = get_option('amb_dido_custom_labels', array());
    $mapping = get_option('amb_dido_taxonomy_mapping', array());

    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values_with_mode());
    
    // Add description field
    $all_fields['amb_description'] = array('field_label' => 'Beschreibung');
    
    // Add keywords field
    $all_fields['amb_keywords'] = array('field_label' => 'Schlüsselwörter');

    foreach ($all_fields as $key => $info) {
        if (!empty($options[$key])) {
            $label = isset($custom_labels[$key]) && !empty($custom_labels[$key]) ? $custom_labels[$key] : $info['field_label'];
            
            if ($key === 'amb_description') {
                $description = amb_get_description($post);
                if (!empty($description)) {
                    $output .= '<h4>' . esc_html($label) . ':</h4><p>' . esc_html($description) . '</p>';
                }
            } elseif ($key === 'amb_keywords') {
                $keywords = amb_get_keywords($post->ID);
                if (!empty($keywords)) {
                    $output .= '<h4>' . esc_html($label) . ':</h4><ul>';
                    foreach ($keywords as $keyword) {
                        $output .= '<li class="is-style-pill wp-block-post-terms"><a href="' . get_term_link($keyword) . '">' . esc_html($keyword) . '</a></li>';
                    }
                    $output .= '</ul>';
                }
            } elseif (isset($mapping[$key])) {
                $terms = wp_get_post_terms($post->ID, $mapping[$key]);
                if (!empty($terms) && !is_wp_error($terms)) {
                    $output .= '<h4>' . esc_html($label) . ':</h4><ul>';
                    foreach ($terms as $term) {
                        $output .= '<li class="is-style-pill wp-block-post-terms"><a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a></li>';
                    }
                    $output .= '</ul>';
                }
            } else {
                $metadata = get_post_meta($post->ID, $key, true);
                if (!empty($metadata)) {
                    $output .= '<h4>' . esc_html($label) . ':</h4><ul>';
                    foreach ($metadata as $item) {
                        if (isset($item['prefLabel']['de'])) {
                            $label = esc_html($item['prefLabel']['de']);
                            $search_url = esc_url(add_query_arg('s', urlencode("$key: $label"), home_url('/')));
                            $output .= '<li class="is-style-pill wp-block-post-terms"><a href="' . $search_url . '">' . $label . '</a></li>';
                        }
                    }
                    $output .= '</ul>';
                }
            }
        }
    }

    $output .= '</div>';
    return $output;
}



/** 
 * Hook function für das Theme
 **/ 
function show_amb_metadata($meta_key) {
    global $post;
    if (empty($post)) return;

    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values_with_mode());
    $custom_labels = get_option('amb_dido_custom_labels', array());
    $mapping = get_option('amb_dido_taxonomy_mapping', array());

    // Add description and keywords fields
    $all_fields['amb_description'] = array('field_label' => 'Beschreibung');
    $all_fields['amb_keywords'] = array('field_label' => 'Schlüsselwörter');

    if (!isset($all_fields[$meta_key])) return; 

    $field_label = isset($custom_labels[$meta_key]) && !empty($custom_labels[$meta_key])
        ? $custom_labels[$meta_key]
        : $all_fields[$meta_key]['field_label'];

    echo '<div class="amb-metadata-box">';
    echo '<h4>' . esc_html($field_label) . ':</h4>';

    if ($meta_key === 'amb_description') {
        $description = amb_get_description($post);
        if (!empty($description)) {
            echo '<p>' . esc_html($description) . '</p>';
        }
    } elseif ($meta_key === 'amb_keywords') {
        $keywords = amb_get_keywords($post->ID);
        if (!empty($keywords)) {
            echo '<ul>';
            foreach ($keywords as $keyword) {
                echo '<li class="is-style-pill wp-block-post-terms"><a href="' . get_term_link($keyword) . '">' . esc_html($keyword) . '</a></li>';
            }
            echo '</ul>';
        }
    } elseif (isset($mapping[$meta_key])) {
        $terms = wp_get_post_terms($post->ID, $mapping[$meta_key]);
        if (!empty($terms) && !is_wp_error($terms)) {
            echo '<ul>';
            foreach ($terms as $term) {
                echo '<li class="is-style-pill wp-block-post-terms"><a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a></li>';
            }
            echo '</ul>';
        }
    } else {
        $metadata = get_post_meta($post->ID, $meta_key, true);
        if (!empty($metadata)) {
            echo '<ul>';
            if (is_array($metadata)) {
                foreach ($metadata as $item) {
                    if (isset($item['prefLabel']['de'])) {
                        $label = esc_html($item['prefLabel']['de']);
                        $search_url = esc_url(add_query_arg('s', urlencode("$meta_key: $label"), home_url('/')));
                        echo '<li class="is-style-pill wp-block-post-terms"><a href="' . $search_url . '">' . $label . '</a></li>';
                    }
                }
            } else {
                $label = esc_html($metadata);
                $search_url = esc_url(add_query_arg('s', urlencode("$meta_key: $label"), home_url('/')));
                echo '<li class="is-style-pill wp-block-post-terms"><a href="' . $search_url . '">' . $label . '</a></li>';
            }
            echo '</ul>';
        }
    }

    echo '</div>';
}
/* Nutzung im Theme: show_amb_metadata('amb_audience');  */


/** 
 * Shortcode für das Theme
 **/ 
function show_amb_metadata_shortcode($atts) {
    global $post;
    if (empty($post)) return '';

    $atts = shortcode_atts([
        'field' => '',
    ], $atts);

    $all_fields = array_merge(amb_get_other_fields(), amb_get_all_external_values_with_mode());
    $custom_labels = get_option('amb_dido_custom_labels', array());
    $defaults = get_option('amb_dido_defaults', array());
    $mapping = get_option('amb_dido_taxonomy_mapping', array());

    $output = '';
    $options = get_option('amb_dido_metadata_display_options', []);

    if ($atts['field']) {
        $fields = explode(',', $atts['field']);
    } else {
        $fields = array_merge(array_keys($options), ['amb_description', 'amb_keywords']);
    }

    foreach ($fields as $field) {
        $field = trim($field);
        
        if ($field === 'amb_description') {
            $description = amb_get_description($post);
            if (!empty($description)) {
                $output .= '<div class="amb-metadata-box">';
                $output .= '<h4>Beschreibung:</h4><p>' . esc_html($description) . '</p>';
                $output .= '</div>';
            }
            continue;
        }

        if ($field === 'amb_keywords') {
            $keywords = amb_get_keywords($post->ID);
            if (!empty($keywords)) {
                $output .= '<div class="amb-metadata-box">';
                $output .= '<h4>AMB Keywords:</h4><ul>';
                foreach ($keywords as $keyword) {
                    $term = get_term_by('name', $keyword, 'ambkeywords');
                    if ($term && !is_wp_error($term)) {
                        $output .= '<li><a href="' . get_term_link($term) . '">' . esc_html($keyword) . '</a></li>';
                    } else {
                        $output .= '<li>' . esc_html($keyword) . '</li>';
                    }
                }
                $output .= '</ul></div>';
            }
            continue;
        }

        if (!isset($all_fields[$field])) continue;

        $metadata = get_post_meta($post->ID, $field, true);
        $default_value = isset($defaults[$field]) ? $defaults[$field] : null;

        if (empty($metadata) && $default_value !== null && $default_value !== 'deactivate') {
            $metadata = [['id' => $default_value, 'prefLabel' => ['de' => '']]];;
        }

        if (!empty($metadata)) {
            $field_label = isset($custom_labels[$field]) && !empty($custom_labels[$field])
                ? $custom_labels[$field]
                : $all_fields[$field]['field_label'];

            $output .= '<div class="amb-metadata-box">';
            $output .= '<h4>' . esc_html($field_label) . ':</h4><ul>';

            if (isset($mapping[$field])) {
                $terms = wp_get_post_terms($post->ID, $mapping[$field]);
                if (!empty($terms) && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $output .= '<li><a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a></li>';
                    }
                }
            } else {
                if (is_array($metadata)) {
                    foreach ($metadata as $item) {
                        if (isset($item['id'])) {
                            $label = '';
                            foreach ($all_fields[$field]['options'] as $option) {
                                if (isset($option[$item['id']])) {
                                    $label = $option[$item['id']];
                                    break;
                                }
                            }
                            if (empty($label) && isset($item['prefLabel']['de'])) {
                                $label = $item['prefLabel']['de'];
                            }
                            if (!empty($label)) {
                                $url = esc_url(add_query_arg('s', urlencode("$field: $label"), home_url('/')));
                                $output .= '<li><a href="' . $url . '">' . esc_html($label) . '</a></li>';
                            }
                        }
                    }
                } else {
                    $label = $metadata;
                    foreach ($all_fields[$field]['options'] as $option) {
                        if (isset($option[$metadata])) {
                            $label = $option[$metadata];
                            break;
                        }
                    }
                    $url = esc_url(add_query_arg('s', urlencode("$field: $label"), home_url('/')));
                    $output .= '<li><a href="' . $url . '">' . esc_html($label) . '</a></li>';
                }
            }

            $output .= '</ul></div>';
        }
    }

    return $output;
}
                    

function register_amb_metadata_shortcode() {
    add_shortcode('show_amb_metadata', 'show_amb_metadata_shortcode');
}
add_action('init', 'register_amb_metadata_shortcode');
/* Nutzung im Editor: [show_amb_metadata field="amb_audience"] oder [show_amb_metadata] für alle aktivierten Felder */
/* Nutzung im Theme: echo do_shortcode('[show_amb_metadata field="amb_learningResourceType"]'); */
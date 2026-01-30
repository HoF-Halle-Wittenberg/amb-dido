<?php

/**
 *  SUCHFUNKTIONEN
 * --------------
 */


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Funktion zur Erweiterung der Suche
function custom_search_query($search, $wp_query) {
    global $wpdb;

    if (empty($search)) {
        return $search; // Skip processing - no search term in query
    }

    // Get search term
    $search_term = $wp_query->query_vars['s'];

    // Skip processing if doing an admin search
    if (is_admin()) {
        return $search;
    }

    // Initial query parts for standard fields
    $search = " AND (";
    $search .= "{$wpdb->posts}.post_title LIKE '%{$search_term}%'";
    $search .= " OR {$wpdb->posts}.post_content LIKE '%{$search_term}%'";

    // Add custom meta fields to search
    if (function_exists('amb_get_all_external_values_with_mode')) {
        try {
            $all_options = array_merge(amb_get_other_fields(), amb_get_all_external_values_with_mode());
            $meta_keys = array_keys($all_options); // Dynamically get meta keys
        } catch (Exception $e) {
            // Fehler ignorieren, mit lokalen Feldern fortfahren
            $meta_keys = array();
        }
    } else {
        $meta_keys = array();
    }
   
    // Check if search term contains specific meta key search (e.g., "audience: Lehrperson")
    if (strpos($search_term, ':') !== false) {
        list($specific_meta_key, $specific_meta_value) = explode(':', $search_term, 2);
        $specific_meta_key = trim($specific_meta_key);
        $specific_meta_value = trim($specific_meta_value);

        // Ensure the specific meta key is valid
        if (in_array($specific_meta_key, $meta_keys)) {
            $search = " AND (";
            $search .= $wpdb->prepare("EXISTS (
                SELECT 1
                FROM {$wpdb->postmeta}
                WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                AND {$wpdb->postmeta}.meta_key = %s
                AND {$wpdb->postmeta}.meta_value LIKE %s
            )", $specific_meta_key, '%' . $wpdb->esc_like($specific_meta_value) . '%');
            $search .= ")";
            return $search;
        }
    }

    foreach ($meta_keys as $meta_key) {
        if (isset($all_options[$meta_key])) {
            $search .= $wpdb->prepare(" OR EXISTS (
                SELECT 1
                FROM {$wpdb->postmeta}
                WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                AND {$wpdb->postmeta}.meta_key = %s
                AND {$wpdb->postmeta}.meta_value LIKE %s
            )", $meta_key, '%' . $wpdb->esc_like($search_term) . '%');
        }
    }

    $search .= ")";

    return $search;
}

// Hook in to modify the search query
add_filter('posts_search', 'custom_search_query', 10, 2);
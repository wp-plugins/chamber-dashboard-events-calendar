<?php
/**
 * Events Maker Template Hooks
 *
 * Action/filter hooks used for Events Maker functions/templates
 *
 * @author 	Digital Factory
 * @package Events Maker/Templates
 * @since 	1.2.0
 */


/**
 * Content wrappers
 */
add_action('cde_before_main_content', 'cde_output_content_wrapper_start', 10);
add_action('cde_after_main_content', 'cde_output_content_wrapper_end', 10);


/**
 * Breadcrumbs
 */
add_action('cde_before_main_content', 'cde_breadcrumb', 20);


/**
 * Sorting
 */
// add_action('cde_before_main_content', 'cde_sorting', 30);


/**
 * Pagination links
 */
add_action('cde_after_events_loop', 'cde_paginate_links', 10);


/**
 * Sidebar
 */
add_action('cde_get_sidebar', 'cde_get_sidebar', 10);


/**
 * Events archive description
 */
add_action('cde_archive_description', 'cde_display_loop_event_google_map', 10);
add_action('cde_archive_description', 'cde_display_location_details', 20);
add_action('cde_archive_description', 'cde_taxonomy_archive_description', 30);


/**
 * Event content in loop
 */
add_action('cde_before_loop_event', 'cde_display_loop_event_thumbnail', 10);
add_action('cde_before_loop_event_title', 'cde_display_event_categories', 10);
add_action('cde_after_loop_event_title', 'cde_display_loop_event_meta', 10);
add_action('cde_after_loop_event_title', 'cde_display_event_locations', 20);
add_action('cde_after_loop_event', 'cde_display_event_excerpt', 10);
add_action('cde_after_loop_event', 'cde_display_event_tags', 20);
add_action('cde_loop_event_meta_start', 'cde_display_event_date', 10);


/**
 * Single event content
 */
add_action('cde_before_single_event', 'cde_display_single_event_thumbnail', 10);
add_action('cde_before_single_event_title', 'cde_display_event_categories', 10);
add_action('cde_after_single_event_title', 'cde_display_single_event_meta', 10);
add_action('cde_after_single_event_title', 'cde_display_event_locations', 20);
add_action('cde_after_single_event_title', 'cde_display_single_event_google_map', 40);
add_action('cde_after_single_event_title', 'cde_display_event_tickets', 50);
add_action('cde_after_single_event', 'cde_display_event_tags', 10);
add_action('cde_single_event_meta_start', 'cde_display_single_event_date', 10);


/**
 * Widget event content
 */
 add_action('cde_before_widget_event_title', 'cde_display_widget_event_date', 10);
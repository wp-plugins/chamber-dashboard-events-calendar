<?php
/**
 * The template for event google map
 * 
 * Override this template by copying it to yourtheme/single-event/excerpt.php
 *
 * @author 	Digital Factory
 * @package Events Maker/Templates
 * @since 	1.2.0
 */
 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $post;

// display options
$display_options = get_post_meta($post->ID, '_event_display_options', true);

// tickets enabled?
if (!$display_options['google_map'])
	return;

// event locations
$locations = cde_get_locations_for($post->ID);

// locations available?
if (!isset($locations) || empty($locations))
	return;

?>

<?php
$args = apply_filters('cde_single_event_google_map_args', array(
	'width' => '100%',
	'height' => '200px',
	'zoom' => 15,
	'maptype' => 'roadmap',
	'maptypecontrol' => true,
	'zoomcontrol' => true,
	'streetviewcontrol' => true,
	'overviewmapcontrol' => false,
	'pancontrol' => false,
	'rotatecontrol' => false,
	'scalecontrol' => false,
	'draggable' => true,
	'keyboardshortcuts' => true,
	'scrollzoom' => true
));

cde_display_google_map($args, $locations);
?>
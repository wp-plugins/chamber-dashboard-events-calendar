<?php
/**
 * Events Maker public functions
 *
 * Functions available for users and developers. May not be replaced
 *
 * @author 	Digital Factory
 * @package Events Maker/Functions
 * @version 1.1.0
 */

if(!defined('ABSPATH')) exit;


/**
 * Get events
 * @param mixed
 * @return array
 */
function cde_get_events($args = array())
{
	$defaults = array(
		'post_type' => 'event',
		'suppress_filters' => false,
		'posts_per_page' => -1
	);
	$args = wp_parse_args($args, $defaults);
	
	return apply_filters('cde_get_events', get_posts($args));
}


/**
 * Get single event
 * @param int $post_id
 * @return object
 */
function cde_get_event($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	return apply_filters('cde_get_event', (($post = get_post((int)$post_id, 'OBJECT', 'raw')) !== NULL ? $post : NULL), $post_id);
}


/**
 * Get single event occurrences
 * @param int $post_id
 * @param string $period
 * @param string $orderby
 * @return array
 */

function cde_get_occurrences($post_id = 0, $period = 'all', $orderby = 'asc', $limit = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is this a reccuring event?
	if(!cde_is_recurring($post_id))
		return false;
	
	$defaults = array(
		'period'   	=> $period,
		'orderby' 	=> $orderby,
		'limit'   	=> absint($limit)
	);
	
	$args = array();
	$args = apply_filters('cde_get_occurrences_args', wp_parse_args($args, $defaults));
	
	$all_occurrences = get_post_meta($post_id, '_event_occurrence_date', false);

	if($args['orderby'] === 'asc')
		sort($all_occurrences, SORT_STRING);
	else
		rsort($all_occurrences, SORT_STRING);

	$occurrences = array();
	$now = current_time('timestamp');

	if($args['period'] === 'all')
	{
		foreach($all_occurrences as $id => $occurrence)
		{
			$dates = explode('|', $occurrence);
			$occurrences[] = array('start' => $dates[0], 'end' => $dates[1]);
		}
	}
	elseif($args['period'] === 'future')
	{
		foreach($all_occurrences as $id => $occurrence)
		{
			$dates = explode('|', $occurrence);

			if($now < strtotime($dates[0]) && $now < strtotime($dates[1]))
				$occurrences[] = array('start' => $dates[0], 'end' => $dates[1]);
		}
	}
	else
	{
		foreach($occurrences_start as $id => $occurrence)
		{
			$dates = explode('|', $occurrence);

			if($now > strtotime($dates[0]) && $now > strtotime($dates[1]))
				$occurrences[] = array('start' => $dates[0], 'end' => $dates[1]);
		}
	}

	if($limit > 0)
		return $occurrences = array_slice($occurrences, 0, $args['limit']);

	return apply_filters('cde_get_occurrences', $occurrences, $post_id);
}


/**
 * Get event first occurrence
 * @param int $post_id
 * @return array
 */
function cde_get_first_occurrence($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is this a reccuring event?
	if(!cde_is_recurring($post_id))
		return false;
	
	return apply_filters('cde_get_first_occurrence', array('start' => get_post_meta($post_id, '_event_start_date', true), 'end' => get_post_meta($post_id, '_event_end_date', true)), $post_id);
}


/**
 * Get event last occurrence
 * @param int $post_id
 * @return array
 */
function cde_get_last_occurrence($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is this a reccuring event?
	if(!cde_is_recurring($post_id))
		return false;

	$dates = explode('|', get_post_meta($post_id, '_event_occurrence_last_date', true));

	return apply_filters('cde_get_next_occurrence', array('start' => $dates[0], 'end' => $dates[1]), $post_id);
}


/**
 * Get event next occurrence
 * @param int $post_id
 * @return array
 */
function cde_get_next_occurrence($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is this a reccuring event?
	if(!cde_is_recurring($post_id))
		return false;
	
	$occurence = cde_get_occurrences($post_id, 'future');
	
	if (empty($occurence[0]))
		return false;
	
	return apply_filters('cde_get_next_occurrence', $occurence[0], $post_id);
}


/**
 * Get event active occurrence
 * @param int $post_id
 * @return array
 */
function cde_get_active_occurrence($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is this a reccuring event?
	if(!cde_is_recurring($post_id))
		return false;

	$occurrences = get_post_meta($post_id, '_event_occurrence_date', false);
	sort($occurrences, SORT_STRING);

	$now = current_time('timestamp');

	foreach($occurrences as $id => $occurrence)
	{
		$dates = explode('|', $occurrence);

		if($now > strtotime($dates[0]) && $now < strtotime($dates[1]))
			return array('start' => $dates[0], 'end' => $dates[1]);
	}

	return false;
}


/**
 * Get event occurrence date when in loop
 * @param int $post_id
 * @return array
 */
function cde_get_current_occurrence($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	// is it reccuring event?
	if(!cde_is_recurring($post_id))
		return false;
	
	global $post;
	
	if(empty($post->event_occurrence_start_date))
		return false;
	
	return apply_filters('cde_get_current_occurrence', array('start' => $post->event_occurrence_start_date, 'end' => $post->event_occurrence_end_date), $post_id);
}


/**
 * Get the event date
 * @param int $post_id
 * @param array	$args
 * @return string or array
 */
function cde_get_the_date($post_id = 0, $args = array())
{

	$post_id 	= (int)(empty($post_id) ? get_the_ID() : $post_id);
	$date 		= array();
	
	if(empty($post_id))
		return false;
	
	$defaults = array(
		'range'   	=> '',		// start, end
		'output' 	=> '',		// datetime, date, time
		'format' 	=> '',		// date or time format
	);
	$args = apply_filters('cde_get_the_date_args', wp_parse_args($args, $defaults));
 
	$occurrence = cde_get_current_occurrence($post_id);
	
	// if current event is event occurrence?
	if(!empty($occurrence))
	{
		$start_date = $occurrence['start'];
		$end_date = $occurrence['end'];
	}
	else
	{
		$start_date = get_post_meta($post_id, '_event_start_date', true);
		$end_date = get_post_meta($post_id, '_event_end_date', true);
	}

	if(empty($start_date))
		return false;
	
	// date format options
	$options = get_option('cdash_events_general');
	$date_format = $options['datetime_format']['date'];
	$time_format = $options['datetime_format']['time'];
	
	if(!empty($args['format']) && is_array($args['format']))
	{
		$date_format = (!empty($args['format']['date']) ? $args['format']['date'] : $date_format);
		$time_format = (!empty($args['format']['time']) ? $args['format']['time'] : $time_format);
	}
	
	// what is there to display?
	if(!empty($args['range']))
	{
		if($args['range'] === 'start' && !empty($start_date))
			$date['start'] = $start_date;
		elseif ($args['range'] === 'end' && !empty($end_date))
			$date['end'] = $end_date;
	}
	else
		$date = array('start' => $start_date, 'end' => $end_date);

	// what part of the date to display and how to format it?
	if(!empty($date))
	{
		foreach ($date as $key => $value)
		{
			if ($args['output'] === 'date') // output date only
			{
				$date[$key] = !empty($args['format']) ? cde_format_date($value, 'date', $args['format']) : cde_format_date($value, 'date', $date_format);
			} 
			elseif ($args['output'] === 'time') // output time only
			{
				$date[$key] = !empty($args['format']) ? cde_format_date($value, 'time', $args['format']) : cde_format_date($value, 'time', $time_format);
			}
			else // output date & time
			{
				$date[$key] = !empty($args['format']) ? cde_format_date($value, 'datetime', $args['format']) : cde_format_date($value, 'datetime', $date_format.' '.$time_format);
			}
		}
	}

	return apply_filters('cde_get_the_date', $date, $post_id, $args);
}


/**
 * Get event start date
 * @param int $post_id
 * @param string $type
 * @return string
 */
function cde_get_the_start($post_id = 0, $type = 'datetime')
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	$date = get_post_meta((int)$post_id, '_event_start_date', true);

	return apply_filters('cde_get_the_start', (!empty($date) ? cde_format_date($date, $type) : false), $post_id);
}


/**
 * Get event end date
 * @param int $post_id
 * @param string $type
 * @return string
 */
function cde_get_the_end($post_id = 0, $type = 'datetime')
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	$date = get_post_meta((int)$post_id, '_event_end_date', true);

	return apply_filters('cde_get_the_end', (!empty($date) ? cde_format_date($date, $type) : false), $post_id);
}


/**
 * Format given date
 * @param string $date
 * @param string $type
 * @param string $format
 * @return string
 */
function cde_format_date($date = NULL, $type = 'datetime', $format = false)
{
	if($date === NULL)
		$date = current_time('timestamp', false);

	$options = get_option('cdash_events_general');
	$date_format = $options['datetime_format']['date'];
	$time_format = $options['datetime_format']['time'];

	if(is_array($format))
	{
		$date_format = (!empty($format['date']) ? $format['date'] : $date_format);
		$time_format = (!empty($format['time']) ? $format['time'] : $time_format);
	} 
	elseif(!empty($format))
	{
		if($type === 'date')
			$date_format = $format;
		if($type === 'time')
			$time_format = $format;
	}

	if($type === 'date')
		return date_i18n($date_format, strtotime($date));
	elseif($type === 'time')
		return date($time_format, strtotime($date));
	else
		return date_i18n($date_format.' '.$time_format, strtotime($date));
}


/**
 * Check if given event is an all day event
 * @param int $post_id
 * @return bool
 */
function cde_is_all_day($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);
	
	if(empty($post_id))
		return false;

	return apply_filters('cde_is_all_day', (get_post_meta((int)$post_id, '_event_all_day', true) === '1' ? true : false), $post_id);
}


/**
 * Check if given event is a reccurring event
 * @param int $post_id
 * @return bool
 */
function cde_is_recurring($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);

	if(empty($post_id))
		return false;

	$recurrence = get_post_meta($post_id, '_event_recurrence', true);

	return apply_filters('cde_is_recurring',	($recurrence['type'] === 'once' ? false : true), $post_id);
}


/**
 * Check if given event is a free event
 * @param int $post_id
 * @return bool
 */
function cde_is_free($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);
	
	if(empty($post_id))
		return false;

	return apply_filters('cde_is_free', (get_post_meta((int)$post_id, '_event_free', true) === '1' ? true : false), $post_id);
}


/**
 * Get the ticket data for a given event
 * @param int $post_id
 * @return array
 */
function cde_get_tickets($post_id = 0)
{
	$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);
	$tickets = array();
	
	if(empty($post_id))
		return false;
	
	if(cde_is_free($post_id) === false)
		$tickets = get_post_meta((int)$post_id, '_event_tickets', true);

	return apply_filters('cde_get_tickets', $tickets, $post_id);
}


/**
 * Get the currency symbol and append it to the price
 * @param string $price
 * @return string
 */
function cde_get_currency_symbol($price = '')
{
	$options = get_option('cdash_directory_options');

	$symbol = ($options['currency_symbol'] === '' ? strtoupper($options['currency']) : $options['currency_symbol']);

	if(is_numeric($price))
	{
		$price = number_format($price, 2, '.', ',');
		return apply_filters('cde_get_currency_symbol', ($options['currencies']['position'] === 'after' ? $price.' '.$symbol : $symbol.' '.$price), $price);
	}
	else
		return apply_filters('cde_get_currency_symbol', $symbol, $price);
}


/**
 * Get event locations with metadata
 * @param array $args
 * @return array
 */
function cde_get_locations($args = array())
{
	$defaults = array(
		'fields' => 'all'
	);
	$args = apply_filters('cde_get_locations_args', array_merge($defaults, $args));

	if(!taxonomy_exists('event-location'))
		return false;
		
	$locations = get_terms('event-location', $args);

	if(isset($args['fields']) && $args['fields'] === 'all')
	{
		foreach($locations as $id => $location)
		{
			$locations[$id]->location_meta = (get_option('event_location_'.$location->term_taxonomy_id) ? get_option('event_location_'.$location->term_taxonomy_id) : get_option('event_location_'.$location->term_id));
		}
	}
	
	return apply_filters('cde_get_locations', $locations);
}


/**
 * Get single event location data
 * @param int $term_id
 * @return object
 */
function cde_get_location($term_id = NULL)
{
	if(!taxonomy_exists('event-location'))
		return false;

	if($term_id === NULL)
	{
		$term = get_queried_object();

		if(is_tax() && is_object($term) && isset($term->term_id))
			$term_id = $term->term_id;
		else
			return NULL;
	}

	if(($location = get_term((int)$term_id, 'event-location', 'OBJECT', 'raw')) !== NULL)
	{
		$location->location_meta = (get_option('event_location_'.$location->term_taxonomy_id) ? get_option('event_location_'.$location->term_taxonomy_id) : get_option('event_location_'.$location->term_id));

		return apply_filters('cde_get_location', $location);
	}
	else
		return NULL;

}


/**
 * Get all event locations for a given event
 * @param int $post_id
 * @return array
 */
function cde_get_locations_for($post_id = 0)
{
	if(!taxonomy_exists('event-location'))
		return false;

	$locations = wp_get_post_terms((int)$post_id, 'event-location');
	
	if(!empty($locations) && is_array($locations))
	{
		foreach($locations as $id => $location)
		{
			$locations[$id]->location_meta = (get_option('event_location_'.$location->term_taxonomy_id) ? get_option('event_location_'.$location->term_taxonomy_id) : get_option('event_location_'.$location->term_id));
		}
	}

	return apply_filters('cde_get_locations_for', $locations, $post_id);
}

/**
 * Get all event categories
 * @param array $args
 * @return array
 */
function cde_get_categories($args = array())
{
	if(!taxonomy_exists('event-category'))
		return false;
	
	return apply_filters('cde_get_categories', get_terms('event-category', $args));
}


/**
 * Get single event category data
 * @param int $term_id
 * @return object
 */
function cde_get_category($term_id = NULL)
{
	if(!taxonomy_exists('event-category'))
		return false;

	if($term_id === NULL)
	{
		$term = get_queried_object();

		if(is_tax() && is_object($term) && isset($term->term_id))
			$term_id = $term->term_id;
		else
			return NULL;
	}
	
	$category = get_term((int)$term_id, 'event-category', 'OBJECT', 'raw') !== NULL ? $category : NULL;

	return apply_filters('cde_get_category', $category);
}


/**
 * Get all event categories for a given event
 * @param int $post_id
 * @return string
 */
function cde_get_categories_for($post_id = 0)
{
	$categories = array();
	
	if(!taxonomy_exists('event-category'))
		return false;
	
	$categories = wp_get_post_terms((int)$post_id, 'event-category');
	
	return apply_filters('cde_get_categories_for', $categories, $post_id);
}

/**
 * Get all event tags
 * @param array $args
 * @return array
 */
function cde_get_tags($args = array())
{
	if(!taxonomy_exists('event-tag'))
		return false;
	
	return apply_filters('cde_get_tags', get_terms('event-tag', $args));
}

/**
 * Get all event tags for a given event
 * @param int $post_id
 * @return string
 */
function cde_get_tags_for($post_id = 0)
{
	$tags = array();
	
	if(!taxonomy_exists('event-tag'))
		return false;
	
	$tags = wp_get_post_terms((int)$post_id, 'event-tag');
	
	return apply_filters('cde_get_tags_for', $tags, $post_id);
}

/**
 * Check if displayed page is an event archive page
 * @param 	datetype
 * @return 	bool
 */
function cde_is_event_archive($datetype = '')
{
	global $wp_query;

	if(!is_post_type_archive('event'))
		return false;

	if($datetype === '')
		return true;

	if(!empty($wp_query->query_vars['event_ondate']))
	{
		$date = explode('/', $wp_query->query_vars['event_ondate']);

		if((($a = count($date)) === 1 && $datetype === 'year') || ($a === 2 && $datetype === 'month') || ($a === 3 && $datetype === 'day'))
			return true;
	}

	return false;
}


/**
 * Get a date archive link
 */
function cde_get_event_date_link($year = 0, $month = 0, $day = 0)
{
	global $wp_rewrite;

	$archive = get_post_type_archive_link('event');

	$year = (int)$year;
	$month = (int)$month;
	$day = (int)$day;

	if($year === 0 && $month === 0 && $day === 0)
		return $archive;

	$cde_year = $year;
	$cde_month = str_pad($month, 2, '0', STR_PAD_LEFT);
	$cde_day = str_pad($day, 2, '0', STR_PAD_LEFT);

	if($day !== 0)
		$link_date = compact('cde_year', 'cde_month', 'cde_day');
	elseif($month !== 0)
		$link_date = compact('cde_year', 'cde_month');
	else
		$link_date = compact('cde_year');

	if(!empty($archive) && $wp_rewrite->using_mod_rewrite_permalinks() && ($permastruct = $wp_rewrite->get_extra_permastruct('event_ondate')))
	{
		$archive = apply_filters('post_type_archive_link', home_url(str_replace('%event_ondate%', implode('/', $link_date), $permastruct)), 'event');
	}
	else
		$archive = add_query_arg('event_ondate', implode('-', $link_date), $archive);

	return $archive;
}


/**
 * Display event taxonomy
 */
function cde_display_event_taxonomy($taxonomy = '', $args = array())
{
	if(!taxonomy_exists($taxonomy))
		return false;

	return apply_filters('cde_display_event_taxonomy', cde_get_event_taxonomy($taxonomy, $args));
}


/**
 * Get event taxonomy
 */
function cde_get_event_taxonomy($taxonomy = '', $args = array())
{
	$defaults = array(
		'display_as_dropdown' => false,
		'show_hierarchy' => true,
		'order_by' => 'name',
		'order' => 'desc'
	);

	$args = apply_filters('cde_get_event_taxonomy_args', wp_parse_args($args, $defaults));

	if($args['display_as_dropdown'] === false)
	{
		return wp_list_categories(
			array(
				'orderby' => $args['order_by'],
				'order' => $args['order'],
				'hide_empty' => false,
				'hierarchical' => (bool)$args['show_hierarchy'],
				'taxonomy' => $taxonomy,
				'echo' => false,
				'style' => 'list',
				'title_li' => ''
			)
		);
	}
	else
	{
		return wp_dropdown_categories(
			array(
				'orderby' => $args['order_by'],
				'order' => $args['order'],
				'hide_empty' => false, 
				'hierarchical' => (bool)$args['show_hierarchy'],
				'taxonomy' => $taxonomy,
				'hide_if_empty' => false,
				'echo' => false
			)
		);
	}
}


/**
 * Display event archive 
 */
function cde_display_event_archives($args = array())
{
	global $wp_locale;

	$defaults = array(
		'display_as_dropdown' => false,
		'show_post_count' => true,
		'type' => 'monthly',
		'order' => 'desc',
		'limit' => 0
	);
	$args = apply_filters('cde_display_event_archives_args', wp_parse_args($args, $defaults));
	
	$archives = $counts = array();
	$cut = ($args['type'] === 'yearly' ? 4 : 7);

	$events = get_posts(
		array(
			'post_type' => 'event',
			'suppress_filters' => false,
			'posts_per_page' => -1
		)
	);

	foreach($events as $event)
	{
		$startdatetime = get_post_meta($event->ID, '_event_start_date', true);
		$enddatetime = get_post_meta($event->ID, '_event_end_date', true);

		if(!empty($startdatetime))
		{
			$start_ym = substr($startdatetime, 0, $cut);
			$archives[] = $start_ym;

			if(isset($counts[$start_ym]))
				$counts[$start_ym]++;
			else
				$counts[$start_ym] = 1;
		}

		if(!empty($enddatetime))
		{
			$end_ym = substr($enddatetime, 0, $cut);
			$archives[] = $end_ym;

			if($start_ym !== $end_ym)
			{
				if(isset($counts[$end_ym]))
					$counts[$end_ym]++;
				else
					$counts[$end_ym] = 1;
			}
		}
	}

	$archives = array_unique($archives, SORT_STRING);
	natsort($archives);

	$elcde_m = ($args['display_as_dropdown'] === true ? 'select' : 'ul');
	$elcde_i = ($args['display_as_dropdown'] === true ? '<option value="%s">%s%s</option>' : '<li><a href="%s">%s</a>%s</li>');
	$html = sprintf('<%s>', $elcde_m);

	foreach(array_slice(($args['order'] === 'desc' ? array_reverse($archives) : $archives), 0, ($args['limit'] === 0 ? NULL : $args['limit'])) as $archive)
	{
		if($args['type'] === 'yearly')
		{
			$link = cde_get_event_date_link($archive);
			$display = $archive;
		}
		else
		{
			$date = explode('-', $archive);
			$link = cde_get_event_date_link($date[0], $date[1]);
			$display = $wp_locale->get_month($date[1]).' '.$date[0];
		}

		$html .= sprintf(
			$elcde_i,
			$link,
			$display,
			($args['show_post_count'] === true ? ' ('.$counts[$archive].')' : '')
		);
	}

	$html .= sprintf('</%s>', $elcde_m);

	return $html;
}


/**
 * Display google map
 */
function cde_display_google_map($args = array(), $locations = 0)
{
	$defaults = array(
		'width' => '100%',
		'height' => '200px',
		'zoom' => 15,
		'maptype' => 'roadmap',
		'locations' => ''
	);

	$defaults_bool = array(
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
	);

	$args = apply_filters('cde_display_google_map_args', array_merge($defaults, $defaults_bool, $args));

	$tmp = array();

	foreach($args as $arg => $value)
	{
		if(in_array($arg, array_keys($defaults_bool), true))
		{
			$tmp[$arg] = ($value === true ? 'on' : 'off');
		}
	}

	extract(array_merge($args, $tmp), EXTR_PREFIX_ALL, 'cde');

	if(is_array($locations) && !empty($locations))
	{
		$locations_tmp = array();

		foreach($locations as $location)
		{
			$locations_tmp[] = (int)$location->term_id;
		}

		$locations_tmp = array_unique($locations_tmp);
		$cde_locations = implode(',', $locations_tmp);
	}
	elseif(is_numeric($locations))
		$cde_locations = ((int)$locations !== 0 ? (int)$locations : '');

	echo do_shortcode('[cde-google-map locations="'.$cde_locations.'" width="'.$cde_width.'" height="'.$cde_height.'" zoom="'.$cde_zoom.'" maptype="'.$cde_maptype.'" maptypecontrol="'.$cde_maptypecontrol.'" zoomcontrol="'.$cde_zoomcontrol.'" streetviewcontrol="'.$cde_streetviewcontrol.'" overviewmapcontrol="'.$cde_overviewmapcontrol.'" pancontrol="'.$cde_pancontrol.'" rotatecontrol="'.$cde_rotatecontrol.'" scalecontrol="'.$cde_scalecontrol.'" draggable="'.$cde_draggable.'" keyboardshortcuts="'.$cde_keyboardshortcuts.'" scrollzoom="'.$cde_scrollzoom.'"]');
}


/**
 * Display events full calendar
 */
function cde_display_calendar($args = array())
{
	// get settings
	$options = get_option('cdash_events_general');

	$defaults = array(
		'start_after' => '',
		'start_before' => '',
		'end_after' => '',
		'end_before' => '',
		'ondate' => '',
		'date_range' => 'between',
		'date_type' => 'all',
		'ticket_type' => 'all',
		'show_past_events' => (int)$options['show_past_events'],
		'show_occurrences' => 1,
		'post_type' => 'event',
		'author' => array()
	);

	// parse arguments
	$args = apply_filters('cde_display_calendar_args', wp_parse_args($args, $defaults));

	// create strings
	$args['start_after'] = (string)$args['start_after'];
	$args['start_before'] = (string)$args['start_before'];
	$args['end_after'] = (string)$args['end_after'];
	$args['end_before'] = (string)$args['end_before'];
	$args['ondate'] = (string)$args['ondate'];

	// valid date range?
	if(!in_array($args['date_range'], array('between', 'outside'), true))
		$args['date_range'] = $defaults['date_range'];

	// valid date type?
	if(!in_array($args['date_type'], array('all', 'all_day', 'not_all_day'), true))
		$args['date_type'] = $defaults['date_type'];

	// valid ticket type?
	if(!in_array($args['ticket_type'], array('all', 'free', 'paid'), true))
		$args['ticket_type'] = $defaults['ticket_type'];

	// make bitwise integers
	$args['show_past_events'] = (int)(bool)$args['show_past_events'];
	$args['show_occurrences'] = (int)(bool)$args['show_occurrences'];

	$authors = array();

	if(!is_array($args['author']))
		$args['author'] = array((int)$args['author']);

	if(!(empty($args['author']) || $args['author'][0] === 0))
	{
		// some magic to handle both string and array
		$users = explode(',', implode(',', $args['author']));

		foreach($users as $author)
		{
			$authors[] = (int)$author;
		}
	}

	if(!empty($authors))
		// removes possible duplicates and makes string from it
		$args['author'] = implode(',', array_unique($authors));
	else
		$args['author'] = '';

	// display calendar
	echo do_shortcode('[events_calendar start_after="'.$args['start_after'].'" start_before="'.$args['start_before'].'" end_after="'.$args['end_after'].'" end_before="'.$args['end_before'].'" date_range="'.$args['date_range'].'" date_type="'.$args['date_type'].'" ticket_type="'.$args['ticket_type'].'" ondate="'.$args['ondate'].'" show_past_events="'.$args['show_past_events'].'" show_occurrences="'.$args['show_occurrences'].'" post_type="'.$args['post_type'].'" author="'.$args['author'].'"]');
}


/**
 * Get template part (for templates like the content-event.php)
 */
function cde_get_template_part($slug, $name = '') 
{
	$template = '';

	// look in yourtheme/slug-name.php and yourtheme/cdash-events/slug-name.php
	if($name)
		$template = locate_template(array("{$slug}-{$name}.php"));

	// get default slug-name.php
	if(!$template && $name && file_exists(CDASH_EVENTS_PATH."/templates/{$slug}-{$name}.php"))
		$template = CDASH_EVENTS_PATH."/templates/{$slug}-{$name}.php";

	// if template file doesn't exist, look in yourtheme/slug.php and yourtheme/cdash-events/slug.php
	if(!$template)
		$template = locate_template( array( "{$slug}.php"));

	$template = apply_filters('cde_get_template_part', $template, $slug, $name);

	if($template)
		load_template($template, false);
}


/**
 * Get other templates (e.g. archives) passing attributes and including the file.
 */
function cde_get_template($template_name, $args = array(), $template_path = '', $default_path = '')
{
	if ($args && is_array($args))
		extract($args);

	$located = cde_locate_template($template_name, $template_path, $default_path);

	if (!file_exists($located))
		return;

	do_action('cde_template_part_before', $template_name, $template_path, $located, $args);

	include($located);

	do_action('cde_template_part_after', $template_name, $template_path, $located, $args);
}


/**
 * Locate a template and return the path for inclusion.
 */
function cde_locate_template($template_name, $template_path = '', $default_path = '')
{
	if (!$template_path)
		$template_path = TEMPLATEPATH . '/';

	if (!$default_path)
		$default_path = CDASH_EVENTS_PATH . 'templates/';

	// look within passed path within the theme - this is priority
	$template = locate_template(array(
		trailingslashit($template_path) . $template_name,
		$template_name
	));
	
	// get default template
	if (!$template)
		$template = $default_path . $template_name;

	// return what we found
	return apply_filters('cde_locate_template', $template, $template_name, $template_path);
}
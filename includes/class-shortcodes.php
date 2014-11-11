<?php
if(!defined('ABSPATH')) exit;

new Cdash_Events_Shortcodes($cdash_events);

class Cdash_Events_Shortcodes
{
	private $options = array();
	private $cdash_events;


	public function __construct($cdash_events)
	{
		// settings
		$this->options = $cdash_events->get_options();

		// main object
		$this->events_maker = $cdash_events;

		// actions
		add_action('init', array(&$this, 'register_shortcodes'));
	}


	/**
	 * 
	*/
	public function register_shortcodes()
	{
		add_shortcode('events_calendar', array(&$this, 'calendar_shortcode'));
		add_shortcode('cde-google-map', array(&$this, 'google_map_shortcode'));
	}


	/**
	 * 
	*/
	public function calendar_shortcode($args)
	{
		$defaults = array(
			'start_after' => '',
			'start_before' => '',
			'end_after' => '',
			'end_before' => '',
			'ondate' => '',
			'date_range' => 'between',
			'date_type' => 'all',
			'ticket_type' => 'all',
			'show_past_events' => true,
			'show_occurrences' => true,
			'post_type' => 'event',
			'author' => ''
		);

		// parse arguments
		$args = shortcode_atts($defaults, $args);

		// makes strings
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

		// makes bitwise integers
		$args['show_past_events'] = (bool)(int)$args['show_past_events'];
		$args['show_occurrences'] = (bool)(int)$args['show_occurrences'];

		$authors = $users = array();

		if(trim($args['author']) !== '')
			$users = explode(',', $args['author']);

		if(!empty($users))
		{
			foreach($users as $author)
			{
				$authors[] = (int)$author;
			}

			// removes possible duplicates
			$args['author__in'] = array_unique($authors);
		}

		// unset author argument
		unset($args['author']);

		// sets new arguments
		$args['event_start_after'] = $args['start_after'];
		$args['event_start_before'] = $args['start_before'];
		$args['event_end_after'] = $args['end_after'];
		$args['event_end_before'] = $args['end_before'];
		$args['event_ondate'] = $args['ondate'];
		$args['event_date_range'] = $args['date_range'];
		$args['event_date_type'] = $args['date_type'];
		$args['event_ticket_type'] = $args['ticket_type'];
		$args['event_show_past_events'] = $args['show_past_events'];
		$args['event_show_occurrences'] = $args['show_occurrences'];

		// unsets old arguments
		unset($args['start_after']);
		unset($args['start_before']);
		unset($args['end_after']);
		unset($args['end_before']);
		unset($args['ondate']);
		unset($args['date_range']);
		unset($args['date_type']);
		unset($args['ticket_type']);
		unset($args['show_past_events']);
		unset($args['show_occurrences']);

		wp_register_script(
			'cdash-events-moment',
			CDASH_EVENTS_URL.'/assets/fullcalendar/moment.min.js',
			array('jquery')
		);

		wp_register_script(
			'cdash-events-fullcalendar',
			CDASH_EVENTS_URL.'/assets/fullcalendar/fullcalendar.min.js',
			array('jquery', 'cdash-events-moment')
		);

		wp_register_script(
			'cdash-events-front-calendar',
			CDASH_EVENTS_URL.'/js/front-calendar.js',
			array('jquery', 'jquery-ui-core', 'cdash-events-fullcalendar')
		);

		wp_enqueue_script('cdash-events-front-calendar');

		$locale = str_replace('_', '-', strtolower(get_locale()));
		$locale_code = explode('-', $locale);

		if(file_exists(CDASH_EVENTS_PATH.'assets/fullcalendar/lang/'.$locale.'.js'))
			$lang_path = CDASH_EVENTS_URL.'/assets/fullcalendar/lang/'.$locale.'.js';
		elseif(file_exists(CDASH_EVENTS_PATH.'assets/fullcalendar/lang/'.$locale_code[0].'.js'))
			$lang_path = CDASH_EVENTS_URL.'/assets/fullcalendar/lang/'.$locale_code[0].'.js';

		if(isset($lang_path))
		{
			wp_register_script(
				'cdash-events-front-calendar-lang',
				$lang_path,
				array('jquery', 'jquery-ui-core', 'cdash-events-front-calendar')
			);

			wp_enqueue_script('cdash-events-front-calendar-lang');
		}

		// filter hook for calendar events args, allow any query modifications
		$args = apply_filters('cde_get_full_calendar_events_args', $args);

		wp_localize_script(
			'cdash-events-front-calendar',
			'emCalendarArgs',
			array(
				'firstWeekDay' => ($this->options['general']['first_weekday'] === 7 ? 0 : 1),
				'events' => $this->get_full_calendar_events($args)
			)
		);

		wp_register_style(
			'cdash-events-front-calendar',
			CDASH_EVENTS_URL.'/assets/fullcalendar/fullcalendar.min.css'
		);

		wp_register_style(
			'cdash-events-front-calendar-print',
			CDASH_EVENTS_URL.'/assets/fullcalendar/fullcalendar.print.css',
			array(),
			false,
			'print'
		);

		wp_enqueue_style('cdash-events-front-calendar');
		wp_enqueue_style('cdash-events-front-calendar-print');

		return '<div id="events-full-calendar"></div>';
	}


	/**
	 * 
	*/
	private function get_full_calendar_events($args)
	{
		$events = cde_get_events($args);
		$calendar = array();

		if(empty($events))
			return $calendar;

		foreach($events as $event)
		{
			$classes = array();
			$event_categories = wp_get_post_terms($event->ID, 'event-category');
			$event_tags = wp_get_post_terms($event->ID, 'event-tag');

			if(!empty($event_categories) && !is_wp_error($event_categories))
			{
				$term_meta = get_option('event_category_'.$event_categories[0]->term_id);

				foreach($event_categories as $category)
				{
					$classes[] = "fc-event-cat-".$category->slug;
					$classes[] = "fc-event-cat-".$category->term_id;
				}
			}

			if(!empty($event_tags) && !is_wp_error($event_tags))
			{
				foreach($event_tags as $tag)
				{
					$classes[] = "fc-event-tag-".$tag->slug;
					$classes[] = "fc-event-tag-".$tag->term_id;
				}
			}

			if(cde_is_recurring($event->ID))
			{
				$start = $event->event_occurrence_start_date;
				$end = $event->event_occurrence_end_date;
			}
			else
			{
				$start = get_post_meta($event->ID, '_event_start_date', true);
				$end = get_post_meta($event->ID, '_event_end_date', true);
			}

			$calendar[] = array(
				'title' => $event->post_title,
				'start' => $start,
				'end' => $end,
				'className' => implode(' ', $classes),
				'allDay' => cde_is_all_day($event->ID),
				'url' => get_permalink($event->ID),
				'backgroundColor' => (isset($term_meta['color']) ? $term_meta['color'] : '')
			);
		}

		return $calendar;
	}


	/**
	 * 
	*/
	public function google_map_shortcode($args)
	{
		$markers = array();
		$map_types = array('hybrid', 'roadmap', 'satellite', 'terrain');
		$booleans = array('on', 'off');
		$defaults = array(
			'width' => '100%',
			'height' => '200px',
			'zoom' => 15,
			'maptype' => 'ROADMAP',
			'locations' => '',
			'maptypecontrol' => 'on',
			'zoomcontrol' => 'on',
			'streetviewcontrol' => 'on',
			'overviewmapcontrol' => 'off',
			'pancontrol' => 'off',
			'rotatecontrol' => 'off',
			'scalecontrol' => 'off',
			'draggable' => 'on',
			'keyboardshortcuts' => 'on',
			'scrollzoom' => 'on'
		);

		$args = shortcode_atts($defaults, $args);
		$args['zoom'] = (int)$args['zoom'];

		if(!in_array(strtolower($args['maptype']), $map_types, TRUE))
			$args['maptype'] = $defaults['maptype'];

		$args['maptype'] = strtoupper($args['maptype']);
		$args['maptypecontrol'] = $this->get_proper_arg($args['maptypecontrol'], $defaults['maptypecontrol'], $booleans);
		$args['zoomcontrol'] = $this->get_proper_arg($args['zoomcontrol'], $defaults['zoomcontrol'], $booleans);
		$args['streetviewcontrol'] = $this->get_proper_arg($args['streetviewcontrol'], $defaults['streetviewcontrol'], $booleans);
		$args['overviewmapcontrol'] = $this->get_proper_arg($args['overviewmapcontrol'], $defaults['overviewmapcontrol'], $booleans);
		$args['pancontrol'] = $this->get_proper_arg($args['pancontrol'], $defaults['pancontrol'], $booleans);
		$args['rotatecontrol'] = $this->get_proper_arg($args['rotatecontrol'], $defaults['rotatecontrol'], $booleans);
		$args['scalecontrol'] = $this->get_proper_arg($args['scalecontrol'], $defaults['scalecontrol'], $booleans);
		$args['draggable'] = $this->get_proper_arg($args['draggable'], $defaults['draggable'], $booleans);
		$args['keyboardshortcuts'] = $this->get_proper_arg($args['keyboardshortcuts'], $defaults['keyboardshortcuts'], $booleans);
		$args['scrollzoom'] = $this->get_proper_arg($args['scrollzoom'], $defaults['scrollzoom'], $booleans);

		//location ids
		$locations = ($args['locations'] !== '' ? explode(',', $args['locations']) : '');

		if(is_array($locations) && !empty($locations))
		{
			$locations_tmp = array();

			foreach($locations as $location)
			{
				$locations_tmp[] = (int)$location;
			}

			foreach(array_unique($locations_tmp) as $location_id)
			{
				$location = cde_get_location($location_id);
				
				if (!empty($location->location_meta['latitude']) && !empty($location->location_meta['latitude']))
				{
					$location->location_meta['name'] = $location->name;
					$markers[] = $location->location_meta;
				}
			}
		}
		elseif(is_tax('event-location') || (in_array(get_post_type(), apply_filters('cde_event_post_type', array('event'))) && is_single()))
		{
			$term = get_queried_object();

			if(isset($term->term_id))
			{
				$location = cde_get_location($term->term_id);
				
				if (!empty($location->location_meta['latitude']) && !empty($location->location_meta['latitude']))
				{
					$location->location_meta['name'] = $location->name;
					$markers[] = $location->location_meta;
				}
			}
			elseif(isset($term->ID))
			{
				$locations = cde_get_locations_for($term->ID);

				if(is_array($locations) && !empty($locations))
				{
					foreach($locations as $location)
					{
						if (!empty($location->location_meta['latitude']) && !empty($location->location_meta['latitude']))
						{
							$location->location_meta['name'] = $location->name;
							$markers[] = $location->location_meta;
						}
					}
				}
			}
		}

		wp_register_script(
			'cdash-events-google-maps',
			'https://maps.googleapis.com/maps/api/js?sensor=false&language='.substr(get_locale(), 0, 2)
		);

		wp_register_script(
			'cdash-events-front-locations',
			CDASH_EVENTS_URL.'/js/front-locations.js',
			array('jquery', 'cdash-events-google-maps')
		);

		wp_enqueue_script('cdash-events-front-locations');

		wp_localize_script(
			'cdash-events-front-locations',
			'emMapArgs',
			array(
				'markers' => $markers,
				'zoom' => $args['zoom'],
				'mapTypeId' => $args['maptype'],
				'mapTypeControl' => $args['maptypecontrol'],
				'zoomControl' => $args['zoomcontrol'],
				'streetViewControl' => $args['streetviewcontrol'],
				'overviewMapControl' => $args['overviewmapcontrol'],
				'panControl' => $args['pancontrol'],
				'rotateControl' => $args['rotatecontrol'],
				'scaleControl' => $args['scalecontrol'],
				'draggable' => $args['draggable'],
				'keyboardShortcuts' => $args['keyboardshortcuts'],
				'scrollwheel' => $args['scrollzoom']
			)
		);

		return '<div id="event-google-map" style="width: '.$args['width'].'; height: '.$args['height'].';"></div>';
	}


	/**
	 * 
	*/
	private function get_proper_arg($arg, $default, $array)
	{
		$arg = strtolower($arg);

		if(!in_array($arg, $array, TRUE))
			$arg = $default;

		if($arg === 'on')
			return 1;
		else
			return 0;
	}
}
?>
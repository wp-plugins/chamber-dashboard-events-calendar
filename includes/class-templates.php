<?php
if(!defined('ABSPATH')) exit;

new Cdash_Events_Templates($cdash_events);

class Cdash_Events_Templates
{

	public function __construct($cdash_events)
	{
		//filters
		add_filter('the_content', array(&$this, 'single_event_view'));
		add_filter('the_content', array(&$this, 'event_taxonomy_view'));
	}

	public function single_event_view($content) {
		if( is_singular('event') ) {
			$options = get_option('cdash_events_general');
			$post_id = get_the_id();
			$event_content = '';

			// display event thumbnail if we need to
			if(has_post_thumbnail() && isset($options['thumbnail_display_options']['single_thumbnail']) && $options['thumbnail_display_options']['single_thumbnail'] == true) {
				$event_content .= get_the_post_thumbnail();
			}

			// display event date/time
			global $post;
			$date 			= cde_get_the_date($post->ID, array('format' => array('date' => 'Y-m-d', 'time' => 'G:i')));
			$all_day_event 	= cde_is_all_day($post->ID);
			$format			= '';

			// date format options
			$options = get_option('cdash_events_general');
			$date_format = $options['datetime_format']['date'];
			$time_format = $options['datetime_format']['time'];
			
			// if format was set, use it
			if(!empty($format) && is_array($format)) {
				$date_format = (!empty($format['date']) ? $format['date'] : $date_format);
				$time_format = (!empty($format['time']) ? $format['time'] : $time_format);
			}
		
			// is all day
			if($all_day_event && !empty($date['start']) && !empty($date['end'])) {
				// format date (date only)
				$date['start'] = cde_format_date($date['start'], 'date', $date_format);
				$date['end'] = cde_format_date($date['end'], 'date', $date_format);
		
				// one day only
				if($date['start'] === $date['end']) {
					$date_output = $date['start'];
				} else { // more than one day
					$date_output = implode(' - ', $date); 
				}
			}
			// is not all day, one day, different hours
			elseif(!$all_day_event && !empty($date['start']) && !empty($date['end'])) {
				// one day only
				if(cde_format_date($date['start'], 'date') === cde_format_date($date['end'], 'date')) {
					$date_output = cde_format_date($date['start'], 'datetime', $format)  . ' - '  . cde_format_date($date['end'], 'time', $format); 
				} else { // more than one day
					$date_output = cde_format_date($date['start'], 'datetime', $format) . ' - ' . cde_format_date($date['end'], 'datetime', $format); 
				}
			}
			// any other case
			else {		
				$date_output = cde_format_date($date['start'], 'datetime', $format) . ' - ' . cde_format_date($date['end'], 'datetime', $format);  
			}
		
			// output date
			$event_content .= sprintf('<span class="entry-date date"><abbr class="dtstart" title="%1$s"></abbr><abbr class="dtend" title="%2$s"></abbr>%3$s</span>',
						esc_attr($date['start']),
						esc_attr($date['end']),
						esc_html($date_output)
					);

			// display repeat dates
			$occurrences 	= cde_get_occurrences();
			$all_day_event 	= cde_is_all_day();
		
			if (!empty($occurrences)) {
				foreach ($occurrences as $date)
				{
					// is all day
					if($all_day_event && !empty($date['start']) && !empty($date['end'])) {
						// format date (date only)
						$date['start'] = cde_format_date($date['start'], 'date', $date_format);
						$date['end'] = cde_format_date($date['end'], 'date', $date_format);
				
						// one day only
						if($date['start'] === $date['end']) {
							$date_output = $date['start'];
						} else { // more than one day
							$date_output = implode(' - ', $date); 
						}
					}
					// is not all day, one day, different hours
					elseif(!$all_day_event && !empty($date['start']) && !empty($date['end'])) {
						// one day only
						if(cde_format_date($date['start'], 'date') === cde_format_date($date['end'], 'date')) {
							$date_output = cde_format_date($date['start'], 'datetime', $format)  . ' - ' . cde_format_date($date['end'], 'time', $format); 
						} else { // more than one day
							$date_output = cde_format_date($date['start'], 'datetime', $format) . ' - ' . cde_format_date($date['end'], 'datetime', $format); 
						}
					} else 	{		
						$date_output = cde_format_date($date['start'], 'datetime', $format) . ' - ' . cde_format_date($date['end'], 'datetime', $format);  
					}
					
					// output format
					$event_content .= sprintf('<br /><span class="entry-date date"><abbr class="dtstart" title="%1$s"></abbr><abbr class="dtend" title="%2$s"></abbr>%3$s</span>',
						esc_attr($date['start']),
						esc_attr($date['end']),
						esc_html($date_output)
					);

				}
			}

			// Event description
			$event_content .= $content;

			// display locations
			$locations = cde_get_locations_for($post_id);
			if(!empty($locations) || !is_wp_error($locations)) {
				$event_display_options = get_post_meta($post_id, '_event_display_options', TRUE); // event display options
				if (!empty($event_display_options) && $event_display_options['display_location_details'] === 1) {
					$event_content .= __('<div class="location"><strong>Location: </strong>', 'cdash-events');
			        	
		        	foreach ($locations as $term) :
						
						$event_content .= '<span class="single-location term-' . $term->term_id . '">';
		            	$term_link = get_term_link($term->slug, 'event-location');
		                
		                if (is_wp_error($term_link))
		                	continue;
						
						$event_content .= '<a href="' . $term_link . '" class="location">' . $term->name . '</a>';
						
						// Location details
						$location_details = $term->location_meta;
						if ($location_details) :
							$event_content .= ' ';
							$event_content .= !empty($location_details['address']) ? $location_details['address'] . ' ' : '';
							$event_content .= !empty($location_details['zip']) ? $location_details['zip'] . ' ' : '';
							$event_content .= !empty($location_details['city']) ? $location_details['city'] . ' ' : '';
							$event_content .= !empty($location_details['state']) ? $location_details['state'] . ' ' : '';
							$event_content .= !empty($location_details['country']) ? $location_details['country'] . ' ' : '';
							$event_content .= ' ';
						endif;
						
						$event_content .= '</span></div>';
						
		            endforeach;
				} else {
					$event_content .= '<div class="location">';
					$event_content .= get_the_term_list($post_id, 'event-location', __('<strong>Location: </strong>', 'cdash-events'), ', ', '');
					$event_content .= '</div>';
				}
			}

			// display Google map
			if ($event_display_options['google_map']) {
				if (isset($locations) || !empty($locations)) {	
					$args = apply_filters('cde_single_event_google_map_args', array(
						'width' => '100%',
						'height' => '200px',
						'zoom' => 15,
						'maptype' => 'roadmap',
						'locations' => '',
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
					$locations_tmp = array();
					foreach($locations as $location)
					{
						$locations_tmp[] = (int)$location->term_id;
					}
					$locations_tmp = array_unique($locations_tmp);
					$cde_locations = implode(',', $locations_tmp);
					$event_content .= do_shortcode('[cde-google-map locations="'.$cde_locations.'" width="'.$args['width'].'" height="'.$args['height'].'" zoom="'.$args['zoom'].'" maptype="'.$args['maptype'].'" maptypecontrol="'.$args['maptypecontrol'].'" zoomcontrol="'.$args['zoomcontrol'].'" streetviewcontrol="'.$args['streetviewcontrol'].'" overviewmapcontrol="'.$args['overviewmapcontrol'].'" pancontrol="'.$args['pancontrol'].'" rotatecontrol="'.$args['rotatecontrol'].'" scalecontrol="'.$args['scalecontrol'].'" draggable="'.$args['draggable'].'" keyboardshortcuts="'.$args['keyboardshortcuts'].'" scrollzoom="'.$args['scrollzoom'].'"]');

				}
			}

			// display event tickets
			$display_options = get_post_meta($post_id, '_event_display_options', true); 

			// tickets enabled?
			if ($display_options['price_tickets_info']) {
				$tickets = get_post_meta($post_id, '_event_tickets', true);
				if ($tickets) {
					$event_content .= "<div class='event-tickets tickets'>";
			   		$event_content .= "<span class='tickets-label'><strong>" . __('Tickets: ', 'cdash-events') . "</strong></span><br />";
			   		foreach ($tickets as $ticket) {
			   			$ticketname = esc_html($ticket['name']);
						$ticketprice = esc_html(cde_get_currency_symbol($ticket['price']));
			       		$event_content .= "<span class='event-ticket'><span class='ticket-name'>" . $ticketname . ": </span><span class='ticket-price'>" . $ticketprice . "</span></span><br />";
			       	}
			   		$event_content .= "</div>";
			   	} else {
			   		$event_content .= "<div class='event-tickets tickets'>";
			   		$event_content .= "<span class='tickets-label'><strong>" . __('Tickets: ', 'cdash-events') . "</strong></span><br />";
			   		$event_content .= "<span class='event-ticket'><span class='ticket-name'>" . _e('Free', 'cdash-events') . "</span></span>";
					$event_content .= "</div>";
				}
		
				$tickets_url = apply_filters('cde_single_event_tickets_url', get_post_meta($post_id, '_event_tickets_url', true));
				if ($tickets_url) {
					$tickurl = esc_url($tickets_url);
					$event_content .= "<div class='event-tickets-url tickets'>";
					$event_content .= "<span class='tickets-url-label'><strong>" . __('Buy tickets: ', 'cdash-events') . "</strong></span>";
					$event_content .= "<a href='" . $tickurl . "' class='tickets-url-link' rel='nofollow' target='_blank'>" . $tickurl . "</a>";
					$event_content .= "</div>";
				}
			}

			// display categories
			$categories = get_the_term_list($post_id, 'event-category', __('<strong>Category: </strong>', 'cdash-events'), ', ', '');
			if ($categories && !is_wp_error($categories)) 
			{ 
				$event_content .=  "<p class='event-categories'>" . $categories . "</p>"; 
			}

			// Event tags
			$tags = get_the_term_list($post_id, 'event-tag', __('<strong>Tags: </strong>', 'cdash-events'), '', '');
			if ($tags && !is_wp_error($tags)) {
				$event_content .= "<span class='term-list event-tag tag-links'>" . $tags . "</span>";
			}

			$content = $event_content;
		}

		return $content;
	}

	public function event_taxonomy_view($content) {
		if(is_tax('event-category') || is_tax('event-tag') || is_tax('event-location')) {
			$options = get_option('cdash_events_general');
			$post_id = get_the_id();
			$tax_content = '';

			// display event thumbnail if we need to
			if(has_post_thumbnail() && isset($options['thumbnail_display_options']['archive_thumbnail']) && $options['thumbnail_display_options']['archive_thumbnail'] == true) {
				$tax_content .= get_the_post_thumbnail();
			}

			// display event date/time
			global $post;
			$date 			= cde_get_the_date($post->ID, array('format' => array('date' => 'Y-m-d', 'time' => 'G:i')));
			$all_day_event 	= cde_is_all_day($post->ID);
			$format			= '';

			// date format options
			$options = get_option('cdash_events_general');
			$date_format = $options['datetime_format']['date'];
			$time_format = $options['datetime_format']['time'];
			
			// if format was set, use it
			if(!empty($format) && is_array($format)) {
				$date_format = (!empty($format['date']) ? $format['date'] : $date_format);
				$time_format = (!empty($format['time']) ? $format['time'] : $time_format);
			}
		
			// is all day
			if($all_day_event && !empty($date['start']) && !empty($date['end'])) {
				// format date (date only)
				$date['start'] = cde_format_date($date['start'], 'date', $date_format);
				$date['end'] = cde_format_date($date['end'], 'date', $date_format);
		
				// one day only
				if($date['start'] === $date['end']) {
					$date_output = $date['start'];
				} else { // more than one day
					$date_output = implode(' - ', $date); 
				}
			}
			// is not all day, one day, different hours
			elseif(!$all_day_event && !empty($date['start']) && !empty($date['end'])) {
				// one day only
				if(cde_format_date($date['start'], 'date') === cde_format_date($date['end'], 'date')) {
					$date_output = cde_format_date($date['start'], 'datetime', $format)  . ' - '  . cde_format_date($date['end'], 'time', $format); 
				} else { // more than one day
					$date_output = cde_format_date($date['start'], 'datetime', $format) . ' - ' . cde_format_date($date['end'], 'datetime', $format); 
				}
			}
			// any other case
			else {		
				$date_output = cde_format_date($date['start'], 'datetime', $format) . ' - ' . cde_format_date($date['end'], 'datetime', $format);  
			}
		
			// output date
			$tax_content .= sprintf('<span class="entry-date date"><abbr class="dtstart" title="%1$s"></abbr><abbr class="dtend" title="%2$s"></abbr>%3$s</span><br />',
						esc_attr($date['start']),
						esc_attr($date['end']),
						esc_html($date_output)
					);

			// display locations
			$locations = cde_get_locations_for($post_id);
			if(!empty($locations) || !is_wp_error($locations)) {
				$event_display_options = get_post_meta($post_id, '_event_display_options', TRUE); // event display options
				if (!empty($event_display_options) && $event_display_options['display_location_details'] === 1) {
					$tax_content .= __('<strong>Location: </strong>', 'cdash-events');
			        	
		        	foreach ($locations as $term) :
						
						$tax_content .= '<span class="single-location term-' . $term->term_id . '">';
		            	$term_link = get_term_link($term->slug, 'event-location');
		                
		                if (is_wp_error($term_link))
		                	continue;
						
						$tax_content .= '<a href="' . $term_link . '" class="location">' . $term->name . '</a>';
						
						// Location details
						$location_details = $term->location_meta;
						if ($location_details) :
							$tax_content .= ' ';
							$tax_content .= !empty($location_details['address']) ? $location_details['address'] . ' ' : '';
							$tax_content .= !empty($location_details['zip']) ? $location_details['zip'] . ' ' : '';
							$tax_content .= !empty($location_details['city']) ? $location_details['city'] . ' ' : '';
							$tax_content .= !empty($location_details['state']) ? $location_details['state'] . ' ' : '';
							$tax_content .= !empty($location_details['country']) ? $location_details['country'] . ' ' : '';
							$tax_content .= ' ';
						endif;
						
						$tax_content .= '</span>';
						
		            endforeach;
				} else {
					$tax_content .= get_the_term_list($post_id, 'event-location', __('<strong>Location: </strong>', 'cdash-events'), ', ', '');
				}
			}

			// Event description
			$tax_content .= $content;


			$content = $tax_content;
		}

		return $content;
	}

}
?>
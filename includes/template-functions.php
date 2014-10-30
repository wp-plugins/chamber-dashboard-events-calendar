<?php
/**
 * Events Maker pluggable template functions
 *
 * Override any of those functions by copying it to your theme or replace it via plugin
 *
 * @author 	Digital Factory
 * @package Events Maker/Functions
 * @version 1.1.0
 */
 
if(!defined('ABSPATH')) exit;

/**
 * Display events list
 */
if (!function_exists('cde_display_events'))
{
	function cde_display_events($args = array())
	{
		$options = get_option('cdash_events_general');
		
		$defaults = array(
			'number_of_events' => 5,
			'thumbnail_size' => 'thumbnail',
			'categories' => array(),
			'locations' => array(),
			'order_by' => 'start',
			'order' => 'asc',
			'show_past_events' => true,
			'show_occurrences' => true,
			'show_event_thumbnail' => true,
			'show_event_excerpt' => false,
			'no_events_message' => __('No Events', 'cdash-events'),
			'date_format' => $options['datetime_format']['date'],
			'time_format' => $options['datetime_format']['time']
		);
	
		$args = apply_filters('cde_display_events_args', array_merge($defaults, $args));

		$events_args = array(
			'post_type' => 'event',
			'suppress_filters' => false,
			'posts_per_page' => ($args['number_of_events'] === 0 ? -1 : $args['number_of_events']),
			'order' => $args['order'],
			'event_show_past_events' => (bool)$args['show_past_events'],
			'event_show_occurrences' => (bool)$args['show_occurrences']
		);
	
		if(!empty($args['categories']))
		{
			$events_args['tax_query'][] = array(
				'taxonomy' => 'event-category',
				'field' => 'id',
				'terms' => $args['categories'],
				'include_children' => false,
				'operator' => 'IN'
			);
		}
	
		if(!empty($args['locations']))
		{
			$events_args['tax_query'][] = array(
				'taxonomy' => 'event-location',
				'field' => 'id',
				'terms' => $args['locations'],
				'include_children' => false,
				'operator' => 'IN'
			);
		}
	
		if($args['order_by'] === 'start' || $args['order_by'] === 'end')
		{
			$events_args['orderby'] = 'meta_value';
			$events_args['meta_key'] = '_event_'.$args['order_by'].'_date';
		}
		elseif($args['order_by'] === 'publish')
			$events_args['orderby'] = 'date';
		else
			$events_args['orderby'] = 'title';
			
		$events = get_posts($events_args);
		
		if ($events)
		{
			ob_start();
			
			echo apply_filters('cde_display_events_wrapper_start', '<ul class="events-list">');
			
			foreach ($events as $post)
			{
				setup_postdata($post);
				
				cde_get_template('content-widget-event.php', array($post, $args));
			}
			
			wp_reset_postdata();
			
			echo apply_filters('cde_display_events_wrapper_end', '</ul>');
			
			$html = ob_get_contents();
			ob_end_clean();
			
			return apply_filters('cde_display_events', $html);
		}
		else
			return $args['no_events_message'];
	}
}


/**
 * Display event categories
 */
if (!function_exists('cde_display_event_categories'))
{
	function cde_display_event_categories($post_id = 0)
	{
		$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);
		
		if(empty($post_id))
			return false;
		
		$categories = get_the_term_list($post_id, 'event-category', __('<strong>Category: </strong>', 'cdash-events'), ', ', '');
		if ($categories && !is_wp_error($categories)) 
		{ ?>
			<div class="entry-meta">
				
				<span class="term-list event-category cat-links"><?php echo $categories; ?></span>
				
			</div>
		<?php
		}
	}
}


/**
 * Display event tags
 */
if (!function_exists('cde_display_event_tags'))
{
	function cde_display_event_tags($post_id = 0)
	{
		$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);
		
		if(empty($post_id))
			return false;
		
		$tags = get_the_term_list($post_id, 'event-tag', __('<strong>Tags: </strong>', 'cdash-events'), '', '');
		if ($tags && !is_wp_error($tags)) 
		{ ?>
			<footer class="entry-meta">
				
				<span class="term-list event-tag tag-links"><?php echo $tags; ?></span>
				
			</footer>
		<?php
		}
	}
}


/**
 * Display event locations
 */
if (!function_exists('cde_display_event_locations'))
{
	function cde_display_event_locations($post_id = 0)
	{
		$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);
		
		if(empty($post_id))
			return false;
		
		$locations = cde_get_locations_for($post_id);
		
		if(empty($locations) || is_wp_error($locations))
			return false;
		?>
		
		<?php $output = get_the_term_list($post_id, 'event-location', __('<strong>Location: </strong>', 'cdash-events'), ', ', ''); ?>
		
		<div class="entry-meta">
			
			<span class="term-list event-location cat-links">

			<?php if (is_single()) : ?>
				
				<?php $event_display_options = get_post_meta($post_id, '_event_display_options', TRUE); // event display options ?>
				
				<?php if (!empty($event_display_options) && $event_display_options['display_location_details'] === 1) : ?>
					
					<?php $output = __('<strong>Location: </strong>', 'cdash-events');
		        	
			        	foreach ($locations as $term) :
							
							$output .= '<span class="single-location term-' . $term->term_id . '">';
			        		
			            	$term_link = get_term_link($term->slug, 'event-location');
			                
			                if (is_wp_error($term_link))
			                	continue;
							
							$output .= '<a href="' . $term_link . '" class="location">' . $term->name . '</a>';
							
							// Location details
							$location_details = $term->location_meta;
							if ($location_details) :
								$output .= ' ';
								$output .= !empty($location_details['address']) ? $location_details['address'] . ' ' : '';
								$output .= !empty($location_details['zip']) ? $location_details['zip'] . ' ' : '';
								$output .= !empty($location_details['city']) ? $location_details['city'] . ' ' : '';
								$output .= !empty($location_details['state']) ? $location_details['state'] . ' ' : '';
								$output .= !empty($location_details['country']) ? $location_details['country'] . ' ' : '';
								$output .= ' ';
							endif;
							
							$output .= '</span>';
							
			            endforeach; ?>
					
				<?php endif; // display location details ?>

			<?php endif; // single ?>
			
			<?php echo $output; ?>
		
			</span>
			
		</div>

    <?php
	}
}


/**
 * Display event tickets
 */
if (!function_exists('cde_display_event_tickets'))
{
	function cde_display_event_tickets($post_id = 0)
	{
		$post_id = (int)(empty($post_id) ? get_the_ID() : $post_id);
		
		if(empty($post_id))
			return false;
		
		cde_get_template('single-event/tickets.php');

	}
}


/**
 * Display event date
 */
if (!function_exists('cde_display_event_date'))
{
	function cde_display_event_date($format = '', $args = array())
	{
		global $post;
		
		$date 			= cde_get_the_date($post->ID, array('format' => array('date' => 'Y-m-d', 'time' => 'G:i')));
		$all_day_event 	= cde_is_all_day($post->ID);
		$html			= '';
		
		// default args
		$defaults = array(
			'separator' => ' - ',
			'format' => 'link',
			'before' => '',
			'after' => '',
			'show_author_link' => false,
			'echo' => true
		);
		$args = apply_filters('cde_display_event_date_args', wp_parse_args($args, $defaults));
		
		// date format options
		$options = get_option('cdash_events_general');
		$date_format = $options['datetime_format']['date'];
		$time_format = $options['datetime_format']['time'];
		
		// if format was set, use it
		if(!empty($format) && is_array($format))
		{
			$date_format = (!empty($format['date']) ? $format['date'] : $date_format);
			$time_format = (!empty($format['time']) ? $format['time'] : $time_format);
		}
		
		// is all day
		if($all_day_event && !empty($date['start']) && !empty($date['end']))
		{
			// format date (date only)
			$date['start'] = cde_format_date($date['start'], 'date', $date_format);
			$date['end'] = cde_format_date($date['end'], 'date', $date_format);
	
			// one day only
			if($date['start'] === $date['end'])
			{
				$date_output = $date['start'];
			}
			// more than one day
			else
			{
				$date_output = implode(' '.$args['separator'].' ', $date); 
			}
		}
		// is not all day, one day, different hours
		elseif(!$all_day_event && !empty($date['start']) && !empty($date['end']))
		{
			// one day only
			if(cde_format_date($date['start'], 'date') === cde_format_date($date['end'], 'date'))
			{
				$date_output = cde_format_date($date['start'], 'datetime', $format)  . ' ' . $args['separator'] . ' ' . cde_format_date($date['end'], 'time', $format); 
			}
			// more than one day
			else
			{
				$date_output = cde_format_date($date['start'], 'datetime', $format) . ' ' . $args['separator'] . ' ' . cde_format_date($date['end'], 'datetime', $format); 
			}
		}
		// any other case
		else 
		{		
			$date_output = cde_format_date($date['start'], 'datetime', $format) . ' ' . $args['separator'] . ' ' . cde_format_date($date['end'], 'datetime', $format);  
		}
		
		// generate output
		$html .= $args['before'];
		
		// output format
		if ($args['format'] == 'link')
			$html .= sprintf('<span class="entry-date date"><a href="%1$s" rel="bookmark"><abbr class="dtstart" title="%2$s"></abbr><abbr class="dtend" title="%3$s"></abbr>%4$s</a></span>',
				esc_url(get_permalink()),
				esc_attr($date['start']),
				esc_attr($date['end']),
				esc_html($date_output)
			);
		else
			$html .= sprintf('<span class="entry-date date"><abbr class="dtstart" title="%1$s"></abbr><abbr class="dtend" title="%2$s"></abbr>%3$s</span>',
				esc_attr($date['start']),
				esc_attr($date['end']),
				esc_html($date_output)
			);
		
		// author link
		if ($args['show_author_link'] === true)
		{
			$html .= sprintf('<span class="byline"><span class="author vcard"><a class="url fn n" href="%1$s" rel="author">%2$s</a></span></span>',
				esc_url(get_author_posts_url(get_the_author_meta('ID'))),
				get_the_author()
			);
		}
		
		$html .= $args['after'];
		
		$html = apply_filters('cde_display_event_date', $html);
		
		if ($args['echo'] === true)
			echo $html;
		else
			return $html;
		
	}
}


/**
 * Display event occurrences date
 */
if (!function_exists('cde_display_event_occurrences'))
{
	function cde_display_event_occurrences($format = '', $args = array())
	{
		$occurrences 	= cde_get_occurrences();
		$all_day_event 	= cde_is_all_day();
		$html			= '';
		
		// default args
		$defaults = array(
			'separator' => ' - ',
			'format' => 'link',
			'before' => '',
			'after' => '',
			'show_author_link' => false,
			'echo' => true
		);
		$args = apply_filters('cde_display_event_occurrences_args', wp_parse_args($args, $defaults));
		
		// date format options
		$options = get_option('cdash_events_general');
		$date_format = $options['datetime_format']['date'];
		$time_format = $options['datetime_format']['time'];
		
		// if format was set, use it
		if(!empty($format) && is_array($format))
		{
			$date_format = (!empty($format['date']) ? $format['date'] : $date_format);
			$time_format = (!empty($format['time']) ? $format['time'] : $time_format);
		}
		
		// generate output
		$html .= $args['before'];
		
		if (!empty($occurrences))
		{
			foreach ($occurrences as $date)
			{
				// is all day
				if($all_day_event && !empty($date['start']) && !empty($date['end']))
				{
					// format date (date only)
					$date['start'] = cde_format_date($date['start'], 'date', $date_format);
					$date['end'] = cde_format_date($date['end'], 'date', $date_format);
			
					// one day only
					if($date['start'] === $date['end'])
					{
						$date_output = $date['start'];
					}
					// more than one day
					else
					{
						$date_output = implode(' '. $args['separator'] .' ', $date); 
					}
				}
				// is not all day, one day, different hours
				elseif(!$all_day_event && !empty($date['start']) && !empty($date['end']))
				{
					// one day only
					if(cde_format_date($date['start'], 'date') === cde_format_date($date['end'], 'date'))
					{
						$date_output = cde_format_date($date['start'], 'datetime', $format)  . ' ' . $args['separator'] . ' ' . cde_format_date($date['end'], 'time', $format); 
					}
					// more than one day
					else
					{
						$date_output = cde_format_date($date['start'], 'datetime', $format) . ' ' . $args['separator'] . ' ' . cde_format_date($date['end'], 'datetime', $format); 
					}
				}
				// any other case
				else 
				{		
					$date_output = cde_format_date($date['start'], 'datetime', $format) . ' ' . $args['separator'] . ' ' . cde_format_date($date['end'], 'datetime', $format);  
				}
				
				// output format
				if ($args['format'] == 'link')
					$html .= sprintf('<span class="entry-date date"><a href="%1$s" rel="bookmark"><abbr class="dtstart" title="%2$s"></abbr><abbr class="dtend" title="%3$s"></abbr>%4$s</a></span>',
						esc_url(get_permalink()),
						esc_attr($date['start']),
						esc_attr($date['end']),
						esc_html($date_output)
					);
				else
					$html .= sprintf('<span class="entry-date date"><abbr class="dtstart" title="%1$s"></abbr><abbr class="dtend" title="%2$s"></abbr>%3$s</span>',
						esc_attr($date['start']),
						esc_attr($date['end']),
						esc_html($date_output)
					);
			}
		}

		// author link
		if ($args['show_author_link'] === true)
		{
			$html .= sprintf('<span class="byline"><span class="author vcard"><a class="url fn n" href="%1$s" rel="author">%2$s</a></span></span>',
				esc_url(get_author_posts_url(get_the_author_meta('ID'))),
				get_the_author()
			);
		}
		
		$html .= $args['after'];
		
		$html = apply_filters('cde_display_event_occurrences', $html);
		
		if ($args['echo'] === true)
			echo $html;
		else
			return $html;
	}
}


/**
 * Display page title
 */
if (!function_exists('cde_page_title'))
{
	function cde_page_title($echo = true)
	{
		if (cde_is_event_archive('day'))
			$page_title = sprintf(__('Event Daily Archives: %s', 'cdash-events'), '<span>' . get_the_date() . '</span>');
		elseif (cde_is_event_archive('month'))
			$page_title = sprintf(__('Event Monthly Archives: %s', 'cdash-events'), '<span>' . get_the_date(_x('F Y', 'monthly archives date format', 'cdash-events')) . '</span>');
		elseif (cde_is_event_archive('year'))
			$page_title = sprintf(__('Event Yearly Archives: %s', 'cdash-events'), '<span>' . get_the_date(_x('Y', 'yearly archives date format', 'cdash-events')) . '</span>');
		elseif (is_tax('event-category'))
			$page_title = sprintf(__('Events Category: %s', 'cdash-events'), single_term_title('', false));
		elseif (is_tax('event-location'))
			$page_title = sprintf(__('Events Location: %s', 'cdash-events'), single_term_title('', false));
		elseif (is_tax('event-tag'))
			$page_title = sprintf(__('Events Tag: %s', 'cdash-events'), single_term_title('', false));
		else
			$page_title = __('Events', 'cdash-events');
		
		$page_title = apply_filters('cde_page_title', $page_title);
		
		if ($echo)
			echo $page_title;
		else
			return $page_title;
	}
}


/**
 * Show an archive description on taxonomy archives
 */
if (!function_exists('cde_taxonomy_archive_description'))
{
	function cde_taxonomy_archive_description()
	{
		if (is_tax(array('event-category', 'event-location', 'event-tag')) && get_query_var('paged') == 0)
		{
			$term_description = term_description();
			
			if (!empty($term_description))
			{
				?>
				<div class="archive-description term-description">
					
					<?php echo apply_filters('cde_taxonomy_archive_description', $term_description); ?>
					
				</div>
				<?php
			}
		}
	}
}


/**
 * Display google map in archive
 */
if (!function_exists('cde_display_loop_event_google_map'))
{
	function cde_display_loop_event_google_map()
	{
		if (is_tax('event-location'))
			cde_get_template('loop-event/google-map.php');
	}
}


/**
 * Display location details
 */
if (!function_exists('cde_display_location_details'))
{
	function cde_display_location_details()
	{
		if (is_tax('event-location'))
			cde_get_template('loop-event/location-details.php');
	}
}


/**
 * Display content wrapper start
 */
if (!function_exists('cde_output_content_wrapper_start'))
{
	function cde_output_content_wrapper_start()
	{
		cde_get_template('global/wrapper-start.php');
	}
}


/**
 * Display content wrapper end
 */
if (!function_exists('cde_output_content_wrapper_end'))
{
	function cde_output_content_wrapper_end()
	{
		cde_get_template('global/wrapper-end.php');
	}
}


/**
 * Display breadcrumbs
 */
if (!function_exists('cde_breadcrumb'))
{
	function cde_breadcrumb()
	{
		cde_get_template('global/breadcrumb.php');
	}
}


/**
 * Display pagination links
 */
if (!function_exists('cde_paginate_links'))
{
	function cde_paginate_links()
	{
		cde_get_template('loop-event/pagination.php');
	}
}


/**
 * Display breadcrumbs
 */
if (!function_exists('cde_sorting'))
{
	function cde_sorting()
	{
		// cde_get_template('loop-event/sorting.php');
	}
}


/**
 * Display sidebar
 */
if (!function_exists('cde_get_sidebar'))
{
	function cde_get_sidebar()
	{
		cde_get_template('global/sidebar.php');
	}
}


/**
 * Display event thumbnail in loop
 */
if (!function_exists('cde_display_loop_event_thumbnail'))
{
	function cde_display_loop_event_thumbnail()
	{
		cde_get_template('loop-event/thumbnail.php');
	}
}


/**
 * Display event meta in loop
 */
if (!function_exists('cde_display_loop_event_meta'))
{
	function cde_display_loop_event_meta()
	{
		cde_get_template('loop-event/meta.php');
	}
}


/**
 * Display event excerpt in loop
 */
if (!function_exists('cde_display_event_excerpt'))
{
	function cde_display_event_excerpt()
	{
		cde_get_template('loop-event/excerpt.php');
	}
}


/**
 * Display single event thumbnail
 */
if (!function_exists('cde_display_single_event_thumbnail'))
{
	function cde_display_single_event_thumbnail()
	{
		cde_get_template('single-event/thumbnail.php');
	}
}


/**
 * Display event meta in loop
 */
if (!function_exists('cde_display_single_event_meta'))
{
	function cde_display_single_event_meta()
	{
		cde_get_template('single-event/meta.php');
	}
}


/**
 * Display single event date
 */
if (!function_exists('cde_display_single_event_date'))
{
	function cde_display_single_event_date()
	{
		// is recurring?
		if (cde_is_recurring())
		{
			// display occurrences date
			cde_display_event_occurrences();
		}
		else
		{
			// display event date
			cde_display_event_date();
		}
	}
}

	
/**
 * Display google map in event
 */
if (!function_exists('cde_display_single_event_google_map'))
{
	function cde_display_single_event_google_map()
	{
		cde_get_template('single-event/google-map.php');
	}
}


/**
 * Display widget event date
 */
if (!function_exists('cde_display_widget_event_date'))
{
	function cde_display_widget_event_date()
	{ 
		// display event date
		cde_display_event_date('', $args = array('format' => ''));
	}
}
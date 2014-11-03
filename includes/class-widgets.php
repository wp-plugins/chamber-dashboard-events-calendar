<?php
if(!defined('ABSPATH')) exit;

new Cdash_Events_Widgets($cdash_events);

class Cdash_Events_Widgets
{
	private $options = array();


	public function __construct($cdash_events)
	{
		//settings
		$this->options = $cdash_events->get_options();

		//actions
		add_action('widgets_init', array(&$this, 'register_widgets'));
	}


	/**
	 * 
	*/
	public function register_widgets()
	{
		register_widget('Cdash_Events_List_Widget');
		register_widget('Cdash_Events_Archive_Widget');
		register_widget('Cdash_Events_Calendar_Widget');
		register_widget('Cdash_Events_Categories_Widget');
		register_widget('Cdash_Events_Locations_Widget');
	}
}


class Cdash_Events_Archive_Widget extends WP_Widget
{
	private $cde_defaults = array();
	private $cde_types = array();
	private $cde_order_types = array();


	public function __construct()
	{
		parent::__construct(
			'Cdash_Events_Archive_Widget',
			__('Events Archives', 'cdash-events'),
			array(
				'description' => __('Displays events archives', 'cdash-events')
			)
		);

		$this->cde_defaults = array(
			'title' => __('Events Archives', 'cdash-events'),
			'display_as_dropdown' => false,
			'show_post_count' => true,
			'type' => 'monthly',
			'order' => 'desc',
			'limit' => 0
		);

		$this->cde_types = array(
			'monthly' => __('Monthly', 'cdash-events'),
			'yearly' => __('Yearly', 'cdash-events')
		);

		$this->cde_order_types = array(
			'asc' => __('Ascending', 'cdash-events'),
			'desc' => __('Descending', 'cdash-events')
		);
	}


	public function widget($args, $instance)
	{
		$instance['title'] = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

		$html = $args['before_widget'].$args['before_title'].(!empty($instance['title']) ? $instance['title'] : '').$args['after_title'];
		$html .= cde_display_event_archives($instance);
		$html .= $args['after_widget'];

		echo $html;
	}


	public function form($instance)
	{
		$html = '
		<p>
			<label for="'.$this->get_field_id('title').'">'.__('Title', 'cdash-events').':</label>
			<input id="'.$this->get_field_id('title').'" class="widefat" name="'.$this->get_field_name('title').'" type="text" value="'.esc_attr(isset($instance['title']) ? $instance['title'] : $this->cde_defaults['title']).'" />
		</p>
		<p>
			<input id="'.$this->get_field_id('display_as_dropdown').'" type="checkbox" name="'.$this->get_field_name('display_as_dropdown').'" value="" '.checked(true, (isset($instance['display_as_dropdown']) ? $instance['display_as_dropdown'] : $this->cde_defaults['display_as_dropdown']), false).' /> <label for="'.$this->get_field_id('display_as_dropdown').'">'.__('Display as dropdown', 'cdash-events').'</label><br />
			<input id="'.$this->get_field_id('show_post_count').'" type="checkbox" name="'.$this->get_field_name('show_post_count').'" value="" '.checked(true, (isset($instance['show_post_count']) ? $instance['show_post_count'] : $this->cde_defaults['show_post_count']), false).' /> <label for="'.$this->get_field_id('show_post_count').'">'.__('Show amount of events', 'cdash-events').'</label>
		</p>
		<p>
			<label for="'.$this->get_field_id('type').'">'.__('Display Type', 'cdash-events').':</label>
			<select id="'.$this->get_field_id('type').'" name="'.$this->get_field_name('type').'">';

		foreach($this->cde_types as $id => $type)
		{
			$html .= '
				<option value="'.esc_attr($id).'" '.selected($id, (isset($instance['type']) ? $instance['type'] : $this->cde_defaults['type']), false).'>'.$type.'</option>';
		}

		$html .= '
			</select>
		</p>
		<p>
			<label for="'.$this->get_field_id('order').'">'.__('Order', 'cdash-events').':</label>
			<select id="'.$this->get_field_id('order').'" name="'.$this->get_field_name('order').'">';

		foreach($this->cde_order_types as $id => $order)
		{
			$html .= '
				<option value="'.esc_attr($id).'" '.selected($id, (isset($instance['order']) ? $instance['order'] : $this->cde_defaults['order']), false).'>'.$order.'</option>';
		}

		$html .= '
			</select>
		</p>
			<label for="'.$this->get_field_id('limit').'">'.__('Limit', 'cdash-events').':</label> <input id="'.$this->get_field_id('limit').'" type="text" name="'.$this->get_field_name('limit').'" value="'.esc_attr(isset($instance['limit']) ? $instance['limit'] : $this->cde_defaults['limit']).'" />
		</p>';

		echo $html;
	}


	public function update($new_instance, $old_instance)
	{
		//checkboxes
		$old_instance['display_as_dropdown'] = (isset($new_instance['display_as_dropdown']) ? true : false);
		$old_instance['show_post_count'] = (isset($new_instance['show_post_count']) ? true : false);

		//title
		$old_instance['title'] = sanitize_text_field(isset($new_instance['title']) ? $new_instance['title'] : $this->cde_defaults['title']);

		//limit
		$old_instance['limit'] = (int)(isset($new_instance['limit']) && (int)$new_instance['limit'] >= 0 ? $new_instance['limit'] : $this->cde_defaults['limit']);

		//order
		$old_instance['order'] = (isset($new_instance['order']) && in_array($new_instance['order'], array_keys($this->cde_order_types), true) ? $new_instance['order'] : $this->cde_defaults['order']);

		//type
		$old_instance['type'] = (isset($new_instance['type']) && in_array($new_instance['type'], array_keys($this->cde_types), true) ? $new_instance['type'] : $this->cde_defaults['type']);

		return $old_instance;
	}
}


class Cdash_Events_Calendar_Widget extends WP_Widget
{
	private $cde_options = array();
	private $cde_defaults = array();
	private $cde_taxonomies = array();
	private $cde_css_styles = array();
	private $cde_included_widgets = 0;


	public function __construct()
	{
		parent::__construct(
			'Cdash_Events_Calendar_Widget',
			__('Events Calendar', 'cdash-events'),
			array(
				'description' => __('Displays events calendar', 'cdash-events')
			)
		);

		add_action('wp_ajax_nopriv_get-events-widget-calendar-month', array(&$this, 'get_widget_calendar_month'));
		add_action('wp_ajax_get-events-widget-calendar-month', array(&$this, 'get_widget_calendar_month'));

		$this->cde_options = array_merge(
			array('general' => get_option('cdash_events_general'))
		);

		$this->cde_defaults = array(
			'title' => __('Events Calendar', 'cdash-events'),
			'show_past_events' => true,
			'highlight_weekends' => true,
			'categories' => 'all',
			'locations' => 'all',
			'css_style' => 'basic'
		);

		$this->cde_taxonomies = array(
			'all' => __('all', 'cdash-events'),
			'selected' => __('selected', 'cdash-events')
		);

		$this->cde_css_styles = array(
			'basic' => __('basic', 'news-manager'),
			'dark' => __('dark', 'news-manager'),
			'light' => __('light', 'news-manager'),
			'flat' => __('flat', 'news-manager')
		);
	}


	/**
	 * 
	*/
	public function get_widget_calendar_month()
	{
		if(!empty($_POST['action']) && !empty($_POST['date']) && !empty($_POST['widget_id']) && !empty($_POST['nonce']) && $_POST['action'] === 'get-events-widget-calendar-month' && check_ajax_referer('cdash-events-widget-calendar', 'nonce', false))
		{
			$widget_options = $this->get_settings();
			$widget_id = (int)$_POST['widget_id'];

			echo $this->display_calendar($widget_options[$widget_id], $_POST['date'], $this->get_events_days($_POST['date'], $widget_options[$widget_id]), $widget_id, true);
		}

		exit;
	}


	/**
	 * 
	*/
	public function widget($args, $instance)
	{
		if(++$this->cde_included_widgets === 1)
		{
			wp_register_script(
				'cdash-events-front-widgets-calendar',
				CDASH_EVENTS_URL.'/js/front-widgets.js',
				array('jquery')
			);

			wp_enqueue_script('cdash-events-front-widgets-calendar');

			wp_localize_script(
				'cdash-events-front-widgets-calendar',
				'emArgs',
				array(
					'ajaxurl' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('cdash-events-widget-calendar')
				)
			);
		}

		$date = date('Y-m', current_time('timestamp'));
		$instance['title'] = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

		$html = $args['before_widget'].$args['before_title'].(!empty($instance['title']) ? $instance['title'] : '').$args['after_title'];
		$html .= $this->display_calendar($instance, $date, $this->get_events_days($date, $instance), $this->number);
		$html .= $args['after_widget'];

		echo $html;
	}


	/**
	 * 
	*/
	public function form($instance)
	{
		$category = isset($instance['categories']) ? $instance['categories'] : $this->cde_defaults['categories'];
		$location = isset($instance['locations']) ? $instance['locations'] : $this->cde_defaults['locations'];

		$html = '
		<p>
			<label for="'.$this->get_field_id('title').'">'.__('Title', 'cdash-events').':</label>
			<input id="'.$this->get_field_id('title').'" class="widefat" name="'.$this->get_field_name('title').'" type="text" value="'.esc_attr(isset($instance['title']) ? $instance['title'] : $this->cde_defaults['title']).'" />
		</p>
		<p>
			<input id="'.$this->get_field_id('show_past_events').'" type="checkbox" name="'.$this->get_field_name('show_past_events').'" value="" '.checked(true, (isset($instance['show_past_events']) ? $instance['show_past_events'] : $this->cde_defaults['show_past_events']), false).' /> <label for="'.$this->get_field_id('show_past_events').'">'.__('Show past events', 'cdash-events').'</label><br />
			<input id="'.$this->get_field_id('highlight_weekends').'" type="checkbox" name="'.$this->get_field_name('highlight_weekends').'" value="" '.checked(true, (isset($instance['highlight_weekends']) ? $instance['highlight_weekends'] : $this->cde_defaults['highlight_weekends']), false).' /> <label for="'.$this->get_field_id('highlight_weekends').'">'.__('Highlight weekends', 'cdash-events').'</label>
		</p>
		<p>
			<label>'.__('CSS Style', 'news-manager').':</label>
			<select name="'.$this->get_field_name('css_style').'">';

		foreach($this->cde_css_styles as $style => $trans)
		{
			$html .= '
				<option value="'.esc_attr($style).'" '.selected($style, (isset($instance['css_style']) ? $instance['css_style'] : $this->cde_defaults['css_style']), false).'>'.$trans.'</option>';
		}

		$html .= '
			</select>
		</p>
		<div class="cdash-events-list">
			<label>'.__('Event Categories', 'cdash-events').':</label>
			<br />';

		foreach($this->cde_taxonomies as $id => $taxonomy)
		{
			$html .= '
			<input class="taxonomy-select-cats" id="'.$this->get_field_id('cat_'.$id).'" name="'.$this->get_field_name('categories').'" type="radio" value="'.esc_attr($id).'" '.checked($id, $category, false).' /><label for="'.$this->get_field_id('cat_'.$id).'">'.$taxonomy.'</label> ';
		}

		$html .= '
			<div class="checkbox-list-cats checkbox-list"'.($category === 'all' ? ' style="display: none;"' : '').'>
				'.$this->display_taxonomy_checkbox_list('event-category', 'categories_arr', $instance).'
			</div>
		</div>
		<div class="cdash-events-list">
			<label>'.__('Event Locations', 'cdash-events').':</label>
			<br />';

		foreach($this->cde_taxonomies as $id => $taxonomy)
		{
			$html .= '
			<input class="taxonomy-select-locs" id="'.$this->get_field_id('loc_'.$id).'" name="'.$this->get_field_name('locations').'" type="radio" value="'.esc_attr($id).'" '.checked($id, $location, false).' /><label for="'.$this->get_field_id('loc_'.$id).'">'.$taxonomy.'</label> ';
		}

		$html .= '
			<div class="checkbox-list-locs checkbox-list"'.($location === 'all' ? ' style="display: none;"' : '').'>
				'.$this->display_taxonomy_checkbox_list('event-location', 'locations_arr', $instance).'
			</div>
		</div>';

		echo $html;
	}


	/**
	 * 
	*/
	public function update($new_instance, $old_instance)
	{
		//checkboxes
		$old_instance['show_past_events'] = (isset($new_instance['show_past_events']) ? true : false);
		$old_instance['highlight_weekends'] = (isset($new_instance['highlight_weekends']) ? true : false);

		//title
		$old_instance['title'] = sanitize_text_field(isset($new_instance['title']) ? $new_instance['title'] : $this->cde_defaults['title']);

		//taxonomies
		$old_instance['categories'] = (isset($new_instance['categories']) && in_array($new_instance['categories'], array_keys($this->cde_taxonomies), true) ? $new_instance['categories'] : $this->cde_defaults['categories']);
		$old_instance['locations'] = (isset($new_instance['locations']) && in_array($new_instance['locations'], array_keys($this->cde_taxonomies), true) ? $new_instance['locations'] : $this->cde_defaults['locations']);

		//css style
		$old_instance['css_style'] = (isset($new_instance['css_style']) && in_array($new_instance['css_style'], array_keys($this->cde_css_styles), true) ? $new_instance['css_style'] : $this->cde_defaults['css_style']);

		//categories
		if($old_instance['categories'] === 'selected')
		{
			$old_instance['categories_arr'] = array();

			if(isset($new_instance['categories_arr']) && is_array($new_instance['categories_arr']))
			{
				foreach($new_instance['categories_arr'] as $cat_id)
				{
					$old_instance['categories_arr'][] = (int)$cat_id;
				}

				$old_instance['categories_arr'] = array_unique($old_instance['categories_arr'], SORT_NUMERIC);
			}
		}
		else
			$old_instance['categories_arr'] = array();

		//locations
		if($old_instance['locations'] === 'selected')
		{
			$old_instance['locations_arr'] = array();

			if(isset($new_instance['locations_arr']) && is_array($new_instance['locations_arr']))
			{
				foreach($new_instance['locations_arr'] as $cat_id)
				{
					$old_instance['locations_arr'][] = (int)$cat_id;
				}

				$old_instance['locations_arr'] = array_unique($old_instance['locations_arr'], SORT_NUMERIC);
			}
		}
		else
			$old_instance['locations_arr'] = array();

		return $old_instance;
	}


	/**
	 * 
	*/
	private function display_calendar($options, $start_date, $events, $widget_id, $ajax = false)
	{
		global $wp_locale;

		$weekdays = array(1 => 7, 2 => 6, 3 => 5, 4 => 4, 5 => 3, 6 => 2, 7 => 1);
		$date = explode(' ', date('Y m j t', strtotime($start_date.'-02')));
		$month = (int)$date[1] - 1;
		$prev_month = (($a = $month - 1) === -1 ? 11 : $a);
		$prev_month_pad = str_pad($prev_month + 1, 2, '0', STR_PAD_LEFT);
		$next_month = ($month + 1) % 12;
		$next_month_pad = str_pad($next_month + 1, 2, '0', STR_PAD_LEFT);
		$first_day = (($first = date('w', strtotime(date($date[0].'-'.$date[1].'-01')))) === '0' ? 7 : $first);
		$rel = $widget_id.'|';

		//Polylang and WPML compatibility
		if(defined('ICL_LANGUAGE_CODE'))
			$rel .= ICL_LANGUAGE_CODE;

		$html = '
		<div id="events-calendar-'.$widget_id.'" class="events-calendar-widget widget_calendar'.(isset($options['css_style']) && $options['css_style'] !== 'basic' ? ' '.$options['css_style'] : '').'" rel="'.$rel.'" '.($ajax === true ? 'style="display: none;"' : '').'>
			<span class="active-month">'.$wp_locale->get_month($date[1]).' '.$date[0].'</span>
			<table class="nav-days">
				<thead>
					<tr>';

		for($i = 1; $i <= 7; $i++)
		{
			$html .= '
						<th scope="col">'.$wp_locale->get_weekday_initial($wp_locale->get_weekday($i !== 7 ? $i : 0)).'</th>';
		}

		$html .= '
					</tr>
				</thead>
				<tbody>';

		$weeks = ceil(($date[3] - $weekdays[$first_day]) / 7) + 1;
		$now = date_parse(current_time('mysql'));
		$day = $k = 1;

		for($i = 1; $i <= $weeks; $i++)
		{
			$html .= '<tr>';

			for($j = 1; $j <= 7; $j++)
			{
				$td_class = array();
				$real_day = (bool)($k++ >= $first_day && $day <= $date[3]);

				if($real_day === true && in_array($day, $events))
					$td_class[] = 'active';

				if($day === $now['day'] && ($month + 1 === $now['month']) && (int)$date[0] === $now['year'])
					$td_class[] = 'today';

				if($real_day === false)
					$td_class[] = 'pad';

				if($options['highlight_weekends'] === true && $j >= 6 && $j <= 7)
					$td_class[] = 'weekend';

				$html .= '<td'.(!empty($td_class) ? ' class="'.implode(' ', $td_class).'"' : '').'>';

				if($real_day === true)
				{
					$html .= (in_array($day, $events) ? '<a href="'.esc_url(cde_get_event_date_link($date[0], $month + 1, $day)).'">'.$day.'</a>' : $day);
					$day++;
				}
				else
					$html .= '&nbsp';

				$html .= '</td>';
			}

			$html .= '</tr>';
		}

		$html .= '
				</tbody>
			</table>
			<table class="nav-months">
				<tr>
					<td class="prev-month" colspan="2">
						<a rel="'.($prev_month === 11 ? ($date[0] - 1) : $date[0]).'-'.$prev_month_pad.'" href="#">&laquo; '.apply_filters('cde_calendar_month_name', $wp_locale->get_month($prev_month_pad)).'</a>
					</td>
					<td class="ajax-spinner" colspan="1"><div></div></td>
					<td class="next-month" colspan="2">
						<a rel="'.($next_month === 0 ? ($date[0] + 1) : $date[0]).'-'.$next_month_pad.'" href="#">'.apply_filters('cde_calendar_month_name', $wp_locale->get_month($next_month_pad)).' &raquo;</a>
					</td>
				</tr>
			</table>
		</div>';

		return $html;
	}


	/**
	 * 
	*/
	private function get_events_days($date, $options)
	{
		$days = $allevents = $exclude_ids = array();

		$args = array(
			'post_type' => 'event',
			'posts_per_page' => -1,
			'suppress_filters' => false,
			'date_range' => 'between',
			'event_show_past_events' => $options['show_past_events']
		);

		if($options['categories'] === 'selected')
		{
			$args['tax_query'][] = array(
				'taxonomy' => 'event-category',
				'field' => 'id',
				'terms' => $options['categories_arr'],
				'include_children' => false,
				'operator' => 'IN'
			);
		}

		if($options['locations'] === 'selected')
		{
			$args['tax_query'][] = array(
				'taxonomy' => 'event-location',
				'field' => 'id',
				'terms' => $options['locations_arr'],
				'include_children' => false,
				'operator' => 'IN'
			);
		}

		//Polylang and WPML compatibility
		if(defined('ICL_LANGUAGE_CODE'))
			$args['lang'] = ICL_LANGUAGE_CODE;

		$allevents['start'] = get_posts(
			array_merge(
				$args,
				array(
					'event_start_after' => $date.'-01',
					'event_start_before' => $date.'-'.date('t', strtotime($date.'-02'))
				)
			)
		);

		foreach($allevents['start'] as $event)
		{
			$exclude_ids[] = $event->ID;
		}

		$allevents['end'] = get_posts(
			array_merge(
				$args,
				array(
					'event_end_after' => $date.'-01',
					'event_end_before' => $date.'-'.date('t', strtotime($date.'-02')),
					'post__not_in' => (!empty($exclude_ids) ? $exclude_ids : array())
				)
			)
		);

		foreach($allevents as $id => $events)
		{
			if(!empty($events))
			{
				foreach($events as $event)
				{
					$s_datetime = explode(' ', get_post_meta($event->ID, '_event_start_date', true));
					$s_date = explode('-', $s_datetime[0]);
					$e_datetime = explode(' ', get_post_meta($event->ID, '_event_end_date', true));
					$e_date = explode('-', $e_datetime[0]);

					if(count($s_date) === 3 && count($e_date) === 3)
					{
						//same years and same months
						if($s_date[0] === $e_date[0] && $s_date[1] === $e_date[1])
						{
							for($i = $s_date[2]; $i <= $e_date[2]; $i++)
							{
								$days[] = $i;
							}
						}
						else
						{
							if($id === 'start')
							{
								$no_days = date('t', strtotime($s_datetime[0]));

								for($i = $s_date[2]; $i <= $no_days; $i++)
								{
									$days[] = (int)$i;
								}
							}
							else
							{
								for($i = $e_date[2]; $i >= 1; $i--)
								{
									$days[] = (int)$i;
								}
							}
						}
					}
				}
			}
		}

		return array_unique($days, SORT_NUMERIC);
	}


	/**
	 * 
	*/
	private function display_taxonomy_checkbox_list($taxonomy_name, $name, $instance, $depth = 0, $parent = 0)
	{
		$html = '';
		$array = isset($instance[$name]) ? $instance[$name] : array();
		$terms = get_terms(
			$taxonomy_name,
			array(
				'hide_empty' => false,
				'parent' => $parent
			)
		);

		if(!empty($terms))
		{
			$html .= '
			<ul class="terms-checkbox-list depth-level-'.$depth++.'">';

			foreach($terms as $term)
			{
				$html .= '
				<li>
					<input id="'.$this->get_field_id('chkbxlst_'.$term->term_taxonomy_id).'" type="checkbox" name="'.$this->get_field_name($name).'[]" value="'.esc_attr($term->term_id).'" '.checked(true, in_array($term->term_id, $array), false).' /> <label for="'.$this->get_field_id('chkbxlst_'.$term->term_taxonomy_id).'">'.$term->name.'</label>
					'.$this->display_taxonomy_checkbox_list($taxonomy_name, $name, $instance, $depth, $term->term_id).'
				</li>';
			}

			$html .= '
			</ul>';
		}
		elseif($parent === 0)
			$html = __('No results were found.', 'cdash-events');

		return $html;
	}
}


class Cdash_Events_List_Widget extends WP_Widget
{
	private $cde_options = array();
	private $cde_defaults = array();
	private $cde_taxonomies = array();
	private $cde_orders = array();
	private $cde_order_types = array();
	private $cde_image_sizes = array();


	public function __construct()
	{
		parent::__construct(
			'Cdash_Events_List_Widget',
			__('Events List', 'cdash-events'),
			array(
				'description' => __('Displays a list of events', 'cdash-events')
			)
		);

		$this->cde_options = array_merge(
			array('general' => get_option('cdash_events_general'))
		);

		$this->cde_defaults = array(
			'title' => __('Events', 'cdash-events'),
			'number_of_events' => 5,
			'thumbnail_size' => 'thumbnail',
			'categories' => 'all',
			'locations' => 'all',
			'order_by' => 'start',
			'order' => 'desc',
			'show_past_events' => true,
			'show_occurrences' => true,
			'show_event_thumbnail' => true,
			'show_event_excerpt' => false,
			'no_events_message' => __('No Events', 'cdash-events'),
			'date_format' => $this->cde_options['general']['datetime_format']['date'],
			'time_format' => $this->cde_options['general']['datetime_format']['time']
		);

		$this->cde_taxonomies = array(
			'all' => __('all', 'cdash-events'),
			'selected' => __('selected', 'cdash-events')
		);

		$this->cde_orders = array(
			'start' => __('Start date', 'cdash-events'),
			'end' => __('End date', 'cdash-events'),
			'publish' => __('Publish date', 'cdash-events'),
			'title' => __('Title', 'cdash-events')
		);

		$this->cde_order_types = array(
			'asc' => __('Ascending', 'cdash-events'),
			'desc' => __('Descending', 'cdash-events')
		);

		$this->cde_image_sizes = array_merge(array('full'), get_intermediate_image_sizes());
		sort($this->cde_image_sizes, SORT_STRING);
	}


	/**
	 * 
	*/
	public function widget($args, $instance)
	{
		$instance['title'] = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

		//backward compatibility
		$comp = $instance;
		$comp['categories'] = ($instance['categories'] === 'selected' ? $instance['categories_arr'] : array());
		$comp['locations'] = ($instance['locations'] === 'selected' ? $instance['locations_arr'] : array());

		$html = $args['before_widget'].$args['before_title'].(!empty($instance['title']) ? $instance['title'] : '').$args['after_title'];
		$html .= cde_display_events($comp);
		$html .= $args['after_widget'];

		echo $html;
	}


	/**
	 * 
	*/
	public function form($instance)
	{
		$category = isset($instance['categories']) ? $instance['categories'] : $this->cde_defaults['categories'];
		$location = isset($instance['locations']) ? $instance['locations'] : $this->cde_defaults['locations'];

		$html = '
		<p>
			<label for="'.$this->get_field_id('title').'">'.__('Title', 'cdash-events').':</label>
			<input id="'.$this->get_field_id('title').'" class="widefat" name="'.$this->get_field_name('title').'" type="text" value="'.esc_attr(isset($instance['title']) ? $instance['title'] : $this->cde_defaults['title']).'" />
		</p>
		<p>
			<label for="'.$this->get_field_id('number_of_events').'">'.__('Number of events', 'cdash-events').':</label>
			<input id="'.$this->get_field_id('number_of_events').'" name="'.$this->get_field_name('number_of_events').'" type="text" value="'.esc_attr(isset($instance['number_of_events']) ? $instance['number_of_events'] : $this->cde_defaults['number_of_events']).'" />
		</p>
		<div class="cdash-events-list">
			<label>'.__('Event Categories', 'cdash-events').':</label>
			<br />';

		foreach($this->cde_taxonomies as $id => $taxonomy)
		{
			$html .= '
			<input class="taxonomy-select-cats" id="'.$this->get_field_id('cat_'.$id).'" name="'.$this->get_field_name('categories').'" type="radio" value="'.esc_attr($id).'" '.checked($id, $category, false).' /><label for="'.$this->get_field_id('cat_'.$id).'">'.$taxonomy.'</label> ';
		}

		$html .= '
			<div class="checkbox-list-cats checkbox-list"'.($category === 'all' ? ' style="display: none;"' : '').'>
				'.$this->display_taxonomy_checkbox_list('event-category', 'categories_arr', $instance).'
			</div>
		</div>
		<div class="cdash-events-list">
			<label>'.__('Event Locations', 'cdash-events').':</label>
			<br />';

		foreach($this->cde_taxonomies as $id => $taxonomy)
		{
			$html .= '
			<input class="taxonomy-select-locs" id="'.$this->get_field_id('loc_'.$id).'" name="'.$this->get_field_name('locations').'" type="radio" value="'.esc_attr($id).'" '.checked($id, $location, false).' /><label for="'.$this->get_field_id('loc_'.$id).'">'.$taxonomy.'</label> ';
		}

		$html .= '
			<div class="checkbox-list-locs checkbox-list"'.($location === 'all' ? ' style="display: none;"' : '').'>
				'.$this->display_taxonomy_checkbox_list('event-location', 'locations_arr', $instance).'
			</div>
		</div>';

		$html .= '
		<p>
			<label for="'.$this->get_field_id('order_by').'">'.__('Order by', 'cdash-events').':</label>
			<select id="'.$this->get_field_id('order_by').'" name="'.$this->get_field_name('order_by').'">';

		foreach($this->cde_orders as $id => $order_by)
		{
			$html .= '
				<option value="'.esc_attr($id).'" '.selected($id, (isset($instance['order_by']) ? $instance['order_by'] : $this->cde_defaults['order_by']), false).'>'.$order_by.'</option>';
		}

		$html .= '
			</select>
			<br />
			<label for="'.$this->get_field_id('order').'">'.__('Order', 'cdash-events').':</label>
			<select id="'.$this->get_field_id('order').'" name="'.$this->get_field_name('order').'">';

		foreach($this->cde_order_types as $id => $order)
		{
			$html .= '
				<option value="'.esc_attr($id).'" '.selected($id, (isset($instance['order']) ? $instance['order'] : $this->cde_defaults['order']), false).'>'.$order.'</option>';
		}

		$show_event_thumbnail = (isset($instance['show_event_thumbnail']) ? $instance['show_event_thumbnail'] : $this->cde_defaults['show_event_thumbnail']);

		$html .= '
			</select>
		</p>
		<p>
			<input id="'.$this->get_field_id('show_past_events').'" type="checkbox" name="'.$this->get_field_name('show_past_events').'" value="" '.checked(true, (isset($instance['show_past_events']) ? $instance['show_past_events'] : $this->cde_defaults['show_past_events']), false).' /> <label for="'.$this->get_field_id('show_past_events').'">'.__('Display past events', 'cdash-events').'</label>
			<br />
			<input id="'.$this->get_field_id('show_occurrences').'" type="checkbox" name="'.$this->get_field_name('show_occurrences').'" value="" '.checked(true, (isset($instance['show_occurrences']) ? $instance['show_occurrences'] : $this->cde_defaults['show_occurrences']), false).' /> <label for="'.$this->get_field_id('show_occurrences').'">'.__('Display event occurrenses', 'cdash-events').'</label>
			<br />
			<input id="'.$this->get_field_id('show_event_excerpt').'" type="checkbox" name="'.$this->get_field_name('show_event_excerpt').'" value="" '.checked(true, (isset($instance['show_event_excerpt']) ? $instance['show_event_excerpt'] : $this->cde_defaults['show_event_excerpt']), false).' /> <label for="'.$this->get_field_id('show_event_excerpt').'">'.__('Display event excerpt', 'cdash-events').'</label>
			<br />
			<input id="'.$this->get_field_id('show_event_thumbnail').'" class="em-show-event-thumbnail" type="checkbox" name="'.$this->get_field_name('show_event_thumbnail').'" value="" '.checked(true, $show_event_thumbnail, false).' /> <label for="'.$this->get_field_id('show_event_thumbnail').'">'.__('Display event thumbnail', 'cdash-events').'</label>
		</p>
		<p class="em-event-thumbnail-size"'.($show_event_thumbnail === true ? '' : ' style="display: none;"').'>
			<label for="'.$this->get_field_id('thumbnail_size').'">'.__('Thumbnail size', 'cdash-events').':</label>
			<select id="'.$this->get_field_id('thumbnail_size').'" name="'.$this->get_field_name('thumbnail_size').'">';

		$size_type = (isset($instance['thumbnail_size']) ? $instance['thumbnail_size'] : $this->cde_defaults['thumbnail_size']);

		foreach($this->cde_image_sizes as $size)
		{
			$html .= '
				<option value="'.esc_attr($size).'" '.selected($size, $size_type, false).'>'.$size.'</option>';
		}

		$html .= '
			</select>
		</p>
		<p>
			<label for="'.$this->get_field_id('no_events_message').'">'.__('No events message', 'cdash-events').':</label>
			<input id="'.$this->get_field_id('no_events_message').'" type="text" name="'.$this->get_field_name('no_events_message').'" value="'.esc_attr(isset($instance['no_events_message']) ? $instance['no_events_message'] : $this->cde_defaults['no_events_message']).'" />
		</p>
		<p>
			<label for="'.$this->get_field_id('date_format').'">'.__('Date format', 'cdash-events').':</label>
			<input id="'.$this->get_field_id('date_format').'" type="text" name="'.$this->get_field_name('date_format').'" value="'.esc_attr(isset($instance['date_format']) ? $instance['date_format'] : $this->cde_defaults['date_format']).'" /><br />
			<label for="'.$this->get_field_id('time_format').'">'.__('Time format', 'cdash-events').':</label>
			<input id="'.$this->get_field_id('time_format').'" type="text" name="'.$this->get_field_name('time_format').'" value="'.esc_attr(isset($instance['time_format']) ? $instance['time_format'] : $this->cde_defaults['time_format']).'" />
		</p>';

		echo $html;
	}


	/**
	 * 
	*/
	public function update($new_instance, $old_instance)
	{
		//number of events
		$old_instance['number_of_events'] = (int)(isset($new_instance['number_of_events']) ? $new_instance['number_of_events'] : $this->cde_defaults['number_of_events']);

		//order
		$old_instance['order_by'] = (isset($new_instance['order_by']) && in_array($new_instance['order_by'], array_keys($this->cde_orders), true) ? $new_instance['order_by'] : $this->cde_defaults['order_by']);
		$old_instance['order'] = (isset($new_instance['order']) && in_array($new_instance['order'], array_keys($this->cde_order_types), true) ? $new_instance['order'] : $this->cde_defaults['order']);

		//thumbnail size
		$old_instance['thumbnail_size'] = (isset($new_instance['thumbnail_size']) && in_array($new_instance['thumbnail_size'], $this->cde_image_sizes, true) ? $new_instance['thumbnail_size'] : $this->cde_defaults['thumbnail_size']);

		//booleans
		$old_instance['show_past_events'] = (isset($new_instance['show_past_events']) ? true : false);
		$old_instance['show_occurrences'] = (isset($new_instance['show_occurrences']) ? true : false);
		$old_instance['show_event_thumbnail'] = (isset($new_instance['show_event_thumbnail']) ? true : false);
		$old_instance['show_event_excerpt'] = (isset($new_instance['show_event_excerpt']) ? true : false);

		//texts
		$old_instance['title'] = sanitize_text_field(isset($new_instance['title']) ? $new_instance['title'] : $this->cde_defaults['title']);
		$old_instance['no_events_message'] = sanitize_text_field(isset($new_instance['no_events_message']) ? $new_instance['no_events_message'] : $this->cde_defaults['no_events_message']);

		//date format
		$old_instance['date_format'] = sanitize_text_field(isset($new_instance['date_format']) ? $new_instance['date_format'] : $this->cde_defaults['date_format']);
		$old_instance['time_format'] = sanitize_text_field(isset($new_instance['time_format']) ? $new_instance['time_format'] : $this->cde_defaults['time_format']);

		//taxonomies
		$old_instance['categories'] = (isset($new_instance['categories']) && in_array($new_instance['categories'], array_keys($this->cde_taxonomies), true) ? $new_instance['categories'] : $this->cde_defaults['categories']);
		$old_instance['locations'] = (isset($new_instance['locations']) && in_array($new_instance['locations'], array_keys($this->cde_taxonomies), true) ? $new_instance['locations'] : $this->cde_defaults['locations']);

		//categories
		if($old_instance['categories'] === 'selected')
		{
			$old_instance['categories_arr'] = array();

			if(isset($new_instance['categories_arr']) && is_array($new_instance['categories_arr']))
			{
				foreach($new_instance['categories_arr'] as $cat_id)
				{
					$old_instance['categories_arr'][] = (int)$cat_id;
				}

				$old_instance['categories_arr'] = array_unique($old_instance['categories_arr'], SORT_NUMERIC);
			}
		}
		else
			$old_instance['categories_arr'] = array();

		//locations
		if($old_instance['locations'] === 'selected')
		{
			$old_instance['locations_arr'] = array();

			if(isset($new_instance['locations_arr']) && is_array($new_instance['locations_arr']))
			{
				foreach($new_instance['locations_arr'] as $cat_id)
				{
					$old_instance['locations_arr'][] = (int)$cat_id;
				}

				$old_instance['locations_arr'] = array_unique($old_instance['locations_arr'], SORT_NUMERIC);
			}
		}
		else
			$old_instance['locations_arr'] = array();

		return $old_instance;
	}


	/**
	 * 
	*/
	private function display_taxonomy_checkbox_list($taxonomy_name, $name, $instance, $depth = 0, $parent = 0)
	{
		$html = '';
		$array = isset($instance[$name]) ? $instance[$name] : array();
		$terms = get_terms(
			$taxonomy_name,
			array(
				'hide_empty' => false,
				'parent' => $parent
			)
		);

		if(!empty($terms))
		{
			$html .= '
			<ul class="terms-checkbox-list depth-level-'.$depth++.'">';

			foreach($terms as $term)
			{
				$html .= '
				<li>
					<input id="'.$this->get_field_id('chkbxlst_'.$term->term_taxonomy_id).'" type="checkbox" name="'.$this->get_field_name($name).'[]" value="'.esc_attr($term->term_id).'" '.checked(true, in_array($term->term_id, $array), false).' /> <label for="'.$this->get_field_id('chkbxlst_'.$term->term_taxonomy_id).'">'.$term->name.'</label>
					'.$this->display_taxonomy_checkbox_list($taxonomy_name, $name, $instance, $depth, $term->term_id).'
				</li>';
			}

			$html .= '
			</ul>';
		}
		elseif($parent === 0)
			$html = __('No results were found.', 'cdash-events');

		return $html;
	}
}


class Cdash_Events_Categories_Widget extends WP_Widget
{
	private $cde_defaults = array();
	private $cde_orders = array();
	private $cde_order_types = array();


	public function __construct()
	{
		parent::__construct(
			'Cdash_Events_Categories_Widget',
			__('Events Categories', 'cdash-events'),
			array(
				'description' => __('Displays a list of events categories', 'cdash-events')
			)
		);

		$this->cde_defaults = array(
			'title' => __('Events Categories', 'cdash-events'),
			'display_as_dropdown' => false,
			'show_hierarchy' => true,
			'order_by' => 'name',
			'order' => 'asc'
		);

		$this->cde_orders = array(
			'id' => __('ID', 'cdash-events'),
			'name' => __('Name', 'cdash-events')
		);

		$this->cde_order_types = array(
			'asc' => __('Ascending', 'cdash-events'),
			'desc' => __('Descending', 'cdash-events')
		);
	}


	/**
	 * 
	*/
	public function widget($args, $instance)
	{
		$instance['title'] = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

		$html = $args['before_widget'].$args['before_title'].(!empty($instance['title']) ? $instance['title'] : '').$args['after_title'];
		$html .= cde_display_event_taxonomy('event-category', $instance);
		$html .= $args['after_widget'];

		echo $html;
	}


	/**
	 * 
	*/
	public function form($instance)
	{
		$html = '
		<p>
			<label for="'.$this->get_field_id('title').'">'.__('Title', 'cdash-events').':</label>
			<input id="'.$this->get_field_id('title').'" class="widefat" name="'.$this->get_field_name('title').'" type="text" value="'.esc_attr(isset($instance['title']) ? $instance['title'] : $this->cde_defaults['title']).'" />
		</p>
		<p>
			<input id="'.$this->get_field_id('display_as_dropdown').'" type="checkbox" name="'.$this->get_field_name('display_as_dropdown').'" value="" '.checked(true, (isset($instance['display_as_dropdown']) ? $instance['display_as_dropdown'] : $this->cde_defaults['display_as_dropdown']), false).' /> <label for="'.$this->get_field_id('display_as_dropdown').'">'.__('Display as dropdown', 'cdash-events').'</label><br />
			<input id="'.$this->get_field_id('show_hierarchy').'" type="checkbox" name="'.$this->get_field_name('show_hierarchy').'" value="" '.checked(true, (isset($instance['show_hierarchy']) ? $instance['show_hierarchy'] : $this->cde_defaults['show_hierarchy']), false).' /> <label for="'.$this->get_field_id('show_hierarchy').'">'.__('Show hierarchy', 'cdash-events').'</label>
		</p>
		<p>
			<label for="'.$this->get_field_id('order_by').'">'.__('Order by', 'cdash-events').':</label>
			<select id="'.$this->get_field_id('order_by').'" name="'.$this->get_field_name('order_by').'">';

		foreach($this->cde_orders as $id => $order_by)
		{
			$html .= '
				<option value="'.esc_attr($id).'" '.selected($id, (isset($instance['order_by']) ? $instance['order_by'] : $this->cde_defaults['order_by']), false).'>'.$order_by.'</option>';
		}

		$html .= '
			</select>
			<br />
			<label for="'.$this->get_field_id('order').'">'.__('Order', 'cdash-events').':</label>
			<select id="'.$this->get_field_id('order').'" name="'.$this->get_field_name('order').'">';

		foreach($this->cde_order_types as $id => $order)
		{
			$html .= '
				<option value="'.esc_attr($id).'" '.selected($id, (isset($instance['order']) ? $instance['order'] : $this->cde_defaults['order']), false).'>'.$order.'</option>';
		}

		$html .= '
			</select>
		</p>';

		echo $html;
	}


	/**
	 * 
	*/
	public function update($new_instance, $old_instance)
	{
		//title
		$old_instance['title'] = sanitize_text_field(isset($new_instance['title']) ? $new_instance['title'] : $this->cde_defaults['title']);

		//checkboxes
		$old_instance['display_as_dropdown'] = (isset($new_instance['display_as_dropdown']) ? true : false);
		$old_instance['show_hierarchy'] = (isset($new_instance['show_hierarchy']) ? true : false);

		//order
		$old_instance['order_by'] = (isset($new_instance['order_by']) && in_array($new_instance['order_by'], array_keys($this->cde_orders), true) ? $new_instance['order_by'] : $this->cde_defaults['order_by']);
		$old_instance['order'] = (isset($new_instance['order']) && in_array($new_instance['order'], array_keys($this->cde_order_types), true) ? $new_instance['order'] : $this->cde_defaults['order']);

		return $old_instance;
	}
}


class Cdash_Events_Locations_Widget extends WP_Widget
{
	private $cde_defaults = array();
	private $cde_orders = array();
	private $cde_order_types = array();


	public function __construct()
	{
		parent::__construct(
			'Cdash_Events_Locations_Widget',
			__('Events Locations', 'cdash-events'),
			array(
				'description' => __('Displays a list of events locations', 'cdash-events')
			)
		);

		$this->cde_defaults = array(
			'title' => __('Events Locations', 'cdash-events'),
			'display_as_dropdown' => false,
			'show_hierarchy' => true,
			'order_by' => 'name',
			'order' => 'asc'
		);

		$this->cde_orders = array(
			'id' => __('ID', 'cdash-events'),
			'name' => __('Name', 'cdash-events')
		);

		$this->cde_order_types = array(
			'asc' => __('Ascending', 'cdash-events'),
			'desc' => __('Descending', 'cdash-events')
		);
	}


	/**
	 * 
	*/
	public function widget($args, $instance)
	{
		$instance['title'] = apply_filters('widget_title', $instance['title'], $instance, $this->id_base);

		$html = $args['before_widget'].$args['before_title'].(!empty($instance['title']) ? $instance['title'] : '').$args['after_title'];
		$html .= cde_display_event_taxonomy('event-locations', $instance);
		$html .= $args['after_widget'];

		echo $html;
	}


	/**
	 * 
	*/
	public function form($instance)
	{
		$html = '
		<p>
			<label for="'.$this->get_field_id('title').'">'.__('Title', 'cdash-events').':</label>
			<input id="'.$this->get_field_id('title').'" class="widefat" name="'.$this->get_field_name('title').'" type="text" value="'.esc_attr(isset($instance['title']) ? $instance['title'] : $this->cde_defaults['title']).'" />
		</p>
		<p>
			<input id="'.$this->get_field_id('display_as_dropdown').'" type="checkbox" name="'.$this->get_field_name('display_as_dropdown').'" value="" '.checked(true, (isset($instance['display_as_dropdown']) ? $instance['display_as_dropdown'] : $this->cde_defaults['display_as_dropdown']), false).' /> <label for="'.$this->get_field_id('display_as_dropdown').'">'.__('Display as dropdown', 'cdash-events').'</label><br />
			<input id="'.$this->get_field_id('show_hierarchy').'" type="checkbox" name="'.$this->get_field_name('show_hierarchy').'" value="" '.checked(true, (isset($instance['show_hierarchy']) ? $instance['show_hierarchy'] : $this->cde_defaults['show_hierarchy']), false).' /> <label for="'.$this->get_field_id('show_hierarchy').'">'.__('Show hierarchy', 'cdash-events').'</label>
		</p>
		<p>
			<label for="'.$this->get_field_id('order_by').'">'.__('Order by', 'cdash-events').':</label>
			<select id="'.$this->get_field_id('order_by').'" name="'.$this->get_field_name('order_by').'">';

		foreach($this->cde_orders as $id => $order_by)
		{
			$html .= '
				<option value="'.esc_attr($id).'" '.selected($id, (isset($instance['order_by']) ? $instance['order_by'] : $this->cde_defaults['order_by']), false).'>'.$order_by.'</option>';
		}

		$html .= '
			</select>
			<br />
			<label for="'.$this->get_field_id('order').'">'.__('Order', 'cdash-events').':</label>
			<select id="'.$this->get_field_id('order').'" name="'.$this->get_field_name('order').'">';

		foreach($this->cde_order_types as $id => $order)
		{
			$html .= '
				<option value="'.esc_attr($id).'" '.selected($id, (isset($instance['order']) ? $instance['order'] : $this->cde_defaults['order']), false).'>'.$order.'</option>';
		}

		$html .= '
			</select>
		</p>';

		echo $html;
	}


	/**
	 * 
	*/
	public function update($new_instance, $old_instance)
	{
		//title
		$old_instance['title'] = sanitize_text_field(isset($new_instance['title']) ? $new_instance['title'] : $this->cde_defaults['title']);

		//checkboxes
		$old_instance['display_as_dropdown'] = (isset($new_instance['display_as_dropdown']) ? true : false);
		$old_instance['show_hierarchy'] = (isset($new_instance['show_hierarchy']) ? true : false);

		//order
		$old_instance['order_by'] = (isset($new_instance['order_by']) && in_array($new_instance['order_by'], array_keys($this->cde_orders), true) ? $new_instance['order_by'] : $this->cde_defaults['order_by']);
		$old_instance['order'] = (isset($new_instance['order']) && in_array($new_instance['order'], array_keys($this->cde_order_types), true) ? $new_instance['order'] : $this->cde_defaults['order']);

		return $old_instance;
	}
}

?>
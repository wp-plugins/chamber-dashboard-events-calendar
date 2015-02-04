<?php
if(!defined('ABSPATH')) exit;

new Cdash_Events_Listing($cdash_events);

class Cdash_Events_Listing
{
	private $options = array();
	private $recurrences = array();
	private $cdash_events;


	public function __construct($cdash_events)
	{
		$this->events_maker = $cdash_events;

		//settings
		$this->options = $cdash_events->get_options();

		//actions
		add_action('after_setup_theme', array(&$this, 'set_recurrences'));
		add_action('manage_posts_custom_column', array(&$this, 'add_new_event_columns_content'), 10, 2);
		add_action('restrict_manage_posts', array(&$this, 'event_filter_dates'));

		//filters
		add_filter('manage_edit-event_sortable_columns', array(&$this, 'register_sortable_custom_columns'));
		add_filter('request', array(&$this, 'sort_custom_columns'));
		add_filter('manage_event_posts_columns', array(&$this, 'add_new_event_columns'));
	}


	/**
	 * 
	*/
	public function set_recurrences()
	{
		$this->recurrences = $this->events_maker->get_recurrences();
	}


	/**
	 * 
	*/
	public function event_filter_dates()
	{
		if(is_admin())
		{
			global $pagenow;

			$screen = get_current_screen();
			$post_types = apply_filters('cde_event_post_type', array('event'));
			
			foreach ($post_types as $post_type)
			{
				if($pagenow === 'edit.php' && $screen->post_type == $post_type && $screen->id === 'edit-'.$post_type)
				{
					echo '
					<label for="emflds">'.__('Start Date', 'cdash-events').'</label> <input id="emflds" class="events-datepicker" type="text" name="event_start_date" value="'.(!empty($_GET['event_start_date']) ? esc_attr($_GET['event_start_date']) : '').'" /> 
					<label for="emflde">'.__('End Date', 'cdash-events').'</label> <input id="emflde" class="events-datepicker" type="text" name="event_end_date" value="'.(!empty($_GET['event_end_date']) ? esc_attr($_GET['event_end_date']) : '').'" /> ';
				}
			}
		}
	}


	/**
	 * Registers sortable columns
	*/
	public function register_sortable_custom_columns($column)
	{
		$column['event_start_date'] = 'event_start_date';
		$column['event_end_date'] = 'event_end_date';

		return $column;
	}


	/**
	 * Sorts custom columns
	*/
	public function sort_custom_columns($qvars)
	{
		if(is_admin() && in_array($qvars['post_type'], apply_filters('cde_event_post_type', array('event'))))
		{
			if(!isset($qvars['orderby']))
			{
						$qvars['orderby'] = 'start';
			}

			if(isset($qvars['orderby']))
			{
				if(in_array($qvars['orderby'], array('event_start_date', 'event_end_date'), true))
				{
					$qvars['meta_key'] = '_'.$qvars['orderby'];
					$qvars['orderby'] = 'meta_value';
				}
				elseif($qvars['orderby'] === 'date')
					$qvars['orderby'] = 'date';
			}

			if(!isset($qvars['order']))
				$qvars['order'] = 'asc';
		}

		return $qvars;
	}


	/**
	 * Adds new event listing columns
	*/
	public function add_new_event_columns($columns)
	{
		unset($columns['date']);

		$columns['event_start_date'] = __('Start', 'cdash-events');
		$columns['event_end_date'] = __('End', 'cdash-events');
		$columns['event_recurrence'] = __('Recurrence', 'cdash-events');
		$columns['event_free'] = __('Tickets', 'cdash-events');

		return $columns;
	}


	/**
	 * Adds new event listing columns content
	*/
	public function add_new_event_columns_content($column_name, $id)
	{
		$mode = !empty($_GET['mode']) ? sanitize_text_field($_GET['mode']) : '';

		switch($column_name)
		{
			case 'event_start_date':
			case 'event_end_date':
				$date = get_post_meta($id, '_'.$column_name, true);

				echo (cde_is_all_day($id) ? substr($date, 0, 10) : substr(str_replace(' ', ', ', $date), 0, 17));
				break;

			case 'event_recurrence':
				$recurrence = get_post_meta($id, '_event_recurrence', true);
				if( isset( $recurrence) && '' !== $recurrence )  {
					echo $this->recurrences[$recurrence['type']];
				}
				break;

			case 'event_free':
				if(!cde_is_free($id))
				{
					echo __('Paid', 'cdash-events').'<br />';

					if($mode === 'excerpt')
					{
						$tickets = get_post_meta($id, '_event_tickets', true);

						foreach($tickets as $ticket)
						{
							echo $ticket['name'].': '.cde_get_currency_symbol($ticket['price']).'<br />';
						}
					}
				}
				else
					echo __('Free', 'cdash-events');
				break;
		}
	}
}
?>
<?php
if(!defined('ABSPATH')) exit;

new Cdash_Events_Settings($cdash_events);

class Cdash_Events_Settings
{
	private $defaults = array();
	private $pages = array();
	private $errors = array();
	private $options = array();
	private $sortings = array();
	private $calendar_displays = array();
	private $calendar_contents = array();
	private $tabs = array();
	private $currencies = array();
	private $cdash_events;
	private $transient_id = '';


	public function __construct($cdash_events)
	{
		// passed vars
		$this->events_maker = $cdash_events;
		$this->options = $cdash_events->get_options();
		$this->defaults = $cdash_events->get_defaults();
		$this->transient_id = $cdash_events->get_session_id();

		//actions
		add_action('admin_menu', array(&$this, 'settings_page'));
		add_action('admin_init', array(&$this, 'register_settings'));
		add_action('after_setup_theme', array(&$this, 'set_currencies'));
		add_action('after_setup_theme', array(&$this, 'load_defaults'));

		//filters
		add_filter('plugin_action_links', array(&$this, 'plugin_settings_link'), 10, 2);
	}


	/**
	 * 
	*/
	public function set_currencies()
	{
		$this->currencies = $this->events_maker->get_currencies();
	}


	/**
	 * 
	*/
	public function load_defaults()
	{
		$this->sortings = array(
			'publish' => __('Publish date', 'cdash-events'),
			'start' => __('Events start date', 'cdash-events'),
			'end' => __('Events end date', 'cdash-events')
		);

		$this->calendar_displays = array(
			'page' => __('selected page', 'cdash-events'),
			'manual' => __('manually', 'cdash-events')
		);

		$this->calendar_contents = array(
			'before' => __('before the content', 'cdash-events'),
			'after' => __('after the content', 'cdash-events')
		);

		$this->pages = get_pages(
			array(
				'sort_column' => 'post_title',
				'sort_order' => 'asc',
				'number' => 0
			)
		);
		
		$this->errors = apply_filters('cde_settings_errors', array(
			'settings_gene_saved' => __('General settings saved.', 'cdash-events'),
			'settings_gene_reseted' => __('General settings restored to defaults.', 'cdash-events'),
			'no_such_menu' => __('There is no such menu.', 'cdash-events'),
			'empty_menu_name' => __('Menu name can not be empty.', 'cdash-events')
		));

		$this->tabs = apply_filters('cde_settings_tabs', array(
			'general' => array(
				'name' => __('General', 'cdash-events'),
				'key' => 'cdash_events_general',
				'submit' => 'save_cde_general',
				'reset' => 'reset_cde_general'
			),
		));

	}


	/**
	 * Adds link to Settings page
	*/
	public function plugin_settings_link($links, $file) 
	{
		if(!is_admin() || !current_user_can('manage_options'))
			return $links;

		static $plugin;

		$plugin = plugin_basename(__FILE__);

		if($file == $plugin)
		{
			$settings_link = sprintf('<a href="%s">%s</a>', admin_url('options-general.php').'?page=cdash-events-options', __('Settings', 'cdash-events'));
			array_unshift($links, $settings_link);
		}

		return $links;
	}


	/**
	 * Adds options page 
	*/
	public function settings_page()
	{
		if ( is_plugin_active( 'chamber-dashboard-business-directory/cdash-business-directory.php' ) || is_plugin_active( 'chamber-dashboard-crm/cdash-crm.php' ) ) {
			// Another Chamber Dashboard plugin is active, so we just need to add a submenu page
			add_submenu_page( '/chamber-dashboard-business-directory/options.php', __('Calendar Options', 'cdash-events'), __('Calendar Options', 'cdash-events'), 'manage_options', 'cdash-events', array($this, 'options_page') );
		} else {
			// Chamber Dashboard Business Directory plugin is not active, so we need to add the whole menu
			add_menu_page( 
				__('Chamber Dashboard', 'cdash-events'), 
				__('Chamber Dashboard', 'cdash-events'), 
				'manage_options', 
				'/events-calendar/options.php', 
				array($this, 'options_page'),
				'dashicons-admin-generic', 
				85 
			);
		}
	}


	/**
	 * 
	*/
	public function options_page()
	{
		$tab_key = (isset($_GET['tab']) ? $_GET['tab'] : 'general');

		echo '
		<div class="wrap">
			<h2>'.__('Chamber Dashboard Events Calendar', 'cdash-events').'</h2>

			<div class="cdash-events-settings">

				<form action="options.php" method="post">';

		wp_nonce_field('update-options');
		settings_fields($this->tabs[$tab_key]['key']);
		do_settings_sections($this->tabs[$tab_key]['key']);

		echo '
					<p class="submit">';

		submit_button('', 'primary', $this->tabs[$tab_key]['submit'], false);

		echo ' ';

		if($this->tabs[$tab_key]['reset'] !== false)
			submit_button(__('Reset to defaults', 'cdash-events'), 'secondary', $this->tabs[$tab_key]['reset'], false);

		echo '
					</p>
				</form>
			</div>
			<div class="clear"></div>
		</div>';
	}


	/**
	 * 
	*/
	public function register_settings()
	{
		// general
		register_setting('cdash_events_general', 'cdash_events_general', array(&$this, 'validate_general'));
		add_settings_section('cdash_events_general', __('General settings', 'cdash-events'), '', 'cdash_events_general');
		add_settings_field('cde_default_event_options', __('Event default options', 'cdash-events'), array(&$this, 'cde_default_event_options'), 'cdash_events_general', 'cdash_events_general');
		add_settings_field('cde_thumbnail_display_options', __('Featured image display options', 'cdash-events'), array(&$this, 'cde_thumbnail_display_options'), 'cdash_events_general', 'cdash_events_general');
		add_settings_field('cde_events_in_rss', __('RSS feed', 'cdash-events'), array(&$this, 'cde_events_in_rss'), 'cdash_events_general', 'cdash_events_general');

		// currencies
		add_settings_section('cdash_events_currencies', __('Currency settings', 'cdash-events'), '', 'cdash_events_general');
		add_settings_field('cde_tickets_currency_code', __('Currency', 'cdash-events'), array(&$this, 'cde_tickets_currency_code'), 'cdash_events_general', 'cdash_events_currencies');
		add_settings_field('cde_tickets_currency_position', __('Currency position', 'cdash-events'), array(&$this, 'cde_tickets_currency_position'), 'cdash_events_general', 'cdash_events_currencies');
		add_settings_field('cde_tickets_currency_symbol', __('Currency symbol', 'cdash-events'), array(&$this, 'cde_tickets_currency_symbol'), 'cdash_events_general', 'cdash_events_currencies');
		add_settings_field('cde_tickets_currency_format', __('Currency display format', 'cdash-events'), array(&$this, 'cde_tickets_currency_format'), 'cdash_events_general', 'cdash_events_currencies');

		// other
		add_settings_section('cdash_events_other', __('Date settings', 'cdash-events'), '', 'cdash_events_general');
		add_settings_field('cde_date_format', __('Date and time format', 'cdash-events'), array(&$this, 'cde_date_format'), 'cdash_events_general', 'cdash_events_other');
		add_settings_field('cde_first_weekday', __('First day of the week', 'cdash-events'), array(&$this, 'cde_first_weekday'), 'cdash_events_general', 'cdash_events_other');

		do_action('cde_after_register_settings');
	}
	
	
	/**
	 * 
	*/
	public function cde_default_event_options()
	{
		$options = array(
			'google_map' => __('Display Google Map', 'cdash-events'),
			'display_location_details' => __('Display Location Details', 'cdash-events'),
			'price_tickets_info' => __('Display Tickets Info', 'cdash-events')
		);
		
		$options = apply_filters('cde_default_event_display_options', $options);
		$values = $this->options['general']['default_event_options'];
		
		echo '
		<div id="cde_default_event_options">
			<fieldset>';
			foreach($options as $key => $name)
			{
				?>
				<label for="cde_default_event_option_<?php echo $key; ?>">
					<input id="cde_default_event_option_<?php echo $key; ?>" type="checkbox" name="cdash_events_general[default_event_options][<?php echo $key; ?>]" <?php checked((isset($values[$key]) && $values[$key] !== '' ? $values[$key] : '0'), '1'); ?> /><?php echo $name; ?>
				</label><br />
				<?php
			}
		echo '
				<span class="description">'.__('Select default display options for single event (this can overriden for each event separately).', 'cdash-events').'</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	*/
	public function cde_thumbnail_display_options()
	{
		$options = array(
			'single_thumbnail' => __('Display Featured Image on Single Event View', 'cdash-events'),
			'archive_thumbnail' => __('Display Featured Image on Category and Tag Archive Views', 'cdash-events'),
		);
		
		$options = apply_filters('cde_thumbnail_display_options', $options);
		$values = $this->options['general']['thumbnail_display_options'];
		
		echo '
		<div id="cde_thumbnail_display_options">
			<fieldset>';
			foreach($options as $key => $name)
			{
				?>
				<label for="cde_thumbnail_display_option_<?php echo $key; ?>">
					<input id="cde_thumbnail_display_option_<?php echo $key; ?>" type="checkbox" name="cdash_events_general[thumbnail_display_options][<?php echo $key; ?>]" <?php checked((isset($values[$key]) && $values[$key] !== '' ? $values[$key] : '0'), '1'); ?> /><?php echo $name; ?>
				</label><br />
				<?php
			}
		echo '
				<span class="description">'.__('Some themes will automatically display featured images - check these options if you want to display featured images, but your theme does not already display them.', 'cdash-events').'</span>
			</fieldset>
		</div>';
	}	
	
	
	/**
	 * 
	*/
	public function cde_events_in_rss()
	{
		echo '
		<div id="cde_events_in_rss">
			<fieldset>
				<input id="em-events-in-rss" type="checkbox" name="cdash_events_general[events_in_rss]" '.checked($this->options['general']['events_in_rss'], true, false).' /><label for="em-events-in-rss">'.__('Enable RSS feed', 'cdash-events').'</label>
				<br />
				<span class="description">'.__('Enable to include events in your website main RSS feed.', 'cdash-events').'</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	*/
	public function cde_tickets_currency_code()
	{
		echo '
		<div id="cde_tickets_currency_code">
			<fieldset>
				<select id="em-tickets-currency-code" name="cdash_events_general[currencies][code]">';

		foreach($this->currencies['codes'] as $code => $currency)
		{
			echo '
					<option value="'.esc_attr($code).'" '.selected($code, strtoupper($this->options['general']['currencies']['code']), false).'>'.esc_html($currency).' ('.$this->currencies['symbols'][$code].')</option>';
		}

		echo '
				</select>
				<br />
				<span class="description">'.__('Choose the currency that will be used for ticket prices.', 'cdash-events').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function cde_tickets_currency_position()
	{
		echo '
		<div id="cde_tickets_currency_position">
			<fieldset>';

		foreach($this->currencies['positions'] as $key => $position)
		{
			echo '
				<input id="em-ticket-currency-position-'.$key.'" type="radio" name="cdash_events_general[currencies][position]" value="'.esc_attr($key).'" '.checked($key, $this->options['general']['currencies']['position'], false).' /><label for="em-ticket-currency-position-'.$key.'">'.$position.'</label>';
		}

		echo '
				<br />
				<span class="description">'.__('Choose the location of the currency sign.', 'cdash-events').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function cde_tickets_currency_symbol()
	{
		echo '
		<div id="cde_tickets_currency_symbol">
			<fieldset>
				<input type="text" size="4" name="cdash_events_general[currencies][symbol]" value="'.esc_attr($this->options['general']['currencies']['symbol']).'" />
				<br />
				<span class="description">'.__('This will appear next to all the currency figures on the website. Ex. $, USD, &euro;...', 'cdash-events').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function cde_tickets_currency_format()
	{
		echo '
		<div id="cde_tickets_currency_format">
			<fieldset>
				<select id="em-tickets-currency-format" name="cdash_events_general[currencies][format]">';

		foreach($this->currencies['formats'] as $code => $format)
		{
			echo '
					<option value="'.esc_attr($code).'" '.selected($code, $this->options['general']['currencies']['format'], false).'>'.$format.'</option>';
		}

		echo '
				</select>
				<br />
				<span class="description">'.__('This determines how your currency is displayed. Ex. 1,234.56 or 1,200 or 1200.', 'cdash-events').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function cde_date_format()
	{
		echo '
		<div id="cde_date_format">
			<fieldset>
				<label for="em-date-format">'.__('Date', 'cdash-events').':</label> <input id="em-date-format" type="text" name="cdash_events_general[datetime_format][date]" value="'.esc_attr($this->options['general']['datetime_format']['date']).'" /> <code>'.date_i18n($this->options['general']['datetime_format']['date'], current_time('timestamp')).'</code>
				<br />
				<label for="em-time-format">'.__('Time', 'cdash-events').':</label> <input id="em-time-format" type="text" name="cdash_events_general[datetime_format][time]" value="'.esc_attr($this->options['general']['datetime_format']['time']).'" /> <code>'.date($this->options['general']['datetime_format']['time'], current_time('timestamp')).'</code>
				<br />
				<span class="description">'.__('Enter your preffered date and time formatting.', 'cdash-events').'</span>
			</fieldset>
		</div>';
	}


	/**
	 * 
	*/
	public function cde_first_weekday()
	{
		global $wp_locale;

		echo '
		<div id="cde_first_weekday">
			<fieldset>
				<select name="cdash_events_general[first_weekday]">
					<option value="1" '.selected(1, $this->options['general']['first_weekday'], false).'>'.$wp_locale->get_weekday(1).'</option>
					<option value="7" '.selected(7, $this->options['general']['first_weekday'], false).'>'.$wp_locale->get_weekday(0).'</option>
				</select>
				<br />
				<span class="description">'.__('Select preffered first day of the week for the calendar display.', 'cdash-events').'</span>
			</fieldset>
		</div>';
	}



	/**
	 * Validates or resets general settings
	*/
	public function validate_general($input)
	{
		if(isset($_POST['save_cde_general']))
		{
			// currencies
			$input['currencies']['symbol'] = sanitize_text_field($input['currencies']['symbol']);
			$input['currencies']['code'] = (isset($input['currencies']['code']) && in_array($input['currencies']['code'], array_keys($this->currencies['codes'])) ? strtoupper($input['currencies']['code']) : $this->defaults['currencies']['code']);
			$input['currencies']['format'] = (isset($input['currencies']['format']) && in_array($input['currencies']['format'], array_keys($this->currencies['formats'])) ? $input['currencies']['format'] : $this->defaults['currencies']['format']);
			$input['currencies']['position'] = (isset($input['currencies']['position']) && in_array($input['currencies']['position'], array_keys($this->currencies['positions'])) ? $input['currencies']['position'] : $this->defaults['currencies']['position']);

			// date, time, weekday
			$input['datetime_format']['date'] = sanitize_text_field($input['datetime_format']['date']);
			$input['datetime_format']['time'] = sanitize_text_field($input['datetime_format']['time']);
			$input['first_weekday'] = (in_array($input['first_weekday'], array(1, 7)) ? (int)$input['first_weekday']: $this->defaults['general']['first_weekday']);

			if($input['datetime_format']['date'] === '')
				$input['datetime_format']['date'] = get_option('date_format');

			if($input['datetime_format']['time'] === '')
				$input['datetime_format']['time'] = get_option('time_format');
	
			// event default options
			$default_event_options = array();

			if (isset($input['default_event_options']))
			{
				foreach($input['default_event_options'] as $key => $value)
				{
					$default_event_options[$key] = (isset($input['default_event_options'][$key]) ? true : false);
				}
			}
			$input['default_event_options'] = $default_event_options;

			// thumbnail default options
			$thumbnail_display_options = array();

			if (isset($input['thumbnail_display_options']))
			{
				foreach($input['thumbnail_display_options'] as $key => $value)
				{
					$thumbnail_display_options[$key] = (isset($input['thumbnail_display_options'][$key]) ? true : false);
				}
			}
			$input['thumbnail_display_options'] = $thumbnail_display_options;
			
			// RSS feed
			$input['events_in_rss'] = (isset($input['events_in_rss']) ? true : false);

		}
		elseif(isset($_POST['reset_cde_general']))
		{
			$input = $this->defaults['general'];

			if(!$this->options['general']['display_page_notice'])
				$input['display_page_notice'] = false;

			//menu
			$input['event_nav_menu']['show'] = false;
			$input['event_nav_menu']['menu_id'] = $this->defaults['general']['event_nav_menu']['menu_id'];
			$input['event_nav_menu']['menu_name'] = $this->defaults['general']['event_nav_menu']['menu_name'];
			$input['event_nav_menu']['item_id'] = $this->update_menu();

			//datetime format
			$input['datetime_format'] = array(
				'date' => get_option('date_format'),
				'time' => get_option('time_format')
			);

			set_transient($this->transient_id, maybe_serialize(array('status' => 'updated', 'text' => $this->errors['settings_gene_reseted'])), 60);
		}

		return $input;
	}

}
?>
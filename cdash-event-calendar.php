<?php
/*
Plugin Name: Chamber Dashboard Event Calendar
Description: Create a calendar of events and display it on your site.  A fork of the Events Maker plugin, modified to work with the Chamber Dashboard suite of plugins.
Version: 1.0.2
Author: Morgan Kay
Author URI: http://wpalchemists.com/
Plugin URI: http://chamberdashboard.com/
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: cdash-events
Domain Path: /languages

Chamber Dashboard Events Calendar
Copyright (C) 2013, Mrgan Kay and the Fremont Chamber of Commerce

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

if(!defined('ABSPATH')) exit;

define('CDASH_EVENTS_URL', plugins_url('', __FILE__));
define('CDASH_EVENTS_PATH', plugin_dir_path(__FILE__));
define('CDASH_EVENTS_REL_PATH', dirname(plugin_basename(__FILE__)).'/');
define('CDASH_EVENTS_UPDATE_VERSION_1', '1.0.10');

$cdash_events = new Cdash_Events();

include_once(CDASH_EVENTS_PATH.'includes/core-functions.php');
include_once(CDASH_EVENTS_PATH.'includes/class-update.php');
include_once(CDASH_EVENTS_PATH.'includes/class-settings.php');
include_once(CDASH_EVENTS_PATH.'includes/class-query.php');
include_once(CDASH_EVENTS_PATH.'includes/class-taxonomies.php');
include_once(CDASH_EVENTS_PATH.'includes/class-templates.php');
include_once(CDASH_EVENTS_PATH.'includes/class-shortcodes.php');
include_once(CDASH_EVENTS_PATH.'includes/class-listing.php');
include_once(CDASH_EVENTS_PATH.'includes/class-metaboxes.php');
include_once(CDASH_EVENTS_PATH.'includes/class-widgets.php');
include_once(CDASH_EVENTS_PATH.'includes/class-helper.php');

class Cdash_Events
{
	private $options = array();
	private $currencies = array();
	private $recurrences = array();
	private $notices = array();
	private $defaults = array(
		'general' => array(
			'display_page_notice' => true,
			'default_event_options' => array(
				'google_map' => true,
				'display_location_details' => true,
				'price_tickets_info' => true,
			),
			'thumbnail_display_options' => array(
				'single_thumbnail' => false,
				'archive_thumbnail' => false
			),
			'events_in_rss' => true,
			'event_nav_menu' => array(
				'show' => false,
				'menu_name' => '',
				'menu_id' => 0,
				'item_id' => 0
			),
			'datetime_format' => array(
				'date' => '',
				'time' => ''
			),
			'first_weekday' => 1,
			'rewrite_rules' => true,
			'currencies' => array(
				'code' => 'usd',
				'symbol' => '$',
				'position' => 'after',
				'format' => 1
			)
		),
		'permalinks' => array(
			'event_rewrite_base' => 'events',
			'event_rewrite_slug' => 'event',
			'event_categories_rewrite_slug' => 'category',
			'event_tags_rewrite_slug' => 'tag',
			'event_locations_rewrite_slug' => 'location',
		),
		'version' => '1.2.2'
	);
	private $transient_id = '';


	public function __construct()
	{
		register_activation_hook(__FILE__, array(&$this, 'multisite_activation'));
		register_deactivation_hook(__FILE__, array(&$this, 'multisite_deactivation'));

		// settings
		$this->options = array(
			'general' => array_merge($this->defaults['general'], get_option('cdash_events_general', $this->defaults['general'])),
			'permalinks' => array_merge($this->defaults['permalinks'], get_option('cdash_events_permalinks', $this->defaults['permalinks'])),
		);

		// session id
		$this->transient_id = (isset($_COOKIE['cde_transient_id']) ? $_COOKIE['cde_transient_id'] : 'emtr_'.sha1($this->generate_hash()));

		// actions
		add_action('init', array(&$this, 'register_taxonomies'));
		add_action('init', array(&$this, 'register_post_types'));
		add_action('plugins_loaded', array(&$this, 'init_session'), 1);
		add_action('plugins_loaded', array(&$this, 'load_textdomain'));
		add_action('admin_footer', array(&$this, 'edit_screen_icon'));
		add_action('admin_enqueue_scripts', array(&$this, 'admin_scripts_styles'));
		add_action('wp_enqueue_scripts', array(&$this, 'front_scripts_styles'));
		add_action('admin_notices', array(&$this, 'event_admin_notices'));
		add_action('after_setup_theme', array(&$this, 'pass_variables'), 9);
		add_action('wp', array(&$this, 'load_pluggable_functions'));
		add_action('wp', array(&$this, 'load_pluggable_hooks'));

		// filters
		add_filter('post_updated_messages', array(&$this, 'register_post_types_messages'));
		add_filter('plugin_row_meta', array(&$this, 'plugin_extend_links'), 10, 2);
	}


	/**
	 * Multisite activation
	*/
	public function multisite_activation($networkwide)
	{
		if(is_multisite() && $networkwide)
		{
			global $wpdb;

			$activated_blogs = array();
			$current_blog_id = $wpdb->blogid;
			$blogs_ids = $wpdb->get_col($wpdb->prepare('SELECT blog_id FROM '.$wpdb->blogs, ''));

			foreach($blogs_ids as $blog_id)
			{
				switch_to_blog($blog_id);
				$this->activate_single();
				$activated_blogs[] = (int)$blog_id;
			}

			switch_to_blog($current_blog_id);
			update_site_option('cdash_events_activated_blogs', $activated_blogs, array());
		}
		else
			$this->activate_single();
	}


	/**
	 * Activation
	*/
	public function activate_single()
	{
		global $wp_roles;

		$this->defaults['general']['datetime_format'] = array(
			'date' => get_option('date_format'),
			'time' => get_option('time_format')
		);

		// adds default options
		add_option('cdash_events_general', $this->defaults['general'], '', 'no');
		add_option('cdash_events_permalinks', $this->defaults['permalinks'], '', 'no');
		add_option('cdash_events_version', $this->defaults['version'], '', 'no');

		// permalinks
		flush_rewrite_rules();
	}


	/**
	 * Multisite deactivation
	*/
	public function multisite_deactivation($networkwide)
	{
		if(is_multisite() && $networkwide)
		{
			global $wpdb;

			$current_blog_id = $wpdb->blogid;
			$blogs_ids = $wpdb->get_col($wpdb->prepare('SELECT blog_id FROM '.$wpdb->blogs, ''));

			if(!($activated_blogs = get_site_option('cdash_events_activated_blogs', false, false)))
				$activated_blogs = array();

			foreach($blogs_ids as $blog_id)
			{
				switch_to_blog($blog_id);
				$this->deactivate_single(true);

				if(in_array((int)$blog_id, $activated_blogs, true))
					unset($activated_blogs[array_search($blog_id, $activated_blogs)]);
			}

			switch_to_blog($current_blog_id);
			update_site_option('cdash_events_activated_blogs', $activated_blogs);
		}
		else
			$this->deactivate_single();
	}


	/**
	 * Deactivation
	*/
	public function deactivate_single($multi = false)
	{

		// permalinks
		flush_rewrite_rules();
	}


	/**
	 * Passes variables to other classes
	*/
	public function pass_variables()
	{
		$this->currencies = array(
			'codes' => array(
				'AUD' => __('Australian Dollar', 'cdash-events'),
				'BDT' => __('Bangladeshi Taka', 'cdash-events'),
				'BRL' => __('Brazilian Real', 'cdash-events'),
				'BGN' => __('Bulgarian Lev', 'cdash-events'),
				'CAD' => __('Canadian Dollar', 'cdash-events'),
				'CLP' => __('Chilean Peso', 'cdash-events'),
				'CNY' => __('Chinese Yuan', 'cdash-events'),
				'COP' => __('Colombian Peso', 'cdash-events'),
				'HRK' => __('Croatian kuna', 'cdash-events'),
				'CZK' => __('Czech Koruna', 'cdash-events'),
				'DKK' => __('Danish Krone', 'cdash-events'),
				'EUR' => __('Euro', 'cdash-events'),
				'HKD' => __('Hong Kong Dollar', 'cdash-events'),
				'HUF' => __('Hungarian Forint', 'cdash-events'),
				'ISK' => __('Icelandic krona', 'cdash-events'),
				'INR' => __('Indian Rupee', 'cdash-events'),
				'IDR' => __('Indonesian Rupiah', 'cdash-events'),
				'ILS' => __('Israeli Shekel', 'cdash-events'),
				'IRR' => __('Iranian Rial', 'cdash-events'),
				'JPY' => __('Japanese Yen', 'cdash-events'),
				'MYR' => __('Malaysian Ringgit', 'cdash-events'),
				'MXN' => __('Mexican Peso', 'cdash-events'),
				'NZD' => __('New Zealand Dollar', 'cdash-events'),
				'NGN' => __('Nigerian Naira', 'cdash-events'),
				'NOK' => __('Norwegian Krone', 'cdash-events'),
				'PHP' => __('Philippine Peso', 'cdash-events'),
				'PLN' => __('Polish Zloty', 'cdash-events'),
				'GBP' => __('Pound Sterling', 'cdash-events'),
				'RON' => __('Romanian Leu', 'cdash-events'),
				'RUB' => __('Russian Ruble', 'cdash-events'),
				'SGD' => __('Singapore Dollar', 'cdash-events'),
				'ZAR' => __('South African Rand', 'cdash-events'),
				'KRW' => __('South Korean Won', 'cdash-events'),
				'SEK' => __('Swedish Krona', 'cdash-events'),
				'CHF' => __('Swiss Franc', 'cdash-events'),
				'TWD' => __('Taiwan New Dollar', 'cdash-events'),
				'THB' => __('Thai Baht', 'cdash-events'),
				'TRY' => __('Turkish Lira', 'cdash-events'),
				'UAH' => __('Ukrainian Hryvnia', 'cdash-events'),
				'AED' => __('United Arab Emirates Dirham', 'cdash-events'),
				'USD' => __('United States Dollar', 'cdash-events'),
				'VND' => __('Vietnamese Dong', 'cdash-events')
			),
			'symbols' => array(
				'AUD' => '&#36;',
				'BDT' => '&#2547;',
				'BRL' => 'R&#36;',
				'BGN' => '&#1083;&#1074;',
				'CAD' => '&#36;',
				'CLP' => '&#36;',
				'CNY' => '&#165;',
				'COP' => '&#36;',
				'HRK' => 'kn',
				'CZK' => 'K&#269;',
				'DKK' => 'kr',
				'EUR' => '&#8364;',
				'HKD' => 'HK&#36;',
				'HUF' => 'Ft',
				'ISK' => 'kr',
				'INR' => '&#8377;',
				'IDR' => 'Rp',
				'ILS' => '&#8362;',
				'IRR' => '&#65020;',
				'JPY' => '&#165;',
				'MYR' => 'RM',
				'MXN' => '&#36;',
				'NZD' => '&#36;',
				'NGN' => '&#8358;',
				'NOK' => 'kr',
				'PHP' => 'Php',
				'PLN' => 'z&#322;',
				'GBP' => '&#163;',
				'RON' => 'lei',
				'RUB' => '&#1088;&#1091;&#1073;',
				'SGD' => '&#36;',
				'ZAR' => 'R',
				'KRW' => '&#8361;',
				'SEK' => 'kr',
				'CHF' => 'SFr.',
				'TWD' => 'NT&#36;',
				'THB' => '&#3647;',
				'TRY' => '&#8378;',
				'UAH' => '&#8372;',
				'AED' => 'د.إ',
				'USD' => '&#36;',
				'VND' => '&#8363;'
			),
			'positions' => array(
				'before' => __('before the price', 'cdash-events'),
				'after' => __('after the price', 'cdash-events')
			),
			'formats' => array(
				1 => '1,234.56',
				2 => '1,234',
				3 => '1234',
				4 => '1234.56',
				5 => '1 234,56',
				6 => '1 234.56'
			)
		);

		$this->recurrences = apply_filters(
			'cde_event_recurrences_options',
			array(
				'once' => __('once', 'cdash-events'),
				'daily' => __('daily', 'cdash-events'),
				'weekly' => __('weekly', 'cdash-events'),
				'monthly' => __('monthly', 'cdash-events'),
				'yearly' => __('yearly', 'cdash-events'),
				'custom' => __('custom', 'cdash-events')
			)
		);
	}


	/**
	 * Load pluggable template functions
	*/
	public function load_pluggable_functions() 
	{
	    include_once(CDASH_EVENTS_PATH.'includes/template-functions.php');
	}
	
	
	/**
	 * Load pluggable template hooks
	*/
	public function load_pluggable_hooks() 
	{
	    include_once(CDASH_EVENTS_PATH.'includes/template-hooks.php');
	}


	/**
	 * Get default options
	*/
	public function get_defaults()
	{
		return $this->defaults;
	}


	/**
	 * Get options
	*/
	public function get_options()
	{
		return $this->options;
	}


	/**
	 * Get currencies options
	*/
	public function get_currencies()
	{
		return $this->currencies;
	}


	/**
	 * Get recurrencies options
	*/
	public function get_recurrences()
	{
		return $this->recurrences;
	}


	/**
	 * Get session id
	*/
	public function get_session_id()
	{
		return $this->transient_id;
	}


	/**
	 * Generate random string
	*/
	private function generate_hash()
	{
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
		$max = strlen($chars) - 1;
		$password = '';

		for($i = 0; $i < 64; $i++)
		{
			$password .= substr($chars, mt_rand(0, $max), 1);
		}

		return $password;
	}


	/**
	 * Initialize cookie-session
	*/
	public function init_session()
	{
		setcookie('cde_transient_id', $this->transient_id, 0, COOKIEPATH, COOKIE_DOMAIN);
	}


	/**
	 * Load text domain
	*/
	public function load_textdomain()
	{
		load_plugin_textdomain('cdash-events', false, CDASH_EVENTS_REL_PATH.'languages/');
	}


	/**
	 * Print admin notices
	*/
	public function event_admin_notices()
	{
		global $pagenow;

		$screen = get_current_screen();
		$message_arr = get_transient($this->transient_id);

		if($screen->post_type === 'event' && $message_arr !== false)
		{
			if(($pagenow === 'post.php' && $screen->id === 'event') || $screen->id === 'event_page_events-settings')
			{
				$messages = maybe_unserialize($message_arr);

				echo '
				<div id="message" class="'.$messages['status'].'">
					<p>'.$messages['text'].'</p>
				</div>';
			}

			delete_transient($this->transient_id);
		}
	}


	/**
	 * Print admin notices
	*/
	public function display_notice($html = '', $status = 'error', $paragraph = false, $network = true)
	{
		$this->notices[] = array(
			'html' => $html,
			'status' => $status,
			'paragraph' => $paragraph
		);

		add_action('admin_notices', array(&$this, 'admin_display_notice'));

		if($network)
			add_action('network_admin_notices', array(&$this, 'admin_display_notice'));
	}


	/**
	 * Print admin notices
	*/
	public function admin_display_notice()
	{
		foreach($this->notices as $notice)
		{
			echo '
			<div class="cdash-events '.$notice['status'].'">
				'.($notice['paragraph'] ? '<p>' : '').'
				'.$notice['html'].'
				'.($notice['paragraph'] ? '</p>' : '').'
			</div>';
		}
	}


	/**
	 * Registration of new custom taxonomies: event-category, event-tag, event-location
	*/
	public function register_taxonomies()
	{
		$post_types = apply_filters('cde_event_post_type', array('event'));

		$labels_event_categories = array(
			'name' => _x('Event Categories', 'taxonomy general name', 'cdash-events'),
			'singular_name' => _x('Event Category', 'taxonomy singular name', 'cdash-events'),
			'search_items' =>  __('Search Event Categories', 'cdash-events'),
			'all_items' => __('All Event Categories', 'cdash-events'),
			'parent_item' => __('Parent Event Category', 'cdash-events'),
			'parent_item_colon' => __('Parent Event Category:', 'cdash-events'),
			'edit_item' => __('Edit Event Category', 'cdash-events'),
			'view_item' => __('View Event Category', 'cdash-events'),
			'update_item' => __('Update Event Category', 'cdash-events'),
			'add_new_item' => __('Add New Event Category', 'cdash-events'),
			'new_item_name' => __('New Event Category Name', 'cdash-events'),
			'menu_name' => __('Categories', 'cdash-events'),
		);

		$labels_event_locations = array(
			'name' => _x('Locations', 'taxonomy general name', 'cdash-events'),
			'singular_name' => _x('Event Location', 'taxonomy singular name', 'cdash-events'),
			'search_items' => __('Search Event Locations', 'cdash-events'),
			'all_items' => __('All Event Locations', 'cdash-events'),
			'parent_item' => __('Parent Event Location', 'cdash-events'),
			'parent_item_colon' => __('Parent Event Location:', 'cdash-events'),
			'edit_item' => __('Edit Event Location', 'cdash-events'), 
			'view_item' => __('View Event Location', 'cdash-events'),
			'update_item' => __('Update Event Location', 'cdash-events'),
			'add_new_item' => __('Add New Event Location', 'cdash-events'),
			'new_item_name' => __('New Event Location Name', 'cdash-events'),
			'menu_name' => __('Locations', 'cdash-events'),
		);

		$args_event_categories = array(
			'public' => true,
			'hierarchical' => true,
			'labels' => $labels_event_categories,
			'show_ui' => true,
			'show_admin_column' => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var' => true,
			'rewrite' => array(
				'slug' => $this->options['permalinks']['event_rewrite_base'].'/'.$this->options['permalinks']['event_categories_rewrite_slug'],
				'with_front' => false,
				'hierarchical' => true
			),
		);

		$args_event_locations = array(
			'public' => true,
			'hierarchical' => true,
			'labels' => $labels_event_locations,
			'show_ui' => true,
			'show_admin_column' => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var' => true,
			'rewrite' => array(
				'slug' => $this->options['permalinks']['event_rewrite_base'].'/'.$this->options['permalinks']['event_locations_rewrite_slug'],
				'with_front' => false,
				'hierarchical' => false
			),
		);

		register_taxonomy('event-category', apply_filters('cde_register_event_categories_for', $post_types), apply_filters('cde_register_event_categories', $args_event_categories));

		$labels_event_tags = array(
			'name' => _x('Event Tags', 'taxonomy general name', 'cdash-events'),
			'singular_name' => _x('Event Tag', 'taxonomy singular name', 'cdash-events'),
			'search_items' =>  __('Search Event Tags', 'cdash-events'),
			'popular_items' => __('Popular Event Tags', 'cdash-events'),
			'all_items' => __('All Event Tags', 'cdash-events'),
			'parent_item' => null,
			'parent_item_colon' => null,
			'edit_item' => __('Edit Event Tag', 'cdash-events'), 
			'update_item' => __('Update Event Tag', 'cdash-events'),
			'add_new_item' => __('Add New Event Tag', 'cdash-events'),
			'new_item_name' => __('New Event Tag Name', 'cdash-events'),
			'separate_items_with_commas' => __('Separate event tags with commas', 'cdash-events'),
			'add_or_remove_items' => __('Add or remove event tags', 'cdash-events'),
			'choose_from_most_used' => __('Choose from the most used event tags', 'cdash-events'),
			'menu_name' => __('Tags', 'cdash-events'),
		);

		$args_event_tags = array(
			'public' => true,
			'hierarchical' => false,
			'labels' => $labels_event_tags,
			'show_ui' => true,
			'show_admin_column' => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var' => true,
			'rewrite' => array(
				'slug' => $this->options['permalinks']['event_rewrite_base'].'/'.$this->options['permalinks']['event_tags_rewrite_slug'],
				'with_front' => false,
				'hierarchical' => false
			),
		);

		register_taxonomy('event-tag', apply_filters('cde_register_event_tags_for', $post_types), apply_filters('cde_register_event_tags', $args_event_tags));

		register_taxonomy('event-location', apply_filters('cde_register_event_locations_for', $post_types), apply_filters('cde_register_event_locations', $args_event_locations));

	}


	/**
	 * Registration of new register post types: event
	*/
	public function register_post_types()
	{
		$labels_event = array(
			'name' => _x('Events', 'post type general name', 'cdash-events'),
			'singular_name' => _x('Event', 'post type singular name', 'cdash-events'),
			'menu_name' => __('Events', 'cdash-events'),
			'all_items' => __('All Events', 'cdash-events'),
			'add_new' => __('Add New', 'cdash-events'),
			'add_new_item' => __('Add New Event', 'cdash-events'),
			'edit_item' => __('Edit Event', 'cdash-events'),
			'new_item' => __('New Event', 'cdash-events'),
			'view_item' => __('View Event', 'cdash-events'),
			'items_archive' => __('Event Archive', 'cdash-events'),
			'search_items' => __('Search Event', 'cdash-events'),
			'not_found' => __('No events found', 'cdash-events'),
			'not_found_in_trash' => __('No events found in trash', 'cdash-events'),
			'parent_item_colon' => ''
		);

		$taxonomies = array('event-category', 'event-location', 'event-tag');

		$args_event = array(
			'labels' => $labels_event,
			'description' => '',
			'public' => true,
			'exclude_from_search' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_admin_bar' => true,
			'show_in_nav_menus' => true,
			'menu_position' => 5,
			'menu_icon' => 'dashicons-calendar',
			'map_meta_cap' => true,
			'hierarchical' => false,
			'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'comments', 'revisions'),
			'rewrite' => array(
				'slug' => $this->options['permalinks']['event_rewrite_base'].'/'.$this->options['permalinks']['event_rewrite_slug'],
				'with_front' => false,
				'feeds'=> true,
				'pages'=> true
			),
			'has_archive' => $this->options['permalinks']['event_rewrite_base'],
			'query_var' => true,
			'can_export' => true,
			'taxonomies' => $taxonomies,
		);

		register_post_type('event', apply_filters('cde_register_event_post_type', $args_event));
	}


	/**
	 * Custom post type messages
	*/
	public function register_post_types_messages($messages)
	{
		global $post, $post_ID;

		$messages['event'] = array(
			0 => '', //Unused. Messages start at index 1.
			1 => sprintf(__('Event updated. <a href="%s">View event</a>', 'cdash-events'), esc_url(get_permalink($post_ID))),
			2 => __('Custom field updated.', 'cdash-events'),
			3 => __('Custom field deleted.', 'cdash-events'),
			4 => __('Event updated.', 'cdash-events'),
			//translators: %s: date and time of the revision
			5 => isset($_GET['revision']) ? sprintf(__('Event restored to revision from %s', 'cdash-events'), wp_post_revision_title((int)$_GET['revision'], false)) : false,
			6 => sprintf(__('Event published. <a href="%s">View event</a>', 'cdash-events'), esc_url(get_permalink($post_ID))),
			7 => __('Event saved.', 'cdash-events'),
			8 => sprintf(__('Event submitted. <a target="_blank" href="%s">Preview event</a>', 'cdash-events'), esc_url( add_query_arg('preview', 'true', get_permalink($post_ID)))),
			9 => sprintf(__('Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>', 'cdash-events'),
			//translators: Publish box date format, see http://php.net/date
			date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)), esc_url(get_permalink($post_ID))),
			10 => sprintf(__('Event draft updated. <a target="_blank" href="%s">Preview event</a>', 'cdash-events'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID))))
		);

		return $messages;
	}


	/**
	 * Enqueue admin scripts and style
	*/
	public function admin_scripts_styles($page)
	{
		$screen = get_current_screen();

		wp_register_style(
			'cdash-events-admin',
			CDASH_EVENTS_URL.'/css/admin.css'
		);

		wp_register_style(
			'cdash-events-wplike',
			CDASH_EVENTS_URL.'/css/wp-like-ui-theme.css'
		);

		if($page === 'edit-tags.php' && in_array($screen->post_type, apply_filters('cde_event_post_type', array('event'))))
		{
			// event location
			if(($screen->id === 'edit-event-location' && $screen->taxonomy === 'event-location') || ($screen->id === 'edit-event-category' && $screen->taxonomy === 'event-category'))
			{
				$timezone = explode('/', get_option('timezone_string'));
				
				if(!isset($timezone[1]))
					$timezone[1] = 'United Kingdom, London';
				
				wp_enqueue_media();
				wp_enqueue_style('wp-color-picker');

				wp_register_script(
					'cdash-events-edit-tags',
					CDASH_EVENTS_URL.'/js/admin-tags.js',
					array('jquery', 'wp-color-picker')
				);

				wp_enqueue_script('cdash-events-edit-tags');
				
				wp_register_script(
					'cdash-events-google-maps',
					'https://maps.googleapis.com/maps/api/js?sensor=false&language='.substr(get_locale(), 0, 2)
				);
				
				// on event locations only
				if ($screen->id === 'edit-event-location')
					wp_enqueue_script('cdash-events-google-maps');

				wp_localize_script(
					'cdash-events-edit-tags',
					'emArgs',
					array(
						'title' => __('Select image', 'cdash-events'),
						'button' => array('text' => __('Add image', 'cdash-events')),
						'frame' => 'select',
						'multiple' => false,
						'country' => $timezone[1]
					)
				);
				
				wp_enqueue_style('cdash-events-admin');
			}
		}
		// widgets
		elseif($page === 'widgets.php')
		{
			wp_register_script(
				'cdash-events-admin-widgets',
				CDASH_EVENTS_URL.'/js/admin-widgets.js',
				array('jquery')
			);

			wp_enqueue_script('cdash-events-admin-widgets');
			wp_enqueue_style('cdash-events-admin');
		}
		// event options page
		elseif($page === 'event_page_events-settings')
		{
			wp_register_script(
				'cdash-events-admin-settings',
				CDASH_EVENTS_URL.'/js/admin-settings.js',
				array('jquery')
			);

			wp_enqueue_script('cdash-events-admin-settings');

			wp_localize_script(
				'cdash-events-admin-settings',
				'emArgs',
				array(
					'resetToDefaults' => __('Are you sure you want to reset these settings to defaults?', 'cdash-events')
				)
			);

			wp_enqueue_style('cdash-events-admin');
		}
		// list of events
		elseif($page === 'edit.php' && in_array($screen->post_type, apply_filters('cde_event_post_type', array('event'))))
		{
			global $wp_locale;

			wp_register_script(
				'cdash-events-admin-edit',
				CDASH_EVENTS_URL.'/js/admin-edit.js',
				array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker')
			);

			wp_enqueue_script('cdash-events-admin-edit');

			wp_localize_script(
				'cdash-events-admin-edit',
				'emEditArgs',
				array(
					'firstWeekDay' => $this->options['general']['first_weekday'],
					'monthNames' => array_values($wp_locale->month),
					'monthNamesShort' => array_values($wp_locale->month_abbrev),
					'dayNames' => array_values($wp_locale->weekday),
					'dayNamesShort' => array_values($wp_locale->weekday_abbrev),
					'dayNamesMin' => array_values($wp_locale->weekday_initial),
					'isRTL' => $wp_locale->is_rtl()
				)
			);

			wp_enqueue_style('cdash-events-admin');
			wp_enqueue_style('cdash-events-wplike');
		}
		// update
		elseif($page === 'event_page_cdash-events-update')
			wp_enqueue_style('cdash-events-admin');
	}


	/**
	 * Enqueue frontend scripts and style
	*/
	public function front_scripts_styles()
	{
		wp_register_style(
			'cdash-events-front',
			CDASH_EVENTS_URL.'/css/front.css'
		);

		wp_enqueue_style('cdash-events-front');
		
		wp_register_script(
			'cdash-events-sorting',
			CDASH_EVENTS_URL.'/js/front-sorting.js',
			array('jquery')
		);

		wp_enqueue_script('cdash-events-sorting');
	}


	/**
	 * Edit screen icon
	*/
	public function edit_screen_icon()
	{
		// Screen icon
		global $wp_version;

		if($wp_version < 3.8)
		{
			global $post;

			$post_types = apply_filters('cde_event_post_type', array('event'));

			foreach($post_types as $post_type)
			{
				if(get_post_type($post) === $post_type || (isset($_GET['post_type']) && $_GET['post_type'] === $post_type))
				{
					echo '
					<style>
						#icon-edit { background: transparent url(\''.CDASH_EVENTS_URL.'/images/icon-events-32.png\') no-repeat; }
					</style>';
				}
			}
		}
	}


	/**
	 * Add links to Support Forum
	*/
	public function plugin_extend_links($links, $file) 
	{
		if(!current_user_can('install_plugins'))
			return $links;

		$plugin = plugin_basename(__FILE__);

		if($file == $plugin)
		{
			return array_merge(
				$links,
				array(sprintf('<a href="http://chamberdashboard.com/professional-services-support/" target="_blank">%s</a>', __('Support', 'cdash-events')))
			);
		}

		return $links;
	}

}
?>
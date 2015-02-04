<?php
/**
 * Pagination - Show numbered pagination for catalog pages.
 * 
 * Override this template by copying it to yourtheme/loop-event/pagination.php
 *
 * @author 	Digital Factory
 * @package Events Maker/Templates
 * @since 	1.2.0
 */
 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $wp_query;

if ($wp_query->max_num_pages <= 1)
	return;
?>

<nav class="navigation paging-navigation" role="navigation">
	
	<div class="loop-pagination pagination cdash-events-pagination">
		
		<?php
		$big = 999999999; // need an unlikely integer
		$args = array();
		
		$defaults = array(
			'base' 			=> str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
			'format' 		=> '?paged=%#%',
			'total'			=> $wp_query->max_num_pages,
			'current'		=> max(1, get_query_var('paged')),
			'show_all'		=> false,
			'end_size'		=> 1,
			'mid_size'		=> 2,
			'prev_next'		=> true,
			'prev_text'		=> __('&laquo; Previous', 'cdash-events'),
			'next_text'		=> __('Next &raquo;', 'cdash-events'),
			'type'			=> 'plain',
			'add_args'		=> False,
			'add_fragment'	=> ''
		);
		
		$args = apply_filters('cde_paginate_links_args', wp_parse_args($defaults, $args));
		
		echo paginate_links($args);
		?>
		
	</div>
	
</nav>
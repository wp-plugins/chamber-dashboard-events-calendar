<?php
/**
 * The template for single event meta.
 * 
 * Override this template by copying it to yourtheme/single-event/meta.php
 *
 * @author 	Digital Factory
 * @package Events Maker/Templates
 * @since 	1.2.0
 */
 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $post;

?>

<div class="entry-meta">
	
	<?php
	/**
	 * cde_single_event_meta_start hook
	 * 
	 * @hooked cde_display_single_event_date - 10
	 */
	do_action('cde_single_event_meta_start');
	?>
	
	<?php // comments link
	if (!post_password_required() && (comments_open() || get_comments_number())) : ?>
	
		<span class="comments-link"><?php comments_popup_link(__('Leave a comment', 'cdash-events' ), __('1 Comment', 'cdash-events'), __('% Comments', 'cdash-events')); ?></span>
	
	<?php endif; ?>
	
	<?php // edit link
	edit_post_link(__('Edit', 'cdash-events'), '<span class="edit-link">', '</span>'); ?>
	
	<?php
	/**
	 * cde_single_event_meta_end hook
	 */
	do_action('cde_single_event_meta_end');
	?>

</div>
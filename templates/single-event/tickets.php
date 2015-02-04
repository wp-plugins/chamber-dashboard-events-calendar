<?php
/**
 * The template for event tickets
 * 
 * Override this template by copying it to yourtheme/single-event/tickets.php
 *
 * @author 	Digital Factory
 * @package Events Maker/Templates
 * @since 	1.2.0
 */
 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $post;

// display options
$display_options = get_post_meta($post->ID, '_event_display_options', true); 

// tickets enabled?
if (!$display_options['price_tickets_info'])
	return;

?>

<div class="entry-meta entry-tickets">
	
	<?php
	/**
	 * cde_event_tickets_start hook
	 */
	do_action('cde_event_tickets_start');
	?>
			
   	<?php // tickets list 
   	$tickets = apply_filters('cde_single_event_tickets', cde_get_tickets($post->ID));
	
	if ($tickets) : ?>

   		<div class="event-tickets tickets">
   			
   			<span class="tickets-label"><strong><?php echo __('Tickets', 'cdash-events'); ?>: </strong></span>
   			
       		<?php foreach ($tickets as $ticket) : ?>
       			
       			<span class="event-ticket"><span class="ticket-name"><?php esc_html_e($ticket['name']); ?>: </span><span class="ticket-price"><?php esc_html_e(cde_get_currency_symbol($ticket['price'])); ?></span></span>
				
       		<?php endforeach; ?>
       		
   		</div>
   	
   	<?php else : ?>
   		
   		<div class="event-tickets tickets">
   			
   			<span class="tickets-label"><strong><?php echo _e('Tickets', 'cdash-events'); ?>: </strong></span>
   			
   			<span class="event-ticket"><span class="ticket-name"><?php _e('Free', 'cdash-events'); ?></span></span>
			
		</div>
	
	<?php endif; ?>
	
	<?php // tickets URL
	$tickets_url = apply_filters('cde_single_event_tickets_url', get_post_meta($post->ID, '_event_tickets_url', true));
	
	if ($tickets_url) : ?>
	
		<div class="event-tickets-url tickets">
			
			<span class="tickets-url-label"><strong><?php _e('Buy tickets URL', 'cdash-events'); ?>: </strong></span>
			
			<a href="<?php echo esc_url($tickets_url); ?>" class="tickets-url-link" rel="nofollow" target="_blank"><?php echo esc_url($tickets_url); ?></a>
			
		</div>
		
	<?php endif; ?>
	
	<?php
	/**
	 * cde_event_tickets_end hook
	 */
	do_action('cde_event_tickets_end');
	?>
	
</div>
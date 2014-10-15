<?php
/**
 * The template for displaying event content in the single-event.php template
 *
 * Override this template by copying it to yourtheme/content-single-event.php
 *
 * @author 	Digital Factory
 * @package Events Maker/Templates
 * @since 	1.1.0
 */
 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Extra event classes
$classes = apply_filters('cde_loop_event_classes', array('hcalendar'));

?>

	<article id="post-<?php the_ID(); ?>" <?php post_class($classes); ?>>
		
		<?php
		/**
		 * cde_before_single_event hook
		 * 
		 * @hooked cde_display_single_event_thumbnail - 10
		 */
		do_action('cde_before_single_event');
		?>
	
	    <header class="entry-header">
	    	
	    	<?php
			/**
			 * cde_before_single_event_title hook
			 * 
			 * @hooked cde_display_event_categories - 10
			 */
			do_action ('cde_before_single_event_title');
			?>
			
	        <h1 class="entry-title summary">
	        	
	        	<?php the_title(); ?>
	        	
	        </h1>
	        
	        <?php
			/**
			 * cde_after_single_event_title hook
			 * 
			 * @hooked cde_display_single_event_meta - 10
			 * @hooked cde_display_event_locations - 20
			 * @hooked cde_display_google_map - 40
			 * @hooked cde_display_event_tickets - 50
			 */
			do_action ('cde_after_single_event_title');
			?>

	    </header>
	
	    <div class="entry-content description">
	    	
	        <?php the_content(); ?>
	        
	    </div>
	    
	    <?php
		/**
		 * cde_after_single_event hook
		 * 
		 * @hooked cde_display_event_tags - 10
		 */
		do_action('cde_after_single_event');
		?>

	</article>
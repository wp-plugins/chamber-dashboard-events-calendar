<?php
/**
 * The template for displaying event widget content
 *
 * Override this template by copying it to yourtheme/content-widget-event.php
 *
 * @author 	Digital Factory
 * @package Events Maker/Templates
 * @since 	1.2.0
 */
 
if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $post;

if ($args && is_array($args))
	extract($args);

// get events args and post object sent via cde_get_template()
if (!$args)
	return;
else
	$post = apply_filters('cde_widget_event_post', $args[0]); // event post object
	$args = apply_filters('cde_widget_event_args', $args[1]); // widget or function args

// extra event classes
$classes = apply_filters('cde_widget_event_classes', array('hcalendar'));

?>

	<li id="post-<?php the_ID(); ?>" <?php post_class($classes); ?>>
		
		<?php
		/**
		 * cde_before_widget_event hook
		 */
		do_action('cde_before_widget_event');
		?>
		
		<?php // event thumbnail
		if (apply_filters('cde_show_widget_event_thumbnail', $args['show_event_thumbnail']) == true && has_post_thumbnail()) : ?>
		
			<?php
			$image_title = apply_filters('cde_widget_event_thumbnail_title', get_the_title());
			$image_link  = apply_filters('cde_widget_event_thumbnail_link', get_permalink());
			$size = apply_filters('cde_widget_event_thumbnail_size', $args['thumbnail_size']);
			$attr = apply_filters('cde_widget_event_thumbnail_attr', array('title' => $image_title));
			$image = get_the_post_thumbnail($post->ID, $size, $attr);

			echo apply_filters('cde_widget_event_thumbnail_html', sprintf('<a href="%s" class="thumbnail event-thumbnail" title="%s">%s</a>', $image_link, $image_title, $image), $post->ID);
			
		endif;
		?>
	    	
    	<?php
		/**
		 * cde_before_widget_event_title hook
		 * 
		 * @hooked cde_display_widget_event_date - 10
		 */
		do_action ('cde_before_widget_event_title');
		?>
		
		<?php // event title
		if (apply_filters('cde_show_widget_event_title', true)) : ?>
			
			<div class="event-link">
				
				<a class="event-title" href="<?php echo get_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
				
			</div>
			
		<?php endif; ?>

        <?php
		/**
		 * cde_after_widget_event_title hook
		 */
		do_action ('cde_after_widget_event_title');
		?>
		
		<?php // event excerpt
		if (apply_filters('cde_show_widget_event_excerpt', $args['show_event_excerpt']) == true) : ?>
		
			<div class="event-excerpt">
				
				<?php the_excerpt(); ?>
			
			</div>
			
		<?php endif; ?>
	    
	    <?php
		/**
		 * cde_after_widget_event hook
		 */
		do_action('cde_after_widget_event');
		?>

	</li>
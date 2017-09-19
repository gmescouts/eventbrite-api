<?php
/**
 * The template for displaying all Eventbrite events (index), and archives (sorted by organizer or venue).
 */

function get_venue_address($venue) {
    $address[] = $venue->name;
    if ($venue->address->address_1) $address[] = $venue->address->address_1;
    if ($venue->address->address_2) $address[] = $venue->address->address_2;
    if ($venue->address->city) $address[] = $venue->address->city;
    if ($venue->address->region) $address[] = $venue->address->region;
    if ($venue->address->postal_code) $address[] = $venue->address->postal_code;
     return implode(", ", $address);
    // return sprintf('<a href="%s">%s</a>', $venue->resource_uri, implode(", ", $address));
}

get_header(); ?>

<div class="container">
    <div class="wrapper">
        <div class="main_content full_width">
            <h2><?php the_title(); ?></h2>
            <?php echo the_content(); ?>

            <?php
            // Set up and call our Eventbrite query.
            $events = new Eventbrite_Query( apply_filters( 'eventbrite_query_args', array(
                // 'display_private' => false, // boolean
                // 'nopaging' => false,        // boolean
                // 'limit' => null,            // integer
                'organizer_id' =>  get_post_meta($post->ID, 'organiser_id', true),     // only events for the GME Training Team
                // 'p' => null,                // integer
                // 'post__not_in' => null,     // array of integers
                // 'venue_id' => null,         // integer
                // 'category_id' => null,      // integer
                // 'subcategory_id' => null,   // integer
                // 'format_id' => null,        // integer
            ) ) );

            if ( $events->have_posts() ) :
                while ( $events->have_posts() ) : $events->the_post(); ?>

                    <article id="event-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <div class="entry-header">
                            <?php the_title( sprintf( '<a href="%s" rel="bookmark"><h2 class="entry-title">', esc_url( get_post()->url ) ), '</h2></a>' ); ?>
                        </div><!-- .entry-header -->

                        <div class="entry-body">
                            <?php eventbrite_ticket_form_widget(); ?>
                            <h3><?php echo trim(eventbrite_event_time()); ?></h3>
                            <h3><?php echo get_venue_address(eventbrite_event_venue()); ?></h3><!-- .entry-meta -->
                            <div class="event-content">
                                <?php the_content(); ?>
                            </div>
                        </div><!-- .entry-content -->

                        <div class="entry-meta">
                            <a href="<?php echo esc_url( get_post()->url );?>">View in Eventbrite</a>&nbsp
                            <?php eventbrite_edit_post_link( __( 'Edit in Eventbrite', 'eventbrite_api' ), '<span class="edit-link">', '</span>' ); ?>
                        </div><!-- .entry-meta -->

                    </article><!-- #post-## -->

                <?php endwhile;

                // Previous/next post navigation.
                eventbrite_paging_nav( $events );

            else :
                // If no content, include the "No posts found" template.
                get_template_part( 'content', 'none' );

            endif;

            // Return $post to its rightful owner.
            wp_reset_postdata();
            ?>

        </div><!-- .main_content -->
    </div><!-- #wrapper -->
</div><!-- #container -->

<?php get_footer(); ?>

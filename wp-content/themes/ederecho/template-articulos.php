<?php
/* Template Name: Artículos */

get_header();
global $wp_query;
global $paged; ?>
<div class="clear"></div>

</header> <!-- / END HOME SECTION  -->
<?php zerif_after_header_trigger(); ?>
<div id="content" class="site-content">

	<div class="container">

		<div class="content-left-wrap col-md-9">

			<div id="primary" class="content-area">

				<main id="main" class="site-main" itemscope itemtype="http://schema.org/Blog">
					<?php
					// Define custom query parameters
					$zerif_posts_per_page = ( get_option( 'posts_per_page' ) ) ? get_option( 'posts_per_page' ) : '6';
					$zerif_custom_query_args = array(
						/* Parameters go here */
						'post_type' => 'post',
						'posts_per_page' => $zerif_posts_per_page,
						'post_status' => array('publish', 'private'),
					);

					// Get current page and append to custom query parameters array
					$zerif_custom_query_args['paged'] = ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? get_query_var( 'page' ) : 1) );
					$paged = $zerif_custom_query_args['paged'];

					// Instantiate custom query
					$zerif_custom_query = new WP_Query( apply_filters( 'zerif_template_blog_parameters', $zerif_custom_query_args ) );

					// Pagination fix
					$zerif_temp_query = $wp_query;
					$wp_query   = null;
					$wp_query   = $zerif_custom_query;

					// Output custom query loop
					if ( $zerif_custom_query->have_posts() ) :
						while ( $zerif_custom_query->have_posts() ) :
							$zerif_custom_query->the_post();
							// Loop output goes here
							//get_template_part( 'content', get_post_format() );
                                                        $post_thumbnail_url = get_the_post_thumbnail( get_the_ID(), array(220,160) );
                                                        ?>
                                    
                                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?> itemtype="http://schema.org/BlogPosting" itemtype="http://schema.org/BlogPosting">

                                        <?php if ( ! empty( $post_thumbnail_url ) ) : ?>

                                                <div class="post-img-wrap">

                                                        <?php if ( has_the_subscription( $current_user->ID, strtotime( date('j-n-Y') ) ) ) : ?>

                                                                <a href="<?php echo esc_url( get_permalink() ) ?>" title="<?php echo the_title_attribute( 'echo=0' ) ?>" >

                                                        <?php endif; ?>

                                                        <?php echo $post_thumbnail_url; ?>

                                                        <?php if ( has_the_subscription( $current_user->ID, strtotime( date('j-n-Y') ) ) ) : ?>

                                                                </a>

                                                        <?php endif; ?>
                                                </div>

                                                <div class="listpost-content-wrap">

                                        <?php else : ?>

                                                <div class="listpost-content-wrap-full">

                                        <?php endif; ?>

                                                        <div class="list-post-top">

                                                                <header class="entry-header">

                                                                        <?php if ( has_the_subscription( $current_user->ID, strtotime( date('j-n-Y') ) ) || $post->post_status=='publish' ) : ?>

                                                                                <h1 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h1>

                                                                        <?php else : ?>

                                                                                <h1 class="entry-title"><?php the_title(); ?></h1>

                                                                        <?php endif; ?>

                                                                        <div class="entry-meta">

                                                                                <?php zerif_posted_on(); ?>

                                                                        </div><!-- .entry-meta -->

                                                                </header><!-- .entry-header -->

                                                                <div class="entry-content">

                                                                        <?php

                                                                                $ismore = ! empty( $post->post_content ) ? strpos( $post->post_content, '<!--more-->' ) : '';

                                                                                if ( ! empty( $ismore ) ) {
                                                                                        the_content( sprintf( esc_html__( '[&hellip;]', 'zerif-lite' ), '<span class="screen-reader-text">' . esc_html__( 'about ', 'zerif-lite' ) . get_the_title() . '</span>' ) );
                                                                                } else {
                                                                                        the_excerpt();
                                                                                }

                                                                                wp_link_pages(
                                                                                        array(
                                                                                                'before' => '<div class="page-links">' . __( 'Pages:', 'zerif-lite' ),
                                                                                                'after' => '</div>',
                                                                                        )
                                                                                );
                                                                        ?>
                                                                    
                                                                        <?php if ( ! ( has_the_subscription( $current_user->ID, strtotime( date('j-n-Y') ) ) || $post->post_status=='publish' ) ) : ?>
                                                                        <div class="art_pago">
                                                                            <a href="<?php echo get_permalink( get_page_by_path( 'registro' ) ) ?>" >
                                                                                <h4>Artículo exclusivo para suscriptores</h4>
                                                                                <p>Para seguir leyendo debes estar suscripto</p>
                                                                            </a>
                                                                        </div>
                                                                        <?php endif; ?>

                                                                        <footer class="entry-footer">

                                                                                <?php
                                                                                if ( 'post' == get_post_type() ) { // Hide category and tag text for pages on Search

                                                                                        /* translators: used between list items, there is a space after the comma */
                                                                                        $categories_list = get_the_category_list( __( ', ', 'zerif-lite' ) );

                                                                                        if ( $categories_list && zerif_categorized_blog() ) {

                                                                                                echo '<span class="cat-links">';

                                                                                                /* Translators: Categories list */
                                                                                                printf( __( 'Posted in %1$s', 'zerif-lite' ), $categories_list );

                                                                                                echo '</span>';

                                                                                        } // End if categories

                                                                                        /* translators: used between list items, there is a space after the comma */

                                                                                        $tags_list = get_the_tag_list( '', __( ', ', 'zerif-lite' ) );

                                                                                        if ( $tags_list ) {

                                                                                                echo '<span class="tags-links">';

                                                                                                /* translators: Tags list */
                                                                                                printf( __( 'Tagged %1$s', 'zerif-lite' ), $tags_list );

                                                                                                echo '</span>';

                                                                                        }
                                                                                }

                                                                                if ( ! post_password_required() && ( comments_open() || '0' != get_comments_number() ) ) {

                                                                                        echo '<span class="comments-link">';
                                                                                                comments_popup_link( __( 'Leave a comment', 'zerif-lite' ), __( '1 Comment', 'zerif-lite' ), __( '% Comments', 'zerif-lite' ) );
                                                                                        echo '</span>';

                                                                                }

                                                                                edit_post_link( __( 'Edit', 'zerif-lite' ), '<span class="edit-link">', '</span>' );
                                                                                ?>

                                                                        </footer><!-- .entry-footer -->

                                                                </div><!-- .entry-content --><!-- .entry-summary -->

                                                        </div><!-- .list-post-top -->

                                                </div><!-- .listpost-content-wrap -->

                                </article><!-- #post-## -->
                                                        <?php
						endwhile;
					else :
						get_template_part( 'content', 'none' );
					endif;
					// Reset postdata
					wp_reset_postdata();

					echo get_the_posts_navigation(
						array(
							/* translators: Newer posts navigation arrow */
							'next_text' => sprintf( __( 'Newer posts %s','zerif-lite' ), '<span class="meta-nav">&rarr;</span>' ),
							/* translators: Older posts navigation arrow */
							'prev_text' => sprintf( __( '%s Older posts', 'zerif-lite' ) , '<span class="meta-nav">&larr;</span>' ),
						)
					);


					// Reset main query object
					$wp_query = null;
					$wp_query = $zerif_temp_query;
					?>
				</main><!-- #main -->

			</div><!-- #primary -->

		</div><!-- .content-left-wrap -->

		<?php zerif_sidebar_trigger(); ?>

	</div><!-- .container -->
<?php get_footer(); ?>

<?php
/* Template Name: Registro confirmado */
get_header(); ?>

<div class="clear"></div>

</header> <!-- / END HOME SECTION  -->

<?php
	zerif_after_header_trigger();
	$zerif_change_to_full_width = get_theme_mod( 'zerif_change_to_full_width' );
        
        $user = filter_input( INPUT_GET, 'user' );

?>

<div id="content" class="site-content">

	<div class="container">

		<?php zerif_before_page_content_trigger(); ?>
            
                <div class="content-left-wrap col-md-9">
		
		<?php zerif_top_page_content_trigger(); ?>
                    
		<div id="primary" class="content-area">

			<main itemscope itemtype="http://schema.org/WebPageElement" itemprop="mainContentOfPage" id="main" class="site-main">

				<?php
				while ( have_posts() ) :
					the_post();

                                        get_template_part( 'content', 'page' );

					endwhile;
				?>

			</main><!-- #main -->

		</div><!-- #primary -->
        </div>
	
	
            <?php 
                zerif_after_page_content_trigger();
		zerif_sidebar_trigger();
            ?>
		
	</div><!-- .container -->

<?php get_footer(); ?>

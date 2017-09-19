<?php

class ed_banner_suscripciones extends WP_Widget {
    function __construct() {
        parent::__construct(
                'ed_banner_suscripciones', 
                'Banner Suscripciones', 
                array( 'description' => 'Forumulario suscripciones' )
        );
    }
    function widget($args, $instance) {
        extract( $args );
        
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
        $url_banner = isset($instance['url_banner']) ? esc_attr($instance['url_banner']) : '';
        $pagina = isset($instance['pagina']) ? esc_attr($instance['pagina']) : '';
        $texto = isset($instance['texto']) ? esc_attr($instance['texto']) : '';
        $precio = isset($instance['precio']) ? esc_attr($instance['precio']) : '';
        $boton = isset($instance['boton']) ? $instance['boton'] : '';
        $user_id = get_current_user_id();
        
        echo $before_widget;
        ?>
            <div class="widget">
                <?php if ( is_user_logged_in() ) : ?>
                        <?php if ( has_the_subscription( $user_id, strtotime( date('j-n-Y') ) ) ) : ?> 
                                <?php $fecha_fin_susc = date_i18n( 'j \d\e F, Y', get_user_meta( $user_id, 'subscription2', true )); ?>
                                <div class="banner_susc">
                                        <h4>Felicitaciones, ya esta suscripto</h4>
                                        <p>Su suscripción finaliza el: <br /><?php echo $fecha_fin_susc; ?></p>
                                </div>
                        <?php else : ?>
                                <div class="banner_susc">
                                        <?php if (!empty($title)) { echo $before_title . esc_attr($title) . $after_title; } ?>
                                        <h4><?php echo $texto ?></h4>
                                        <h2><?php echo $precio ?></h2>
                                        <?php echo $boton ?>
                                </div>
                        <?php endif; ?>
                <?php else : ?>
                        <a href="<?php echo get_page_link( $pagina ); ?>">
                                <img src="<?php echo esc_url( home_url( $url_banner ) ); ?>" />
                        </a>
                <?php endif; ?>
                                        
            </div>
        <?php
        echo $after_widget;
    }
    function update($new_instance, $old_instance) {
        parent::update($new_instance, $old_instance);
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['url_banner'] = strip_tags($new_instance['url_banner']);
        $instance['pagina'] = strip_tags($new_instance['pagina']);
        $instance['texto'] = strip_tags($new_instance['texto']);
        $instance['precio'] = strip_tags($new_instance['precio']);
        $instance['boton'] = $new_instance['boton'];
		
        return $instance;
    }
    function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        $url_banner = isset($instance['url_banner']) ? esc_attr($instance['url_banner']) : '';
        $pagina = isset($instance['pagina']) ? esc_attr($instance['pagina']) : '';
        $texto = isset($instance['texto']) ? esc_attr($instance['texto']) : '';
        $precio = isset($instance['precio']) ? esc_attr($instance['precio']) : '';
        $boton = isset($instance['boton']) ? $instance['boton'] : '';
        ?>
            <div class="widget-content">
                <p>
                        <label for="<?php echo $this->get_field_id('title'); ?>">Título</label>
                        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
                </p>
                <p>
                        <label for="<?php echo $this->get_field_id('url_banner'); ?>">Url del Banner</label>
                        <input class="widefat" id="<?php echo $this->get_field_id('url_banner'); ?>" name="<?php echo $this->get_field_name('url_banner'); ?>" type="text" value="<?php echo esc_attr($url_banner); ?>" />
                </p>
                <p>
                        <label for="pagina">Página del formulario de suscripción</label>
                        <select class="widefat" name="<?php echo $this->get_field_name('pagina'); ?>" id="<?php echo $this->get_field_id('pagina'); ?>">
                                <?php
                                if( $pages = get_pages() ){
                                    foreach( $pages as $page ){
                                        echo '<option value="' . $page->ID . '" ' . selected( $page->ID, $pagina ) . '>' . $page->post_title . '</option>';
                                    }
                                }
                                ?>
                        </select>
                </p>
                <p>
                        <label for="<?php echo $this->get_field_id('texto'); ?>">Texto de costo</label>
                        <input class="widefat" id="<?php echo $this->get_field_id('texto'); ?>" name="<?php echo $this->get_field_name('texto'); ?>" type="text" value="<?php echo esc_attr($texto); ?>" />
                </p>
                <p>
                        <label for="<?php echo $this->get_field_id('precio'); ?>">Precio de la suscripción</label>
                        <input class="widefat" id="<?php echo $this->get_field_id('precio'); ?>" name="<?php echo $this->get_field_name('precio'); ?>" type="text" value="<?php echo esc_attr($precio); ?>" />
                </p>
                <p>
                        <label for="<?php echo $this->get_field_id('boton'); ?>">Código del boton</label>
                        <textarea class="widefat" rows="16" cols="20"  name="<?php echo $this->get_field_name('boton'); ?>" id="<?php echo $this->get_field_id('boton'); ?>"><?php if (!empty($boton)) echo $boton; ?></textarea>
                </p>
            </div>
        <?php
    }
}
?>
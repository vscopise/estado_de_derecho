<?php

class ed_font_accesibility extends WP_Widget {
    function __construct () {
        parent::__construct (
                'ed_font_accesibility', 
                'Tamaño de letra',
                array( 'description' => 'Permite cambiar el tamaño de letra del texto', ) 
        );
    }
    function widget ( $args, $instance ) {
        extract( $args );
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
        wp_enqueue_script( 'ed_font_accesibility', get_stylesheet_directory_uri() . '/js/ed-font-accesibility.js', array('jquery'), false ,true );
        
        echo $before_widget;
        if (!empty($title)) { echo $before_title . esc_attr($title) . $after_title; }
        ?>
                <div class="font_accesibility">
                    <button class="btn-decrease">A-</button>
                    <button class="btn-orig">A</button>
                    <button class="btn-increase">A+</button>
                </div>
        <?php
        echo $after_widget;
    }
    function update ( $new_instance, $old_instance ) {
        parent::update($new_instance, $old_instance);
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
		
        return $instance;
    }
    function form ( $instance ) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        ?>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>">Título</label>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            </p>
            <p>Este widget habilita al lector a cambiar el tamaño de letra.</p>
        <?php
    }
}
?>
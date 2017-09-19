<?php
/**
 * dFlip CUSTOM POST
 *
 * Initializes and Registers the required custom post for dFlip
 *
 * @since 1.0.0
 *
 * @package dFlip
 * @author  Deepak Ghimire
 */

class DFlip_Post_Type{

    /**
     * Holds the singleton class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Holds the base DFlip class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $base;

    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {

        // Load the base class object.
        $this->base = DFlip::get_instance();

        $labels = array(
            'name' => __DFLIP('dFlip Book'),
            'singular_name' => __DFLIP('dFlip Book'),
            'menu_name' => __DFLIP('dFlip Books'),
            'name_admin_bar' => __DFLIP('dFlip Book'),
            'add_new' => __DFLIP('Add New Book'),
            'add_new_item' => __DFLIP('Add New Book'),
            'new_item' => __DFLIP('New dFlip Book'),
            'edit_item' => __DFLIP('Edit dFlip Book'),
            'view_item' => __DFLIP('View dFlip Book'),
            'all_items' => __DFLIP('All Books'),
            'search_items' => __DFLIP('Search dFlip Books'),
            'parent_item_colon' => __DFLIP('Parent dFlip Books:'),
            'not_found' => __DFLIP('No dFlip-Books found.'),
            'not_found_in_trash' => __DFLIP('No dFlip Books found in Trash.')
        );

        $args = array(
            'labels' => $labels,
            'description' => __DFLIP('Description.'),
            'public' => false,  //this removes the permalink option
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => false, //array('slug' => $this->base->slug),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-book',
            'supports' => array('title')
        );

        register_post_type('dflip', $args);

        if ( is_admin() ) {
            $this->init_admin();
        }
    }

    /**
     * Loads all admin related files into scope.
     *
     * @since 1.0.0
     */
    public function init_admin() {

        // Remove quick editing from the dFlip post type row actions.
        add_filter('post_row_actions',array( $this, 'remove_quick_edit'),10,1);

        // Manage post type columns.
        add_filter('manage_dflip_posts_columns', array( $this, 'dflip_columns'));
        add_action('manage_dflip_posts_custom_column', array( $this, 'dflip_columns_content') , 10, 2);

        //Optimize the icons for retina display
        add_action( 'admin_head', array( $this, 'menu_icon' ) );

    }


    /**
     * Filter out unnecessary row actions dFlip post table.
     *
     * @since 1.0.0
     *
     * @param array $actions  Default row actions.
     * @return array $actions Amended row actions.
     */
    public function remove_quick_edit( $actions ) {
        if ( isset( get_current_screen()->post_type ) && 'dflip' == get_current_screen()->post_type ) {
            unset($actions['inline hide-if-no-js']);
        }
        return $actions;
    }

    /**
     * Customize the post columns for the dFlip post type.
     *
     * @since 1.0.0
     *
     * @return array $columns New Updated columns.
     */
    public function dflip_columns()
    {

        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __DFLIP('Title'),
            'shortcode' => __DFLIP('Shortcode'),
            'modified' => __DFLIP('Last Modified'),
            'date' => __DFLIP('Date')
        );

        return $columns;
    }

    /**
     * Add data to the custom columns added to the dFlip post type.
     *
     * @since 1.0.0
     *
     * @param string $column_name Name of the custom column.
     * @param int $post_id Current post ID.
     */
    public function dflip_columns_content($column_name, $post_id)
    {
        $post_id = absint($post_id);

        switch ($column_name) {
            case 'shortcode':
                echo '<code>[dflip id="' . $post_id . '"][/dflip]</code>';
                break;

            case 'modified' :
                the_modified_date();
                break;
        }
    }

    /**
     * Forces the dFlip menu icon width/height for Retina devices.
     *
     * @since 1.0.0
     */
    public function menu_icon() {

        ?>
        <style type="text/css">#menu-posts-dflip .wp-menu-image img { width: 16px; height: 16px; }</style>
    <?php

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object DFlip_Post_Type object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DFlip_Post_Type ) ) {
            self::$instance = new DFlip_Post_Type();
        }

        return self::$instance;

    }
}

// Load the post-type class.
$dflip_post_type = DFlip_Post_Type::get_instance();


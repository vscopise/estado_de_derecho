<?php

/**
 * dFlip Metaboxes
 *
 * creates, displays and saves metaboxes and their values
 *
 * @since   1.0.0
 *
 * @package dFlip
 * @author  Deepak Ghimire
 */
class DFlip_Meta_boxes {

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
	 * Holds the base DFlip class fields.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public $fields;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Load the base class object.
		$this->base = DFlip::get_instance();

		$this->fields = $this->base->defaults;

		// Load metabox assets.
		add_action(
			'admin_enqueue_scripts', array( $this, 'meta_box_styles_scripts' )
		);

		// Load the metabox hooks and filters.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 100 );

		// Add action to save metabox config options.
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );
	}

	/**
	 * Loads styles and scripts for our metaboxes.
	 *
	 * @since 1.0.0
	 *
	 * @return null Bail out if not on the proper screen.
	 */
	public function meta_box_styles_scripts() {

		global $id, $post;

		if ( isset( get_current_screen()->base ) && 'post' !== get_current_screen()->base ) {
			return;
		}
		if ( isset( get_current_screen()->post_type )
		     && $this->base->plugin_slug !== get_current_screen()->post_type
		) {
			return;
		}

		// Set the post_id for localization.
		$post_id = isset( $post->ID ) ? $post->ID : (int) $id;

		// Load necessary metabox styles.
		wp_register_style(
			$this->base->plugin_slug . '-metabox-style',
			plugins_url( 'assets/css/metaboxes.css', $this->base->file ),
			array(),
			$this->base->version
		);
		wp_enqueue_style( $this->base->plugin_slug . '-metabox-style' );

		// Load necessary metabox scripts.
		wp_register_script(
			$this->base->plugin_slug . '-metabox-script',
			plugins_url( 'assets/js/metaboxes.js', $this->base->file ),
			array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-resizable' ),
			$this->base->version
		);
		wp_enqueue_script( $this->base->plugin_slug . '-metabox-script' );

		wp_enqueue_media( array( 'post' => $post_id ) );

	}

	/**
	 * Adds metaboxes for handling settings
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {

		add_meta_box(
			'dflip_post_meta_box', __DFLIP( 'dFlip Settings' ),
			array( $this, 'create_meta_boxes' ), 'dflip', 'normal', 'high'
		);

		add_meta_box(
			'dflip_post_meta_box_shortcode', __DFLIP( 'Shortcode' ),
			array( $this, 'create_meta_boxes_shortcode' ), 'dflip', 'side', 'high'
		);

	}

	/**
	 * Creates metaboxes for shortcode display
	 *
	 * @since 1.2.4
	 *
	 * @param object $post The current post object.
	 */
	public function create_meta_boxes_shortcode( $post ) {
		global $current_screen;

		if ( $current_screen->post_type == 'dflip' ) {
			if ( $current_screen->action == 'add' ) {
				echo "Save Post to generate shortcode.";
			}
			else {
				echo '<code>[dflip id="' . $post->ID . '"][/dflip]</code>';
			}
		}

	}


	/**
	 * Creates metaboxes for handling settings
	 *
	 * @since 1.0.0
	 *
	 * @param object $post The current post object.
	 */
	public function create_meta_boxes( $post ) {

		// Keep security first.
		wp_nonce_field( $this->base->plugin_slug, $this->base->plugin_slug );

		$tabs = array(
			'source'  => __DFLIP( 'Source' ),
			'layout'  => __DFLIP( 'Layout' ),
			'outline' => __DFLIP( 'Outline' )
		);

		//create tabs and content
		?>
		<div id="dflip-tabs" class="">
			<ul id="dflip-tabs-list" class="">
				<?php
				//create tabs
				$active_set = false;
				foreach ( (array) $tabs as $id => $title ) {
					?>
					<li class="<?php echo( $active_set == false ? 'dflip-active' : '' ) ?>">
						<a href="#dflip-tab-<?php echo $id ?>"><?php echo $title ?></a></li>
					<?php $active_set = true;
				}
				?>
			</ul>
			<?php

			$active_set = false;
			foreach ( (array) $tabs as $id => $title ) {
				?>
				<div id="dflip-tab-<?php echo $id ?>"
				     class="dflip-tabs <?php echo( $active_set == false ? "dflip-active" : "" ) ?>">

					<?php
					$active_set = true;

					//create content for tab
					$function = $id . "_tab";
					call_user_func( array( $this, $function ), $post );

					?>
				</div>
			<?php } ?>
		</div>
	<?php

	}

	/**
	 * Creates the UI for Source tab
	 *
	 * @since 1.0.0
	 *
	 * @param object $post The current post object.
	 */
	public function source_tab( $post ) {

		$this->create_normal_setting( 'source_type',$post);
		$this->create_normal_setting( 'pdf_source',$post);
		$this->create_normal_setting( 'pdf_thumb',$post);

		?>

		<!--Pages for the book-->
		<div id="dflip_pages_box" class="dflip-box " data-condition="dflip_source_type:is(image)" data-operator="and">

			<label for="dflip_pages" class="dflip-label">
				<?php echo __DFLIP( 'Custom Pages' ); ?>
			</label>

			<div class="dflip-desc">
				<?php echo __DFLIP(
					'Add or remove pages as per your requirement. Plus reorder them in the order needed.'
				); ?>
			</div>
			<div class="dflip-option dflip-page-list">
				<a href="javascript:void(0);" class="dflip-page-list-add button button-primary"
				   title="Add New Page">
					<?php echo __DFLIP( 'Add New Page' ); ?>
				</a>
				<ul id="dflip_page_list">
					<?php
					$page_list = $this->get_config( 'pages', $post );
					$index     = 0;
					foreach ( (array) $page_list as $page ) {

						/* build the arguments*/
						$title   = isset( $page['title'] ) ? $page['title'] : '';
						$url     = isset( $page['url'] ) ? $page['url'] : '';
						$content = isset( $page['content'] ) ? $page['content'] : '';

						if ( $url != '' ) {
							?>
							<li class="dflip-page-item">
								<img class="dflip-page-thumb" src="<?php echo $url; ?>" alt=""/>

								<div class="dflip-page-options">

									<label for="dflip-page-<?php echo $index; ?>-title">
										<?php echo __DFLIP( 'Title' ); ?>
									</label>
									<input type="text"
									       name="_dflip[pages][<?php echo $index; ?>][url]"
									       id="dflip-page-<?php echo $index; ?>-url"
									       value="<?php echo $url; ?>"
									       class="widefat">

									<label for="dflip-page-<?php echo $index; ?>-content">
										<?php echo __DFLIP( 'Content' ); ?>
									</label>
									<textarea rows="10" cols="40"
									          name="_dflip[pages][<?php echo $index; ?>][content]"
									          id="dflip-page-<?php echo $index; ?>-content">
										<?php echo esc_textarea( $content ); ?>
									</textarea>
									<?php
									if ( isset( $page['hotspots'] ) ) {
										$spotindex = 0;
										foreach (
											(array) $page['hotspots'] as $spot
										) {
											?>
											<input class="dflip-hotspot-input"
											       name="_dflip[pages][<?php echo $index; ?>][hotspots][<?php echo $spotindex; ?>]"
											       value="<?php echo $spot; ?>">
											<?php
											$spotindex ++;
										}
									}
									?>
								</div>
							</li>
						<?php
						}
						$index ++;
					} ?>
				</ul>
			</div>
		</div>

		<!--Clear-fix-->
		<div class="dflip-box"></div>

	<?php

	}

	/**
	 * Sanitizes an array value even if not existent
	 *
	 * @since 1.0.0
	 *
	 * @param object $arr     The array to lookup
	 * @param mixed  $key     The key to look into array
	 * @param mixed  $default Default value in-case value is not found in array
	 *
	 * @return mixed appropriate value if exists else default value
	 */
	private function val( $arr, $key, $default = '' ) {
		return isset( $arr[ $key ] )
			? $arr[ $key ] : $default;
	}

	private function create_global_setting($key,$post,$global_key){
		$this->base->create_setting( $key,null,$this->get_config( $key, $post, $global_key ),$global_key,$this->global_config( $key ) );

	}
	private function create_normal_setting($key,$post){
		$this->base->create_setting( $key,null,$this->get_config( $key, $post ));

	}

	/**
	 * Creates the UI for layout tab
	 *
	 * @since 1.0.0
	 *
	 * @param object $post The current post object.
	 */
	public function layout_tab( $post ) {

		$this->create_global_setting( 'webgl',$post,'global');
		$this->create_global_setting( 'hard',$post,'global');
		$this->create_global_setting( 'bg_color',$post,'');
		$this->create_global_setting( 'bg_image',$post,'');
		$this->create_global_setting( 'duration',$post,'');
		$this->create_global_setting( 'height',$post,'');
		$this->create_global_setting( 'texture_size',$post,'global');

		$this->create_global_setting( 'auto_sound',$post,'global');
		$this->create_global_setting( 'enable_download',$post,'global');
		$this->create_normal_setting( 'page_mode',$post);
		$this->create_global_setting( 'single_page_mode',$post,'global');
		$this->create_normal_setting( 'direction',$post);
		$this->create_normal_setting( 'force_fit',$post);

		?>

		<!--Clear-fix-->
		<div class="dflip-box"></div>
	<?php

	}

	/**
	 * Creates the UI for outline tab
	 *
	 * @since 1.0.0
	 *
	 * @param object $post The current post object.
	 */
	public function outline_tab( $post ) {

		$this->create_normal_setting( 'auto_outline',$post);
		$this->create_normal_setting( 'auto_thumbnail',$post);
		$this->create_normal_setting( 'overwrite_outline',$post);
		?>

		<!--Outline/Bookmark-->
		<div id="dflip_outline_box" class="dflip-box dflip-js-code">

			<div class="dflip-desc">
				<p>
					<?php echo sprintf(
						__DFLIP( 'Create a tree structure bookmark/outline of your book for easy access:<br>%s' ),
						'<code>	Outline Name : (destination as blank or link to url or page number)</code>'
					); ?>
				</p>
			</div>

			<div class="dflip-option dflip-textarea-simple">
				<textarea rows="8" cols="40" id="dflip_outline">
					<?php
					$outline = $this->get_config( 'outline', $post );
					echo json_encode( $this->get_config( 'outline', $post ) );
					?>
				</textarea>
			</div>
		</div>

		<!--Clear-fix-->
		<div class="dflip-box"></div>
	<?php
	}

	/**
	 * Helper method for retrieving config values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key  The config key to retrieve.
	 * @param object $post The current post object.
	 *
	 * @param null   $_default
	 *
	 * @return string Key value on success, empty string on failure.
	 */
	public function get_config( $key, $post, $_default = null ) {

		$values = get_post_meta( $post->ID, '_dflip_data', true );
		$value  = isset( $values[ $key ] ) ? $values[ $key ] : '';

		$default = $_default === null
			? isset( $this->fields[ $key ] )
				? is_array( $this->fields[ $key ] )
					? isset( $this->fields[ $key ]['std'] )
						? $this->fields[ $key ]['std']
						: ''
					: $this->fields[ $key ]
				: ''
			: $_default;

		/* set standard value */
		if ( $default !== null ) {
			$value = $this->filter_std_value( $value, $default );
		}

		return $value;

	}

	/**
	 * Helper function to filter standard option values.
	 *
	 * @param     mixed $value Saved string or array value
	 * @param     mixed $std   Standard string or array value
	 *
	 * @return    mixed     String or array
	 *
	 * @access    public
	 * @since     1.0.0
	 */
	public function filter_std_value( $value = '', $std = '' ) {

		$std = maybe_unserialize( $std );

		if ( is_array( $value ) && is_array( $std ) ) {

			foreach ( $value as $k => $v ) {

				if ( '' === $value[ $k ] && isset( $std[ $k ] ) ) {

					$value[ $k ] = $std[ $k ];

				}

			}

		}
		else {
			if ( '' === $value && $std !== null ) {

				$value = $std;

			}
		}

		return $value;

	}

	/**
	 * Saves values from dFlip metaboxes.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $post_id The current post ID.
	 * @param object $post    The current post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {

		// Bail out if we fail a security check.
		if ( ! isset( $_POST['dflip'] )
		     || ! wp_verify_nonce(
				$_POST['dflip'], 'dflip'
			)
		     || ! isset( $_POST['_dflip'] )
		) {
			return;
		}

		// Bail out if running an autosave, ajax, cron or revision.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Bail if this is not the correct post type.
		if ( isset( $post->post_type )
		     && $this->base->plugin_slug !== $post->post_type
		) {
			return;
		}

		// Bail out if user is not authorized
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sanitize all user inputs.
		$settings = get_post_meta( $post_id, '_dflip_data', true );
		if ( empty( $settings ) ) {
			$settings = array();
		}

		$data = $_POST['_dflip'];

		$settings = array_merge( $settings, $data );


		$settings['outline'] = $this->array_val( $settings['outline'], 'items' );

		if ( isset( $post->post_type ) && 'dflip' == $post->post_type ) {
			if ( empty( $settings['title'] ) ) {
				$settings['title'] = trim( strip_tags( $post->post_title ) );
			}

			if ( empty( $settings['slug'] ) ) {
				$settings['slug'] = sanitize_text_field( $post->post_name );
			}
		}

		// Get publish/draft status from Post
		$settings['status'] = $post->post_status;

		// Update the post meta.
		update_post_meta( $post_id, '_dflip_data', $settings );

	}

	/**
	 * Removes index of array and returns only values array
	 *
	 * @since 1.0.0
	 *
	 * @param        array Array to be sanitized
	 * @param string $scan key index that needs to be re-sanitized
	 *
	 * @return array sanitized array
	 */
	private function array_val( $arr = array(), $scan = '' ) {

		if ( is_null($arr) ) {
			return array();
		}

		$_arr = array_values( $arr );
		if ( $_arr != null && $scan !== '' ) {
			foreach ( $_arr as &$val ) {
				if ( is_array( $val ) ) {
					if ( isset( $val[ $scan ] ) ) {
						$val[ $scan ] = $this->array_val( $val[ $scan ], $scan );
					}
				}
			}
		}

		return $_arr;

	}

	/**
	 * Helper method for retrieving global check values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key  The config key to retrieve.
	 * @param object $post The current post object.
	 *
	 * @return string Key value on success, empty string on failure.
	 */
	public function global_config( $key ) {

		$global_value = $this->base->get_config( $key );
		$value        = isset( $this->fields[ $key ] )
			? is_array( $this->fields[ $key ] )
				? isset( $this->fields[ $key ]['choices'][ $global_value ] )
					? $this->fields[ $key ]['choices'][ $global_value ]
					: $global_value
				: $global_value
			: $global_value;

		return $value;

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @return object dFlip_PostType object.
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance )
		     && ! ( self::$instance instanceof DFlip_Meta_Boxes )
		) {
			self::$instance = new DFlip_Meta_Boxes();
		}

		return self::$instance;

	}
}

// Load the DFlip_Metaboxes class.
$dflip_meta_boxes = DFlip_Meta_Boxes::get_instance();


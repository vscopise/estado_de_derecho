<?php
//Importar estilos del tema padre
add_action( 'wp_enqueue_scripts', 'ed_child_enqueue_styles',99);
function ed_child_enqueue_styles() {
    $parent_style = 'parent-style';
    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    //wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( $parent_style ) );
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/css/styles.css', NULL, filemtime( get_stylesheet_directory() . '/css/styles.css' ) );
}
if ( get_stylesheet() !== get_template() ) {
    add_filter( 'pre_update_option_theme_mods_' . get_stylesheet(), function ( $value, $old_value ) {
         update_option( 'theme_mods_' . get_template(), $value );
         return $old_value; // prevent update to child theme mods
    }, 10, 2 );
    add_filter( 'pre_option_theme_mods_' . get_stylesheet(), function ( $default ) {
        return get_option( 'theme_mods_' . get_template(), $default );
    } );
}

add_action( 'after_switch_theme', 'ed_schedule_function' );
function ed_schedule_function() {
    //crea un cronjob para chequear si hay usuarios que hayan expirado
    if ( !wp_next_scheduled( 'ed_chequear_usuarios_diariamente' ) ) {
            wp_schedule_event( current_time( 'timestamp' ), 'daily', 'ed_chequear_usuarios_diariamente');
    }
}

add_action( 'ed_chequear_usuarios_diariamente', 'ed_chequear_usuarios' );
function ed_chequear_usuarios(){
    $usuarios_temporales = get_option( 'usuarios_temporales' );
    $today_date = date('Y-m-d');
    foreach ( $usuarios_temporales as $id=>$usuario ) {
        if ( $usuario[expiry_date] <= $today_date ) {
            unset( $usuarios_temporales[ $user_found ] );
        }
    }
    update_option( 'usuarios_temporales', $usuarios_temporales );
}



//Crea las páginas necesarias
function ed_create_defaults_pages() {
    if ( NULL == get_page_by_path( 'perfil' )->ID ) {
        $page = array(
	    'post_type' => 'page',
	    'post_title' => 'Perfil',
	    'post_status' => 'publish',
	    'post_name' => 'perfil',
	    'page_template' => 'template-perfil.php',
        );
        wp_insert_post($page);
    }
    if ( NULL == get_page_by_path( 'terminos-y-condiciones' )->ID ) {
        $page = array(
	    'post_type' => 'page',
	    'post_title' => 'Términos y Condiciones',
	    'post_status' => 'publish',
	    'post_name' => 'terminos-y-condiciones',
        );
        wp_insert_post($page);
    }
    if ( NULL == get_page_by_path( 'edicion-impresa' )->ID ) {
        $page = array(
	    'post_type' => 'page',
	    'post_title' => 'Edición Impresa',
	    'post_status' => 'private',
	    'post_name' => 'edicion-impresa',
            'page_template' => 'template-edicion-impresa.php',
        );
        wp_insert_post($page);
    }
    if ( NULL == get_page_by_path( 'registro' )->ID ) {
        $page = array(
	    'post_type' => 'page',
	    'post_title' => 'Registro',
	    'post_status' => 'publish',
	    'post_name' => 'registro',
            'page_template' => 'template-registro.php',
        );
        wp_insert_post($page);
    }
    if ( NULL == get_page_by_path( 'registro' )->ID ) {
        $page = array(
	    'post_type' => 'page',
	    'post_title' => 'Registro Confirmado',
	    'post_status' => 'publish',
	    'post_name' => 'registro-confirmado',
        );
        wp_insert_post($page);
    }
}
add_action('after_switch_theme', 'ed_create_defaults_pages');

//Ajax Login - Registro
add_action( 'wp_enqueue_scripts', 'ed_login_scripts' );
function ed_login_scripts() {
    wp_enqueue_script( 'ed_login_scripts', get_stylesheet_directory_uri() . '/js/ed-login-scripts.js', array('jquery'), false, true);

    wp_localize_script( 'ed_login_scripts', 'ed_login_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'redirecturl' => home_url('/'),
        )
    );
    
}

function ed_new_user() {
    $nonce = filter_input( INPUT_POST, 'nonce');
    $user_name = filter_input( INPUT_POST, 'user_name');
    $user_mail = filter_input( INPUT_POST, 'user_mail', FILTER_VALIDATE_EMAIL);
    $user_key = wp_generate_password( 20, false );
    $expiry_date = date( 'Y-m-d', strtotime('+7 days') );
    
    $usuarios_temporales = get_option( 'usuarios_temporales' );
    
    $result = 1;
    
    if( !isset( $nonce ) || !wp_verify_nonce( $nonce, 'new_user' ) || !$user_name ) {
        $mensaje = 'error';
    } else {
        if ( !$user_mail ) {
            $mensaje = 'E-mail incorrecto';
        } elseif ( email_exists( $user_mail ) ) {
            $mensaje = 'Este e-mail ya está registrado';
        } elseif ( $usuarios_temporales && is_int( array_search( $user_mail, array_column($usuarios_temporales, 'user_mail') ) ) ) {
            $mensaje = 'Este Email ya está registrado';
        } elseif ( $usuarios_temporales && is_int( array_search( $user_name, array_column($usuarios_temporales, 'user_name') ) ) ) {
            $mensaje = 'Este Usuario ya está registrado';
        } elseif ( username_exists( $user_name ) ) {
            $mensaje = 'Este Usuario ya está registrado';
        } else {
            $new_user = array(
                'user_name' => $user_name,
                'user_mail' => $user_mail,
                'user_key' => $user_key,
                'expiry_date' => $expiry_date,
            );
            if ( ! $usuarios_temporales ) {
                $usuarios_temporales = array();
            }
            array_push( $usuarios_temporales, $new_user );
            update_option( 'usuarios_temporales', $usuarios_temporales );
            
            //enviar mail
            $logo_url = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ) , 'full' );
            $home_url = home_url('/');
            $terminos_y_condiciones = get_permalink( get_page_by_path( 'terminos-y-condiciones' ) -> ID );
            
            /*$parms = array(
                '{{logo_url}}' => $logo_url[0],
                '{{home_url}}' => $home_url,
                '{{terminos_y_condiciones}}' => $terminos_y_condiciones,
            );*/
            
            /*$mail_data1 = '
                <html>
                    <body>
                        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                            <tr>
                                <td style="text-align: center; ">
                                    <img src="'.$logo_url[0].'" />
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; margin-bottom: 30px; ">
                                    <h3>
                                        Gracias por registrarse.
                                    </h3>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; ">
                                    <p style="text-align: center; margin-bottom: 30px;">
                                        Su registro está prácticamente completo.<br /><br />
                                        Haga click en el siguiente enlace para confirmar su suscripción.
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center;">
                                    <a href="'.$home_url.'?action=confirmar_usuario&user_name='.$user_name.'&user_key='.$user_key.'&user_mail='.$user_mail.'" 
                                        style="width:60%; padding: 13px 100px; text-decoration: none; border-radius: 4px; color: #fff; background: #e96656; display: inline-block;">ACTIVAR REGISTRO</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p style="text-align: center; font-size: 0.7em;">
                                        Al confirmar el registro Ud. manifiesta que está de acuerdo con nuestros <a href="'.$terminos_y_condiciones.'">términos y condiciones</a>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </body>
                </html>
            ';*/
            $mail_data = get_mail_new_user( $logo_url[0], $home_url, $user_name, $user_key, $user_mail, $terminos_y_condiciones );
            
            $result = wp_mail( $user_mail, 'Bienvenido a Estado de Derecho', $mail_data, array('Content-Type: text/html; charset=UTF-8') );
            
            $mensaje = 'Mensaje enviado correctamente, ahora revise su mail';
            //$result = 0;
        }
    }
    echo json_encode( ['result' => $result, 'mensaje' => $mensaje] );
    wp_die();
}
add_action('wp_ajax_new_user', 'ed_new_user');
add_action('wp_ajax_nopriv_new_user', 'ed_new_user');

function ed_login_user() {
    $nonce = filter_input( INPUT_POST, 'nonce');
    $login_user_name = filter_input( INPUT_POST, 'login_user_name');
    $login_user_pass = filter_input( INPUT_POST, 'login_user_pass');
    $user_data = array(
        'user_login' => $login_user_name,
        'user_password' => $login_user_pass,
        'remember' => true,
    );
    
    $result = 1;
    
    if( !isset( $nonce ) || !wp_verify_nonce( $nonce, 'login_user' ) || !$login_user_name ) {
        $mensaje = 'error';
    }
    $user_signon = wp_signon( $user_data, false );
    if ( is_wp_error( $user_signon ) ) {
        $mensaje = 'Usuario o clave incorrecta.';
    } else {
        $result = 0;
        $mensaje = 'Ingreso correcto, redirigiendo...';
    }
    
    echo json_encode( ['result' => $result, 'mensaje' => $mensaje] );
    wp_die();
}
add_action('wp_ajax_login_user', 'ed_login_user');
add_action('wp_ajax_nopriv_login_user', 'ed_login_user');

function ed_lost_password(){
    $nonce = filter_input( INPUT_POST, 'nonce');
    $user_mail = filter_input( INPUT_POST, 'user_mail', FILTER_VALIDATE_EMAIL);
    
    $result = 1;
    
    if( !isset( $nonce ) || !wp_verify_nonce( $nonce, 'lost_password' ) || !$user_mail ) {
        $mensaje = 'error';
    } elseif ( ! $user_id = email_exists( $user_mail ) ) {
        $mensaje = 'Error: email no registrado';
    } else {
        $new_pass = wp_generate_password(20, false);
        wp_set_password( $new_pass, $user_id );
        
        //enviar mail
        $logo_url = wp_get_attachment_image_src ( get_theme_mod( 'custom_logo' ) , 'full' );
        
        $user_name = get_userdata( $user_id )->user_login;
            
        $mail_data = get_mail_lost_password ( $logo_url[0], $user_name, $new_pass );
            
        $result = wp_mail( $user_mail, 'Contraseña recuperada en Estado de Derecho', $mail_data, array('Content-Type: text/html; charset=UTF-8') );
            
        $mensaje = 'Una nueva contraseña fue enviada a su correo electrónico';
        
    }
    
    echo json_encode( ['result' => $result, 'mensaje' => $mensaje] );
    wp_die();
    
}
add_action('wp_ajax_lost_password', 'ed_lost_password');
add_action('wp_ajax_nopriv_lost_password', 'ed_lost_password');

function ed_compose_mail( $template, $parms ) {
    //return strtr( file_get_contents( get_stylesheet_directory_uri().'/mail-templates/'. $template ), $parms );
    
    
    ob_start();
    include( get_stylesheet_directory_uri().'/mail-templates/'. $template );
    $content = ob_get_clean();
    //$mail->Body = strtr(file_get_contents('path/to/template.html'), array('%var1%' => 'Value 1', '%var2%' => 'Value 2'));
    
    /*$array_data = file( get_stylesheet_directory_uri().'/mail-templates/'. $template );
    foreach ($array_data as $value) {
        $mail_data .= trim($value);
    }
    
    foreach ( $parms as $key => $value ) {
        $mail_data = str_replace( '{{'.$key.'}}', $value, $mail_data );
    }*/
    //$mail_data = nl2br( $mail_data, FALSE ); 
    //$mail_data = preg_replace('/\R/', '', $mail_data);
    //$mail_data = str_replace( '\r\n', '\n', $mail_data ); 
    //$mail_data = str_replace( '\r', '\n', $mail_data ); 
    //$mail_data = str_replace( chr(13), '\n', $mail_data ); 
    //$mail_data = eregi_replace("\r","",$mail_data);
    //$mail_data = eregi_replace("\n","",$mail_data);
    //$mail_data = preg_replace('/\s+/S', " ", $mail_data);
    //$mail_data = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $mail_data);
    //$mail_data = ereg_replace(" {2,}", '',$mail_data);

    return $content;
}

function ed_add_custom_query_var( $vars ) {
    $new_vars = array( 'action', 'user_name', 'user_key', 'user_mail', 'id_postulante', 'id' );
  
    return array_merge( $vars, $new_vars );
}
add_filter( 'query_vars', 'ed_add_custom_query_var' );

function ed_confirmar_usuario () {
    global $wp_query;
    $user_name = $wp_query->query['user_name'];
    $user_mail = $wp_query->query['user_mail'];
    $user_key = $wp_query->query['user_key'];
    $action = $wp_query->query['action'];
    
    if( !isset( $action ) ) { return $template; }

    if( $wp_query->query['action']=='confirmar_usuario' && isset( $user_key ) && isset( $user_name ) && isset( $user_mail ) ){
        
        $usuarios_temporales = get_option( 'usuarios_temporales' );
        $user_found = array_search( $user_name, array_column($usuarios_temporales, 'user_name'));
        if ( is_int( $user_found ) &&
            $usuarios_temporales[$user_found][user_name] == $user_name &&
            $usuarios_temporales[$user_found][user_mail] == $user_mail && 
            $usuarios_temporales[$user_found][user_key] == $user_key  
        ) {
            unset( $usuarios_temporales[ $user_found ] );
            update_option( 'usuarios_temporales', $usuarios_temporales);
            
            $user_pass = wp_generate_password(20, false);
            /*$userdata = array(
                'user_email'    =>  $user_mail,
                'user_login'    =>  $user_name,
                'user_pass'     =>  $user_pass,
                'role'          =>  'subscriber'
            );*/
            $user_id = wp_insert_user( array(
                'user_email'    =>  $user_mail,
                'user_login'    =>  $user_name,
                'user_pass'     =>  $user_pass,
                'role'          =>  'subscriber'
            ));

            //enviar mail
            $logo_url = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ) , 'full' );
            $mail_data = get_mail_confirm_user ($logo_url[0], $user_name, $user_pass);
            
            $result = wp_mail( $user_mail, 'Registro confirmado en Estado de Derecho', $mail_data, array('Content-Type: text/html; charset=UTF-8') );
        }
        
    }
    wp_redirect( home_url( '/registro-confirmado/?user=' . $user_id ) );
    //return $template;
}
add_action('template_redirect', 'ed_confirmar_usuario');
        
add_filter( 'wp_nav_menu_items', 'ed_add_loginout_link', 10, 2 );
function ed_add_loginout_link( $items, $args ) {
    //if ( is_user_logged_in() && $args->theme_location == 'main-menu') {
    if( ! is_user_logged_in() && $args->theme_location == apply_filters('login_menu_location', 'primary') ) {  
        $items .= '<li id="login-modal-btn"><a data-toggle="modal" href="#" data-toggle="modal" data-target="#login-modal">Ingresar / Registrarse</a></li>';
    } elseif( $args->theme_location == 'primary' ) {
        global $current_user;
        $user_login = get_the_author_meta( 'user_login', $current_user->ID );
        $first_name = get_the_author_meta( 'first_name', $current_user->ID );
        $last_name = get_the_author_meta( 'last_name', $current_user->ID );
        $nombre = $first_name == '' ? $user_login : $first_name . ' ' . $last_name;
        $items .= '<li class="menu-item"><a href="'. get_page_link( get_page_by_path( 'perfil' )->ID ).'" title="Editar perfil" />Hola '.$nombre.'</a></li>';
        $items .= '<li class="menu-item login-link"><a href="' . wp_logout_url( home_url() ) . '">Cerrar sesión</a></li>';
        //$items .= '<li id="logout"><a href="' . wp_logout_url( home_url('/') ) . '" title="' . __('Salir', 'ascent') . '">' . __('Salir', 'ascent') . '</a></li>';
    }
    return $items;
}

//add_filter( 'the_permalink', 'ed_custom_permalink' );
function ed_custom_permalink( $url ) {
    global $post;
    if ( $post->post_status == 'private' ) {
        if ( ! is_user_logged_in()  ) {
            return 'google.com';

        } 
    } else {
        return get_permalink( $post );
    }
}

add_action('wp_footer', 'ed_login_register_modal');
function ed_login_register_modal() {
	if( ! is_user_logged_in() ){ 
            include_once( 'ajax-login-register/ajax-login-register.php' );
	}
}

//Quita la barra de administrador a los postulantes y empleadores
add_action('after_setup_theme', 'ed_remove_admin_bar');
function ed_remove_admin_bar() {
    if ( !current_user_can('administrator') && !is_admin() ) {
        show_admin_bar(false);
    }
}

//Funciones para las suscripciones

//Agrega los suscriptores digitales como copia de los suscriptores
add_action( 'after_setup_theme', 'ed_subscriptor_setup' );
function ed_subscriptor_setup() {
    $subscriber = get_role('subscriber');
    add_role( 'subscriber_d', 'Suscriptor Digital', $subscriber->capabilities );
    
    //Permite a los suscriptores digitales a ver post privados
    $digital_subscriber = get_role('subscriber_d');
    $digital_subscriber -> add_cap( 'read_private_posts' );
    $digital_subscriber -> add_cap( 'read_private_pages' );
}

add_action( 'show_user_profile', 'ed_show_subscriptions_fields' );
add_action( 'edit_user_profile', 'ed_show_subscriptions_fields' );
function ed_show_subscriptions_fields( $user ) { 
    if ( !current_user_can( 'edit_posts', $user->ID ) ) { return false; }
    
    $subscription1_meta = ( get_the_author_meta( 'subscription1', $user->ID ) == '' ) ? '1/1/2015' : get_the_author_meta( 'subscription1', $user->ID );
    $subscription1 = date( 'd/m/Y', $subscription1_meta );
    $subscription2_meta = ( get_the_author_meta( 'subscription2', $user->ID ) == '' ) ? '1/1/2015' : get_the_author_meta( 'subscription2', $user->ID );
    $subscription2 = date( 'd/m/Y', $subscription2_meta );
    ?>
        <h3>Información de suscripciones</h3>
        <table class="form-table">
            <tr>
                <th><label for="subscriptions">Inicio / fin de las suscripciones <br />(dd / mm / aaaa)</label></th>
                <td>
                    <input type="text" name="subscription1" id="subscription1" value="<?php echo $subscription1; ?>" class="regular-text" />
                    <span class="description">Fecha del inicio.</span><br />
                    <input type="text" name="subscription2" id="subscription2" value="<?php echo $subscription2; ?>" class="regular-text" />
                    <span class="description">Fecha del fin.</span>
                </td>
            </tr>
        </table>
<?php 
}

add_action( 'personal_options_update', 'save_subscriptions_fields' );
add_action( 'edit_user_profile_update', 'save_subscriptions_fields' );
function save_subscriptions_fields( $user_id ) {
    if ( !current_user_can( 'edit_user', $user_id ) )
        return false;
    $subscription1  = strtotime( str_replace('/', '-', filter_input(INPUT_POST, 'subscription1')) );
    $subscription2  = strtotime( str_replace('/', '-', filter_input(INPUT_POST, 'subscription2')) );
    if ( !$subscription2 || !$subscription2 ) 
        return false;
    if ( $subscription2 < $subscription1 )
        return false;
    update_usermeta( $user_id, 'subscription1', $subscription1 );
    update_usermeta( $user_id, 'subscription2', $subscription2 );
}

//Si terminó el período de suscripción actualizo el usuario a "Subscriber"
function ed_check_user_permission( $user_login, $user ) {
        if ( user_can( $user, 'edit_posts' ) )
                return false;
        $subscription1_meta = get_the_author_meta( 'subscription1', $user->ID );
        $subscription2_meta = get_the_author_meta( 'subscription2', $user->ID );
        $time = time();
        if ( $subscription2_meta < $time ) {
                wp_update_user(array('ID'=>$user->ID, 'role'=>'subscriber'));
        }
}
add_action('wp_login', 'ed_check_user_permission', 10, 2);

//determina si la suscripción del usuario (si existe) abarca la fecha dada
function has_the_subscription($user_id, $date){
    $subscription1 = get_user_meta( $user_id, 'subscription1', true );
    $subscription2 = get_user_meta( $user_id, 'subscription2', true );
    if ( $subscription1 && $subscription2 ) {
        if ( ($subscription1 < $date) && ($date < $subscription2) && user_can( $user_id, 'subscriber_d' ) ) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

// remove "Private: " from titles
function ed_change_private_prefix( $title ) {
	$the_title = str_replace( 'Privado: ', '<span class="privado"><i class="fa fa-unlock-alt" aria-hidden="true"></i></span>', $title );
	return $the_title;
}
add_filter('the_title', 'ed_change_private_prefix');

// Load translation files from your child theme instead of the parent theme
add_action( 'after_setup_theme', 'ed_theme_locale' );
function ed_theme_locale() {
        load_child_theme_textdomain( 'ederecho', get_stylesheet_directory() . '/languages' );
}

function zerif_posted_on() {
    if ( is_single() ) :
        if ( has_post_thumbnail() ) :
            ?>
        <div class="post-thumbnail"><?php the_post_thumbnail() ?></div>
            <?php
        endif;
        
        if ( has_excerpt( $some_post_id ) ) :
            ?>
                <div class="excerpt"><?php echo get_the_excerpt() ?></div>
            <?php

        endif;
        
        ?><div class="meta"<?php
	$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time>';
        
	if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
		$time_string .= '<time class="updated" datetime="%3$s">%4$s</time>';
	}
	$time_string = sprintf( $time_string,
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() ),
		esc_attr( get_the_modified_date( 'c' ) ),
		esc_html( get_the_modified_date() )
	);

	printf( __( '<span class="posted-on">Posted on %1$s</span><span class="byline"> by %2$s</span>', 'zerif-lite' ),
		sprintf( '<a href="%1$s" rel="bookmark">%2$s</a>',
			esc_url( get_permalink() ),
			$time_string
		),

		sprintf( '<span class="author vcard"><a class="url fn n" href="%1$s">%2$s</a></span>',
			esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
			esc_html( get_the_author() )
		)

	);
        ?></div><?php
        
    endif;
}



/***** Retorna frace, limitada por un punto *****/
function ed_get_paragraph( $string ) {
        preg_match('/^(.*?)[.?!]\s/', $string . ' ', $result);
        return $result[0];
}

function ed_custom_excerpt( $excerpt ) {
    if ( ! is_single() ) {
        $excerpt = ed_get_paragraph( $excerpt ) . '...';
    }
    return $excerpt;
}
add_filter( 'get_the_excerpt', 'ed_custom_excerpt' );

function ed_custom_pagination($numpages = '', $pagerange = '', $paged='') {
    if (empty($pagerange)) {
        $pagerange = 2;
    }

    global $paged;
    if (empty($paged)) {
        $paged = 1;
    }
    if ($numpages == '') {
        global $wp_query;
        $numpages = $wp_query->max_num_pages;
        if(!$numpages) {
            $numpages = 1;
        }
    }
    $pagination_args = array(
        'base'            => get_pagenum_link(1) . '%_%',
        'format'          => 'page/%#%',
        'total'           => $numpages,
        'current'         => $paged,
        'show_all'        => False,
        'end_size'        => 1,
        'mid_size'        => $pagerange,
        'prev_next'       => True,
        'prev_text'       => __('&laquo;'),
        'next_text'       => __('&raquo;'),
        'type'            => 'plain',
        'add_args'        => false,
        'add_fragment'    => ''
    );

    $paginate_links = paginate_links($pagination_args);

    if ($paginate_links) {
        echo "<nav class='custom-pagination'>";
        echo "<span class='page-numbers page-num'>Página " . $paged . " de " . $numpages . "</span> ";
        echo $paginate_links;
        echo "</nav>";
    }

}

/*****  Widgets *****/
function ed_register_widgets() {
	register_widget( 'ed_font_accesibility' );
	register_widget( 'ed_banner_suscripciones' );
}
add_action( 'widgets_init', 'ed_register_widgets' );

/***** Incluir Widgets *****/
require_once( 'widgets/ed-font-accesibility.php' );
require_once( 'widgets/ed-banner-suscripciones.php' );

function ed_customize_register( $wp_customize ) {
    
    $wp_customize->add_setting(
            'ed_google_analytics', array(
                    'default'           => '',
                    'transport'         => 'postMessage',
                    'sanitize_callback' => 'esc_html',
            )
    );
    
    $wp_customize->add_control(
            'ed_google_analytics', array(
                    'label'    => 'Google Analytics',
                    'section'  => 'zerif_general_section',
                    'priority' => 7,
            )
    );
    
}
add_action( 'customize_register', 'ed_customize_register' );

/***** Google Analytics *****/

function ed_add_googleanalytics() { 
    $google_analytics_code = get_theme_mod( 'ed_google_analytics', '' );
    if ( '' != $google_analytics_code ) :
?>
    <script type="text/javascript">
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount', '<?php echo $google_analytics_code ?>']);
        _gaq.push(['_trackPageview']);
        (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        })();
    </script>
<?php
    endif;
    }
add_action('wp_footer', 'ed_add_googleanalytics');

function get_mail_new_user( $logo_url, $home_url, $user_name, $user_key, $user_mail, $terminos_y_condiciones ) {
    $mail_template = '
        <html>
            <body>
                <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                    <tr>
                        <td style="text-align: center; ">
                            <img src="'.$logo_url.'" />
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; margin-bottom: 30px; ">
                            <h3>
                                Gracias por registrarse.
                            </h3>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; ">
                            <p style="text-align: center; margin-bottom: 30px;">
                                Su registro está prácticamente completo.<br /><br />
                                Haga click en el siguiente enlace para confirmar su suscripción.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center;">
                            <a href="'.$home_url.'?action=confirmar_usuario&user_name='.$user_name.'&user_key='.$user_key.'&user_mail='.$user_mail.'" 
                                style="width:60%; padding: 13px 100px; text-decoration: none; border-radius: 4px; color: #fff; background: #e96656; display: inline-block;">ACTIVAR REGISTRO</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p style="text-align: center; font-size: 0.7em;">
                                Al confirmar el registro Ud. manifiesta que está de acuerdo con nuestros <a href="'.$terminos_y_condiciones.'">términos y condiciones</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </body>
        </html>
    ';
    
    return $mail_template;
    
}

function get_mail_confirm_user ( $logo_url, $user_name, $user_pass ) {
    $mail_template = '
                <html>
                    <body>
                        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                            <tr>
                                <td style="text-align: center; ">
                                <img src="'.$logo_url.'" />
                                </td>
                            </tr>
                        <tr>
                            <td style="text-align: center; ">
                                <h2 style="text-align: center">
                                    Felicitaciones '.$user_name.'
                                </h2>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p style="text-align: center">
                                    Ud. ya está registrado. Puede ingresar al sitio con su nombre de usuario.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h4 style="text-align: center">
                                    La contraseña asignada es:
                                </h4>
                                <h4 style="text-align: center">'.$user_pass.'</h4>
                                <p style="text-align: center">

                                </p>
                            </td>
                        </tr>
                    </table>
                </body>
            </html>      
        ';
    return $mail_template;
}

function get_mail_lost_password ( $logo_url, $user_name, $new_pass ) {
    $mail_template = '
        <html>
            <body>
                <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                    <tr>
                        <td style="text-align: center; ">
                            <img src="'.$logo_url.'" />
                        </td>
                    </tr>
                    <tr>
                            <td style="text-align: center; ">
                                <h2 style="text-align: center">
                                    Hola '.$user_name.'
                                </h2>
                            </td>
                        </tr>
                    <tr>
                        <td style="text-align: center; ">
                            <h4 style="text-align: center">
                                Una nueva contraseña le ha sido asignada:
                            </h4>
                            <h4 style="text-align: center">'.$new_pass.'</h4>
                        </td>
                    </tr>
                </table>
            </body>
        </html>
    ';
    return $mail_template;
}
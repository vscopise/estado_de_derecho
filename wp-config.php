<?php
/** 
 * Configuración básica de WordPress.
 *
 * Este archivo contiene las siguientes configuraciones: ajustes de MySQL, prefijo de tablas,
 * claves secretas, idioma de WordPress y ABSPATH. Para obtener más información,
 * visita la página del Codex{@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} . Los ajustes de MySQL te los proporcionará tu proveedor de alojamiento web.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** Ajustes de MySQL. Solicita estos datos a tu proveedor de alojamiento web. ** //
/** El nombre de tu base de datos de WordPress */
define('DB_NAME', 'derecho');

/** Tu nombre de usuario de MySQL */
define('DB_USER', 'root');

/** Tu contraseña de MySQL */
define('DB_PASSWORD', '');

/** Host de MySQL (es muy probable que no necesites cambiarlo) */
define('DB_HOST', 'localhost');

/** Codificación de caracteres para la base de datos. */
define('DB_CHARSET', 'utf8mb4');

/** Cotejamiento de la base de datos. No lo modifiques si tienes dudas. */
define('DB_COLLATE', '');

/**#@+
 * Claves únicas de autentificación.
 *
 * Define cada clave secreta con una frase aleatoria distinta.
 * Puedes generarlas usando el {@link https://api.wordpress.org/secret-key/1.1/salt/ servicio de claves secretas de WordPress}
 * Puedes cambiar las claves en cualquier momento para invalidar todas las cookies existentes. Esto forzará a todos los usuarios a volver a hacer login.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'Lv/cw1u~C87vbvg!/Y_foZyGENGjp.>j?U3qYuG7h+A8V$~Z^3^k>oXt`b_Y)sQ+');
define('SECURE_AUTH_KEY', '<C7WzbU@CA4AWTL&50&m$.|CBu!0^a5t0BG.}v+*3MUVr5{dZLMwq4yvr0^;9V@#');
define('LOGGED_IN_KEY', 'V74sR9NmCX?g])C|!B+*24Q3+tKKve>TBSbaf7l:OPN/kJ:PvfI|wh>|tlr&v~uH');
define('NONCE_KEY', 'n2PuiYwgTiLH~ @(> mLs&1b&$uMO>*;kUZ-+Z|96hUy1yt(aDhKUcx,m96W/oca');
define('AUTH_SALT', '4ZC4OJ27yfU#|Ofap|zBz$8U<2rs0`{]k3ic9s<Ks(e`feZ(Xn)+R1Tx+{Q[I;J:');
define('SECURE_AUTH_SALT', '5FC@,^ZebOg2/CCw9xydY[XxA5MD|. eRtMP%ZJ*+-vT~JwH*^h=xF%{6TKX6a~p');
define('LOGGED_IN_SALT', '#]MT^a<ts4D(Ul;$fa`|E[C6q:SRExosLClwDj^ZbWVZAF9/nWRj%MZ*xkzh*H]3');
define('NONCE_SALT', 'ba4ZsO}p[fqJX;2;a)-*!nO?;6l-7D*V`|Q:rjc|l@y6w zrG~cxSEdYz&4/S~kT');

/**#@-*/

/**
 * Prefijo de la base de datos de WordPress.
 *
 * Cambia el prefijo si deseas instalar multiples blogs en una sola base de datos.
 * Emplea solo números, letras y guión bajo.
 */
$table_prefix  = 'wp_';


/**
 * Para desarrolladores: modo debug de WordPress.
 *
 * Cambia esto a true para activar la muestra de avisos durante el desarrollo.
 * Se recomienda encarecidamente a los desarrolladores de temas y plugins que usen WP_DEBUG
 * en sus entornos de desarrollo.
 */
define('WP_DEBUG', false);

/* ¡Eso es todo, deja de editar! Feliz blogging */

/** WordPress absolute path to the Wordpress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');


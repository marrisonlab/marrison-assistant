<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u272699205_eUdft' );

/** Database username */
define( 'DB_USER', 'u272699205_GgqQ6' );

/** Database password */
define( 'DB_PASSWORD', 'z9KCjAWImb' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '9JFr}Is&q{Ti$FOFn94zu<JL(e3hAQL?sjH>Y66=KR1wM(=D^{2X(AOu^nP#.!z8' );
define( 'SECURE_AUTH_KEY',   '|a`_dLl;PN9uFRt( y3[o4F~lY2UU yYE|>=p<xjX:I*?tLcH@VTVkSVR3-w2@,a' );
define( 'LOGGED_IN_KEY',     'LoQa4S9w(8pOBhWCC7C8&^QK1Ocz*=N6|FXFpIIGk(6`7ap)CjFxM3$JurhgE8_3' );
define( 'NONCE_KEY',         'cU{YyISRxGdPym=S!/;xQE/jU;8 {!TRX(Im+Jn%[rjyEv)aMeHRxPv[W_k! X?q' );
define( 'AUTH_SALT',         '[u@k~IC=dT&HqGh!d!=>lw_1:cvSt][n|6,jw!:BV-nDz00B1_Mm0wcc!>Qmd9mb' );
define( 'SECURE_AUTH_SALT',  'F0Hz3lL@hfZ_o[5R8fdkti[drDW8bDU%7mh:%d5G=#/0~.6Cp$tAXvd*MQOz;;t7' );
define( 'LOGGED_IN_SALT',    '_%S`;.V`oO+=3JWA@;% 6xJ9+>I2z*s?*Z|1eLJxNjj6rA;Xz=]B#0q;LOzULEB7' );
define( 'NONCE_SALT',        '|n)b@%!s#Pj-04X *acH4#2m9s59 Cz5nfJb7F5~j7%P8^!IN-Vc_rM2c]IP5tXu' );
define( 'WP_CACHE_KEY_SALT', 'Lr>We2(k%Pg26n](+>I`h!QRD#{yPc,)U+m%5{X0Nl-;ML@S#,JI-Tli$]cOo&d9' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}
// Attiva la modalità debug
define( 'WP_DEBUG', true );

// Salva gli errori in /wp-content/debug.log
define( 'WP_DEBUG_LOG', true );

// Non mostrare gli errori sul sito (nascondi agli utenti)
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', '40f7bf85f24e736721068069d70b7213' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

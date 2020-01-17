<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'wisdmlabs' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '$yP$Se}),x(la<PS2SidQZ8YxKQR1cMk|[W~;z<h;>AMF_?@E|/I~=6V2F3(2rj&');
define('SECURE_AUTH_KEY',  'LY1~R]X.EBjTe[{&Zd]ra&V5;  rbY0mgNRb^g-{GLE#5cpBb0<5nC-PZ]3k.e,#');
define('LOGGED_IN_KEY',    '+-zd[rj.oU*a y/(Y-gu@dYq2Jyl5,l)NDOH%6]!.5]H99x8ri)0eQziYN8Uj%bp');
define('NONCE_KEY',        '-z+JAoI%Wb|Z_JJf1%@wy@kbc1=2Kb&bw6=~R(oY9<6++Ub%{~&KC!y0[K~MWOQT');
define('AUTH_SALT',        '2oTCqwh /hPZl|IM46+lN7K(8I:wRwS~31sGu+WT|Y~[Gz{JfcqBP9uW)(vY*[#x');
define('SECURE_AUTH_SALT', 'KZK}?9B.k>$e|a#r6 |YS)h;f.IFZGj^@<:)$TnX-0G|k|SVPuyw;sK%Nx28{|ja');
define('LOGGED_IN_SALT',   'kn^fxSks{9#%^UPns8bk^>2~MA#y4*Gi:*B]a$G8qB,-+TTLs>mDt 65S.&(lCAG');
define('NONCE_SALT',       '{{^[86ZF/z}-T|e(J+V2w7D@{6`j(I)+50;g3lPx !7sJtvI}5$kW_$:R8F=T[{e');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', true );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );

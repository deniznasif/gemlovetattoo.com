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
define('DB_NAME', 'gemlove_db');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'SNu+l-YI2@@/[8_XhtY35(`M[Ay|4RZag@3[OHMKW3(WoBv35L:*ne/d9=x5w1t8');
define('SECURE_AUTH_KEY',  '%jrk_d.jwSJ|A+[5[e*j$sdGrukO^  [7S1sDLnvOh?ydG^Fs6)VFxhuhw0-.1WH');
define('LOGGED_IN_KEY',    'sELgKf`q0NrTS=[/i9DI2t5i{8s|lWCcH2I/VQ&S#2$}X3M`,%sL-vQwLJT0Jn=F');
define('NONCE_KEY',        '/9o7tOe,]T0eZ0#)wUaKhMNv&qhH(FDXRv7+$>shn]|IA@0-/~jLj2$p|aK~zhsQ');
define('AUTH_SALT',        'Vixft4K}!)QZe+1V$,%V9imf>N~iC6i^!g$W?-1I?xDdBN?^i=zm(7}=[DV^gD97');
define('SECURE_AUTH_SALT', 'wqZ+Yv/[aw,+p4i9&)j5n(wxE 6^|m@B R1y_SuKx,? vK9xGN.ZW~ReI<G^Ok==');
define('LOGGED_IN_SALT',   '|OpMy<t/pn;odj8mw<+SR 1 g7(6|o-EW;39K*i(KR!EiuimH,Reykyb+N{O1zlR');
define('NONCE_SALT',       'g]*.I<Khj~`|4IM=*)LNIW KCX:T$1,7a]B+I0|Z=>t6} }h1wl4X>y2^~PK_IqG');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_G3mLOvT4tdb_';

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
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

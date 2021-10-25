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
define('DB_NAME', 'proyectodaw');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

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
define('AUTH_KEY',         '5FszHVV7vROI.#Ezx|,otk@Le[nR4(crHT8y*g@I+?+1Kj1TM7xynL+$&MQ2(#Dx');
define('SECURE_AUTH_KEY',  '7TKNI1jM.?jRp?821(FHg1$:t[AwoamC.*D-X0-d&60tLGur;K|f8,[09zW7<h+E');
define('LOGGED_IN_KEY',    '><eRtYe%S.l ([+e{I`hw*+1E1{Gfa_J8a{v6|rQHt@4(bz|AAanz-L}grdNEFPf');
define('NONCE_KEY',        'A+7F%6PjB !3h-p:?_NB64TAAVVfw rwLyWp6anc*5,Yx$C%`N*9r0WG<mn.n-*f');
define('AUTH_SALT',        'N6]iZ![OXyQX5>QC}rUh^z-:f Pg|lizC4u*RkW-7{3@oz+G1r5$P{3xUya(A+4+');
define('SECURE_AUTH_SALT', '_Ij@fk=o<Qg<$ Aou-V~DQ0#n-)-ZC~RWjbMhoGez^JIK|M]trxaCz#(,1T@5*N*');
define('LOGGED_IN_SALT',   ' gbeL^xe!n}>h:tfYRqf-}E{)ztQ,}d )rImd;T)ne{2. o a.C xU4FT+Do?el}');
define('NONCE_SALT',       'Wf3%]LC,*$]uA$x|TpUn;|w)w[OM6?i-V5?QcF:<{D1%7+PWQ&IX{[|B$w-t.g6U');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

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

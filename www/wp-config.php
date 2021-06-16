<?php

//Begin Really Simple SSL session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple SSL
define( 'WP_CACHE', true );
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
define('DB_NAME', 'hugotoiblue');
/** MySQL database username */
define('DB_USER', 'hugotoiblue');
/** MySQL database password */
define('DB_PASSWORD', '7Q3lgdVpzbY0n2TYSIgHHeon8UJjib');
/** MySQL hostname */
define('DB_HOST', 'hugotoiblue.mysql.db:3306');
/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');
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
define('AUTH_KEY',         'TpKQ9ttXqjS88LYq5AGdczTzaBqNNs5VqMNS6/uJScVq3JhV6lKz8D0GITpC');
define('SECURE_AUTH_KEY',  'rD3PBDYkMO5kdxDJA3fvPVQ4Lv7imfbDaZZD7XXMq7oRzyJJ8PMlAs1QIpVr');
define('LOGGED_IN_KEY',    'vxc+Kms8mSXuFOFL1oRLCH85xi6U3phSNDqQXfKupwDliQ94x0mn7YyqyXTl');
define('NONCE_KEY',        'eE+eGC62REVEpmvaPZOlIeqLRsy6+rGRcfCdTvjNcjGP/JauCdRe3xvgigCa');
define('AUTH_SALT',        'U4xvLpak0XxoLYWmxtDOkVzI0DHOaM7suMQPMFSxJvTCljdULjH6cb5R/fer');
define('SECURE_AUTH_SALT', 'BUXvoV5viGGxSBRffPICBsMVialdIqu6yR0kilSvplgjasfz6R3O5QvQyuri');
define('LOGGED_IN_SALT',   'ZxEEkogl12kEUTwZW8qM/FbzIPUs32vCjGF+9LECiNLwsd6Wf9K9W2Pnu1q1');
define('NONCE_SALT',       'dWxdorZs3sTZajxjfI7P6dCcKsbFQeNeSApF+s7X5U0PcRL42I3I6UpxjuYY');
/**#@-*/
/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wor4281_';
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
/* Fixes "Add media button not working", see http://www.carnfieldwebdesign.co.uk/blog/wordpress-fix-add-media-button-not-working/ */
define('CONCATENATE_SCRIPTS', false );
/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

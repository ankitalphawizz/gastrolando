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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */
define( 'FS_METHOD', 'direct' );
// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'gastrolandodev' );

/** MySQL database username */
define( 'DB_USER', 'phpmyadmin' );

/** MySQL database password */
define( 'DB_PASSWORD', 'Admin@123' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'TYtd_1#1(DQMAyQQ0/|{V5Q4+iIVX7V^ unq8UBG|vueRoZ*7Gwq7^7f$`-p9[}+' );
define( 'SECURE_AUTH_KEY',  'l%$u@4C9VqquCMzZ#Gcc7u=GhW%]p,ajKG[AP?G/=Ta80Vo)G&A^((cU8oX<!sG2' );
define( 'LOGGED_IN_KEY',    's>JM2&);ru4:k-Pb(wV#Lo1)NXo:0`iF;;w6a`Kf:Oe[+PEO!rD[`uOitqMC;w1e' );
define( 'NONCE_KEY',        ' LCHu0>lS|5}HIj^gXyak>*rNZyq8w,1C0;~|xr%x09Z[v WcIi:mPp==k)nSO/L' );
define( 'AUTH_SALT',        'LYN*LTG0_P_Rf6moV9<#nKL^]@siL#(~`<8_0O^Tx9i97d>OyGW|WPmHj,e2oV*.' );
define( 'SECURE_AUTH_SALT', 'T;9v7S`hi%4*sc>$3(5:LF{h6!kDu$34UzfxdY5)`m3iz(.en&gJJ,h2Cx%3i6aO' );
define( 'LOGGED_IN_SALT',   'imjmU9o%F8R1_HE/`3@22v*hdg4h;V=tCX.==3e)%uCn*7f%MgK[[5Wdda_Cl9+z' );
define( 'NONCE_SALT',       'BkZAC$<-6TfUknL,8FC@jHiB[MT2-e<N:mvtjp;QV12Gg4m/EBVXRR|cJ6H>YT5Y' );

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
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

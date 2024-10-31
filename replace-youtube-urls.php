<?php
/**
 * Plugin Name: Replace YouTube URLs WP-CLI Command
 * Description: Custom WP-CLI command to replace YouTube URLs in post content.
 * Version: 1.0
 * Author: WordPress.com Special Projects
 * Author URI: https://wpspecialprojects.wordpress.com/
 * License: GPLv3
 */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once __DIR__ . '/class-replace-youtube-urls-command.php';
}
?>

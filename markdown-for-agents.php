<?php
/**
 * Plugin Name: Markdown for Agents
 * Plugin URI:  https://github.com/greg-randall/markdown-for-agents
 * Description: Serve published posts and pages as clean Markdown for AI agents and crawlers.
 * Version:     1.1.1
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      Greg Randall
 * Author URI:  https://gregr.org
 * License:     GPL-2.0-only
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MFA_VERSION', '1.1.1' );
define( 'MFA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Require Composer autoloader.
$mfa_autoload = MFA_PLUGIN_DIR . 'vendor/autoload.php';

if ( ! file_exists( $mfa_autoload ) ) {
    add_action( 'admin_notices', function () {
        printf(
            '<div class="notice notice-error"><p><strong>Markdown for Agents:</strong> '
            . 'Dependencies not installed. Run <code>composer install</code> in <code>%s</code>.</p></div>',
            esc_html( MFA_PLUGIN_DIR )
        );
    } );
    return;
}

require_once $mfa_autoload;
require_once MFA_PLUGIN_DIR . 'includes/routing.php';
require_once MFA_PLUGIN_DIR . 'includes/converter.php';

/**
 * Rewrite rule management.
 *
 * WordPress stores rewrite rules in the database. Our .md suffix rule
 * (registered in routing.php) must be flushed into that stored set whenever
 * it might be missing or stale:
 *
 *  - On activation:   rule doesn't exist yet.
 *  - On deactivation: rule should be removed so it doesn't 404 without us.
 *  - On version bump: rule pattern may have changed between releases.
 *
 * flush_rewrite_rules() is expensive (rewrites the .htaccess on Apache),
 * so we gate the version check behind a cheap option comparison.
 */
add_action( 'init', function () {
    $current_version = get_option( 'mfa_version', '0.0.0' );
    if ( version_compare( $current_version, MFA_VERSION, '<' ) ) {
        flush_rewrite_rules();
        update_option( 'mfa_version', MFA_VERSION, true );
    }
} );

register_activation_hook( __FILE__, function () {
    // routing.php is already included above, so the add_rewrite_rule()
    // call has fired and the rule is registered before we flush.
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/**
 * Wipe the entire markdown cache when any plugin is activated, deactivated,
 * or switched themes. Filters like markdown_output, markdown_frontmatter,
 * and markdown_clean_html may have changed — cached output is untrusted.
 */
add_action( 'activated_plugin', 'mfa_flush_entire_cache' );
add_action( 'deactivated_plugin', 'mfa_flush_entire_cache' );
add_action( 'switch_theme', 'mfa_flush_entire_cache' );

function mfa_flush_entire_cache(): void {
    $upload_dir = wp_upload_dir();
    $cache_dir  = $upload_dir['basedir'] . '/mfa-cache';

    if ( ! is_dir( $cache_dir ) ) {
        return;
    }

    mfa_rmdir_contents( $cache_dir );
    mfa_protect_directory( $cache_dir );
}

/**
 * Recursively delete all files inside a directory, preserving the directory itself.
 */
function mfa_rmdir_contents( string $dir ): void {
    $entries = @scandir( $dir );
    if ( false === $entries ) {
        mfa_log( 'failed to scan cache directory: ' . $dir );
        return;
    }

    foreach ( $entries as $entry ) {
        if ( '.' === $entry || '..' === $entry ) {
            continue;
        }

        $path = $dir . '/' . $entry;

        // Never follow symlinks — remove the link itself and move on.
        if ( is_link( $path ) ) {
            wp_delete_file( $path );
            continue;
        }

        if ( is_dir( $path ) ) {
            mfa_rmdir_contents( $path );
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- no wp_rmdir() exists; deleting our own cache directory.
            @rmdir( $path );
        } else {
            mfa_safe_unlink( $path );
        }
    }
}
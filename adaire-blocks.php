<?php
/**
 * Plugin Name: Adaire Blocks Diactivation/Detele Intent
 * Plugin URI: https://adaire.digital/
 * Description: Professional WordPress blocks for Gutenberg editor with GSAP animations and modern design.
 * Version: 1.1.8
 * Author: Adaire
 * Author URI: https://adaire.digital/
 * License: GPL-3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: adaire-blocks
 * Domain Path: /languages
 * Requires at least: 6.7
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ADAIRE_BLOCKS_VERSION', '1.1.8');
define('ADAIRE_BLOCKS_PLUGIN_FILE', __FILE__);
define('ADAIRE_BLOCKS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ADAIRE_BLOCKS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ADAIRE_BLOCKS_IS_FREE', true);

// Include the main plugin class
require_once ADAIRE_BLOCKS_PLUGIN_PATH . 'includes/class-adaire-blocks-config.php';
require_once ADAIRE_BLOCKS_PLUGIN_PATH . 'includes/sendgrid.php';

// Initialize the plugin
function adaire_blocks_init()
{
    // Get settings instance
    $settings = AdaireBlocksConfig::get_instance();

    // Register blocks
    adaire_blocks_register_blocks();
}
add_action('init', 'adaire_blocks_init');

/**
 * Register all blocks
 */
function adaire_blocks_register_blocks()
{
    $blocks_dir = ADAIRE_BLOCKS_PLUGIN_PATH . 'build/';

    if (!is_dir($blocks_dir)) {
        return;
    }

    $block_dirs = glob($blocks_dir . '*', GLOB_ONLYDIR);

    foreach ($block_dirs as $block_dir) {
        $block_name = basename($block_dir);
        $block_json = $block_dir . '/block.json';

        if (file_exists($block_json)) {
            register_block_type($block_json);
        }
    }
}

/**
 * Enqueue block assets
 */
function adaire_blocks_enqueue_assets()
{
    $blocks_dir = ADAIRE_BLOCKS_PLUGIN_PATH . 'build/';

    if (!is_dir($blocks_dir)) {
        return;
    }

    $block_dirs = glob($blocks_dir . '*', GLOB_ONLYDIR);

    foreach ($block_dirs as $block_dir) {
        $block_name = basename($block_dir);
        $asset_file = $block_dir . '/index.asset.php';

        if (file_exists($asset_file)) {
            $asset = require $asset_file;
            $dependencies = $asset['dependencies'] ?? [];
            $version = $asset['version'] ?? ADAIRE_BLOCKS_VERSION;

            // Enqueue block script
            wp_enqueue_script(
                'adaire-blocks-' . $block_name,
                ADAIRE_BLOCKS_PLUGIN_URL . 'build/' . $block_name . '/index.js',
                $dependencies,
                $version,
                true
            );

            // Enqueue block style
            $style_file = $block_dir . '/style-index.css';
            if (file_exists($style_file)) {
                wp_enqueue_style(
                    'adaire-blocks-' . $block_name . '-style',
                    ADAIRE_BLOCKS_PLUGIN_URL . 'build/' . $block_name . '/style-index.css',
                    [],
                    $version
                );
            }
        }
    }
}
add_action('wp_enqueue_scripts', 'adaire_blocks_enqueue_assets');
add_action('enqueue_block_editor_assets', 'adaire_blocks_enqueue_assets');

// Bootstrap admin settings (register menu, assets, etc.)
if (is_admin()) {
    require_once ADAIRE_BLOCKS_PLUGIN_PATH . 'admin/settings-page.php';
    if (class_exists('AdaireBlocksSettings')) {
        AdaireBlocksSettings::get_instance();
    }

    // Include block migration tool
    require_once ADAIRE_BLOCKS_PLUGIN_PATH . 'admin/block-migration.php';

    // Deactivation feedback modal
    require_once ADAIRE_BLOCKS_PLUGIN_PATH . 'admin/deactivation-modal.php';
    Adaire_Deactivation_Modal::get_instance();

    // Deactivation feedback log + test page (remove for production)
    require_once ADAIRE_BLOCKS_PLUGIN_PATH . 'admin/deactivation-log-page.php';
    Adaire_Deactivation_Log_Page::get_instance();

}

/**
 * Plugin activation hook
 */
function adaire_blocks_activate()
{
    // Set default options
    add_option('adaire_blocks_version', ADAIRE_BLOCKS_VERSION);
}
register_activation_hook(__FILE__, 'adaire_blocks_activate');

/**
 * Plugin deactivation hook
 */
function adaire_blocks_deactivate()
{
    // Clean up if needed
}
register_deactivation_hook(__FILE__, 'adaire_blocks_deactivate');

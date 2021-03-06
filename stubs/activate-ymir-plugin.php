<?php

function activate_ymir_plugin() {
    if (defined('WP_INSTALLING') && WP_INSTALLING) {
        return;
    } elseif (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    // Important to not activate the plugin if it's already active. It's fine for normal sites, but
    // it breaks the backbone media uploader with multisite due to a weird edgecase with the "networkwide"
    // query value being set globally.
    foreach (get_plugins() as $file => $plugin) {
        if (1 !== preg_match('/ymir\.php$/', $file)) {
            continue;
        } elseif (defined('MULTISITE') && MULTISITE && !is_plugin_active_for_network($file)) {
            activate_plugin($file, '', true);
        } elseif ((!defined('MULTISITE') || !MULTISITE) && !is_plugin_active($file)) {
            activate_plugin($file);
        }
    }
}
add_action('plugins_loaded', 'activate_ymir_plugin');

/**
 * Ensures that the plugin is always the first one to be loaded per site.
 */
function ensure_ymir_plugin_loaded_first(array $active_plugins): array
{
    foreach ($active_plugins as $key => $basename) {
        if (1 === preg_match('/ymir\.php$/', $basename)) {
            array_splice($active_plugins, $key, 1);
            array_unshift($active_plugins, $basename);
        }
    }

    return $active_plugins;
}
add_filter('pre_update_option_active_plugins', 'ensure_ymir_plugin_loaded_first', 9999);

/**
 * Ensures that the plugin is always the first one to be loaded for the network.
 */
function ensure_ymir_plugin_loaded_first_on_network(array $active_plugins): array
{
    $active_plugins = array_keys($active_plugins);

    foreach ($active_plugins as $index => $plugin) {
        if (1 === preg_match('/ymir\.php$/', $plugin)) {
            array_splice($active_plugins, $index, 1);
            array_unshift($active_plugins, $plugin);
        }
    }

    return array_fill_keys($active_plugins, time());
}
add_filter('pre_update_site_option_active_sitewide_plugins', 'ensure_ymir_plugin_loaded_first_on_network', 9999);

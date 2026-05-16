<?php
/**
 * Uninstall handler for RenderWhen.
 *
 * This file runs when the user deletes the plugin from the Plugins screen
 * (not on deactivation). It must be safe to run on a site where the plugin
 * was never fully activated.
 *
 * v1.0 of RenderWhen stores no options, no custom tables, no transients,
 * and no user meta. Block visibility rules live in post content as block
 * attributes, which we deliberately leave alone — removing the plugin
 * should not modify the user's content.
 *
 * This file exists so the WordPress.org plugin review team sees that
 * uninstall behaviour is considered, and so that future versions adding
 * persistent data have an obvious place to clean up.
 *
 * @package RenderWhen
 */
 
// Bail if WordPress is not invoking this file via the uninstall hook.
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
 
/*
 * Intentionally empty — no persistent data to remove in v1.0.
 *
 * Future versions adding options, transients, or custom tables should
 * delete them here. Use delete_option(), delete_site_option() (multisite),
 * delete_transient(), and direct $wpdb queries for custom tables.
 *
 * For multisite installs, iterate over sites with get_sites() and call
 * switch_to_blog() / restore_current_blog() around per-site cleanup.
 */
<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Rcb_Comments
 * @subpackage Rcb_Comments/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Rcb_Comments
 * @subpackage Rcb_Comments/includes
 * @author     Your Name <email@example.com>
 */
class Rcb_Comments_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $existsSettings = get_option( 'rcb_comments_settings' );

        if(empty($existsSettings)) {
            $defaultSettings = [
                'sync' => 1,
                'counter' => 1
            ];

            update_option('rcb_comments_settings', $defaultSettings);
        }

        $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";

        $tableChannels = $wpdb->get_blog_prefix() . 'recobox_channels';
        $sql = "CREATE TABLE {$tableChannels} (
                    id int(11) unsigned NOT NULL auto_increment,
                    post_id text NOT NULL default '',
                    recobox_id text NOT NULL default '',
                    rating text NOT NULL default '',
                    PRIMARY KEY  (id)
                ) {$charset_collate};";

        dbDelta( $sql );
	}

}

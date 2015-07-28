<?php
/**
 * Installation related functions and actions.
 *
 * @author 		Actsupport
 * @category 	Admin
 * @package 	ActStopSpam/Classes
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'ACT_STOP_SPAM_Install' ) ) :
/**
 * ACT_STOP_SPAM_Install Class
 */
class ACT_STOP_SPAM_Install {
	/**
	 * Instance of this class.
	 * @since    1.0
	 */
	protected static $instance = null;
	
    public function __construct() { 
	    add_filter( 'act_stop_spam_settings',  array( $this, 'upgrade_settings' ) );
	}
		
	/**
	 * Main Act Stop Spam Instance
	 * @since 1.0
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * Init function Act Stop Spam
	 * @since 1.0
	 */
	public static function init() {
		add_filter( 'plugin_action_links_' . ACT_PLUGIN_STOPSPAM, array( __CLASS__, 'plugin_action_links' ) );
	}
	/**
	 * Act Stop Spam Plugin Activation
	 * @since 1.0
	 */
	public static function activate() { 
		$settings=array();
		$settings['ACT_STOP_SPAM_CONFIDANCE']=30;
		$settings['ACT_STOP_SPAM_MESSAGE']="Your public IP address is blacklisted in stopforumspam.com";
        Act_Stop_Spam::update_settings( $settings ); 
		self::createdb();
	}
	/**
	 * Show action links on the plugin screen.
	 *
	 * @param	mixed $links Plugin Action links
	 * @return	array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=act-settings' ) . '" title="' . esc_attr( __( 'View Act Stop Spam Settings', 'actstopspam' ) ) . '">' . __( 'Settings', 'actstopspam' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}
	/**
	 * Act Stop Spam Plugin Deactivation function
	 * @since 1.0
	 */
    public static function deactivate (){
		 self::uninstall();
		 
    } 
		 
	/**
	 * Uninstall function Removes ACT_STOP_SPAM_KEY and act_stop_spam table.
	 * @since 1.0
	 */
	private static function uninstall() {
		global $wpdb;
		delete_option('ACT_STOP_SPAM_KEY');
		$wpdb->hide_errors();
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}act_stop_spam" );
	}
	/**
	 * Create Database
	 * @since 1.0
	 */
	private static function createdb() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->hide_errors();
		dbDelta(self::dbschema());
	}
		
	/**
	 * Get Table dbschema
	 * @return string
	 */
	private static function dbschema() {
		global $wpdb;
		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}
		return "
CREATE TABLE {$wpdb->prefix}act_stop_spam (
  act_id bigint(20) NOT NULL auto_increment,
  ip_address varchar(200) NOT NULL,
  first_visit datetime DEFAULT NULL,
  latest_visit datetime DEFAULT NULL,
  occurrence bigint(20) NOT NULL,
  PRIMARY KEY  (act_id),
  KEY ip_address (ip_address)
) $collate;";
	}
	
}
endif;
ACT_STOP_SPAM_Install::init();
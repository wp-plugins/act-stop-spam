<?php
/**
 * Plugin Name:       Act Stop Spam
 * Plugin URI:        http://www.actsupport.com
 * Description:       Act Stop Span is an automated plugin to check and block IP Address based on StopForumSpam.com blacklisted ip database. Checks only the IP address and takes advantage of Stop Forum Spam's "confidence" assessment.
 * Version:           1.0
 * Author:            ActSupport <plugin@actsupport.com>
 * Author URI:        http://www.actsupport.com
 * Text Domain:       act-stop-forum
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *
 */


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Act_Stop_Spam' ) ) :


final class Act_Stop_Spam {
	
	/**
    *
    * @var string
    */
	private $Act_api_url="http://www.stopforumspam.com/api";
	public $version = '1.0';
	/**
	 * @var string
	 * Note that it should match the Text Domain file header in this file
	 */
	public $Act_Plugin = 'act-stop-spam';

	/**
	 * @var Act Stop Spam The single instance of the class
	 * @since 1.0
	 */
	protected static $_instance = null;

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'act-stop-spam' ), $this->version );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'act-stop-spam' ), $this->version );
	}
	
	/**
    * Constructor.
    *
    * @param string $api_url.
    */
    public function __construct( $api_url = null ) {
		define('ACT_PLUGIN_STOPSPAM',$this->Act_Plugin);
		if(!empty($api_url))
        $this->Act_api_url = $api_url;
		$this->includes();
		$this->Act_setup();
		
	 /*
	 * Register hooks that are fired when the plugin is activated  
	 * When the plugin is deleted, the uninstall.php file is loaded.
	 */
	register_activation_hook( __FILE__, array( 'ACT_STOP_SPAM_Install', 'activate' ) );
    register_deactivation_hook( __FILE__, array( 'ACT_STOP_SPAM_Install', 'deactivate' ) );

    }
	
	/**
	 * Main Act Stop Spam Instance
	 * @since 1.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * Stop Forum Spam Donation Link to support StopForumSpam.com.
	 * @access		private
	 * @version		1.0
	 * @since		1.0
	 */
	public function act_stop_spam_links($links="", $file="")
	{
	if($file=="")
	$file=$this->Act_Plugin;
	if ($file == plugin_basename(__FILE__))
	{
		
		$links[] = '<a href="http://www.stopforumspam.com/donate" title="StopForumSpam.com Help Keep Us Online" target="_blank">StopForumSpam.com Donate</a>';
	}
	return $links;
	}
	/**
	 * Act menu Setup for the Act Stop Spam
	 * @access		private
	 * @version		1.0
	 * @since		1.0
	 */
	private function Act_setup(){
		add_filter('plugin_row_meta', array( $this, 'act_stop_spam_links' ),10,2);
		add_action('admin_menu', array( &$this, 'admin_menu' ) );
        add_filter('act_stop_spam_settings', array( $this, 'get_settings') );
	}
	/**
	 * Includes installation class for the Act Stop Spam
	 * @access		private
	 * @version		1.0
	 * @since		1.0
	 */
	private function includes() {
		include_once( 'includes/class-act-install.php' );
	}
	/**
	 * Renders and handles the options page for the Act Stop Spam
	 * @access		public
	 * @version		1.0
	 * @since		1.0
	 */
	public function admin_options()
	{
		if (! current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		$actstop_settings=$this->get_settings();
		$actstopspam_confidence =$actstop_settings['ACT_STOP_SPAM_CONFIDANCE'];
		$actstopspam_BlockMessage	=$actstop_settings['ACT_STOP_SPAM_MESSAGE'];
		
		if ( isset( $_POST['ActStopSpamUpdate'] ) ) {
			$actstopspam_confidence			= $_POST['actstopspam_confidence'];
			$actstopspam_BlockMessage	= $_POST['actstopspam_BlockMessage'];
			$settings=array();
			$settings['ACT_STOP_SPAM_CONFIDANCE']=$actstopspam_confidence	;
			$settings['ACT_STOP_SPAM_MESSAGE']=$actstopspam_BlockMessage;
			$status =$this->update_settings( $settings ); 
		}else{
			$status =true;	
		}

		if ( file_exists( plugin_dir_path( __FILE__ ).'act-stop-spam-options.php' ) ) {
			include_once( 'act-stop-spam-options.php' );
		}
	}
	/**
	 * @access		private
	 * @param		IpAddress
	 * @return		string
	 * @since		1.0
	 */
	private function Client_Address(){
		$ipaddress=$_SERVER["HTTP_CLIENT_IP"]?$_SERVER["HTTP_CLIENT_IP"]:($_SERVER["HTTP_X_FORWARDED_FOR"]?$_SERVER["HTTP_X_FORWARDED_FOR"]:$_SERVER["REMOTE_ADDR"]);
		return $ipaddress;
	}
	/**
	 * @access		public
	 * @param		none
	 * @return		none
	 * @since		1.0
	 */
	public  function act_stop_spam(){
		global $wpdb;
		$GuestIp = $this->Client_Address();
		$JsonLink = $this->Act_api_url."?&ip=".urlencode($GuestIp)."&f=json";
	if(function_exists('file_get_contents')){
		$JsonData = json_decode(file_get_contents($JsonLink));
	}elseif(function_exists('curl_init')){
		$JsonData=json_decode(url_get_contents($JsonLink));
	}
	if($JsonData->success==1){
		$Appears=$JsonData->ip->appears;
		$actstop_option=get_option(ACT_STOP_SPAM_KEY);
		if($Appears==1&&$JsonData->ip->confidence>=$actstop_option['ACT_STOP_SPAM_CONFIDANCE']){
		$act_stop_spam_message=$actstop_option['ACT_STOP_SPAM_MESSAGE'];
		if(empty($act_stop_spam_message))
		$act_stop_spam_message="Your public IP address is blacklisted in stopforumspam.com";
		echo '<div style="width:100%;"><div style="text-align:center">'.$act_stop_spam_message.'</div></div>';
		$wpdb->hide_errors();
		$result = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}act_stop_spam WHERE ip_address = '".$_SERVER['REMOTE_ADDR']."'", OBJECT );
		if($result->act_id>0){
		$wpdb->query( "update {$wpdb->prefix}act_stop_spam set ip_address='".$_SERVER['REMOTE_ADDR']."' , latest_visit='".date("Y-m-d H:i:s")."',occurrence='".($result->occurrence+1)."' where act_id='".$result->act_id."'");	
		}else{
		$wpdb->query( "insert into {$wpdb->prefix}act_stop_spam set ip_address='".$_SERVER['REMOTE_ADDR']."' ,first_visit='".date("Y-m-d H:i:s")."' , latest_visit='".date("Y-m-d H:i:s")."',occurrence=1");
		}
		//Stops Page Loading for Blacklisted IP and Prevent Spam Posts. 
		exit;
		}
		
	}
	}
	/**
	 * Grab url contents through Curl function
	 * @access		public
	 * @return		string
	 * @since		1.0
	 */
	public function url_get_contents($Url) {
    if (!function_exists('curl_init')){ 
        die('CURL is not installed!');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
	}
	/**
     * Get all settings. Settings are stored as an array
     * with key ACT_STOP_SPAM_KEY
     */
     public static function get_settings(){
        return get_option( ACT_STOP_SPAM_KEY );
     }
    /**
	 * Generates the menu item
	 * @access		public
	 * @version		1.0
	 * @since		1.0
	 */
	public function admin_menu()
	{
		add_options_page('Act Stop Spam Settings', 'Act Stop Spam', 'manage_options', 'act_stop_spam_settings_menu', array( &$this, 'admin_options' ) );
		$hook = add_menu_page( 'Act Stop Spam', 'Act Stop Spam', 'activate_plugins', 'act_stop_spam_list',array( &$this, 'act_stop_spam_list_page') ,'dashicons-shield',39);
  		add_action( "load-$hook", array( &$this,'add_options' ));
	}
	/**
	 * Generates the Option and data for Act Stop Spam page
	 * @access		public
	 * @version		1.0
	 * @since		1.0
	 */
	public function add_options() {
  		global $myListTable;
  		$option = 'per_page';
  		$args = array(
         'label' => 'Act Stop Spam',
         'default' => 25,
         'option' => 'ip_per_page'
        );
  		add_screen_option( $option, $args );
  	if(file_exists( plugin_dir_path( __FILE__ ).'/includes/class-act-table.php' ) ) {
		include_once( 'includes/class-act-table.php' );
	}
  	$myListTable = new Act_Stop_Spam_Table();
	}
	/**
	 * Generates Html code for Act Stop Spam page
	 * @access		public
	 * @version		1.0
	 * @since		1.0
	 */
	public function act_stop_spam_list_page(){
  		global $myListTable;
  		echo '</pre><div class="wrap"><h2><span class="dashicons dashicons-shield act-shield"></span> Act Stop Spam</h2>'; 
  		$myListTable->prepare_items(); 
		echo '<form method="post">
    	<input type="hidden" name="page" value="ttest_list_table">';
   		$myListTable->search_box( 'search', 'search_id' );
  		$myListTable->display(); 
  		echo '</form></div>'; 
	}
	
    /**
     * Update settings. 
     * @TODO Change this to use a filter
     */
    public static function update_settings( $act_settings ){             
         return update_option( ACT_STOP_SPAM_KEY, $act_settings );
     }
}//Class

endif;

/**
 *
 * @since  1.0
 */
function ACT_SUPPORT() {
	return Act_Stop_Spam::instance();
}

// Global for backwards compatibility.
$GLOBALS['act_data'] = ACT_SUPPORT();
function act_stop_spam(){
	$GLOBALS['act_data']->act_stop_spam();
}
include_once(ABSPATH . "wp-includes/pluggable.php");
include_once(ABSPATH . "wp-admin/includes/plugin.php");
//Spam IP Protection Check for Guest Users
if (!is_user_logged_in())
{
	add_action('init','act_stop_spam');
}
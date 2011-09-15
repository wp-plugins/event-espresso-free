<?php
/*
Plugin Name: Event Espresso Lite - Event Management and Registration System
Plugin URI: http://eventespresso.com/
Description: Out-of-the-box Events Registration integrated with PayPal IPN for your Wordpress blog/website. <a href="admin.php?page=support" >Support</a>

Reporting features provide a list of events, list of attendees, and excel export.

Version: 3.0.19.48.L

Author: Seth Shoultes
Author URI: http://www.eventespresso.com

Copyright (c) 2008-2011 Event Espresso  All Rights Reserved.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

//Define the version of the plugin
function espresso_version() {
	return '3.0.19.48.L';
}

function ee_init_session()
{
global $org_options;

        session_start();
        if((isset($_REQUEST['page_id']) && ($_REQUEST['page_id'] == $org_options['return_url'] || $_REQUEST['page_id'] == $org_options['notify_url'])) || $_SESSION['espresso_session_id'] == '')
        {
            session_regenerate_id(true);
            $_SESSION['espresso_session_id'] = '';
            $_SESSION['events_in_session'] = '';
            $_SESSION['event_espresso_pre_discount_total'] = 0;
            $_SESSION['event_espresso_grand_total'] = 0;
            $_SESSION['event_espresso_coupon_code'] = '';

        }

            $_SESSION['espresso_session_id'] = session_id();

}

if (!session_id() || $_SESSION['espresso_session_id'] == '' || !isset($_SESSION['espresso_session_id']) ) {
	add_action('init', 'ee_init_session', 1);
} 
add_action('init','ee_check_for_export');

function ee_check_for_export(){
	if (isset($_REQUEST['export'])){
		if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/functions/export.php')){
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/functions/export.php');
			espresso_export_stuff();
		}
	}
}

function espresso_info_header() {
	print( "<meta name='generator' content='Event Espresso Version " . EVENT_ESPRESSO_VERSION . "' />");
}
add_action('wp_head', 'espresso_info_header');

//Globals
global $org_options;
$org_options = get_option('events_organization_settings');
$page_id = isset($_REQUEST['page_id'])?$_REQUEST['page_id']:'';

//regevent_action is only set during the checkout process
if ( isset($_REQUEST['regevent_action']) && $org_options['event_ssl_active'] == 'Y' && !is_ssl() && !is_admin()) {

            $wp_ssl_url = str_replace( 'http://' , 'https://' , home_url() );

            $url = $wp_ssl_url . $_SERVER['REQUEST_URI'];
            header("Location:$url");
            exit;

//The only way that I can make the menu links non ssl
//but am afraid this may break another plugin that uses ssl.
//Will wait for feedback
//Have a little extra specificity..
//added page_id check for iDEAL mollie.  Hard to tell from Dutch translation but it looks like they need an SSL for the notify page.
 } elseif( (!isset($_REQUEST['regevent_action']) 
         && (!isset($_POST['firstdata'])
         && !isset($_POST['authnet_aim'])
         && !isset($_POST['paypal_pro'])
         && $page_id != $org_options['notify_url']
         && $page_id != $org_options['return_url']
         && $page_id != $org_options['cancel_return']
         && !isset($_GET['transaction_id'])))
         && $org_options['event_ssl_active'] == 'Y' && is_ssl() && !is_admin()){

     $wp_ssl_url = str_replace( 'https://' , 'http://' , home_url() );

            $url = $wp_ssl_url . $_SERVER['REQUEST_URI'];
            header("Location:$url");
            exit;

 }

 //This will (should) make sure everything is loaded via SSL
 //So that the "..not everything is secure.." message doesn't appear
 //Still will be a problem if other themes and plugins do not implement ssl correctly
 $wp_plugin_url = WP_PLUGIN_URL;
 $wp_content_url = WP_CONTENT_URL;

 if (is_ssl()){

    $wp_plugin_url = str_replace( 'http://' , 'https://' ,WP_PLUGIN_URL );
	$wp_content_url = str_replace( 'http://' , 'https://' ,WP_CONTENT_URL );

 }

define("EVENT_ESPRESSO_VERSION", espresso_version() );
define('EVENT_ESPRESSO_POWERED_BY', 'Event Espresso - ' . EVENT_ESPRESSO_VERSION);
//Define the plugin directory and path
define("EVENT_ESPRESSO_PLUGINPATH", "/" . plugin_basename( dirname(__FILE__) ) . "/");
define("EVENT_ESPRESSO_PLUGINFULLPATH", WP_PLUGIN_DIR . EVENT_ESPRESSO_PLUGINPATH  );
define("EVENT_ESPRESSO_PLUGINFULLURL", $wp_plugin_url . EVENT_ESPRESSO_PLUGINPATH );
//End - Define the plugin directory and path

//Define dierectory structure for uploads
if ( !defined('WP_CONTENT_DIR') ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content');
}
$upload_path = WP_CONTENT_DIR."/uploads";
$event_espresso_upload_dir = "{$upload_path}/espresso/";
$event_espresso_template_dir = "{$event_espresso_upload_dir}templates/";

$includes_directory = EVENT_ESPRESSO_PLUGINFULLPATH.'includes/';
define("EVENT_ESPRESSO_INCLUDES_DIR", $includes_directory);

define("EVENT_ESPRESSO_UPLOAD_DIR", $event_espresso_upload_dir);
define("EVENT_ESPRESSO_UPLOAD_URL", $wp_content_url . '/uploads/espresso/');
define("EVENT_ESPRESSO_TEMPLATE_DIR", $event_espresso_template_dir);
$event_espresso_gateway_dir = EVENT_ESPRESSO_UPLOAD_DIR."gateways/";
define("EVENT_ESPRESSO_GATEWAY_DIR", $event_espresso_gateway_dir);
define("EVENT_ESPRESSO_GATEWAY_URL", $wp_content_url . '/uploads/espresso/gateways/');
//End - Define dierectory structure for uploads

require_once EVENT_ESPRESSO_PLUGINFULLPATH . 'class/SimpleMath.php';
global $simpleMath;
$simpleMath  = new SimpleMath();

//Set the default time zone
//If the default time zone is set up in the WP Settings, then we will use that as the default.
if (get_option('timezone_string') != ''){
	date_default_timezone_set(get_option('timezone_string'));
}

//Build the addon files
if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/addons_includes.php')){
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/addons_includes.php');
}

//Call the required function files
require_once("includes/functions/main.php");
require_once("includes/functions/admin.php");
require_once("includes/functions/time_date.php");

//Install/Update Tables when plugin is activated
require_once("includes/database_install.new.php");
register_activation_hook(__FILE__,'events_data_tables_install');

//Define all of the plugins database tables
define("EVENTS_CATEGORY_TABLE", get_option('events_category_detail_tbl') );
define("EVENTS_CATEGORY_REL_TABLE", get_option('events_category_rel_tbl') );
define("EVENTS_DETAIL_TABLE", get_option('events_detail_tbl') );
define("EVENTS_ORGANIZATION_TABLE", get_option('events_organization_tbl') );
define("EVENTS_ATTENDEE_TABLE", get_option('events_attendee_tbl') );
define("ADDITIONAL_ATTENDEES_TABLE", get_option('additional_attendees_tbl') );
define("EVENTS_START_END_TABLE", get_option('events_start_end_tbl') );
define("EVENTS_PAYMENT_GATEWAYS_TABLE", get_option('events_payment_gateways_tbl') );
define("EVENTS_QUESTION_TABLE", get_option('events_question_tbl') );
define("EVENTS_QST_GROUP_REL_TABLE", get_option('events_qst_group_rel_tbl') );
define("EVENTS_QST_GROUP_TABLE", get_option('events_qst_group_tbl') );
define("EVENTS_ANSWER_TABLE",get_option('events_answer_tbl') );
define("EVENTS_DISCOUNT_CODES_TABLE", get_option('events_discount_codes_tbl') );
define("EVENTS_DISCOUNT_REL_TABLE", get_option('events_discount_rel_tbl') );
define("EVENTS_PRICES_TABLE", get_option('events_prices_tbl') );
define("EVENTS_EMAIL_TABLE", get_option('events_email_tbl') );
define("EVENTS_SESSION_TABLE", get_option('events_sessions_tbl') );
define("EVENTS_VENUE_TABLE", get_option('events_venue_tbl') );
define("EVENTS_VENUE_REL_TABLE", get_option('events_venue_rel_tbl') );
define("EVENTS_LOCALE_TABLE", get_option('events_locale_tbl') );
define("EVENTS_LOCALE_REL_TABLE", get_option('events_locale_rel_tbl') );
define("EVENTS_PERSONNEL_TABLE", get_option('events_personnel_tbl') );
define("EVENTS_PERSONNEL_REL_TABLE", get_option('events_personnel_rel_tbl') );


//Wordpress function for setting the locale.
//print get_locale();
//setlocale(LC_ALL, get_locale());
setlocale(LC_TIME, get_locale());

//Get language files
load_plugin_textdomain( 'event_espresso', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

//Registration forms
require_once("includes/event_espresso_form_build.inc.php");

//New form builder
require_once("includes/form-builder/index.php");
require_once("includes/form-builder/groups/index.php");

//Payment/Registration Processing - Used to display the payment options and the payment link in the email. Used with the [ESPRESSO_PAYMENTS] tag
require_once("includes/process-registration/payment_page.php");

//Add attendees to the database
require_once("includes/process-registration/add_attendees_to_db.php");

//Payment processing - Used for onsite payment processing. Used with the [ESPRESSO_TXN_PAGE] tag
event_espresso_require_gateway('process_payments.php');

//Get the payment settings page
event_espresso_require_gateway('payment_gateways.php');

//Get the payment gateways class
event_espresso_require_gateway('PaymentGateway.php');

/*Core template files used by this plugin*/
//Events Listing - Shows the events on your page. Used with the [ESPRESSO_EVENTS] tag
event_espresso_require_template('event_list.php');

//This is the form page for registering the attendee
event_espresso_require_template('registration_page.php');

//List Attendees - Used with the [LISTATTENDEES] shortcode
event_espresso_require_template('attendee_list.php');
/*End Core template files used by this plugin*/

//Widget - Display the list of events in your sidebar
//The widget can be over-ridden with the custom files addon
event_espresso_require_template('widget.php');

function load_event_espresso_widget() {
	register_widget( 'Event_Espresso_Widget' );
}
add_action( 'widgets_init', 'load_event_espresso_widget' );

//Admin Widget - Display event stats in your admin dashboard
event_espresso_require_file('dashboard_widget.php', EVENT_ESPRESSO_PLUGINFULLPATH."includes/admin-files/", '', false, true);

//Event Registration Subpage - Configure Organization
require_once("includes/organization_config.php");

//Event Registration Subpage - Add/Delete/Edit Events
require_once("includes/event-management/index.php");

//Event Registration Subpage - Add/Delete/Edit Discount Codes
if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/coupon-management/index.php')){
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/coupon-management/index.php');
	//Include dicount codes
	require_once("includes/admin-files/coupon-management/use_coupon_code.php");
}else{
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/coupon_management.php');
}

//Event Registration Subpage - Add/Delete/Edit Venues
if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/venue-management/index.php')){
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/venue-management/index.php');
}else{
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/venue_management.php');
}

//Event Registration Subpage - Add/Delete/Edit Locales
if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/locale-management/index.php')){
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/locale-management/index.php');
}else{
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/locale_management.php');
}


//Event Registration Subpage - Add/Delete/Edit Staff
if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/staff-management/index.php')){
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/staff-management/index.php');
}else{
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/staff-management.php');
}

//Event Registration Subpage - Admin Reporting
//require_once("includes/admin-reports/index.php");

//Event Registration Subpage - Category Manager
require_once("includes/category-management/index.php");

//Event Registration Subpage - Email Manager
if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/email-manager/index.php')){
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/email-manager/index.php');
}else{
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/email-manager.php');
}

//Event Registration Subpage - Plugin Support
require_once("includes/admin_support.php");

//Process email confirmations
require_once("includes/functions/email.php");

//Process email confirmations
require_once("includes/functions/attendee_functions.php");

if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/functions.php')){
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/functions.php');
	global $espresso_premium;
	$espresso_premium = espresso_system_check();
}

if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/admin_addons.php')){
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/admin_addons.php');
}else{
	require_once(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin_addons.php');
}

if (file_exists(EVENT_ESPRESSO_UPLOAD_DIR . "/ticketing/template.php")){
	global $ticketing_installed;
	$ticketing_installed = true;
}


//Core shortcode support
require_once("includes/shortcodes.php");

//Premium upgrade options
require_once("includes/premium_upgrade.php");
/*
 *
 * turning off db session handling for now
 * will turn it back on after the multi reg is ready
 *
 */
//require_once("includes/functions/session.php");
/* Set the session to expire in 30 minutes
ini_set('session.gc_maxlifetime',30*60);
ini_set('session.gc_probability',1);
ini_set('session.gc_divisor',1);
$session = new Session();
session_set_save_handler(array($session, 'open'),
                         array($session, 'close'),
                         array($session, 'read'),
                         array($session, 'write'),
                         array($session, 'destroy'),
                         array($session, 'gc'));

*/
/*session_start();
//session_regenerate_id(true);

if ($_SESSION['espresso_session_id'] =='')
{
	$_SESSION['espresso_session_id'] =  session_id();
}*/


//Custom post type integration
if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH.'includes/admin-files/custom_post_type.php') && $org_options['use_custom_post_types']=='Y'){
	require('includes/admin-files/custom_post_type.php');
}
//Load the required Javascripts
add_action('wp_footer', 'espresso_load_javascript_files');
add_action('init', 'espresso_load_jquery', 10);
if (!function_exists('espresso_load_javascript_files')) {
	function espresso_load_javascript_files() {
		global $load_espresso_scripts;

		if ( ! $load_espresso_scripts )
			return;
		wp_register_script('reCopy', (EVENT_ESPRESSO_PLUGINFULLURL . "scripts/reCopy.js"), false, '1.1.0');
		wp_print_scripts('reCopy');

		wp_register_script('jquery.validate.pack', (EVENT_ESPRESSO_PLUGINFULLURL . "scripts/jquery.validate.pack.js"), false, '1.7');
		wp_print_scripts('jquery.validate.pack');

		wp_register_script('validation', (EVENT_ESPRESSO_PLUGINFULLURL . "scripts/validation.js"), false,  EVENT_ESPRESSO_VERSION);
		wp_print_scripts('validation');
	}
}
if (!function_exists('espresso_load_jquery')) {
	function espresso_load_jquery() {
            global $org_options;
		wp_enqueue_script('jquery');
                if (get_option('event_espresso_multi_reg_active') == 1 || (isset($_REQUEST['page'])&&( $_REQUEST['page'] == 'form_builder' || $_REQUEST['page'] == 'form_groups'))){
                    wp_enqueue_script( 'ee_ajax_request', EVENT_ESPRESSO_PLUGINFULLURL . 'scripts/espresso_cart_functions.js', array( 'jquery' ));
                    wp_localize_script( 'ee_ajax_request', 'EEGlobals', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'plugin_url' => EVENT_ESPRESSO_PLUGINFULLURL , 'event_page_id' => $org_options['event_page_id'] )) ;
                }
	}
}

 add_action('wp_print_styles', 'add_event_espresso_stylesheet');

 if (!function_exists('add_event_espresso_stylesheet')) {
    
    function add_event_espresso_stylesheet() {
        global $org_options;

        if ($org_options['enable_default_style'] != 'Y')
            return;

        $event_espresso_style_sheet = EVENT_ESPRESSO_PLUGINFULLURL . 'templates/event_espresso_style.css';


        if (file_exists(EVENT_ESPRESSO_UPLOAD_DIR . "templates/event_espresso_style.css")){
			$event_espresso_style_sheet = EVENT_ESPRESSO_UPLOAD_URL . 'templates/event_espresso_style.css';
		}

            wp_register_style('event_espresso_style_sheets', $event_espresso_style_sheet);
            wp_enqueue_style( 'event_espresso_style_sheets');

    }
 }
 
//Build the admin menu
if (!function_exists('add_event_espresso_menus')) {
	function add_event_espresso_menus() {
		global $org_options, $espresso_premium;
	
		//Main menu tab
		add_menu_page(__('Event Espresso','event_espresso'), __('Event Espresso','event_espresso'), 'administrator', 'event_espresso', 'organization_config_mnu', EVENT_ESPRESSO_PLUGINFULLURL.'images/events_icon_16.png');

		//General Settings
		add_submenu_page('event_espresso', __('Event Espresso - General Settings','event_espresso'), __('General Settings','event_espresso'), 'administrator',  'event_espresso', 'organization_config_mnu');

		//Event Setup
		add_submenu_page('event_espresso', __('Event Espresso - Event Overview','event_espresso'), __('Event Overview','event_espresso'), 'administrator', 'events', 'event_espresso_manage_events');
		
		//Venues
		if ($org_options['use_venue_manager'] == 'Y' && $espresso_premium == true){
			add_submenu_page('event_espresso', __('Event Espresso - Venue Manager','event_espresso'), __('Venue Manager','event_espresso'), 'administrator', 'event_venues', 'event_espresso_venue_config_mnu');
			//add_submenu_page('event_espresso', __('Event Espresso - Locales/Regions Manager','event_espresso'), __('Locale Manager','event_espresso'), 'administrator', 'event_locales', 'event_espresso_locale_config_mnu');
		}
		//Personnel
		if ($org_options['use_personnel_manager'] == 'Y' && $espresso_premium == true){
			add_submenu_page('event_espresso', __('Event Espresso - Staff Manager','event_espresso'), __('Staff Manager','event_espresso'), 'administrator', 'event_staff', 'event_espresso_staff_config_mnu');
		}
		
		//Form Questions
		add_submenu_page('event_espresso', __('Event Espresso - Questions','event_espresso'), __('Questions','event_espresso'), 'administrator', 'form_builder', 'event_espresso_questions_config_mnu');

		//Questions Groups
		add_submenu_page('event_espresso', __('Event Espresso - Question Groups','event_espresso'), __('Question Groups','event_espresso'), 'administrator', 'form_groups', 'event_espresso_question_groups_config_mnu');

		//EventCategories
		add_submenu_page('event_espresso', __('Event Espresso - Manage Event Categories','event_espresso'), __('Categories','event_espresso'), 'administrator', 'event_categories', 'event_espresso_categories_config_mnu');

		//Discounts
		if (function_exists('event_espresso_discount_config_mnu') && $espresso_premium == true) {
			add_submenu_page('event_espresso', __('Event Espresso - Discounts','event_espresso'), __('Discounts','event_espresso'), 'administrator', 'discounts', 'event_espresso_discount_config_mnu');
		}

		//Groupons
		if (function_exists('event_espresso_groupon_config_mnu') && $espresso_premium == true) {
			add_submenu_page('event_espresso', __('Groupons','event_espresso'), __('Groupon Codes','event_espresso'), 'administrator', 'groupons', 'event_espresso_groupon_config_mnu');
		}		
		
		//Email Manager
		if (function_exists('event_espresso_email_config_mnu') && $espresso_premium == true) {
			add_submenu_page('event_espresso', __('Event Espresso - Email Manager','event_espresso'), __('Email Manager','event_espresso'), 'administrator', 'event_emails', 'event_espresso_email_config_mnu');
		}
		
		//Calendar Settings
		if (is_plugin_active('espresso-calendar/espresso-calendar.php') && $espresso_premium == true) {
			add_submenu_page('event_espresso', __('Event Espresso - Calendar Settings','event_espresso'), __('Calendar Settings','event_espresso'), 'administrator', 'espresso_calendar', 'espresso_calendar_config_mnu');
		}
		
		//Payment Settings
		if (function_exists('event_espresso_agteways_mnu')) {
			add_submenu_page('event_espresso', __('Event Espresso - Payment Settings','event_espresso'), __('Payment Settings','event_espresso'), 'administrator', 'payment_gateways', 'event_espresso_agteways_mnu');
		}
		
		//Member Settings
		if (function_exists('event_espresso_member_config_mnu') && $espresso_premium == true) {
			add_submenu_page('event_espresso', __('Event Espresso - Member Settings','event_espresso'), __('Member Settings','event_espresso'), 'administrator', 'members', 'event_espresso_member_config_mnu');
		}

		//MailChimp Integration Settings
		if (function_exists('event_espresso_mailchimp_settings') && $espresso_premium == true) {
			add_submenu_page('event_espresso', __('Event Espresso - MailChimp Integration','event_espresso'), __('MailChimp Integration','event_espresso'), 'administrator', 'espresso-mailchimp', 'event_espresso_mailchimp_settings');
		}
		
		//Facebook Event Integration Settings
		if (function_exists('espresso_fb_settings') && $espresso_premium == true) {
			add_submenu_page('event_espresso', __('Event Espresso - Facebook Settings','event_espresso'), __('Facebook Settings','event_espresso'), 'administrator', 'espresso_facebook', 'espresso_fb_settings');
		}		

		//Social Media Settings
		if (is_plugin_active('espresso-social/espresso-social.php') && $espresso_premium == true) {
			add_submenu_page('event_espresso', __('Event Espresso - Social Media Settings','event_espresso'), __('Social Media','event_espresso'), 'administrator', 'espresso_social', 'espresso_social_config_mnu');
		}
		
		//Addons
		add_submenu_page('event_espresso', __('Event Espresso - Addons','event_espresso'), __('Addons','event_espresso'), 'administrator', 'admin_addons', 'event_espresso_addons_mnu');

		//Help/Support
		add_submenu_page('event_espresso', __('Event Espresso - Help/Support','event_espresso'), __('<span style="color: red;">Help/Support</span>','event_espresso'), 'administrator', 'support', 'event_espresso_support');
		
	}
}



/**
 * Add a settings link to the Plugins page, so people can go straight from the plugin page to the
 * settings page.
 */
function event_espresso_filter_plugin_actions( $links, $file ){
	// Static so we don't call plugin_basename on every plugin row.
	static $this_plugin;
	if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);

	if ( $file == $this_plugin ){
		$org_settings_link = '<a href="admin.php?page=event_espresso">' . __('Settings') . '</a>';
		$events_link = '<a href="admin.php?page=events">' . __('Events') . '</a>';
		array_unshift( $links, $org_settings_link, $events_link ); // before other links
	}
	return $links;
}
add_filter( 'plugin_action_links', 'event_espresso_filter_plugin_actions', 10, 2 );



//ADMIN MENU
add_action('admin_menu', 'add_event_espresso_menus');
add_action('admin_print_scripts', 'event_espresso_config_page_scripts');
add_action('admin_print_styles', 'event_espresso_config_page_styles');

//Run the program
if (!function_exists('event_espresso_run')) {
	function event_espresso_run(){
		global $wpdb, $org_options, $load_espresso_scripts;

		$load_espresso_scripts = true;//This tells the plugin to load the required scripts
		ob_start();
            // Get action type
            $regevent_action = isset($_REQUEST['regevent_action']) ? $_REQUEST['regevent_action'] : '';
            
            switch ($regevent_action) {
                case "post_attendee":
					event_espresso_add_attendees_to_db();
                    break;
                case "register":
				    register_attendees();
                    break;
                case "add_to_session":
                    break;
                case "show_shopping_cart":
                    //This is the form page for registering the attendee
                    event_espresso_require_template('shopping_cart.php');
                    event_espresso_shopping_cart();
                    break;
                case "load_checkout_page":
                    if ($_POST) event_espresso_calculate_total( 'details' );
                    event_espresso_load_checkout_page();
                    break;
                case "post_multi_attendee":
                    //echo " YESssss";
                    event_espresso_update_item_in_session('attendees');
                    event_espresso_add_attendees_to_db_multi();
                    break;
                default:
				    display_all_events();
            }
            
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
}

//New way of doing it with showrtcodes
add_shortcode('ESPRESSO_PAYMENTS', 'event_espresso_pay');
add_shortcode('ESPRESSO_TXN_PAGE', 'event_espresso_txn');
add_shortcode('ESPRESSO_EVENTS', 'event_espresso_run');

require_once(EVENT_ESPRESSO_PLUGINFULLPATH . "includes/functions/cart.php");
/*
 * AJAX functions
 */
  
add_action( 'wp_ajax_update_sequence', 'event_espresso_questions_config_mnu' );//Update the question sequences
add_action( 'wp_ajax_update_qgr_sequence', 'event_espresso_question_groups_config_mnu' );//Update the question group sequences

add_action( 'wp_ajax_event_espresso_add_item', 'event_espresso_add_item_to_session' );
add_action('wp_ajax_nopriv_event_espresso_add_item', 'event_espresso_add_item_to_session');

add_action( 'wp_ajax_event_espresso_delete_item', 'event_espresso_delete_item_from_session' );
add_action('wp_ajax_nopriv_event_espresso_delete_item', 'event_espresso_delete_item_from_session');

add_action( 'wp_ajax_event_espresso_update_item', 'event_espresso_update_item_in_session' );
add_action('wp_ajax_nopriv_event_espresso_update_item', 'event_espresso_update_item_in_session');

add_action( 'wp_ajax_event_espresso_calculate_total', 'event_espresso_calculate_total' );
add_action('wp_ajax_nopriv_event_espresso_calculate_total', 'event_espresso_calculate_total');

add_action( 'wp_ajax_event_espresso_load_regis_form', 'event_espresso_load_regis_form' );
add_action('wp_ajax_nopriv_event_espresso_load_regis_form', 'event_espresso_load_regis_form');

add_action( 'wp_ajax_event_espresso_confirm_and_pay', 'event_espresso_confirm_and_pay' );
add_action('wp_ajax_nopriv_event_espresso_confirm_and_pay', 'event_espresso_confirm_and_pay');



/*
* These actions need to be loaded a the bottom of this script to prevent errors when post/get requests are received.
*/

//Export PDF invoice
if (isset($_REQUEST['download_invoice'])){
	if (get_option('events_invoice_payment_active') == 'true' && $_REQUEST['download_invoice'] == 'true'){

            $invoice_type = $_GET['invoice_type']; //regular will be an empty string and multi will be multi_invoice_

		if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/invoice/{$invoice_type}template.php")){
			require_once(EVENT_ESPRESSO_GATEWAY_DIR . "/invoice/{$invoice_type}template.php");
		}else{
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH. "gateways/invoice/{$invoice_type}template.php");
		}
	}
}

//Export PDF Ticket
if (isset($_REQUEST['download_ticket'])){
    if ($_REQUEST['download_ticket']=='true'){
    	if (file_exists(EVENT_ESPRESSO_UPLOAD_DIR . "/ticketing/template.php")){
    		require_once(EVENT_ESPRESSO_UPLOAD_DIR . "/ticketing/template.php");
    		espresso_ticket($_REQUEST['id'],$_REQUEST['registration_id']);
    	}
    }
}

//Check to make sure all of the main pages are setup properly, if not show an admin message.
if (((!isset($_REQUEST['event_page_id']) || $_REQUEST['event_page_id'] == NULL) && ($org_options['event_page_id']==('0'||''))) || $org_options['return_url']==('0'||'') || $org_options['notify_url']==('0'||'')){
	add_action( 'admin_notices', 'event_espresso_activation_notice');
}

//Check to make sure there are no empty registration id fields in the database.
if (event_espresso_verify_attendee_data() == true && $_POST['action'] != 'event_espresso_update_attendee_data'){
	add_action( 'admin_notices', 'event_espresso_registration_id_notice');
}

//copy themes to template directory
if (isset($_REQUEST['event_espresso_admin_action'])){
	if($_REQUEST['event_espresso_admin_action'] == 'copy_templates') {
		add_action('admin_init', 'event_espresso_trigger_copy_templates');
	}
}
//copy gateways to gateway directory
if (isset($_REQUEST['event_espresso_admin_action'])){
	if($_REQUEST['event_espresso_admin_action'] == 'copy_gateways') {
		add_action('admin_init', 'event_espresso_trigger_copy_gateways');
	}
}

//Load the EE short URL
if 	(isset($_REQUEST['ee'])){
	espresso_redirect($_REQUEST['ee']);
}

 if ( ! function_exists( 'is_ssl' ) ) {
  function is_ssl() {
   if ( isset($_SERVER['HTTPS']) ) {
    if ( 'on' == strtolower($_SERVER['HTTPS']) )
     return true;
    if ( '1' == $_SERVER['HTTPS'] )
     return true;
   } elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
    return true;
   }
   return false;
  }
 }


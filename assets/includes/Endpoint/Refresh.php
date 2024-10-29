<?php
/**
 * AMZWatcher
 *
 *
 * @package   AMZWatcher
 * @author    AMZWatcher
 * @license   GPL-3.0
 * @link      https://amzwatcher.com
 * @copyright 2021 Graitch LLC
 */

namespace AMZWatcher\WPR\Endpoint;
use AMZWatcher\WPR;

/**
 * @subpackage REST_Controller
 */
class Refresh {
    /**
	 * Instance of this class.
	 *
	 * @since    0.8.1
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     0.8.1
	 */
	private function __construct() {
        $plugin = WPR\Plugin::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
	}

    /**
     * Set up WordPress hooks and filters
     *
     * @return void
     */
    public function do_hooks() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.8.1
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
			self::$instance->do_hooks();
		}

		return self::$instance;
	}

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {
        $version = '1';
        $namespace = $this->plugin_slug . '/v' . $version;
        $endpoint = '/refresh/';

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'get_refresh' ),
                'permission_callback'   => array( $this, 'refresh_permissions_check' ),
                'args'                  => array(),
            ),
        ) );

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::CREATABLE,
                'callback'              => array( $this, 'update_refresh' ),
                'permission_callback'   => array( $this, 'refresh_permissions_check' ),
                'args'                  => array(),
            ),
        ) );

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array( $this, 'update_refresh' ),
                'permission_callback'   => array( $this, 'refresh_permissions_check' ),
                'args'                  => array(),
            ),
        ) );

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::DELETABLE,
                'callback'              => array( $this, 'delete_refresh' ),
                'permission_callback'   => array( $this, 'refresh_permissions_check' ),
                'args'                  => array(),
            ),
        ) );
    }

    /**
     * Get Refresh
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function get_refresh( $request ) {
        $this->amz_cron_function();
        $this->amz_cron_site_function();
        $this->amz_cron_all_job_function();
        $this->amz_cron_specific_job_details();
        $this->amz_cron_plugin_function();

        return new \WP_REST_Response( array(
            'success' => true,
            'value' => 'done'
        ), 200 );
    }
    function amz_cron_function() {
        global $wpdb;
        // user
         $url ='https://app.amzwatcher.com/api/plugin/user';
         $call_user =CallAPI($url);
        if($call_user):
            $amz_user_check  = $wpdb->prefix . 'amz_user_check';
            $get_call_user=json_decode($call_user,true);
            $plan            = $get_call_user['plan'];
            $user_id	        = $get_call_user['userId'];
            $planCredits     = $get_call_user['planCredits'];
            $credits         = $get_call_user['credits'];
            $allowedProjects = $get_call_user['allowedProjects'];
            $isYearly        = $get_call_user['isYearly'];
            $isActive	     = $get_call_user['isActive'];
            if($user_id):
                    update_option('amzw_failed_fetch',false);
                    $user_id_mod ='"'.$user_id.'"';
                    $sql_qet='SELECT * FROM '.$amz_user_check.' WHERE `userId`='.$user_id_mod;
                   $check_user = $wpdb->get_row(($sql_qet));

                   if($check_user->id):
                       $update_user =$wpdb->update($amz_user_check, array(
                           'plan'           =>$plan,
                           'planCredits'    =>$planCredits,
                           'credits'        =>$credits,
                           'allowedProjects'=>$allowedProjects,
                           'isYearly'       =>$isYearly,
                           'isActive'       =>$isActive), array('id'=>$check_user->id)
                       );
                   else:
                         $multirows[] = "('".$user_id."', '".$plan."',  '".$planCredits."', '".$credits."', '".$allowedProjects."','".$isYearly."','".$isActive."' )";
                          $multirows = implode(", ", $multirows);
                          $inquery = "INSERT ignore INTO $amz_user_check (userId, plan, planCredits, credits, allowedProjects, isYearly, isActive)  VALUES {$multirows}";
                        $wpdb->query($inquery);
                    endif;
                else:
                    update_option('amzw_failed_fetch',true);
                endif;
            endif;

    }

    function amz_cron_site_function() {
        $url ='https://app.amzwatcher.com/api/plugin/site';
        $call_site =CallAPI($url);
        if($call_site):
            $get_call_site=json_decode($call_site,true);
             global $wpdb;
             $amz_site_check     = $wpdb->prefix . 'amz_site_check';

            $siteUrl            = $get_call_site['siteUrl'];
            $userId	           = $get_call_site['userId'];
            $siteId             = $get_call_site['siteId'];
            $crawlJobIds        = json_encode($get_call_site['crawlJobIds']);
            $all_other          = $call_site;
            if($userId):
                update_option('amzw_failed_fetch',false);
            $user_id_mod ='"'.$userId.'"';
            $sql_check='SELECT * FROM '.$amz_site_check.' WHERE `userId`='.$user_id_mod;
           $check_site = $wpdb->get_row(($sql_check));
               if($check_site->id):
                   $update_site =$wpdb->update($amz_site_check, array(
                       'siteUrl'           =>$siteUrl,
                       'userId'    		  =>$userId,
                       'siteId'    		  =>$siteId,
                       'crawlJobIds'       =>$crawlJobIds,
                       'all_other'         =>$all_other), array('id'=>$check_site->id)
                   );
               else:
                    $multirows[] = "('".$siteUrl."', '".$userId."',  '".$siteId."', '".$crawlJobIds."', '".$all_other."')";
                      $multirows = implode(", ", $multirows);
                      $inquery = "INSERT ignore INTO $amz_site_check (siteUrl, userId, siteId, crawlJobIds, all_other)  VALUES {$multirows}";
                    $wpdb->query($inquery);
                endif;
            else:
                update_option('amzw_failed_fetch',true);
        endif;
    endif;
    }

     function amz_cron_all_job_function() {
        $url ='https://app.amzwatcher.com/api/plugin/jobs/all/';
        $call_jobs =CallAPI($url);
        global $wpdb;
        $amz_job_check     = $wpdb->prefix . 'amz_all_jobs';
        if($call_jobs):
            $get_call_job=json_decode($call_jobs,true);
            foreach ($get_call_job as $key => $job_details):

                    $mod_key 				='"'.$key.'"';
                    $sql_chek_job='SELECT * FROM '.$amz_job_check.' WHERE `crawlJobId`='.$mod_key;
                    $check_job_all = $wpdb->get_row(($sql_chek_job));
                    $siteUrl             = $job_details['siteUrl'];
                    $userId	            = $job_details['userId'];
                    $siteId              = $job_details['siteId'];
                    $crawlJobId	         = $key;
                    $all_other           = json_encode($job_details);
                    if($job_details !='API Key is not valid' &&  $job_details!=""):
                            update_option('amzw_failed_fetch',false);
                            if($check_job_all->id):
                                $update_user =$wpdb->update($amz_job_check, array(
                              'siteUrl'           =>$siteUrl,
                              'userId'    		  =>$userId,
                              'siteId'            =>$siteId,
                               'crawlJobId'        =>$crawlJobId,
                              'all_other'         =>$all_other), array('id'=>$check_job_all->id)
                           );

                            else:
                                 $multirows[] = "('".$siteUrl."', '".$userId."',  '".$siteId."', '".$crawlJobId."', '".$all_other."')";
                             endif;
                         else:
                             update_option('amzw_failed_fetch',true);
                     endif;
            endforeach;
            if($multirows):
                  $multirows = implode(", ", $multirows);
                  $inquery = "INSERT ignore INTO $amz_job_check (siteUrl, userId, siteId, crawlJobId	, all_other)  VALUES {$multirows}";
                $wpdb->query($inquery);
            endif;
        endif;
    }

    function amz_cron_specific_job_details(){

        global $wpdb;
        $amz_site_check     = $wpdb->prefix . 'amz_site_check';
        $sql_qet='SELECT * FROM '.$amz_site_check.' ORDER BY id DESC LIMIT 1';
        $results = $wpdb->get_results(('SELECT * FROM '.$amz_site_check.' ORDER BY id DESC LIMIT 1'));
        $amz_jobs_details     = $wpdb->prefix . 'amz_jobs_details';
        $multirows =array();
        $crawlJobIds =array();
        $parent_id=0;
        if($results):
            foreach ($results as $get_job_list):
                $crawlJobIds =json_decode($get_job_list->crawlJobIds);
                $parent_id =$get_job_list->id;
            endforeach;
        endif;
        if($parent_id):
            foreach ($crawlJobIds as $job_id):
                $url ='https://app.amzwatcher.com/api/plugin/products/'.$job_id;
                $call_job_dt =CallAPI($url);

                if($call_job_dt):
                    $lenth_st = strlen($call_job_dt);
                    if($lenth_st > 4):
                        $crawlJobId         = $job_id;
                        $parent_id	        = $parent_id;
                        $all_other          = base64_encode($call_job_dt);
                        $job_id_mod 		  ='"'.$crawlJobId.'"';
                        $sql_job_check      ='SELECT * FROM '.$amz_jobs_details.' WHERE `crawlJobId`='.$job_id_mod;
                        $check_job_exists   = $wpdb->get_row(($sql_job_check));
                             if($check_job_exists->id):
                               $update_job   =$wpdb->update($amz_jobs_details, array(
                                   'crawlJobId' =>$crawlJobId,
                                   'parent_id'  =>$parent_id,
                                   'all_other'  =>$all_other), array('id'=>$check_job_exists->id)
                               );
                           else:
                                $multirows[] = "('".$crawlJobId."', '".$parent_id."',  '".$all_other."')";
                            endif;
                    endif;
                endif;
            endforeach;
                if($multirows):
                    $multirows = implode(", ", $multirows);
                      $inquery = "INSERT ignore INTO $amz_jobs_details (crawlJobId, parent_id,  all_other)  VALUES {$multirows}";
                    $wpdb->query($inquery);
                endif;
        endif;


    }

     function amz_cron_plugin_function() {
        $url ='https://app.amzwatcher.com/api/plugin/a';
        $call_plugins =CallAPI($url);
        global $wpdb;
        $amz_plugin_check     = $wpdb->prefix . 'amz_plugin_check';
        if($call_plugins):
            $get_call_plugins=json_decode($call_plugins,true);
            foreach ($get_call_plugins as $key => $plugin_details):

                $healthScore	     = $plugin_details['healthScore'];
                $pages              = $plugin_details['crawlPageCount'];
                $healtyProduct      = $plugin_details['products']['totalHealthyProductsCount'];
                $unHealtyProduct    = $plugin_details['products']['totalUnhealthyProductsCount'];
                $crawlJobId	        = $key;
                $crawlJobId_mod     ='"'.$crawlJobId.'"';
                $all_other          = json_encode($plugin_details);
                if($plugin_details !='API Key is not valid' && $plugin_details !=""):
                    update_option('amzw_failed_fetch',false);
                    $sql_check_plugin   ='SELECT * FROM '.$amz_plugin_check.' WHERE `crawlJobId`='.$crawlJobId_mod;
                    $check_plugin       = $wpdb->get_row(($sql_check_plugin));
                   if($check_plugin->id):
                       $update_plugin =$wpdb->update($amz_plugin_check, array(
                           'healthScore'       =>$healthScore,
                           'pages'   			  =>$pages,
                           'healtyProduct'     =>$healtyProduct,
                           'unHealtyProduct'   =>$unHealtyProduct,
                           'crawlJobId'        =>$crawlJobId,
                           'all_other'         =>$all_other), array('id'=>$check_plugin->id)
                       );
                   else:
                        $multirows[] = "('".$healthScore."',  '".$pages."', '".$healtyProduct."', '".$unHealtyProduct."','".$crawlJobId."','".$all_other."')";
                    endif;
                else:
                    update_option('amzw_failed_fetch',true);
                endif;
                endforeach;
                if($multirows):
                      $multirows = implode(", ", $multirows);
                     $inquery = "INSERT ignore INTO $amz_plugin_check (healthScore, pages, healtyProduct , unHealtyProduct, crawlJobId ,all_other)  VALUES {$multirows}";
                    $wpdb->query($inquery);
                endif;
        endif;
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function refresh_permissions_check( $request ) {
        return current_user_can( 'edit_pages' );
    }
}

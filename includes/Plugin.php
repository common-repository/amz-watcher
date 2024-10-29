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

namespace AMZWatcher\WPR;

/**
 * @subpackage Plugin
 */

class Plugin {
	/**
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */

	protected $plugin_slug = 'amzwatcher';



	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */

	protected static $instance = null;



	/**
	 * Setup instance attributes
	 *
	 * @since     1.0.0
	 */

	private function __construct() {

		$this->plugin_version = AMZWATCHER_VERSION;
		add_filter( 'cron_schedules', array($this,'amz_custom_cron_schedule' ));
        add_action( 'amz_cron_hook', array($this,'amz_cron_function' ));
        add_action( 'amz_cron_hook', array($this,'amz_cron_site_function' ));
        add_action( 'amz_cron_hook', array($this,'amz_cron_all_job_function' ));
        add_action( 'amz_cron_hook', array($this,'amz_cron_specific_job_details' ));
        add_action( 'amz_cron_hook', array($this,'amz_cron_plugin_function' ));
        add_action('wp_dashboard_setup', array($this,'amz_dashboard_widgets'));
        add_action( 'wp_ajax_amz_dashbard_ajax', array($this,'amz_dashbard_ajax_callback'));
		if ( ! wp_next_scheduled( 'amz_cron_hook' ) ) {
			wp_schedule_event( time(), 'every_six_hours', 'amz_cron_hook' );
        }
        // add metabox

        add_action( 'add_meta_boxes', array($this,'amz_page_metabox'));
		add_action( 'save_post', array($this,'save_amz_post_meta_field' ));
	}

	function amz_page_metabox(){
		$check_site_key =get_option('amzw_api_key');
		if($check_site_key){
			$check_widget_enable=get_option('amzw_settings_widget_enabled');
			if($check_widget_enable =='true'){
				$screens = array( 'post','page');
				add_meta_box(
					'amz_page_metabox',
					'AMZ Watcher Links Report',
						array($this,'amz_page_metabox_callback'),
						$screens		
				);
			}
		}
	}

			
	function save_amz_post_meta_field( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( $parent_id = wp_is_post_revision( $post_id ) ) {
			$post_id = $parent_id;
		}
		$fields = [
			'enable_amz_post_meta',
		];
		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $_POST ) ) {
				update_post_meta( $post_id, $field,  esc_html($_POST[$field])  );
			}else{
				update_post_meta( $post_id, $field,  0  );
			}
		}
	}


		function amz_page_metabox_callback() {

			global $post;
			global $wpdb;
			$amz_amz_jobs_details     = $wpdb->prefix . 'amz_jobs_details';
			$sql_qet='SELECT * FROM '.$amz_amz_jobs_details.' ORDER BY id DESC LIMIT 1';
			$results = $wpdb->get_row(($sql_qet));
			if(isset($results->all_other)):
				$all_other =base64_decode($results->all_other);
				$all_other=json_decode($all_other,true);
			else:	
				$all_other ='';
			endif;
		   $current_page_url=get_the_permalink(get_the_ID()); 
			$match_results=array();
				if($all_other){
					foreach ($all_other as $job_details){
					  if(isset($job_details['from']) && (!empty($job_details['from']))){
					  		
						  	foreach($job_details['from'] as $job_arr){
						  		$form_page_url = $job_arr['page'].'/';
						  		if($form_page_url == $current_page_url){
						  			$url_matched =true;	
						  			$match_results[]  = array(
									    'link'        => isset($job_details['to']) ? $job_details['to'] :0 ,
									    'affiliateId' => isset($job_details['affiliateId']) ? $job_details['affiliateId'] :0 ,
									    'reviewScore' => isset($job_details['reviewScore']) ? $job_details['reviewScore'] : 0,
										'reviewCount' => isset($job_details['reviewCount']) ? $job_details['reviewCount'] : 0,
									    'isAvailable' => isset($job_details['isAvailable']) ? $job_details['isAvailable'] :0 ,
									    'anchor' 		=> isset($job_arr['anchor']) ? $job_arr['anchor'] : '' ,
									  
									);
						  			
						  		}
						  	}
					  }
				}
			}
		?>
<div>
	<table class="full_width" width="100%" id="post_wise_amz_details">
		<?php if(empty($match_results)):?>
		<tr class="form-field">
			<th scope="row" valign="top"></th>
			<td>
				<p>We can't show any Affiliate Links for this post. Please complete a crawl or add Affiliate links to this post.</p>
			</td>
		</tr>
	</table>
</div>
<?php else:?>
	<div>
		<table class="full_width amzw-metabox-table" width="100%" id="post_wise_amz_details">
			<thead>
				<tr>
					<th style="min-width: 300px; max-width: 500px">Link</th>
					<th style="">Anchor</th>
					<th style="">Affiliate Id</th>
					<th style="">Reviews</th>
					<th style="">Review Score</th>
					<th style="min-width: 100px">Status</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($match_results as $amz_report):
					$sub_array= array();
				?>
					<tr style="display: <?php if($amz_report['scraperStatus'] == 'failed') : echo "none"; else: echo "table-row"; endif;?>;">
						<td style="min-width: 300px; max-width: 500px"><div><a href="<?php echo esc_url($amz_report['link']);?>" target="_blank"><?php echo esc_html($amz_report['link']);?></a></div></td>
						<td style=""><div><?php echo esc_html($amz_report['anchor']);?></div></td>
						<td style=""><div><?php if($amz_report['affiliateId']) : echo esc_html($amz_report['affiliateId']); else: echo "None"; endif;?></div></td>
						<td style=""><div><?php if($amz_report['reviewCount']) : echo esc_html($amz_report['reviewCount']); else: echo "None"; endif;?></div></td>
						<td style=""><div><?php if($amz_report['reviewScore']) : echo esc_html($amz_report['reviewScore']); else: echo "None"; endif;?></div></td>
						<td style="min-width: 100px"><div><?php if($amz_report['isAvailable'] == 1): echo '<span class="ant-badge-status-dot ant-badge-status-success"></span> Available'; else: echo '<span class="ant-badge-status-dot ant-badge-status-error"></span>Not Available'; endif;?></div></td>
					</tr>
				<?php endforeach;?>
			</tbody>
		</table>
		<div style="margin-top: 15px; margin-left: 10px">
			<div style="margin-bottom: 15px">This data is not real-time and contains results from the most recent crawl by AMZ Watcher. <a href="https://amzwatcher.com/knowledge-base/#wordpress-plugin" target="_blank">Learn More</a></div>
			<button type="button" class="ant-btn amzw-button amzw-button-header" ant-click-animating-without-extra-node="false">
				<a href="<?php bloginfo('url');?>/wp-admin/admin.php?page=amzwatcher" target="_blank">See Full Report</a>
			</button>
		</div>	
	</div>
<?php endif;?>
	
<?php }
		function amz_dashboard_widgets(){

			global $wp_meta_boxes;
			$check_site_key =get_option('amzw_api_key');
			if($check_site_key):
				wp_add_dashboard_widget('custom_amz_widget', 'Latest Crawl Report By AMZ Watcher', array($this,'amz_widget_callback'));
			endif;
		}

		function amz_dashbard_ajax_callback(){
			$this->amz_cron_function();
			$this->amz_cron_site_function();
			$this->amz_cron_all_job_function();
			$this->amz_cron_specific_job_details();
			$this->amz_cron_plugin_function();
			global $wpdb;
			 $amz_plugin_check  = $wpdb->prefix . 'amz_plugin_check';
			 $sql_qet='SELECT * FROM '.$amz_plugin_check.' WHERE `pages` > 0 ORDER BY id DESC LIMIT 1';
			 $results = $wpdb->get_row(($sql_qet));
			 $pages           = 0;
			 $healthScore     = 0;
			 $healtyProduct   = 0;
			 $unHealtyProduct = 0;
			 $all_details     = 0;
			 $totalLinksCount = 0;
			 $noAffiliateIdProductsCount  = 0;
			 $totalProductCount  = 0;
			 $totalUnhealthyProductsCount  = 0;
			 $totalUnealthyLinksCount  = 0;
			 $usedUserCredits  = 0;
			 $noAffiliateIdLiksCount =0;
			if($results):
				 $pages = $results->pages;
				 $healthScore = $results->healthScore;
				 $healtyProduct = $results->healtyProduct;
				 $unHealtyProduct = $results->unHealtyProduct;
				 $all_details =json_decode($results->all_other,true);
								
				 if(isset($all_details['products']['totalProductCount'])):
				 	 $totalProductCount  =$all_details['products']['totalProductCount'];
				 endif;
				 if(isset($all_details['products']['totalUnhealthyProductsCount'])):
				 	$totalUnhealthyProductsCount  =$all_details['products']['totalUnhealthyProductsCount'];
				 endif;
				 if(isset($all_details['links']['totalLinksCount'])):
				 	 $totalLinksCount  =$all_details['links']['totalLinksCount'];
				 endif;		 
				 if(isset($all_details['links']['totalUnealthyLinksCount'])):
				 	$totalUnealthyLinksCount  =$all_details['links']['totalUnealthyLinksCount'];
				 endif;
				 if(isset($all_details['credits']['usedUserCredits'])):
				 	$usedUserCredits  =$all_details['credits']['usedUserCredits'];
				 endif;
				 if(isset($all_details['links']['noAffiliateIdLiksCount'])):
				 		$noAffiliateIdLiksCount  =$all_details['links']['noAffiliateIdLiksCount'];
				endif;

			endif;

			// $healthScore =0;
			// $totalLinksCount=0;
			// $totalUnealthyLinksCount =0;
			// $pages =200;
			// $usedUserCredits=0;
			// $totalUnhealthyProductsCount =0;
			?>

	<?php if( $healthScore !=0 || $totalLinksCount !=0 ||  $totalUnealthyLinksCount !=0 || $pages !=0 || $usedUserCredits !=0 || $totalUnhealthyProductsCount !=0 ):?>
<div>
	<div style="display: grid; grid-template-columns: repeat(2, 180px); grid-template-rows: repeat(2, 65px); gap: 2px 5px; margin-top: 15px;">
		<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;">
			<span style="margin: 0px 20px; font-size: 30px;">
				<svg viewBox="64 64 896 896" focusable="false" data-icon="heart" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M923 283.6a260.04 260.04 0 00-56.9-82.8 264.4 264.4 0 00-84-55.5A265.34 265.34 0 00679.7 125c-49.3 0-97.4 13.5-139.2 39-10 6.1-19.5 12.8-28.5 20.1-9-7.3-18.5-14-28.5-20.1-41.8-25.5-89.9-39-139.2-39-35.5 0-69.9 6.8-102.4 20.3-31.4 13-59.7 31.7-84 55.5a258.44 258.44 0 00-56.9 82.8c-13.9 32.3-21 66.6-21 101.9 0 33.3 6.8 68 20.3 103.3 11.3 29.5 27.5 60.1 48.2 91 32.8 48.9 77.9 99.9 133.9 151.6 92.8 85.7 184.7 144.9 188.6 147.3l23.7 15.2c10.5 6.7 24 6.7 34.5 0l23.7-15.2c3.9-2.5 95.7-61.6 188.6-147.3 56-51.7 101.1-102.7 133.9-151.6 20.7-30.9 37-61.5 48.2-91 13.5-35.3 20.3-70 20.3-103.3.1-35.3-7-69.6-20.9-101.9zM512 814.8S156 586.7 156 385.5C156 283.6 240.3 201 344.3 201c73.1 0 136.5 40.8 167.7 100.4C543.2 241.8 606.6 201 679.7 201c104 0 188.3 82.6 188.3 184.5 0 201.2-356 429.3-356 429.3z" fill="#0ec851"></path><path d="M679.7 201c-73.1 0-136.5 40.8-167.7 100.4C480.8 241.8 417.4 201 344.3 201c-104 0-188.3 82.6-188.3 184.5 0 201.2 356 429.3 356 429.3s356-228.1 356-429.3C868 283.6 783.7 201 679.7 201z" fill="#e6ffeb"></path></svg>
			</span>
			<div>
				<div>Health Score</div>
				<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;">
					<span><?php echo esc_html($healthScore);?></span>
					<span> / 100</span>
				</div>
			</div>
		</div>
		<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;"><span style="margin: 0px 20px; font-size: 30px;"><svg viewBox="64 64 896 896" focusable="false" data-icon="tags" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M483.2 790.3L861.4 412c1.7-1.7 2.5-4 2.3-6.3l-25.5-301.4c-.7-7.8-6.8-13.9-14.6-14.6L522.2 64.3c-2.3-.2-4.7.6-6.3 2.3L137.7 444.8a8.03 8.03 0 000 11.3l334.2 334.2c3.1 3.2 8.2 3.2 11.3 0zm62.6-651.7l224.6 19 19 224.6L477.5 694 233.9 450.5l311.9-311.9zm60.16 186.23a48 48 0 1067.88-67.89 48 48 0 10-67.88 67.89zM889.7 539.8l-39.6-39.5a8.03 8.03 0 00-11.3 0l-362 361.3-237.6-237a8.03 8.03 0 00-11.3 0l-39.6 39.5a8.03 8.03 0 000 11.3l243.2 242.8 39.6 39.5c3.1 3.1 8.2 3.1 11.3 0l407.3-406.6c3.1-3.1 3.1-8.2 0-11.3z"></path></svg></span>
			<div>
				<div>Missing Tags</div>
				<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><span><?php echo esc_html($noAffiliateIdLiksCount);?></span></span><span> / <?php if(isset($totalLinksCount)): echo esc_html($totalLinksCount); else:  echo '0'; endif; ?></span>
				</div>
			</div>
		</div>
		<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;"><span style="margin: 0px 20px; font-size: 30px;"><svg viewBox="64 64 896 896" focusable="false" data-icon="link" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M574 665.4a8.03 8.03 0 00-11.3 0L446.5 781.6c-53.8 53.8-144.6 59.5-204 0-59.5-59.5-53.8-150.2 0-204l116.2-116.2c3.1-3.1 3.1-8.2 0-11.3l-39.8-39.8a8.03 8.03 0 00-11.3 0L191.4 526.5c-84.6 84.6-84.6 221.5 0 306s221.5 84.6 306 0l116.2-116.2c3.1-3.1 3.1-8.2 0-11.3L574 665.4zm258.6-474c-84.6-84.6-221.5-84.6-306 0L410.3 307.6a8.03 8.03 0 000 11.3l39.7 39.7c3.1 3.1 8.2 3.1 11.3 0l116.2-116.2c53.8-53.8 144.6-59.5 204 0 59.5 59.5 53.8 150.2 0 204L665.3 562.6a8.03 8.03 0 000 11.3l39.8 39.8c3.1 3.1 8.2 3.1 11.3 0l116.2-116.2c84.5-84.6 84.5-221.5 0-306.1zM610.1 372.3a8.03 8.03 0 00-11.3 0L372.3 598.7a8.03 8.03 0 000 11.3l39.6 39.6c3.1 3.1 8.2 3.1 11.3 0l226.4-226.4c3.1-3.1 3.1-8.2 0-11.3l-39.5-39.6z"></path></svg></span>
			<div>
				<div>Broken Links</div>
				<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><span><?php echo esc_html($totalUnealthyLinksCount);?></span></span><span> / <?php echo esc_html($totalLinksCount);?></span>
				</div>
			</div>
		</div>
		<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;"><span style="margin: 0px 20px; font-size: 30px;"><svg viewBox="64 64 896 896" focusable="false" data-icon="amazon" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M825 768.9c-3.3-.9-7.3-.4-11.9 1.3-61.6 28.2-121.5 48.3-179.7 60.2C507.7 856 385.2 842.6 266 790.3c-33.1-14.6-79.1-39.2-138-74a9.36 9.36 0 00-5.3-2c-2-.1-3.7.1-5.3.9-1.6.8-2.8 1.8-3.7 3.1-.9 1.3-1.1 3.1-.4 5.4.6 2.2 2.1 4.7 4.6 7.4 10.4 12.2 23.3 25.2 38.6 39s35.6 29.4 60.9 46.8c25.3 17.4 51.8 32.9 79.3 46.4 27.6 13.5 59.6 24.9 96.1 34.1s73 13.8 109.4 13.8c36.2 0 71.4-3.7 105.5-10.9 34.2-7.3 63-15.9 86.5-25.9 23.4-9.9 45-21 64.8-33 19.8-12 34.4-22.2 43.9-30.3 9.5-8.2 16.3-14.6 20.2-19.4 4.6-5.7 6.9-10.6 6.9-14.9.1-4.5-1.7-7.1-5-7.9zM527.4 348.1c-15.2 1.3-33.5 4.1-55 8.3-21.5 4.1-41.4 9.3-59.8 15.4s-37.2 14.6-56.3 25.4c-19.2 10.8-35.5 23.2-49 37s-24.5 31.1-33.1 52c-8.6 20.8-12.9 43.7-12.9 68.7 0 27.1 4.7 51.2 14.3 72.5 9.5 21.3 22.2 38 38.2 50.4 15.9 12.4 34 22.1 54 29.2 20 7.1 41.2 10.3 63.2 9.4 22-.9 43.5-4.3 64.4-10.3 20.8-5.9 40.4-15.4 58.6-28.3 18.2-12.9 33.1-28.2 44.8-45.7 4.3 6.6 8.1 11.5 11.5 14.7l8.7 8.9c5.8 5.9 14.7 14.6 26.7 26.1 11.9 11.5 24.1 22.7 36.3 33.7l104.4-99.9-6-4.9c-4.3-3.3-9.4-8-15.2-14.3-5.8-6.2-11.6-13.1-17.2-20.5-5.7-7.4-10.6-16.1-14.7-25.9-4.1-9.8-6.2-19.3-6.2-28.5V258.7c0-10.1-1.9-21-5.7-32.8-3.9-11.7-10.7-24.5-20.7-38.3-10-13.8-22.4-26.2-37.2-37-14.9-10.8-34.7-20-59.6-27.4-24.8-7.4-52.6-11.1-83.2-11.1-31.3 0-60.4 3.7-87.6 10.9-27.1 7.3-50.3 17-69.7 29.2-19.3 12.2-35.9 26.3-49.7 42.4-13.8 16.1-24.1 32.9-30.8 50.4-6.7 17.5-10.1 35.2-10.1 53.1L408 310c5.5-16.4 12.9-30.6 22-42.8 9.2-12.2 17.9-21 25.8-26.5 8-5.5 16.6-9.9 25.7-13.2 9.2-3.3 15.4-5 18.6-5.4 3.2-.3 5.7-.4 7.6-.4 26.7 0 45.2 7.9 55.6 23.6 6.5 9.5 9.7 23.9 9.7 43.3v56.6c-15.2.6-30.4 1.6-45.6 2.9zM573.1 500c0 16.6-2.2 31.7-6.5 45-9.2 29.1-26.7 47.4-52.4 54.8-22.4 6.6-43.7 3.3-63.9-9.8-21.5-14-32.2-33.8-32.2-59.3 0-19.9 5-36.9 15-51.1 10-14.1 23.3-24.7 40-31.7s33-12 49-14.9c15.9-3 33-4.8 51-5.4V500zm335.2 218.9c-4.3-5.4-15.9-8.9-34.9-10.7-19-1.8-35.5-1.7-49.7.4-15.3 1.8-31.1 6.2-47.3 13.4-16.3 7.1-23.4 13.1-21.6 17.8l.7 1.3.9.7 1.4.2h4.6c.8 0 1.8-.1 3.2-.2 1.4-.1 2.7-.3 3.9-.4 1.2-.1 2.9-.3 5.1-.4 2.1-.1 4.1-.4 6-.7.3 0 3.7-.3 10.3-.9 6.6-.6 11.4-1 14.3-1.3 2.9-.3 7.8-.6 14.5-.9 6.7-.3 12.1-.3 16.1 0 4 .3 8.5.7 13.6 1.1 5.1.4 9.2 1.3 12.4 2.7 3.2 1.3 5.6 3 7.1 5.1 5.2 6.6 4.2 21.2-3 43.9s-14 40.8-20.4 54.2c-2.8 5.7-2.8 9.2 0 10.7s6.7.1 11.9-4c15.6-12.2 28.6-30.6 39.1-55.3 6.1-14.6 10.5-29.8 13.1-45.7 2.4-15.9 2-26.2-1.3-31z"></path></svg></span>
			<div>
				<div>Broken Products</div>
				<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><span><?php echo esc_html($totalUnhealthyProductsCount);?></span></span><span> / <?php echo esc_html($totalProductCount);?></span>
				</div>
			</div>
		</div>
		<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;"><span style="margin: 0px 20px; font-size: 30px;"><svg viewBox="64 64 896 896" focusable="false" data-icon="file-done" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M688 312v-48c0-4.4-3.6-8-8-8H296c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h384c4.4 0 8-3.6 8-8zm-392 88c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h184c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8H296zm376 116c-119.3 0-216 96.7-216 216s96.7 216 216 216 216-96.7 216-216-96.7-216-216-216zm107.5 323.5C750.8 868.2 712.6 884 672 884s-78.8-15.8-107.5-44.5C535.8 810.8 520 772.6 520 732s15.8-78.8 44.5-107.5C593.2 595.8 631.4 580 672 580s78.8 15.8 107.5 44.5C808.2 653.2 824 691.4 824 732s-15.8 78.8-44.5 107.5zM761 656h-44.3c-2.6 0-5 1.2-6.5 3.3l-63.5 87.8-23.1-31.9a7.92 7.92 0 00-6.5-3.3H573c-6.5 0-10.3 7.4-6.5 12.7l73.8 102.1c3.2 4.4 9.7 4.4 12.9 0l114.2-158c3.9-5.3.1-12.7-6.4-12.7zM440 852H208V148h560v344c0 4.4 3.6 8 8 8h56c4.4 0 8-3.6 8-8V108c0-17.7-14.3-32-32-32H168c-17.7 0-32 14.3-32 32v784c0 17.7 14.3 32 32 32h272c4.4 0 8-3.6 8-8v-56c0-4.4-3.6-8-8-8z"></path></svg></span>
			<div>
				<div>Pages Discovered</div>
				<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><span><?php echo esc_html($pages);?></span></span>
				</div>
			</div>
		</div>
		<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;"><span style="margin: 0px 20px; font-size: 30px;"><svg viewBox="64 64 896 896" focusable="false" data-icon="api" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M917.7 148.8l-42.4-42.4c-1.6-1.6-3.6-2.3-5.7-2.3s-4.1.8-5.7 2.3l-76.1 76.1a199.27 199.27 0 00-112.1-34.3c-51.2 0-102.4 19.5-141.5 58.6L432.3 308.7a8.03 8.03 0 000 11.3L704 591.7c1.6 1.6 3.6 2.3 5.7 2.3 2 0 4.1-.8 5.7-2.3l101.9-101.9c68.9-69 77-175.7 24.3-253.5l76.1-76.1c3.1-3.2 3.1-8.3 0-11.4zM769.1 441.7l-59.4 59.4-186.8-186.8 59.4-59.4c24.9-24.9 58.1-38.7 93.4-38.7 35.3 0 68.4 13.7 93.4 38.7 24.9 24.9 38.7 58.1 38.7 93.4 0 35.3-13.8 68.4-38.7 93.4zm-190.2 105a8.03 8.03 0 00-11.3 0L501 613.3 410.7 523l66.7-66.7c3.1-3.1 3.1-8.2 0-11.3L441 408.6a8.03 8.03 0 00-11.3 0L363 475.3l-43-43a7.85 7.85 0 00-5.7-2.3c-2 0-4.1.8-5.7 2.3L206.8 534.2c-68.9 69-77 175.7-24.3 253.5l-76.1 76.1a8.03 8.03 0 000 11.3l42.4 42.4c1.6 1.6 3.6 2.3 5.7 2.3s4.1-.8 5.7-2.3l76.1-76.1c33.7 22.9 72.9 34.3 112.1 34.3 51.2 0 102.4-19.5 141.5-58.6l101.9-101.9c3.1-3.1 3.1-8.2 0-11.3l-43-43 66.7-66.7c3.1-3.1 3.1-8.2 0-11.3l-36.6-36.2zM441.7 769.1a131.32 131.32 0 01-93.4 38.7c-35.3 0-68.4-13.7-93.4-38.7a131.32 131.32 0 01-38.7-93.4c0-35.3 13.7-68.4 38.7-93.4l59.4-59.4 186.8 186.8-59.4 59.4z"></path></svg></span>
			<div>
				<div>Credits Used</div>
				<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><span><?php echo esc_html($usedUserCredits);?></span></span>
				</div>
			</div>
		</div>
	</div>
	<div style="display: flex; justify-content: space-evenly; margin-top: 20px">
		<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;">
			<div>
				<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;">
					<span>
						<button type="button" class="ant-btn amzw-button amzw-button-header" ant-click-animating-without-extra-node="false">
							<a href="<?php bloginfo('url');?>/wp-admin/admin.php?page=amzwatcher" target="_blank">See Report</a>
						</button>
					</span>
				</div>
			</div>
		</div>
		<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;">
			<div>
				<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;">
					<span>
						<button type="button" class="ant-btn amzw-button amzw-button-header" ant-click-animating-without-extra-node="false">
							<a href="#" id="refresh_widget">Refresh</a>
						</button>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>
	<?php else:?>

<div style="display: flex; align-items: center; justicy-content: center; flex-direction: column;">
	<div style="margin-bottom: 10px;">
		There are no complete crawls
	</div>
		<div>
		<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;">
			<div>
				<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><button type="button" class="ant-btn amzw-button amzw-button-header" ant-click-animating-without-extra-node="false"><a href="#" id="refresh_widget">Refresh</a></button></span>
				</div>
			</div>
		</div>
	</div>
</div>
	<?php endif;
		die;
		}

		function amz_widget_callback(){?>

		<?php
			 global $wpdb;
			 $amz_plugin_check     = $wpdb->prefix . 'amz_plugin_check';
			 $sql_qet='SELECT * FROM '.$amz_plugin_check.' WHERE `pages` > 0 ORDER BY id DESC LIMIT 1';
			 $results = $wpdb->get_row(($sql_qet));
			 $pages           = 0;
			 $healthScore     = 0;
			 $healtyProduct   = 0;
			 $unHealtyProduct = 0;
			 $all_details     = 0;
			 $totalLinksCount = 0;
			 $noAffiliateIdProductsCount  = 0;
			 $totalProductCount  = 0;
			 $totalUnhealthyProductsCount  = 0;
			 $totalUnealthyLinksCount  = 0;
			 $usedUserCredits  = 0;
			 $noAffiliateIdLiksCount =0;
			if($results):
				 $pages = $results->pages;
				 $healthScore = $results->healthScore;
				 $healtyProduct = $results->healtyProduct;
				 $unHealtyProduct = $results->unHealtyProduct;
				 $all_details =json_decode($results->all_other,true);
								
				 if(isset($all_details['products']['totalProductCount'])):
				 	 $totalProductCount  =$all_details['products']['totalProductCount'];
				 endif;
				 if(isset($all_details['products']['totalUnhealthyProductsCount'])):
				 	$totalUnhealthyProductsCount  =$all_details['products']['totalUnhealthyProductsCount'];
				 endif;
				 if(isset($all_details['links']['totalLinksCount'])):
				 	 $totalLinksCount  =$all_details['links']['totalLinksCount'];
				 endif;		 
				 if(isset($all_details['links']['totalUnealthyLinksCount'])):
				 	$totalUnealthyLinksCount  =$all_details['links']['totalUnealthyLinksCount'];
				 endif;
				 if(isset($all_details['credits']['usedUserCredits'])):
				 	$usedUserCredits  =$all_details['credits']['usedUserCredits'];
				 endif;
				 if(isset($all_details['links']['noAffiliateIdLiksCount'])):
				 		$noAffiliateIdLiksCount  =$all_details['links']['noAffiliateIdLiksCount'];
				endif;

			endif;

			// $healthScore =0;
			// $totalLinksCount=0;
			// $totalUnealthyLinksCount =0;
			// $pages =0;
			// $usedUserCredits=0;
			// $totalUnhealthyProductsCount =0;
			?>

	<?php if( $healthScore !=0 || $totalLinksCount !=0 ||  $totalUnealthyLinksCount !=0 || $pages !=0 || $usedUserCredits !=0 || $totalUnhealthyProductsCount !=0 ):?>
<div>
	<div style="display: grid; grid-template-columns: repeat(2, 180px); grid-template-rows: repeat(2, 65px); gap: 2px 5px; margin-top: 15px;">
		<div>
			<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;"><span role="img" aria-label="heart" class="anticon anticon-heart" style="margin: 0px 20px; font-size: 30px;"><svg viewBox="64 64 896 896" focusable="false" data-icon="heart" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M923 283.6a260.04 260.04 0 00-56.9-82.8 264.4 264.4 0 00-84-55.5A265.34 265.34 0 00679.7 125c-49.3 0-97.4 13.5-139.2 39-10 6.1-19.5 12.8-28.5 20.1-9-7.3-18.5-14-28.5-20.1-41.8-25.5-89.9-39-139.2-39-35.5 0-69.9 6.8-102.4 20.3-31.4 13-59.7 31.7-84 55.5a258.44 258.44 0 00-56.9 82.8c-13.9 32.3-21 66.6-21 101.9 0 33.3 6.8 68 20.3 103.3 11.3 29.5 27.5 60.1 48.2 91 32.8 48.9 77.9 99.9 133.9 151.6 92.8 85.7 184.7 144.9 188.6 147.3l23.7 15.2c10.5 6.7 24 6.7 34.5 0l23.7-15.2c3.9-2.5 95.7-61.6 188.6-147.3 56-51.7 101.1-102.7 133.9-151.6 20.7-30.9 37-61.5 48.2-91 13.5-35.3 20.3-70 20.3-103.3.1-35.3-7-69.6-20.9-101.9zM512 814.8S156 586.7 156 385.5C156 283.6 240.3 201 344.3 201c73.1 0 136.5 40.8 167.7 100.4C543.2 241.8 606.6 201 679.7 201c104 0 188.3 82.6 188.3 184.5 0 201.2-356 429.3-356 429.3z" fill="#0ec851"></path><path d="M679.7 201c-73.1 0-136.5 40.8-167.7 100.4C480.8 241.8 417.4 201 344.3 201c-104 0-188.3 82.6-188.3 184.5 0 201.2 356 429.3 356 429.3s356-228.1 356-429.3C868 283.6 783.7 201 679.7 201z" fill="#e6ffeb"></path></svg></span>
				<div>
					<div>Health Score</div>
					<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><span><?php echo esc_html($healthScore);?></span></span><span> / 100</span>
					</div>
				</div>
			</div>
		</div>
		<div>
			<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;"><span style="margin: 0px 20px; font-size: 30px;"><svg viewBox="64 64 896 896" focusable="false" data-icon="tags" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M483.2 790.3L861.4 412c1.7-1.7 2.5-4 2.3-6.3l-25.5-301.4c-.7-7.8-6.8-13.9-14.6-14.6L522.2 64.3c-2.3-.2-4.7.6-6.3 2.3L137.7 444.8a8.03 8.03 0 000 11.3l334.2 334.2c3.1 3.2 8.2 3.2 11.3 0zm62.6-651.7l224.6 19 19 224.6L477.5 694 233.9 450.5l311.9-311.9zm60.16 186.23a48 48 0 1067.88-67.89 48 48 0 10-67.88 67.89zM889.7 539.8l-39.6-39.5a8.03 8.03 0 00-11.3 0l-362 361.3-237.6-237a8.03 8.03 0 00-11.3 0l-39.6 39.5a8.03 8.03 0 000 11.3l243.2 242.8 39.6 39.5c3.1 3.1 8.2 3.1 11.3 0l407.3-406.6c3.1-3.1 3.1-8.2 0-11.3z"></path></svg></span>
				<div>
					<div>Missing Tags</div>
					<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><span><?php echo esc_html($noAffiliateIdLiksCount);?></span></span><span> / <?php if(isset($totalLinksCount)): echo esc_html($totalLinksCount); else:  echo '0'; endif; ?></span>
					</div>
				</div>
			</div>
		</div>
		<div>
			<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;"><span style="margin: 0px 20px; font-size: 30px;"><svg viewBox="64 64 896 896" focusable="false" data-icon="link" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M574 665.4a8.03 8.03 0 00-11.3 0L446.5 781.6c-53.8 53.8-144.6 59.5-204 0-59.5-59.5-53.8-150.2 0-204l116.2-116.2c3.1-3.1 3.1-8.2 0-11.3l-39.8-39.8a8.03 8.03 0 00-11.3 0L191.4 526.5c-84.6 84.6-84.6 221.5 0 306s221.5 84.6 306 0l116.2-116.2c3.1-3.1 3.1-8.2 0-11.3L574 665.4zm258.6-474c-84.6-84.6-221.5-84.6-306 0L410.3 307.6a8.03 8.03 0 000 11.3l39.7 39.7c3.1 3.1 8.2 3.1 11.3 0l116.2-116.2c53.8-53.8 144.6-59.5 204 0 59.5 59.5 53.8 150.2 0 204L665.3 562.6a8.03 8.03 0 000 11.3l39.8 39.8c3.1 3.1 8.2 3.1 11.3 0l116.2-116.2c84.5-84.6 84.5-221.5 0-306.1zM610.1 372.3a8.03 8.03 0 00-11.3 0L372.3 598.7a8.03 8.03 0 000 11.3l39.6 39.6c3.1 3.1 8.2 3.1 11.3 0l226.4-226.4c3.1-3.1 3.1-8.2 0-11.3l-39.5-39.6z"></path></svg></span>
				<div>
					<div>Broken Links</div>
					<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><span><?php echo esc_html($totalUnealthyLinksCount);?></span></span><span> / <?php echo esc_html($totalLinksCount);?></span>
					</div>
				</div>
			</div>
		</div>
		<div>
			<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;"><span style="margin: 0px 20px; font-size: 30px;"><svg viewBox="64 64 896 896" focusable="false" data-icon="amazon" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M825 768.9c-3.3-.9-7.3-.4-11.9 1.3-61.6 28.2-121.5 48.3-179.7 60.2C507.7 856 385.2 842.6 266 790.3c-33.1-14.6-79.1-39.2-138-74a9.36 9.36 0 00-5.3-2c-2-.1-3.7.1-5.3.9-1.6.8-2.8 1.8-3.7 3.1-.9 1.3-1.1 3.1-.4 5.4.6 2.2 2.1 4.7 4.6 7.4 10.4 12.2 23.3 25.2 38.6 39s35.6 29.4 60.9 46.8c25.3 17.4 51.8 32.9 79.3 46.4 27.6 13.5 59.6 24.9 96.1 34.1s73 13.8 109.4 13.8c36.2 0 71.4-3.7 105.5-10.9 34.2-7.3 63-15.9 86.5-25.9 23.4-9.9 45-21 64.8-33 19.8-12 34.4-22.2 43.9-30.3 9.5-8.2 16.3-14.6 20.2-19.4 4.6-5.7 6.9-10.6 6.9-14.9.1-4.5-1.7-7.1-5-7.9zM527.4 348.1c-15.2 1.3-33.5 4.1-55 8.3-21.5 4.1-41.4 9.3-59.8 15.4s-37.2 14.6-56.3 25.4c-19.2 10.8-35.5 23.2-49 37s-24.5 31.1-33.1 52c-8.6 20.8-12.9 43.7-12.9 68.7 0 27.1 4.7 51.2 14.3 72.5 9.5 21.3 22.2 38 38.2 50.4 15.9 12.4 34 22.1 54 29.2 20 7.1 41.2 10.3 63.2 9.4 22-.9 43.5-4.3 64.4-10.3 20.8-5.9 40.4-15.4 58.6-28.3 18.2-12.9 33.1-28.2 44.8-45.7 4.3 6.6 8.1 11.5 11.5 14.7l8.7 8.9c5.8 5.9 14.7 14.6 26.7 26.1 11.9 11.5 24.1 22.7 36.3 33.7l104.4-99.9-6-4.9c-4.3-3.3-9.4-8-15.2-14.3-5.8-6.2-11.6-13.1-17.2-20.5-5.7-7.4-10.6-16.1-14.7-25.9-4.1-9.8-6.2-19.3-6.2-28.5V258.7c0-10.1-1.9-21-5.7-32.8-3.9-11.7-10.7-24.5-20.7-38.3-10-13.8-22.4-26.2-37.2-37-14.9-10.8-34.7-20-59.6-27.4-24.8-7.4-52.6-11.1-83.2-11.1-31.3 0-60.4 3.7-87.6 10.9-27.1 7.3-50.3 17-69.7 29.2-19.3 12.2-35.9 26.3-49.7 42.4-13.8 16.1-24.1 32.9-30.8 50.4-6.7 17.5-10.1 35.2-10.1 53.1L408 310c5.5-16.4 12.9-30.6 22-42.8 9.2-12.2 17.9-21 25.8-26.5 8-5.5 16.6-9.9 25.7-13.2 9.2-3.3 15.4-5 18.6-5.4 3.2-.3 5.7-.4 7.6-.4 26.7 0 45.2 7.9 55.6 23.6 6.5 9.5 9.7 23.9 9.7 43.3v56.6c-15.2.6-30.4 1.6-45.6 2.9zM573.1 500c0 16.6-2.2 31.7-6.5 45-9.2 29.1-26.7 47.4-52.4 54.8-22.4 6.6-43.7 3.3-63.9-9.8-21.5-14-32.2-33.8-32.2-59.3 0-19.9 5-36.9 15-51.1 10-14.1 23.3-24.7 40-31.7s33-12 49-14.9c15.9-3 33-4.8 51-5.4V500zm335.2 218.9c-4.3-5.4-15.9-8.9-34.9-10.7-19-1.8-35.5-1.7-49.7.4-15.3 1.8-31.1 6.2-47.3 13.4-16.3 7.1-23.4 13.1-21.6 17.8l.7 1.3.9.7 1.4.2h4.6c.8 0 1.8-.1 3.2-.2 1.4-.1 2.7-.3 3.9-.4 1.2-.1 2.9-.3 5.1-.4 2.1-.1 4.1-.4 6-.7.3 0 3.7-.3 10.3-.9 6.6-.6 11.4-1 14.3-1.3 2.9-.3 7.8-.6 14.5-.9 6.7-.3 12.1-.3 16.1 0 4 .3 8.5.7 13.6 1.1 5.1.4 9.2 1.3 12.4 2.7 3.2 1.3 5.6 3 7.1 5.1 5.2 6.6 4.2 21.2-3 43.9s-14 40.8-20.4 54.2c-2.8 5.7-2.8 9.2 0 10.7s6.7.1 11.9-4c15.6-12.2 28.6-30.6 39.1-55.3 6.1-14.6 10.5-29.8 13.1-45.7 2.4-15.9 2-26.2-1.3-31z"></path></svg></span>
				<div>
					<div>Broken Products</div>
					<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><span><?php echo esc_html($totalUnhealthyProductsCount);?></span></span><span> / <?php echo esc_html($totalProductCount);?></span>
					</div>
				</div>
			</div>
		</div>
		<div>
			<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;"><span style="margin: 0px 20px; font-size: 30px;"><svg viewBox="64 64 896 896" focusable="false" data-icon="file-done" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M688 312v-48c0-4.4-3.6-8-8-8H296c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h384c4.4 0 8-3.6 8-8zm-392 88c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8h184c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8H296zm376 116c-119.3 0-216 96.7-216 216s96.7 216 216 216 216-96.7 216-216-96.7-216-216-216zm107.5 323.5C750.8 868.2 712.6 884 672 884s-78.8-15.8-107.5-44.5C535.8 810.8 520 772.6 520 732s15.8-78.8 44.5-107.5C593.2 595.8 631.4 580 672 580s78.8 15.8 107.5 44.5C808.2 653.2 824 691.4 824 732s-15.8 78.8-44.5 107.5zM761 656h-44.3c-2.6 0-5 1.2-6.5 3.3l-63.5 87.8-23.1-31.9a7.92 7.92 0 00-6.5-3.3H573c-6.5 0-10.3 7.4-6.5 12.7l73.8 102.1c3.2 4.4 9.7 4.4 12.9 0l114.2-158c3.9-5.3.1-12.7-6.4-12.7zM440 852H208V148h560v344c0 4.4 3.6 8 8 8h56c4.4 0 8-3.6 8-8V108c0-17.7-14.3-32-32-32H168c-17.7 0-32 14.3-32 32v784c0 17.7 14.3 32 32 32h272c4.4 0 8-3.6 8-8v-56c0-4.4-3.6-8-8-8z"></path></svg></span>
				<div>
					<div>Pages Discovered</div>
					<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><span><?php echo esc_html($pages);?></span></span>
					</div>
				</div>
			</div>
		</div>
		<div>
			<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;"><span style="margin: 0px 20px; font-size: 30px;"><svg viewBox="64 64 896 896" focusable="false" data-icon="api" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M917.7 148.8l-42.4-42.4c-1.6-1.6-3.6-2.3-5.7-2.3s-4.1.8-5.7 2.3l-76.1 76.1a199.27 199.27 0 00-112.1-34.3c-51.2 0-102.4 19.5-141.5 58.6L432.3 308.7a8.03 8.03 0 000 11.3L704 591.7c1.6 1.6 3.6 2.3 5.7 2.3 2 0 4.1-.8 5.7-2.3l101.9-101.9c68.9-69 77-175.7 24.3-253.5l76.1-76.1c3.1-3.2 3.1-8.3 0-11.4zM769.1 441.7l-59.4 59.4-186.8-186.8 59.4-59.4c24.9-24.9 58.1-38.7 93.4-38.7 35.3 0 68.4 13.7 93.4 38.7 24.9 24.9 38.7 58.1 38.7 93.4 0 35.3-13.8 68.4-38.7 93.4zm-190.2 105a8.03 8.03 0 00-11.3 0L501 613.3 410.7 523l66.7-66.7c3.1-3.1 3.1-8.2 0-11.3L441 408.6a8.03 8.03 0 00-11.3 0L363 475.3l-43-43a7.85 7.85 0 00-5.7-2.3c-2 0-4.1.8-5.7 2.3L206.8 534.2c-68.9 69-77 175.7-24.3 253.5l-76.1 76.1a8.03 8.03 0 000 11.3l42.4 42.4c1.6 1.6 3.6 2.3 5.7 2.3s4.1-.8 5.7-2.3l76.1-76.1c33.7 22.9 72.9 34.3 112.1 34.3 51.2 0 102.4-19.5 141.5-58.6l101.9-101.9c3.1-3.1 3.1-8.2 0-11.3l-43-43 66.7-66.7c3.1-3.1 3.1-8.2 0-11.3l-36.6-36.2zM441.7 769.1a131.32 131.32 0 01-93.4 38.7c-35.3 0-68.4-13.7-93.4-38.7a131.32 131.32 0 01-38.7-93.4c0-35.3 13.7-68.4 38.7-93.4l59.4-59.4 186.8 186.8-59.4 59.4z"></path></svg></span>
				<div>
					<div>Credits Used</div>
					<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><span><?php echo esc_html($usedUserCredits);?></span></span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div style="display: flex; justify-content: space-evenly; margin-top: 20px">
		<div>
			<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;">
				<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;">
					<span>
						<button type="button" class="ant-btn amzw-button amzw-button-header" ant-click-animating-without-extra-node="false">
							<a href="<?php bloginfo('url');?>/wp-admin/admin.php?page=amzwatcher" target="_blank">See Report</a>
						</button>
					</span>
				</div>
			</div>
		</div>
		<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;">
			<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;">
				<span>
					<button type="button" class="ant-btn amzw-button amzw-button-header" ant-click-animating-without-extra-node="false">
						<a href="#" id="refresh_widget">Refresh</a>
					</button>
				</span>
			</div>
		</div>
	</div>
</div>
	<?php else:?>

<div style="display: flex; align-items: center; justicy-content: center; flex-direction: column;">
	<div style="margin-bottom: 10px;">
		There are no complete crawls
	</div>
		<div>
		<div style="padding: 0px; display: flex; align-items: center; margin: 5px 0px 0px; text-align: left;">
			<div>
				<div style="color: rgb(89, 89, 89); font-size: 17px; font-weight: 500;"><span><button type="button" class="ant-btn amzw-button amzw-button-header" ant-click-animating-without-extra-node="false"><a href="#" id="refresh_widget">Refresh</a></button></span>
				</div>
			</div>
		</div>
	</div>
</div>



	<?php endif;?>
	<?php }


		function amz_custom_cron_schedule( $schedules ) {
            $schedules['every_six_hours'] = array(
                'interval' => 21600, // Every 6 hours
                'display'  => __( 'Every 6 hours' ),
            );
            return $schedules;
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

	 * Return the plugin slug.

	 *

	 * @since    1.0.0

	 *

	 * @return    Plugin slug variable.

	 */

	public function get_plugin_slug() {

		return $this->plugin_slug;

	}



	/**

	 * Return the plugin version.

	 *

	 * @since    1.0.0

	 *

	 * @return    Plugin slug variable.

	 */

	public function get_plugin_version() {

		return $this->plugin_version;

	}



	/**

	 * Fired when the plugin is activated.

	 *

	 * @since    1.0.0

	 */

	public static function activate() {

		add_option( 'wpr_example_setting' );

		create_datebase_tables();
		add_option( 'amzw_failed_fetch', false );
		add_option( 'amzw_settings_widget_enabled', true );

	}



	/**

	 * Fired when the plugin is deactivated.

	 *

	 * @since    1.0.0

	 */

	public static function deactivate() {

		//delete_amz_database_tables();

	}





	/**

	 * Return an instance of this class.

	 *

	 * @since     1.0.0

	 *

	 * @return    object    A single instance of this class.

	 */

	public static function get_instance() {



		// If the single instance hasn't been set, set it now.

		if ( null == self::$instance ) {

			self::$instance = new self;

		}



		return self::$instance;

	}


}
	function delete_amz_database_tables(){
       global $wpdb;
       $tableArray = [   
       $wpdb->prefix . "amz_site_check",
       $wpdb->prefix . "amz_user_check",
       $wpdb->prefix . "amz_all_jobs",
       $wpdb->prefix . "amz_jobs_details",
       $wpdb->prefix . "amz_plugin_check",
    ];

	   foreach ($tableArray as $tablename) {
	      $wpdb->query("DROP TABLE IF EXISTS $tablename");
	   }
	}




 	function create_datebase_tables() {
    	global $wpdb;
	    $charset_collate = $wpdb->get_charset_collate();
	    
	    $amz_user_check  = $wpdb->prefix . 'amz_user_check';
	    $amz_site_check  = $wpdb->prefix . 'amz_site_check';
	    $amz_all_jobs    = $wpdb->prefix . 'amz_all_jobs';
	    $amz_jobs_details= $wpdb->prefix . 'amz_jobs_details';
	    $amz_plugin_check= $wpdb->prefix . 'amz_plugin_check';

	  if($wpdb->get_var("show tables like '$amz_user_check'") != $amz_user_check) {
	    $sql = "CREATE TABLE `{$amz_user_check}` (
	      id bigint(15) NOT NULL AUTO_INCREMENT,
	     `userId` varchar(255)  NULL,
	     `plan` varchar(50)  NULL,
	     `planCredits` varchar(50)  NULL,
	     `credits` varchar(50)  NULL,
	     `allowedProjects` varchar(50)  NULL,
	     `isYearly` varchar(50)  NULL,
	     `isActive` varchar(50)  NULL,
	      created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	      PRIMARY KEY (id)
	    ) $charset_collate;";
	      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    dbDelta($sql);
	  }

	   if($wpdb->get_var("show tables like '$amz_site_check'") != $amz_site_check) {
	    $sql = "CREATE TABLE `{$amz_site_check}` (
	      id bigint(15) NOT NULL AUTO_INCREMENT,
	     `siteUrl` varchar(255)  NULL,
	     `userId` varchar(255)  NULL,
	     `siteId` varchar(50)  NULL,
	     `credits` varchar(255)  NULL,
	     `crawlJobIds` varchar(500)  NULL,
	     `all_other` longtext  NULL,
	      created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	      PRIMARY KEY (id)
	    ) $charset_collate;";
	      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    dbDelta($sql);
	  }
 
  	if($wpdb->get_var("show tables like '$amz_all_jobs'") != $amz_all_jobs) {
	    $sql = "CREATE TABLE `{$amz_all_jobs}` (
	      id bigint(15) NOT NULL AUTO_INCREMENT,
	     `siteUrl` varchar(255)  NULL,
	     `userId` varchar(255)  NULL,
	     `siteId` varchar(50)  NULL,
	     `credits` varchar(255)  NULL,
	     `crawlJobId` varchar(500)  NULL,
	     `all_other` longtext  NULL,
	      created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	      PRIMARY KEY (id)
	    ) $charset_collate;";
	    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    dbDelta($sql);
	  }

	  if($wpdb->get_var("show tables like '$amz_jobs_details'") != $amz_jobs_details) {
	    $sql = "CREATE TABLE `{$amz_jobs_details}` (
	      id bigint(15) NOT NULL AUTO_INCREMENT,
	     `crawlJobId` varchar(500)  NULL,
	     `parent_id`  bigint(15)  NULL,
	     `all_other` longtext  NULL,
	      created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	      PRIMARY KEY (id)
	    ) $charset_collate;";
	      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	      dbDelta($sql);
	  }

	  if($wpdb->get_var("show tables like '$amz_plugin_check'") != $amz_plugin_check) {
	    $sql = "CREATE TABLE `{$amz_plugin_check}` (
	      id bigint(15) NOT NULL AUTO_INCREMENT,
	     `crawlJobId` varchar(500)  NULL,
	     `healthScore` varchar(255)  NULL,
	     `pages` varchar(255)  NULL,
	     `healtyProduct` varchar(50)  NULL,
	     `unHealtyProduct` varchar(255)  NULL,
	     `all_other` longtext  NULL,
	      created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	      PRIMARY KEY (id)
	    ) $charset_collate;";
	    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	    dbDelta($sql);
	  }

	}

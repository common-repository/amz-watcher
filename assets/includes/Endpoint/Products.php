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
class Products {
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
        $endpoint = '/products/(?P<id>.*)';

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'get_products_crud' ),
                'permission_callback'   => array( $this, 'products_permissions_check' ),
                'args'                  => [
                    'id' => [
                        'description' => __( 'Unique identifier for the term.' ),
                        'type'        => 'string',
                    ],
                ],
            ),
        ) );
    }

    /**
     * Get Products
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    function get_products_crud( $request ) {
        global $wpdb;
        $parameters = $request->get_params();

        $amz_products_check  = $wpdb->prefix . 'amz_jobs_details';
        $job_id ='"'.$parameters['id'].'"';
        $sql_qet ='SELECT * FROM '.$amz_products_check.' WHERE `crawlJobId`='.$job_id;

		$results = $wpdb->get_row(($sql_qet));

        if ( ! $results ) {
            return new \WP_REST_Response( array(
                'success' => false,
                'value' => array('')
            ), 200 );
        }

        $results = base64_decode($results->all_other);

        // Don't return false if there is no option


        return new \WP_REST_Response( array(
            'success' => true,
            'value' => $results
        ), 200 );
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function products_permissions_check( $request ) {
        return current_user_can( 'edit_pages' );
    }
}

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
class Site {
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
        $endpoint = '/site/';

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array( $this, 'get_site' ),
                'permission_callback'   => array( $this, 'site_permissions_check' ),
                'args'                  => array(),
            ),
        ) );

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::CREATABLE,
                'callback'              => array( $this, 'update_site' ),
                'permission_callback'   => array( $this, 'site_permissions_check' ),
                'args'                  => array(),
            ),
        ) );

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array( $this, 'update_site' ),
                'permission_callback'   => array( $this, 'site_permissions_check' ),
                'args'                  => array(),
            ),
        ) );

        register_rest_route( $namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::DELETABLE,
                'callback'              => array( $this, 'delete_site' ),
                'permission_callback'   => array( $this, 'site_permissions_check' ),
                'args'                  => array(),
            ),
        ) );
    }

    /**
     * Get Site
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function get_site( $request ) {
        global $wpdb;
        $amz_site_check  = $wpdb->prefix . 'amz_site_check';
        $sql_qet='SELECT * FROM '.$amz_site_check;
		$results = $wpdb->get_row(($sql_qet));

        // Don't return false if there is no option
        if ( ! $results ) {
            return new \WP_REST_Response( array(
                'success' => false,
                'value' => 'Error'
            ), 200 );
        }

        return new \WP_REST_Response( array(
            'success' => true,
            'value' => $results
        ), 200 );
    }

    /**
     * Create OR Update Site
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function update_site( $request ) {
        $updated = update_option( 'wpr_site_setting', sanitize_text_field($request->get_param( 'siteSetting' )) );

        return new \WP_REST_Response( array(
            'success'   => $updated,
            'value'     => $request->get_param( 'siteSetting' )
        ), 200 );
    }

    /**
     * Delete Site
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function delete_site( $request ) {
        $deleted = delete_option( 'wpr_site_setting' );

        return new \WP_REST_Response( array(
            'success'   => $deleted,
            'value'     => ''
        ), 200 );
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function site_permissions_check( $request ) {
        return current_user_can( 'edit_pages' );
    }
}

<?php

// If uninstall not called from WordPress exit

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )

  exit();



delete_option( 'wpr_example_setting' );
delete_option('amzw_api_key');
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
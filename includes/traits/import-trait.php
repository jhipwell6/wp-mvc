<?php

namespace WP_MVC\Traits;

use \ActionScheduler;
use \ActionScheduler_Store;

if ( ! defined( 'ABSPATH' ) )
	exit;

trait Import_Trait
{
	
	// example
//	protected $importer_config = array(
//		'wp_mvc/import_schedule' => array(
//			'timestamp' => 'strtotime( "tomorrow + 8 hours" )',
//			'interval' => DAY_IN_SECONDS,
//			'group' => 'wp-mvc-import-schedule',
//		),
//	);
	
	public function __construct()
	{
		add_action( 'init', array( $this, 'set_import_schedules' ) );
	}

	public function set_import_schedule()
	{
		if ( empty( $this->importer_config ) ) {
			return false;
		}
		
		foreach ( $this->importer_config as $hook => $import ) {
			$results = WP_MVC()->queue()->search( array( 'hook' => $hook, 'status' => ActionScheduler_Store::STATUS_PENDING ) );
			if ( empty( $results ) ) {
				WP_MVC()->queue()->schedule_recurring( $import['timestamp'], $import['interval'], $hook, array(), $import['group'] );
			}

			$this->garbage_collection( $results );
		}
	}
	
	protected function add_batch( $args, $hook, $group )
	{
		if ( empty( WP_MVC()->queue()->search( array( 'hook' => $hook, 'args' => $args, 'status' => ActionScheduler_Store::STATUS_PENDING ) ) ) ) {
			WP_MVC()->queue()->add( $hook, $args, $group );
		}
	}
	
	private function garbage_collection( $results )
	{
		if ( count( $results ) > 1 ) {
			$i = 0;
			foreach ( $results as $action_id => $action ) {
				if ( $i > 0 ) {
					WP_MVC()->queue()->delete_action( $action_id );
				}
				$i++;
			}
		}
	}
}
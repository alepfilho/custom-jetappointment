<?php
namespace JET_APB\Cron;

use JET_APB\Plugin;

class Events_Dispatcher extends Base {

	public function __construct() {
		// Handler is allways atached, event initilized only when need it
		add_action( $this->event_name(), array( $this, 'event_callback' ) );
		// Schedule test handler
		add_action( 'init', function() {
			
			if ( ! Plugin::instance()->workflows->is_debug() ) {
				return;
			}

			$this->event_callback();
			die();
			
		}, 100 );
	}

	/**
	 * Event callback
	 * 
	 * @return [type] [description]
	 */
	public function event_callback() {
		Plugin::instance()->workflows->collection->dispatch_workflows( true );
	}

	/**
	 * Event interval name
	 * 
	 * @return [type] [description]
	 */
	public function event_interval() {
		return 'twicedaily';
	}

	/**
	 * Event hook name
	 * 
	 * @return [type] [description]
	 */
	public function event_name() {
		return 'jet-apb-events-dispatcher';
	}

}

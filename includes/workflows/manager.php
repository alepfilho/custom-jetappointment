<?php
namespace JET_APB\Workflows;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Define workflows manager class
 */
class Manager {

	private $_events  = [];
	private $_actions = [];

	public $collection;

	public function __construct() {
		
		$this->collection = new Workflows();
		
		add_action( 'init', [ $this, 'init' ], 99 );
		add_action( 'init', [ $this->collection, 'dispatch_workflows' ], 999 );
		
	}

	public function init() {
		$this->events();
		$this->actions();
	}

	public function events() {

		$this->register_event_type( new Events\Appointment_Created() );
		$this->register_event_type( new Events\Appointment_Status_Changed() );

		do_action( 'jet-apb/workflows/events/register', $this );

	}

	public function is_debug() {
		return ( ! empty( $_GET['jet_apb_test_scheduled_workflows'] ) && current_user_can( 'manage_options' ) );
	}

	public function actions() {

		$this->register_action_type( new Actions\Send_Email() );
		$this->register_action_type( new Actions\Webhook() );

		do_action( 'jet-apb/workflows/actions/register', $this );

	}

	public function get_event( $event_id ) {
		return isset( $this->_events[ $event_id ] ) ? $this->_events[ $event_id ] : false;
	}

	public function get_action( $action_id ) {
		return isset( $this->_actions[ $action_id ] ) ? $this->_actions[ $action_id ] : false;
	}

	public function register_event_type( Events\Base_Event $event_type ) {
		$this->_events[ $event_type->get_id() ] = $event_type;
	}

	public function register_action_type( Actions\Base_Action $action_type ) {
		$this->_actions[ $action_type->get_id() ] = $action_type;
	}

	public function get_options_for_js( $key = 'events', $placeholder = 'Select...' ) {
		
		$items  = [];
		$result = [ [
			'value' => '',
			'label' => $placeholder,
		] ];

		switch ( $key ) {
			case 'events':
				$items = $this->_events;
				break;
			
			case 'actions':
				$items = $this->_actions;
				break;
		}

		foreach ( $items as $item ) {
			
			$result[] = [
				'value' => $item->get_id(),
				'label' => $item->get_name(),
			];

		}

		return $result;

	}

}
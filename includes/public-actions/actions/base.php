<?php
namespace JET_APB\Public_Actions\Actions;

abstract class Base {
	
	public function __construct() {
		add_filter( 'jet-apb/public-actions/process/' . $this->action_id(), [ $this, 'do_action' ], 10, 2 );
		add_action( 'jet-apb/public-actions/print-styles/' . $this->action_id(), [ $this, 'action_css' ] );
		add_action( 'jet-apb/form-action/insert-appointment', [ $this, 'save_action_meta' ], 10, 2 );
		add_action( 'jet-apb/display-meta-fields', [ $this, 'show_action_meta' ], 10, 2 );
		add_filter( 'jet-apb/macros-list', [ $this, 'register_macros' ], 10, 2 );
	}

	abstract public function action_id();

	abstract public function do_action( $appointment = [], $manager = null );

	public function action_css() {
	}

	public function register_macros( $macros, $manager ) {
		return $macros;
	}

	public function save_action_meta( $appointment, $action ) {
		foreach ( $this->action_meta() as $key => $data ) {
			$appointment->set_meta( [
				$key => call_user_func( $data['get_cb'], $appointment, $action ),
			] );
		}
	}

	public function show_action_meta( $fields = [] ) {

		foreach ( $this->action_meta() as $key => $data ) {
		
			$fields[ $key ] = [
				'label' => $data['label'],
				'cb'    => $data['show_cb'],
			];
			
		}

		return $fields;

	}

	public function action_meta() {
		return [];
	}

}

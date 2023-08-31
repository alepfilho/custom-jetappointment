<?php
namespace JET_APB\Integrations\Zoom;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Macros {

	private $macros_manager = null;

	public function __construct() {
		add_filter( 'jet-apb/macros-list', [ $this, 'register_macros' ], 10, 2 );
	}

	public function register_macros( $macros, $manager ) {

		$this->macros_manager = $manager;

		$macros['zoom_start_url'] = [
			'label' => __( 'Zoom Meeting Start URL (for admin)', 'jet-appointments-booking' ),
			'cb'    => [ $this, 'get_start_url' ]
		];

		$macros['zoom_join_url'] = [
			'label' => __( 'Zoom Meeting Join URL (for user)', 'jet-appointments-booking' ),
			'cb'    => [ $this, 'get_join_url' ]
		];

		$macros['zoom_password'] = [
			'label' => __( 'Zoom Meeting Password', 'jet-appointments-booking' ),
			'cb'    => [ $this, 'get_password' ]
		];

		return $macros;
	}

	public function get_start_url( $result = null, $args_str = null ) {
		$appointment = $this->macros_manager->get_macros_object();
		return isset( $appointment['meta']['zoom_start_url'] ) ? $appointment['meta']['zoom_start_url'] : '';
	}

	public function get_join_url( $result = null, $args_str = null ) {
		$appointment = $this->macros_manager->get_macros_object();
		return isset( $appointment['meta']['zoom_join_url'] ) ? $appointment['meta']['zoom_join_url'] : '';
	}

	public function get_password( $result = null, $args_str = null ) {
		$appointment = $this->macros_manager->get_macros_object();
		return isset( $appointment['meta']['zoom_password'] ) ? $appointment['meta']['zoom_password'] : '';
	}

}

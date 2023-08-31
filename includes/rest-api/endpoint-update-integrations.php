<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;
use JET_APB\Integrations\Manager as Integrations_Manager;

class Endpoint_Update_Integrations extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'update-appointment-integrations';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {

		$params       = $request->get_params();
		$integrations = ! empty( $params['integrations'] ) ? $params['integrations'] : [];
		
		Integrations_Manager::instance()->update_data( $integrations );

		return rest_ensure_response( [ 'success' => true, ] );

	}

	/**
	 * Check user access to current end-popint
	 *
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Returns endpoint request method - GET/POST/PUT/DELTE
	 *
	 * @return string
	 */
	public function get_method() {
		return 'POST';
	}

	/**
	 * Returns arguments config
	 *
	 * @return array
	 */
	public function get_args() {
		return [
			'integrations' => [
				'default'  => [],
				'required' => false,
			],
		];
	}

}
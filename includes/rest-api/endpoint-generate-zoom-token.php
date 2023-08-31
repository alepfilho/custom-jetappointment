<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;
use JET_APB\Integrations\Manager as Integrations_Manager;
use JET_APB\Integrations\Zoom\API as Zoom_API;

class Endpoint_Generate_Zoom_Token extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'appointment-zoom-token';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {

		$params   = $request->get_params();
		$settings = ! empty( $params['settings'] ) ? $params['settings'] : [];
		
		$api = new Zoom_API( $settings['account_id'], $settings['client_id'], $settings['client_secret'] );

		$token = $api->get_token();

		if ( ! $token ) {
			return new \WP_Error( 404, $api->last_error );
		}

		return rest_ensure_response( [
			'success' => true,
			'token' => $token,
			'message' => __( 'Access token created. Now you can create meetings with Zoom API', 'jet-engine' ),
		] );

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
			'settings' => [
				'default'  => [],
				'required' => true,
			],
		];
	}

}
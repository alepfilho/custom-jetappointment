<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;

class Endpoint_Provider_Services extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'appointment-provider-services';
	}

	/**
	 * API callback
	 *
	 */
	public function callback( $request ) {

		$params          = $request->get_params();
		$provider        = ! empty( $params['provider'] ) ? absint( $params['provider'] ) : 0;
		$is_ajax         = ! empty( $params['is_ajax'] ) ? $params['is_ajax'] : false;
		$namespace       = ! empty( $params['namespace'] ) ? $params['namespace'] : 'jet-form';

		if ( ! $provider ) {
			return rest_ensure_response( array(
				'success' => false,
			) );
		}

		$services = Plugin::instance()->tools->get_services_for_provider( $provider );

		return rest_ensure_response( array(
			'success'  => true,
			'services' => $services,
		) );
	}

	/**
	 * Returns endpoint request method - GET/POST/PUT/DELTE
	 *
	 * @return string
	 */
	public function get_method() {
		return 'GET';
	}

	/**
	 * Returns arguments config
	 *
	 * @return array
	 */
	public function get_args() {
		return array(
			'provider'  => array(
				'default'  => 0,
				'required' => true,
			),
			'is_ajax'   => array(
				'default'  => false,
				'required' => false,
			),
			'namespace' => array(
				'default'  => 'jet-form',
				'required' => false,
			),
		);
	}

}

<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;

class Endpoint_Update_Workflows extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'update-appointment-workflows';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {

		$params    = $request->get_params();
		$workflows = ! empty( $params['workflows'] ) ? $params['workflows'] : [];
		
		Plugin::instance()->workflows->collection->update_workflows( $workflows );

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
			'workflows' => [
				'default'  => [],
				'required' => true,
			],
		];
	}

}
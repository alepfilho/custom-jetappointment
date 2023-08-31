<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;
use JET_APB\Time_Slots;

class Endpoint_Get_Appointment extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'get-appointment';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {
		
		$params  = $request->get_params();
		$item_id = ! empty( $params['id'] ) ? absint( $params['id'] ) : 0;

		if ( ! $item_id ) {
			return new \WP_Error( 404, 'Item ID not found in the request' );
		}

		return rest_ensure_response( [
			'success' => true,
			'item'    => $this->format_dates( Plugin::instance()->db->get_appointment_by( 'ID', $item_id ) ),
		] );

	}

	public function format_dates( $appointment = [] ) {

		//$date_format = get_option( 'date_format', 'd/m/y' );
		$date_format = 'd/m/y';
		$time_format = get_option( 'time_format', 'H:i' );

		$appointment['date_timestamp']     = $appointment['date'];
		$appointment['slot_timestamp']     = $appointment['slot'];
		$appointment['slot_end_timestamp'] = $appointment['slot_end'];

		$appointment['date']     = date_i18n( $date_format, $appointment['date'] );
		$appointment['slot']     = date_i18n( $time_format, $appointment['slot'] );
		$appointment['slot_end'] = date_i18n( $time_format, $appointment['slot_end'] );

		// remove 0 orders
		$appointment['order_id'] = ! empty( $appointment['order_id'] ) ? $appointment['order_id'] : '';
		
		return $appointment;

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
		return 'GET';
	}

	/**
	 * Get query param. Regex with query parameters
	 *
	 * @return string
	 */
	public function get_query_params() {
		return '(?P<id>[\d]+)';
	}

	/**
	 * Returns arguments config
	 *
	 * @return array
	 */
	public function get_args() {
		return array();
	}

}

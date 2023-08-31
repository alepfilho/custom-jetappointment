<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;
use JET_APB\Time_Slots;

class Endpoint_Appointments_List extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'appointments-list';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {

		$params       = $request->get_params();
		$appointments = Plugin::instance()->db->appointments->get_appointments( $params );
		$filter       = ! empty( $params['filter'] ) ? json_decode( $params['filter'], true ) : array();

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $this->format_dates( $appointments ),
			'total'   => intval( Plugin::instance()->db->appointments->count( $filter ) ),
			'on_page' => count( $appointments ),
		) );

	}

	public function format_dates( $appointments = array() ) {

		//$date_format = get_option( 'date_format', 'd/m/y' );
		$date_format = 'd/m/y';
		$time_format = get_option( 'time_format', 'H:i' );

		return array_map( function( $appointment ) use ( $date_format, $time_format ) {

			$appointment['date_timestamp']     = $appointment['date'];
			$appointment['slot_timestamp']     = $appointment['slot'];
			$appointment['slot_end_timestamp'] = $appointment['slot_end'];

			$appointment['date']     = date_i18n( $date_format, $appointment['date'] );
			$appointment['slot']     = date_i18n( $time_format, $appointment['slot'] );
			$appointment['slot_end'] = date_i18n( $time_format, $appointment['slot_end'] );

			// remove 0 orders
			$appointment['order_id'] = ! empty( $appointment['order_id'] ) ? $appointment['order_id'] : '';
			
			return $appointment;
		}, $appointments );
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
	 * Returns arguments config
	 *
	 * @return array
	 */
	public function get_args() {
		return array(
			'offset' => array(
				'default'  => 0,
				'required' => false,
			),
			'per_page' => array(
				'default'  => 50,
				'required' => false,
			),
			'filter' => array(
				'default'  => array(),
				'required' => false,
			),
			'mode' => array(
				'default'  => 'all',
				'required' => false,
			),
			'sort' => array(
				'default'  => array(),
				'required' => false,
			),
		);
	}

}

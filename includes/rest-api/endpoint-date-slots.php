<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;
use JET_APB\Time_Types;
use JET_APB\Time_Slots;

class Endpoint_Date_Slots extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'appointment-date-slots';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {
		$params  = $request->get_params();
		$service = ! empty( $params['service'] ) ? absint( $params['service'] ) : 0 ;
		$date    = ! empty( $params['date'] ) ? absint( $params['date'] ) : 0 ;
		
		if ( ! $service || ! $date ) {
			return rest_ensure_response( array(
				'success' => false,
			) );
		}

		$use_local_time = apply_filters( 'jet-apb/time-slots/use-local-time', false );
		
		if ( $use_local_time ) {
			$timestamp = ! empty( $params['timestamp'] ) ? absint( $params['timestamp'] ) : 0;
		} else {
			$timestamp = time() + current_datetime()->getOffset();
		}
		
		$result =  Time_Types::render_frontend_view( [
			'service'        => $service,
			'provider'       => ! empty( $params['provider'] ) ? absint( $params['provider'] ) : 0,
			'timezone'       => ! empty( $params['timezone'] ) ? esc_attr( $params['timezone'] ) : false,
			'date'           => $date,
			'time'           => $timestamp,
			'selected_slots' => ! empty( $params['selected_slots'] ) ? json_decode( $params['selected_slots'] ) : [] ,
			'admin'          => ! empty( $params['admin'] ) ? filter_var( $params['admin'], FILTER_VALIDATE_BOOLEAN ) : false,
		]);

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $result,
		) );
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
		return array(
			'date' => array(
				'default'  => '',
				'required' => true,
			),
			'service' => array(
				'default'  => '',
				'required' => true,
			),
			'provider' => array(
				'default'  => '',
				'required' => false,
			),
			'timestamp' => array(
				'default'  => '',
				'required' => false,
			),
			'timezone' => array(
				'default'  => '',
				'required' => false,
			),
		);
	}

}
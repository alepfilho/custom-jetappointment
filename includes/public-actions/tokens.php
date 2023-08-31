<?php
namespace JET_APB\Public_Actions;

use JET_APB\Plugin;

/**
 * Public actions manager
 */
class Tokens {
	
	public static $token_key  = '_action_token';
	public static $timestamp  = null;

	public function get_appointment_by_token( $token = '' ) {
		
		$raw_meta = Plugin::instance()->db->appointments_meta->query( [
			'meta_key'   => self::$token_key,
			'meta_value' => $token,
		] );

		if ( empty( $raw_meta ) ) {
			return false;
		}

		$appointment_id = $raw_meta[0]['appointment_id'];

		return Plugin::instance()->db->get_appointment_by( 'ID', $appointment_id );

	}

	public function token_url( $url, $appointment = [] ) {
		return add_query_arg( [ self::$token_key => $this->get_token( $appointment ) ], $url );
	}

	public function get_token( $appointment = [] ) {
	
		if ( is_object( $appointment ) ) {
			$appointment = $appointment->to_array();
		}

		if ( ! self::$timestamp ) {
			self::$timestamp = time();
		}

		$str = $appointment['slot'] . $appointment['provider'] . $appointment['service'] . $appointment['user_email'];

		return md5( $str ) . self::$timestamp;

	}

}

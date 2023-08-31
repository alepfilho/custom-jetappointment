<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;

class Endpoint_Appointment_Meta extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'appointment-meta';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {

		$params       = $request->get_params();
		$item_id      = ! empty( $params['id'] ) ? absint( $params['id'] ) : 0;
		$result       = [];
		$allowed_meta = $this->get_allowed_meta_fields();

		$table     = Plugin::instance()->db->appointments_meta->table();
		$keys_list = array_map( function( $item ) {
			return "'$item'";
		}, array_keys( $allowed_meta ) );

		$keys_list = implode( ', ', $keys_list );

		$raw_meta = Plugin::instance()->db->appointments_meta->wpdb()->get_results( "SELECT * FROM $table WHERE appointment_id = $item_id AND meta_key IN ( $keys_list );" );

		foreach ( $raw_meta as $row ) {
			
			$meta_value = maybe_unserialize( $row->meta_value );
			$data       = $allowed_meta[ $row->meta_key ];
			
			if ( ! empty( $data['cb'] ) && is_callable( $data['cb'] ) ) {
				$meta_value = call_user_func( $data['cb'], $meta_value, $row->meta_key );
			}

			$result[] = [
				'label' => ! empty( $data['label'] ) ? $data['label'] : ucfirst( str_replace( '_', ' ', $row->meta_key ) ),
				'value' => $meta_value,
				'key'   => trim( $row->meta_key, '-_' ),
			];

		}

		return rest_ensure_response( array(
			'success' => true,
			'fields'  => $result,
		) );

	}

	public function get_allowed_meta_fields() {
		return apply_filters( 'jet-apb/display-meta-fields', [
			'user_local_date' => [
				'label' => __( 'User Local Date', 'jet-appointments-booking' ),
				'cb'    => false,
			],
			'user_local_time' => [
				'label' => __( 'User Local Time', 'jet-appointments-booking' ),
				'cb'    => false,
			],
			'user_timezone' => [
				'label' => __( 'User Timezone', 'jet-appointments-booking' ),
				'cb'    => false,
			],
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

}
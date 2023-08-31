<?php
namespace JET_APB\DB;

/**
 * Database manager class
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define DB class
 */
class Appointments_Meta extends Base {

	/**
	 * Return table slug
	 * 
	 * @return [type] [description]
	 */
	public function table_slug() {
		return 'appointments_meta';
	}

	/**
	 * Try to recreate DB table by request
	 *
	 * @return void
	 */
	public function install_table() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$table_recreated = get_option( 'jet_booking_meta_table_recreated', false );

		if ( ! $table_recreated ) {
			$this->create_table( true );
			update_option( 'jet_booking_meta_table_recreated', true, true );
		}

	}

	public function set_meta( $appointment_id, $meta_key, $meta_value ) {
		
		$table  = $this->table();
		$exists = $this->wpdb()->get_results( "SELECT ID FROM $table WHERE appointment_id = $appointment_id AND meta_key = '$meta_key' LIMIT 1" );

		if ( ! empty( $exists ) ) {
			$this->update( [ 'meta_value' => $meta_value ], [ 'ID' => $exists[0]->ID ] );
		} else {
			$this->insert( [ 'appointment_id' => $appointment_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value ] );
		}

	}

	public function get_meta( $appointment_id, $meta_key ) {

		$table = $this->table();
		$item  = $this->wpdb()->get_results( "SELECT meta_value FROM $table WHERE appointment_id = $appointment_id AND meta_key = '$meta_key' LIMIT 1" );

		if ( empty( $item ) ) {
			return false;
		} else {
			return $item[0]->meta_value;
		}

	}

	/**
	 * Returns columns schema
	 * @return [type] [description]
	 */
	public function schema() {
		return array(
			'ID'             => 'bigint(20) NOT NULL AUTO_INCREMENT',
			'appointment_id' => 'bigint(20)',
			'meta_key'       => 'text',
			'meta_value'     => 'longtext',
		);
	}

	/**
	 * Create DB table for apartment units
	 *
	 * @return [type] [description]
	 */
	public function get_table_schema() {

		$charset_collate = $this->wpdb()->get_charset_collate();
		$table           = $this->table();
		$default_columns = $this->schema();
		$columns_schema  = '';

		foreach ( $default_columns as $column => $desc ) {
			$columns_schema .= $column . ' ' . $desc . ',';
		}

		return "CREATE TABLE $table (
			$columns_schema
			PRIMARY KEY (ID)
		) $charset_collate;";

	}

}
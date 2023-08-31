<?php
namespace JET_APB\DB;

use JET_APB\Plugin;
use JET_APB\Tools;

/**
 * Database manager class
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Base DB class
 */
class Manager {

	public $appointments;
	public $appointments_meta;
	public $excluded_dates;

	/**
	 * Constructor for the class
	 */
	public function __construct() {

		$this->appointments      = new Appointments();
		$this->appointments_meta = new Appointments_Meta();
		$this->excluded_dates    = new Excluded_Dates();

	}

	/**
	 * Remove date of passed appoinemtnt from excluded dates
	 *
	 * @param  [type] $appointment [description]
	 * @return [type]              [description]
	 */
	public function remove_appointment_date_from_excluded( $appointment ) {

		if ( is_integer( $appointment ) ) {
			$appointment = $this->get_appointment_by( 'ID', $appointment );
		}

		if ( ! $appointment ) {
			return;
		}

		$excluded_where = array();

		if ( ! empty( $appointment['date'] ) ) {
			$excluded_where['date'] = $appointment['date'];
		}

		if ( ! empty( $appointment['service'] ) ) {
			$excluded_where['service'] = $appointment['service'];
		}

		if ( ! empty( $appointment['provider'] ) ) {
			$excluded_where['provider'] = $appointment['provider'];
		}

		$this->excluded_dates->delete( $excluded_where );

	}

	/**
	 * Check if this appointmetn is available
	 *
	 * @param  [type] $appointment_data [description]
	 * @return [type]                   [description]
	 */
	public function appointment_available( $appointment ) {
		
		$query_args   = [];
		$is_available = false;
		$service_id   = ! empty( $appointment['service'] ) ? $appointment['service'] : null;
		$provider_id  = ! empty( $appointment['provider'] ) ? $appointment['provider'] : null;
		$buffer_before = Tools::get_time_settings( $service_id, $provider_id, 'buffer_before', 0 );
		$buffer_after = Tools::get_time_settings( $service_id, $provider_id, 'buffer_after', 0 );

		$appointment['slot'] -= intval( $buffer_before );
		$appointment['slot_end'] += intval( $buffer_after );
		
		if ( ! empty( $service_id ) && 'service' === Plugin::instance()->settings->get( 'check_by' ) ) {
			$query_args['service'] = $service_id;
		}

		if ( ! empty( $provider_id ) ) {
			$query_args['provider'] = $provider_id;
		}
		
		$query_args['date']     = ! empty( $appointment['date'] ) ? $appointment['date'] : null;
		$query_args['status']   = array_merge( Plugin::instance()->statuses->exclude_statuses() );
		
		$manage_capacity = Plugin::instance()->settings->get( 'manage_capacity' );
		
		if ( $manage_capacity ) {
			$booked_appointments = Plugin::instance()->db->appointments->query_with_capacity( $query_args );
			$service_count       = Plugin::instance()->tools->get_service_count( $service_id );
		} else {
			$booked_appointments = Plugin::instance()->db->appointments->query( $query_args );
		}
		
		if ( ! empty( $booked_appointments ) ) {
			$appointment_range = range( $appointment['slot'], $appointment['slot_end'] );
			foreach ( $booked_appointments as $booked_appointment ){
				if( in_array( $booked_appointment['slot'] - $buffer_before + 1, $appointment_range )
					|| in_array( $booked_appointment['slot_end'] + $buffer_after - 1, $appointment_range )
				){

					if ( 'slot' === $appointment['type'] && $manage_capacity ) {
						$slot_count = !empty( $booked_appointment['slot_count'] ) ? absint( $booked_appointment['slot_count'] ) : 1;
						if ( $slot_count === $service_count ) {
							$is_available = true;

							break;
						}
					} else {
						$is_available = true;

						break;
					}
				}
			}
		}

		if ( ! $is_available ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Delete appointment from DB
	 *
	 * @param  [type] $appointment_id [description]
	 * @return [type]                 [description]
	 */
	public function delete_appointment( $appointment_id ) {

		$appointment = $this->get_appointment_by( 'ID', $appointment_id );

		if ( ! $appointment ) {
			return;
		}

		$appointment_where = array(
			'ID' => $appointment_id,
		);

		$this->appointments->delete( $appointment_where );
		
		$this->appointments_meta->create_table( false );
		$this->appointments_meta->delete( [
			'appointment_id' => $appointment_id,
		] );

		$this->remove_appointment_date_from_excluded( $appointment );

	}

	/**
	 * Insert new appointment and maybe add excluded date
	 *
	 * @param  array  $appointment [description]
	 * @return [type]              [description]
	 */
	public function add_appointment( $appointment = array() ) {

		if ( empty( $appointment['user_id'] ) && is_user_logged_in() ) {
			$appointment['user_id'] = get_current_user_id();
		}

		if ( empty( $appointment['provider'] ) ) {
			$appointment['provider'] = 0;
		}

		$meta = ! empty( $appointment['meta'] ) ? $appointment['meta'] : [];

		if ( isset( $appointment['meta'] ) ) {
			unset( $appointment['meta'] );
		}

		$appointment_id = $this->appointments->insert( $appointment );

		$this->appointments_meta->create_table( false );

		foreach ( $meta as $meta_key => $meta_value ) {
			$this->appointments_meta->insert( [
				'appointment_id' => $appointment_id,
				'meta_key'       => $meta_key,
				'meta_value'     => maybe_serialize( $meta_value ),
			] );
		}

		$this->maybe_exclude_appointment_date( $appointment );

		$appointment['ID'] = $appointment_id;

		/**
		 * Trigger hook after appoinetment created
		 */
		do_action( 'jet-apb/db/create/appointments', $appointment );

		/**
		 * Trigger update hook after appoinetment created
		 */
		do_action( 'jet-apb/db/update/appointments', $appointment, true, [] );

		return $appointment_id;
	}

	/**
	 * Maybe add appointment date to excluded
	 *
	 * @param  [type] $appointment [description]
	 * @return [type]              [description]
	 */
	public function maybe_exclude_appointment_date( $appointment ) {

		if ( is_integer( $appointment ) ) {
			$appointment = $this->get_appointment_by( 'ID', $appointment );
		}

		if ( ! $appointment ) {
			return;
		}

		$service_id       = ! empty( $appointment['service'] ) ? $appointment['service'] : null;
		$provider_id      = ! empty( $appointment['provider'] ) ? $appointment['provider'] : null;
		$date             = ! empty( $appointment['date'] ) ? $appointment['date'] : null;
		$slot             = ! empty( $appointment['slot'] ) ? $appointment['slot'] : null;
		$capacity_is_full = true;

		if ( ! $slot ) {
			return;
		}

		/**
		 * If status of current appointment shouldn't be excluded from calendar - 
		 * we don't need to do any more checks
		 */
		if ( ! in_array( $appointment['status'], Plugin::instance()->statuses->exclude_statuses() ) ) {
			return;
		}

		$manage_capacity = Plugin::instance()->settings->get( 'manage_capacity' );
		
		if ( $manage_capacity ) {
			
			$query_args = array(
				'date'     => $date,
				'status'   => Plugin::instance()->statuses->exclude_statuses(),
			);

			if ( $service_id ){
				$query_args['service'] = $service_id;
			}

			if ( $provider_id ) {
				$query_args['provider'] = $provider_id;
			}

			$capacity       = Plugin::instance()->db->appointments->query_with_capacity( $query_args );
			$total_capacity = Plugin::instance()->tools->get_service_count( $service_id );

			if( ! empty( $capacity ) && count( $capacity ) === $total_capacity ) {
				$capacity_is_full = true;
			} else {
				$capacity_is_full = false;
			}

		}

		$all_slots = Plugin::instance()->calendar->get_date_slots( $service_id, $provider_id, $date );
		$all_slots = ! empty( $all_slots ) ? $all_slots : [] ;

		if ( ! empty( $all_slots ) && isset( $all_slots[ $slot ] ) && $capacity_is_full ) {
			unset( $all_slots[ $slot ] );
		}

		if ( empty( $all_slots ) ) {
			$this->excluded_dates->insert( array(
				'service'  => $service_id,
				'provider' => $provider_id,
				'date'     => $date,
			) );
		}
	}

	/**
	 * Returns appointments detail by order id
	 *
	 * @return [type] [description]
	 */
	public function get_appointments_by( $field = 'ID', $value = null ) {

		$appointments = $this->appointments->query( [ $field => $value ] );

		if ( empty( $appointments ) ) {
			return false;
		}

		return $appointments;

	}

	/**
	 * Returns appointment detail by order id
	 *
	 * @return [type] [description]
	 */
	public function get_appointment_by( $field = 'ID', $value = null ) {
		
		$appointment = $this->get_appointments_by( $field, $value );
		$appointment = $this->get_appointments_meta( $appointment );

		return ! empty( $appointment ) ? $appointment[0] : false;
	}

	/**
	 * Get meta data of given appointments
	 * 
	 * @param  array  $appointments [description]
	 * @return [type]               [description]
	 */
	public function get_appointments_meta( $appointments = [] ) {

		if ( empty( $appointments ) ) {
			return [];
		}

		$ids = [];
		$appointments_by_ids = [];

		foreach ( $appointments as $app ) {
			$appointments_by_ids[ $app['ID'] ] = $app;
			$ids[] = $app['ID'];
		}

		$this->appointments_meta->create_table( false );

		$meta = $this->appointments_meta->query( [ 'appointment_id' => $ids ] );

		if ( ! empty( $meta ) ) {
			foreach ( $meta as $_row ) {
				if ( empty( $appointments_by_ids[ $_row['appointment_id'] ] ) ) {
					$appointments_by_ids[ $_row['appointment_id'] ]['meta'] = [];
				}

				$appointments_by_ids[ $_row['appointment_id'] ]['meta'][ $_row['meta_key'] ] = maybe_unserialize( $_row['meta_value'] );
			}
		}

		return array_values( $appointments_by_ids );
	}

}

<?php
namespace JET_APB;

/**
 * Calendar related data
 */
class Calendar {

	public $off_dates   = []; // Dates which are always off
	public $work_dates  = []; // Dates forced to be working instead of default
	//public $merge_dates = []; // Dates we need to merge with default schedule
	public $week_days   = []; // Available week days
	public $date_slots  = []; // Available slots of selected date

	public function get_day_schedule( $date = [], $working_hours = [], $working_days = [] ) {
		
		$weekday  = strtolower( date( 'l', $date ) );
		$schedule = ! empty( $working_hours[ $weekday ] ) ? $working_hours[ $weekday ] : [];

		foreach ( $working_days as $day ) {
			
			if ( empty( $day['schedule'] ) ) {
				continue;
			}

			$end = ! empty( $day['end'] ) ? $day['end'] : $day['start'];

			$start = strtotime( $day['start'] . ' 00:00' );
			$end   = strtotime( $end . ' 23:59:59' );

			if ( $start <= $date && $date <= $end ) {
				$schedule = $day['schedule'];
			}

		}

		return $schedule;
	}

	/**
	 * Get date slots
	 *
	 * @return [type] [description]
	 */
	public function get_date_slots( $service = 0, $provider = 0, $date = 0, $time = 0, $selected = [] ) {

		if ( ! $service || ! $date ) {
			return false;
		}

		$cache_key = $this->get_request_key( $service, $provider, $date, $time );

		if ( isset( $this->date_slots[ $cache_key ] ) ) {
			return $this->date_slots[ $cache_key ];
		}
		
		$slots         = [];
		$time         += $this->get_schedule_settings( $provider, $service, 0, 'locked_time' );
		$buffer_before = $this->get_schedule_settings( $provider, $service, 0, 'buffer_before' );
		$buffer_after  = $this->get_schedule_settings( $provider, $service, 0, 'buffer_after' );
		$duration      = $this->get_schedule_settings( $provider, $service, 0, 'default_slot' );
		$working_hours = $this->get_schedule_settings( $provider, $service, [], 'working_hours' );
		$working_days  = $this->get_schedule_settings( $provider, $service, [], 'working_days' );

		$day_schedule  = $this->get_day_schedule( $date, $working_hours, $working_days );
		Time_Slots::set_starting_point( $date );

		if ( 0 < $time ) {
			Time_Slots::set_timenow( $time );
		}

		if ( 1 < count( $day_schedule ) ) {

			usort( $day_schedule, function( $a, $b ) {

				$a_from = strtotime( $a['from'] );
				$b_from = strtotime( $b['from'] );

				if ( $a_from === $b_from ) {
					return 0;
				}

				return ( $a_from < $b_from ) ? -1 : 1;

			} );
		}

		if ( $selected ) {
			$selected = array_filter( $selected, function( $item ) use ( &$service, &$provider ) {
				$checkService = $item->service === $service;
				$checkProvider = isset( $item->provider ) ? $item->provider === $provider : true;

				if( $checkService && $checkProvider ){
					return true;
				}else{
					return false;
				}
			});
		}

		foreach ( $day_schedule as $day_part ) {
			$slots = $slots + Time_Slots::generate_intervals( array(
				'from'          => $day_part['from'],
				'to'            => $day_part['to'],
				'duration'      => $duration,
				'buffer_before' => $buffer_before,
				'buffer_after'  => $buffer_after,
				'from_now'      => true,
				'selected'      => $selected,
			) );
		}

		$query_args = array(
			'date'     => $date,
			'status'   => Plugin::instance()->statuses->exclude_statuses(),
			'provider' => 0,
		);

		if ( 'service' === Plugin::instance()->settings->get( 'check_by' ) ) {
			$query_args['service'] = $service;
		}

		if ( $provider ) {
			$query_args['provider'] = $provider;
		}

		$manage_capacity = Plugin::instance()->settings->get( 'manage_capacity' );
		$service_count   = 1;

		if ( 0 === $query_args['provider'] ) {
			// Ensure all slots will be found (in some cases for version prior 1.4.10 provider could be stored with 0 or empty string)
			$query_args['provider'] = array( 0, '' );
		}

		if ( $manage_capacity ) {
			$excluded      = Plugin::instance()->db->appointments->query_with_capacity( $query_args );
			$service_count = Plugin::instance()->tools->get_service_count( $service );
		} else {
			$excluded = Plugin::instance()->db->appointments->query( $query_args );
		}

		if ( ! empty( $excluded ) ) {
			foreach ( $excluded as $appointment ) {

				$excl_slot_from = absint( $appointment['slot'] );
				$excl_slot_to   = absint( $appointment['slot_end'] );
				$slot_count     = ! empty( $appointment['slot_count'] ) ? absint( $appointment['slot_count'] ) : 1;
				$excl_range     = range( $excl_slot_from, $excl_slot_to );

				if ( ! $excl_slot_from ) {
					continue;
				}

				if ( $manage_capacity ) {

					if ( isset( $slots[ $excl_slot_from ] ) && $slot_count >= $service_count ) {
						unset( $slots[ $excl_slot_from ] );
					}

				} elseif ( isset( $slots[ $excl_slot_from ] ) ) {
					unset( $slots[ $excl_slot_from ] );
				}
				
				foreach ( $slots as $slot => $slot_data ) {
					$slot_range = range( $slot_data['from'], $slot_data['to'] );
					
					if( in_array( $slot_data['from'] + 1, $excl_range )
						|| in_array( $slot_data['to'] - 1, $excl_range )
						|| in_array( $excl_slot_from + 1, $slot_range )
						|| in_array( $excl_slot_to - 1, $slot_range )
					){
						if ( $manage_capacity && $slot_count >= $service_count ) {
							unset( $slots[ $slot ] );
						} elseif ( $manage_capacity ) {
							$slots[ $slot ]['slot_count'] = $slot_count;
						} elseif ( ! $manage_capacity ) {
							unset( $slots[ $slot ] );
						}
					}
				}

			}
		}

		if ( empty( $slots ) ) {

			$excluded_args = array(
				'service'  => $service,
				'provider' => $provider,
				'date'     => $date,
			);

			if( empty( Plugin::instance()->db->excluded_dates->query( $excluded_args ) ) ) {
				Plugin::instance()->db->excluded_dates->insert( $excluded_args );
			}
		}

		$this->date_slots[ $cache_key ] = $slots;

		return $this->date_slots[ $cache_key ];

	}

	/**
	 * Returns names of excluded week days
	 *
	 * @return [type] [description]
	 */
	public function get_available_week_days( $service = null, $provider = null ) {

		$key = $this->get_request_key( $service, $provider );

		if ( ! isset( $this->week_days[ $key ] ) ) {

			$working_hours = $this->get_schedule_settings( $provider, $service, [], 'working_hours' );
			$result        = array();

			foreach ( $working_hours as $week_day => $schedule ) {
				if ( ! empty( $schedule ) ) {
					$result[] = $week_day;
				}
			}

			$this->week_days[ $key ] = $result;

		}

		return $this->week_days[ $key ];
	}

	/**
	 * Returns week days list
	 *
	 * @return [type] [description]
	 */
	public function get_week_days() {
		return array(
			'sunday',
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
		);
	}

	public function get_request_key( $service = null, $provider = null, $date = null, $time = null ) {
		return absint( $service ) . ':' . absint( $provider ) . ':' . absint( $date ) . ':' . absint( $time );
	}

	/**
	 * Returns excluded dates - official days off and booked dates
	 *
	 * @return [type] [description]
	 */
	public function get_off_dates( $service = null, $provider = null ) {

		$key = $this->get_request_key( $service, $provider );

		if ( ! isset( $this->off_dates[ $key ] ) ) {
			$result     = array();
			$days_off   = $this->get_schedule_settings( $provider, $service, null, 'days_off' );
			$query_args = array(
				'date>=' => time(),
			);

			if ( ! empty( $service ) ) {
				if( 'service' === Plugin::instance()->settings->get( 'check_by' ) ){
					$query_args['service'] = $service;
				}
			}

			if ( ! empty( $provider ) ) {
				$query_args['provider'] = $provider;
			}

			if ( ! empty( $days_off ) ) {
				foreach ( $days_off as $day ) {
					$result[] = [
						'start' => $day['startTimeStamp'] / 1000,
						'end' => $day['endTimeStamp'] / 1000,
					];
				}
			}

			$excluded = Plugin::instance()->db->excluded_dates->query( $query_args );

			if ( ! empty( $excluded ) ) {
				foreach ( $excluded as $date ) {
					if ( ! isset( $date['start'] ) ) {
						$date_period = [
							'start'   => absint( $date['date'] ),
							'end'     => absint( $date['date'] ),
							'service' => absint( $date['service'] ),
						];

						if ( ! in_array( $date_period, $result ) ){
							$result[] = $date_period;
						}

					} else {
						$result[] = absint( $date['date'] );
					}
				}
			}

			$this->off_dates[ $key ] = $result;
		}

		return $this->off_dates[ $key ];
		
	}

	public function get_working_days_mode( $service = null, $provider = null ) {
		return $this->get_schedule_settings( $provider, $service, 'override_full', 'working_days_mode' );
	}

	public function get_works_dates( $service = null, $provider = null ) {

		$key = $this->get_request_key( $service, $provider );

		if ( ! isset( $this->work_dates[ $key ] ) ) {
		
			$result             = array();
			$working_days       = $this->get_schedule_settings( $provider, $service, null, 'working_days' );
			$appointments_range = $this->get_schedule_settings( $provider, $service, null, 'appointments_range' );

			if ( ! empty( $working_days ) ) {
				foreach ( $working_days as $day ) {
					$result[] = [
						'start' => $day['startTimeStamp'] / 1000,
						'end' => $day['endTimeStamp'] / 1000,
					];
				}
			}

			if ( ! empty( $appointments_range ) && 'range' === $appointments_range['type'] ) {

				$range_num  = ! empty( $appointments_range['range_num'] ) ? $appointments_range['range_num'] : 60;
				$range_unit = ! empty( $appointments_range['range_unit'] ) ? $appointments_range['range_unit'] : 'days';
				$range      = $range_num . ' ' . $range_unit;

				$result[] = [
					'start' => absint( wp_date( 'U', strtotime( 'today - 1 day' ) ) ),
					'end'   => absint( wp_date( 'U', strtotime( 'today + ' . $range ) ) ),
				];

			}

			$this->work_dates[ $key ] = $result;
			
		}

		return array_values( $this->work_dates[ $key ] );
		
	}

	public function day_available( $day ) {

		$excluded            = Plugin::instance()->calendar->get_off_dates( $service, $provider );
		$work_dates          = Plugin::instance()->calendar->get_works_dates( $service, $provider );
		$available_week_days = Plugin::instance()->calendar->get_available_week_days( $service, $provider );

	}

	public function get_schedule_settings( $provider = null, $service = null, $default_value  = null, $meta_key = null ){

		$value         = null;
		$post_meta     = get_post_meta( $provider, 'jet_apb_post_meta', true );
		$general_value = Plugin::instance()->settings->get( $meta_key );
		$general_value = $general_value ? $general_value : $default_value;

		if ( ! isset( $post_meta[ 'custom_schedule' ] ) || ! $post_meta[ 'custom_schedule' ][ 'use_custom_schedule' ] ){
			$post_meta = get_post_meta( $service, 'jet_apb_post_meta', true );
		}

		if ( ! isset( $post_meta[ 'custom_schedule' ] ) || ! $post_meta[ 'custom_schedule' ][ 'use_custom_schedule' ] ) {
			$value = $general_value;
		} else {
			if ( isset( $post_meta[ 'custom_schedule' ][ $meta_key ] ) ){
				$value = $post_meta[ 'custom_schedule' ][ $meta_key ];
				$value = NULL !== $value ? $value : $general_value;
			} else {
				// Do not inherit these keys from parent
				$not_inherit_keys = [ 'working_days' ];

				if ( ! in_array( $meta_key, $not_inherit_keys ) ) {
					$value = $general_value;
				} else {
					$value = false;
				}
				
			}
		}

		return apply_filters( 'jet-apb/calendar/custom-schedule', $value, $meta_key, $default_value, $provider, $service );
	}
}

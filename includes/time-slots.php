<?php
namespace JET_APB;

/**
 * Time slots generator class
 */
class Time_Slots {

	private static $starting_point = false;
	private static $timenow        = false;

	public static $current_timezone     = null;
	public static $current_timezone_raw = null;

	/**
	 * Returns current starting point
	 *
	 * @return int
	 */
	public static function get_starting_point() {

		if ( ! self::$starting_point ) {
			self::$starting_point = strtotime( 'today midnight' );
		}

		return self::$starting_point;
	}

	/**
	 * Returns current starting point
	 *
	 * @return int
	 */
	public static function set_starting_point( $timestamp ) {
		self::$starting_point = $timestamp;
	}

	public static function set_timezone( $tz_string ) {

		// fix DST offsets
		if ( true === apply_filters( 'jet-apb/time-slots/fix-dst', false ) ) {
			
			$fix_dst = array(
				'America/Mexico_City' => 'UTC-6',
			);

			$now = time();

			if ( isset( $fix_dst[ $tz_string ] )
				&& strtotime( 'March, 26, 3:00AM' ) <= $now 
				&& $now <= strtotime( 'October 29, 4:00AM' ) 
			) {
				$tz_string = $fix_dst[ $tz_string ];
			}

		}

		$tz = $tz_string;

		if ( false !== strpos( $tz_string, 'UTC+' ) || false !== strpos( $tz_string, 'UTC-' ) ) {
			$tz      = str_replace( 'UTC', '', $tz );
			$offset  = (float) $tz;
			$hours   = (int) $offset;
			$minutes = ( $offset - $hours );

			$sign     = ( $offset < 0 ) ? '-' : '+';
			$abs_hour = abs( $hours );
			$abs_mins = abs( $minutes * 60 );
			$tz       = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
		}

		self::$current_timezone_raw = $tz_string;
		self::$current_timezone     = new \DateTimeZone( $tz );
	}

	/**
	 * Returns current starting point
	 *
	 * @return int
	 */
	public static function set_timenow( $timestamp ) {
		self::$timenow = $timestamp;
	}

	/**
	 * Generate time slots array
	 *
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public static function generate_slots( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'from'     => 0,
			'to'       => 0,
			'interval' => 30 * MINUTE_IN_SECONDS,
			'format'   => 'G:i',
			'from_now' => false,
		) );

		$starting_point = self::get_starting_point();
		$result         = array();
		$from           = ! empty( $args['from'] ) ? $starting_point + $args['from'] : $starting_point;
		$to             = ! empty( $args['to'] ) ? $starting_point + $args['to'] : $starting_point + DAY_IN_SECONDS;
		$timestamp      = $from;

		if ( $args['from_now'] && self::$timenow ) {
			$from = self::$timenow;
		}

		if ( ! is_integer( $timestamp ) ) {
			return $result;
		}

		while ( $timestamp <= $to ) {
			$result[]  = date( $args['format'], $timestamp );
			$timestamp += $args['interval'];
		}

		return $result;

	}

	/**
	 * Generate intervals
	 *
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public static function generate_intervals( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'from'          => '00:00',
			'to'            => '24:00',
			'duration'      => HOUR_IN_SECONDS,
			'buffer_before' => 0,
			'buffer_after'  => 0,
			'from_now'      => false, 
			'selected'      => [],
		) );

		$result   = array();
		$from     = self::get_timestamp_from_time( $args['from'] );
		$to       = self::get_timestamp_from_time( $args['to'] );
		$i        = $from;
		$em_brake = 0;

		if ( $args['from_now'] && self::$timenow ) {
			while ( $i < self::$timenow ) {
				$i = $i + $args['buffer_before'] + $args['duration'] + $args['buffer_after'];
			}
		}

		if ( ! is_integer( $from ) || ! is_integer( $to ) ) {
			return $result;
		}

		if ( ! is_integer( $i ) || ! is_integer( $to ) ) {
			return $result;
		}

		$duration = ! empty( $args['duration'] ) ? absint( $args['duration'] ) : 0;

		if ( empty( $duration ) ) {
			return $result;
		}

		$buffer_before = ! empty( $args['buffer_before'] ) ? absint( $args['buffer_before'] ) : 0;
		$buffer_after  = ! empty( $args['buffer_after'] ) ? absint( $args['buffer_after'] ) : 0;

		while ( $i < $to ) {

			$start = $i + $buffer_before;
			$end   = $start + $duration;
			$i     = $end + $buffer_after;

			if ( $start >= $to ) {
				break;
			}

			if ( $end >= $to ) {
				$end = $to;
			}

			$isSelected = false !== array_search( $start, array_column($args['selected'], 'slot') ) ? true : false ;

			$result[ $start ] = array(
				'from'     => $start,
				'to'       => $end,
				'selected' => $isSelected,
			);

			$em_brake++;

			if ( 100 < $em_brake ) {
				break;
			}

		}

		return $result;
	}

	/**
	 * Generate slots HTML markup
	 *
	 * @param  array  $slots  [description]
	 * @param  string $format [description]
	 * @return [type]         [description]
	 */
	public static function generate_slots_html( $slots = array(), $format = 'H:i', $dataset = array(), $date = '', $service = false,
	$provider = 0 ) {

		if ( self::$current_timezone_raw ) {
			$dataset[] = 'data-timezone="' . self::$current_timezone_raw . '"';
		}

		/**
		 * Filter slots data list before processing
		 */
		$slots = apply_filters(
			'jet-apb/time-slots/slots-html/slots-list',
			$slots, $format, $dataset, $date, $service, $provider
		);

		$dataset         = implode( ' ', $dataset );
		$manage_capacity = Plugin::instance()->settings->get( 'manage_capacity' );
		$show_counter    = Plugin::instance()->settings->get( 'show_capacity_counter' );
		$check_date      = date( 'Ymd', $date );
		$date            = date( get_option( 'date_format' ), $date );

		/**
		 * Available values:
		 * %1$d - booked num
		 * %2$d - total num
		 * %3$d - available num
		 */
		$capacity_format = apply_filters(
			'jet-apb/time-slots/slots-html/capacity-format',
			'<small>(%3$d/%2$d)</small>'
		);

		if ( $manage_capacity && $service && $show_counter ) {
			$service_count = Plugin::instance()->tools->get_service_count( $service );
		}

		$prepared_slots = [];
		$slots_before   = [];
		$slots_after    = [];

		$starting_point = self::$starting_point;
		
		foreach ( $slots as $timestamp => $slot ) {
			$class = $slot['selected'] ? 'jet-apb-slot--selected' : '' ;
			if ( $manage_capacity && $show_counter ) {
				$count           = ! empty( $slot['slot_count'] ) ? $slot['slot_count'] : 0;
				$available_count = $service_count - $count;
				$capacity_html   = sprintf( $capacity_format, $count, $service_count, $available_count );
			} else {
				$capacity_html = '';
			}

			$from_date_check = false;
			$to_date_check   = false;

			if ( self::$current_timezone ) {
				$from_time       = date_create( date( 'Y-m-d H:i:s', $slot['from'] ), wp_timezone() );
				$from_date_check = $from_time->setTimezone( self::$current_timezone )->format( 'Ymd' );
				$from_time       = $from_time->setTimezone( self::$current_timezone )->format( $format );
				$to_time         = date_create( date( 'Y-m-d H:i:s', $slot['to'] ), wp_timezone() );
				$to_date_check   = $to_time->setTimezone( self::$current_timezone )->format( 'Ymd' );
				$to_time         = $to_time->setTimezone( self::$current_timezone )->format( $format );
			} else {
				$from_time = date( ltrim( $format ), $slot['from'] );
				$to_time   = date( ltrim( $format ), $slot['to'] );
			}

			$prepared_slot = [
				'from' => $slot['from'],
				'to' => $slot['to'],
				'starting_point' => $starting_point,
				'from_time' => $from_time,
				'to_time' => $to_time,
				'capacity' => $capacity_html,
				'class' => $class,
				'date' => $date,
				'gmt_from' => get_gmt_from_date( $date . ' ' . $from_time, 'Y-m-d H:i:s' ),
				'gmt_to' => get_gmt_from_date( $date . ' ' . $to_time, 'Y-m-d H:i:s' ),
			];

			if ( ! $from_date_check || ! $to_date_check || ( $from_date_check == $check_date && $to_date_check == $check_date ) ) {
				$prepared_slots[] = $prepared_slot;
			} else {
				
				$from_date_check = absint( $from_date_check );
				$to_date_check   = absint( $to_date_check );
				$check_date      = absint( $check_date );

				if ( $from_date_check < $check_date || $to_date_check < $check_date ) {

					//$prepared_slot['date'] = date( get_option( 'date_format' ), strtotime( $date . ' + 1 day' ) );
					$prepared_slot['from'] = $prepared_slot['from'] + DAY_IN_SECONDS;
					$prepared_slot['to'] = $prepared_slot['to'] + DAY_IN_SECONDS;
					$prepared_slot['starting_point'] = $prepared_slot['starting_point'] + DAY_IN_SECONDS;
					$prepared_slot['gmt_from'] = get_gmt_from_date( $prepared_slot['date'] . ' ' . $from_time, 'Y-m-d H:i:s' );
					$prepared_slot['gmt_to'] = get_gmt_from_date( $prepared_slot['date'] . ' ' . $to_time, 'Y-m-d H:i:s' );

					$available_slots = Plugin::instance()->calendar->get_date_slots(
						absint( $service ),
						absint( $provider ),
						$prepared_slot['starting_point']
					);

					if ( isset( $available_slots[ $prepared_slot['from'] ] ) ) {
						$slots_after[] = $prepared_slot;
					}
					

				} elseif ( $from_date_check > $check_date || $to_date_check > $check_date ) {

					//$prepared_slot['date'] = date( get_option( 'date_format' ), strtotime( $date . ' - 1 day' ) );
					$prepared_slot['from'] = $prepared_slot['from'] - DAY_IN_SECONDS;
					$prepared_slot['to'] = $prepared_slot['to'] - DAY_IN_SECONDS;
					$prepared_slot['starting_point'] = $prepared_slot['starting_point'] - DAY_IN_SECONDS;
					$prepared_slot['gmt_from'] = get_gmt_from_date( $prepared_slot['date'] . ' ' . $from_time, 'Y-m-d H:i:s' );
					$prepared_slot['gmt_to'] = get_gmt_from_date( $prepared_slot['date'] . ' ' . $to_time, 'Y-m-d H:i:s' );

					$available_slots = Plugin::instance()->calendar->get_date_slots(
						absint( $service ),
						absint( $provider ),
						$prepared_slot['starting_point']
					);

					if ( isset( $available_slots[ $prepared_slot['from'] ] ) ) {
						$slots_before[] = $prepared_slot;
					}
					
				}

			}

			

		}

		$prepared_slots = array_merge( $slots_before, $prepared_slots, $slots_after );

		/**
		 * %4$s - slot start time in selected format
		 * %5$s - slot end time in selected format
		 * @var string
		 */
		$slot_time = apply_filters( 'jet-apb/time-slots/slots-html/slot-time-format', '%4$s-%5$s', $slot );

		foreach ( $prepared_slots as $slot ) {
			printf(
				'<div class="jet-apb-slot %8$s" data-slot="%1$s" data-slot-end="%2$s" data-date="%3$s" data-friendly-time="' . $slot_time . '" data-friendly-date="%9$s" data-utc-start="%10$s" data-utc-end="%11$s" %6$s>' . $slot_time . '  %7$s</div>',
				$slot['from'],
				$slot['to'],
				$slot['starting_point'],
				$slot['from_time'],
				$slot['to_time'],
				$dataset,
				$slot['capacity'],
				$slot['class'],
				$slot['date'],
				$slot['gmt_from'],
				$slot['gmt_to']
			);
		}

	}

	/**
	 * Returns timestamp from human readable time
	 *
	 * @return [type] [description]
	 */
	public static function get_timestamp_from_time( $time ) {

		$time  = explode( ':', $time );
		$hours = absint( $time[0] );
		$mins  = absint( $time[1] );

		return self::$starting_point + $hours * HOUR_IN_SECONDS + $mins * MINUTE_IN_SECONDS;

	}

	/**
	 * Generate time slots array
	 *
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public static function prepare_slots_for_js( $slots = array(), $l_format = false, $plain = false, $diff = false ) {

		$result = array();

		foreach ( $slots as $slot ) {

			$value = $slot;
			$label = ! empty( $l_format ) ? date( $l_format, $slot ) : $slot;

			if ( $diff ) {
				$value = $slot - self::$starting_point;
			}

			if ( $plain ) {
				$result[ $value ] = $label;
			} else {
				$result[] = array(
					'value' => $value,
					'label' => $label,
				);
			}
		}

		return $result;

	}

}

<?php
namespace JET_APB;

use JET_APB\Tools;

/**
 * Time_Range class
 */
class Time_Range {
	
	public static function get_range_view( $args = null  ){

		$price_instans = new Appointment_Price( $args );
		$price = $price_instans->get_price( false );

		$settings = self::get_settings( $args );

		if( ! $settings['min_max_time']['max'] ){
			return null;
		}
		
		$pickrConfig = [
			'enableTime'      => true,
			'noCalendar'      => true,
			'dateFormat'      => $settings['time_format'],
			'altFormat'       => $settings['time_format'],
			'time_24hr'       => $settings['time_24hr'],
			'position'        => $settings['position'],
			'hourIncrement'   => $settings['hour_increment'],
			'minuteIncrement' => $settings['minute_increment'],
			'defaultDate'     => $settings['default_date'],
			'minTime'         => $settings['min_max_time']['min'],
			'maxTime'         => $settings['min_max_time']['max'],
		];

		if( ! $settings['only_start'] ){
			$pickrConfig['endMinTime'] = $settings['min_max_time']['end_min'];
			$pickrConfig['endMaxTime'] = $settings['min_max_time']['end_max'];
		}

		$dataset = [
			'data-provider="' . $args['provider'] . '"',
			'data-service="' . $args['service'] . '"',
			'data-price="' . $price['price'] . '"',
			'data-price-type="' . $price['type'] . '"',
			'data-date="' . $args['date'] . '"',
			'data-friendly-date="' . $settings['data_friendly_date'] . '"',
			'data-time24hr="' . $settings['time_24hr'] . '"',
			'data-duration="' . $settings['duration'] . '"',
			'data-min-duration="'. $settings['duration'] . '"',
			'data-max-duration="'. $settings['max_duration'] . '"',
		];

		if( ! $settings['only_start'] && $settings['max_duration'] ){
			array_push( $dataset, 'data-end-time="' . $settings['end_time']. '"' );
		}

		$result = self::get_html(
			[
				'work_hours' => $settings['hours_list'],
				'busy_hours' => $settings['busy_hours_list'],
				'only_start' => $settings['only_start'],
				'config'     => $pickrConfig,
				'data_set'   => $dataset,
				'service'    => $args['service'],
				'provider'   => $args['provider']
			]
		);
		
		return $result;
	}
	
	public static function get_settings( $args = [] ){
		$settings = [
			'time_format'        => self::parse_time_format( Plugin::instance()->settings->get('slot_time_format') ),
			'hours_list'         => self::get_working_hours_list( $args ),
			'busy_hours_list'    => self::get_busy_hours_list( $args ),
			'duration'           => Tools::get_time_settings( $args['service'], $args['provider'], 'default_slot', 0 ),
			'locked_time'        => Tools::get_time_settings( $args['service'], $args['provider'], 'locked_time', 0 ),
			'only_start'         => Tools::get_time_settings( $args['service'], $args['provider'], 'only_start', false ),
			'data_friendly_date' => date( get_option( 'date_format' ), $args['date'] ),
			'position'           => apply_filters( 'jet-appointments/time-picker/config/position', 'auto center' ),
			'hour_increment'     => apply_filters( 'jet-appointments/time-picker/config/hour-increment', 1 ),
			'minute_increment'   => Tools::get_time_settings( $args['service'], $args['provider'], 'step_duration', 300 ),
		];

		$settings[ 'time_24hr' ]           = ( 'H:i' === $settings[ 'time_format' ] ) ? 'true' : 'false';
		$settings[ 'current_time_format' ] = sprintf( 'D, d F Y %s:i', filter_var( $settings[ 'time_24hr' ], FILTER_VALIDATE_BOOLEAN ) ? 'H' : 'h' );
		$settings[ 'max_duration' ]        = $settings['only_start'] ? false : Tools::get_time_settings( $args['service'], $args['provider'], 'max_duration' );
		$settings[ 'min_max_time' ]        = self::get_min_max_working_hours( [
			'date'        => intval( $args['date'] ),
			'time'        => intval( $args['time'] ),
			'duration'    => intval( $settings['duration'] ),
			'locked_time' => intval( $settings['locked_time'] ),
			'settings'    => $args,
			'only_start'  => $settings['only_start'],
		] );
		$settings[ 'default_date' ] = date( $settings[ 'current_time_format' ], strtotime( $settings[ 'min_max_time' ]['min'], $args['date'] ) );
		$settings[ 'end_time' ]     = date( $settings[ 'current_time_format' ], strtotime( $settings[ 'min_max_time' ]['min'], $args['date'] ) +  $settings[ 'duration' ] );

		return $settings;
	}

	public static function get_html( $args = [] ){
		$time_format = '<div class="jet-apb-app-hours jet-apb-%1$s">
							<span class="jet-apb-hours-label">%2$s</span>
							<span class="jet-apb-hours-value">%3$s</span>
						</div>';

		$time_picker = '<div class="jet-apb-time-picker-wrapper">
							<label for="jet-apb-time-picker-%1$s">%2$s</label>
							<input id="jet-apb-time-picker-%1$s" type="text" class="jet-apb-time-picker-input jet-apb-time-picker-input-%1$s flatpickr-input" placeholder="%3$s" data-type="%1$s" data-config=\'%4$s\' %5$s>
						</div>';

		$html = sprintf(
			$time_format,
			'work-hours',
			esc_html__( 'Work time:', 'jet-appointments-booking' ),
			$args['work_hours']
		);

		if( ! empty( $args['busy_hours'] )){
			$html .= sprintf(
				$time_format,
				'busy-hours',
				esc_html__( 'Already booked:', 'jet-appointments-booking' ),
				$args['busy_hours']
			);
		}

		$html .= '<div class="jet-apb-time-picker-control">';
		$html .= sprintf(
			$time_picker,
			'start',
			esc_html__( 'Start:', 'jet-appointments-booking' ),
			esc_html__( 'Set start time', 'jet-appointments-booking' ),
			json_encode( $args['config'] ),
			implode( ' ', $args['data_set'] ),
			''
		);

		if ( $args['only_start'] !== true ){
			$html .= sprintf(
				$time_picker,
				'end',
				esc_html__( 'End:', 'jet-appointments-booking' ),
				esc_html__( 'Set end time', 'jet-appointments-booking' ),
				json_encode( $args['config'] ),
				implode( ' ', $args['data_set'] )
			);
		}

		$html .= '</div>';

		return $html;
	}

	public static function get_min_max_working_hours( $args = [ 'date' => '', 'time' => '', 'duration' => 0, 'locked_time' => 0, 'only_start' => true, 'settings' => '' ] ){
		$zero_time = strtotime( '00:00' );
		$midnight  = strtotime( 'today midnight' );

		$min       = strtotime( '00:00' );
		$max       = strtotime( '00:00' );
		$end_min   = strtotime( '00:00' );
		$end_max   = strtotime( '00:00' );

		$time_format   = 'H:i';
		$working_hours = self::get_working_hours_list( $args['settings'], 'array' );

		if ( ! empty( $working_hours ) ) {
			$min = strtotime( $working_hours[0]['from'] );
			$max = strtotime( $working_hours[ count( $working_hours ) - 1 ]['to'] );

			if( ! $args['only_start'] ){
				$end_min = strtotime( $working_hours[0]['from'] );
				$end_max = strtotime( $working_hours[ count( $working_hours ) - 1 ]['to'] );
			}
		}

		if( $zero_time !== $max ) {
			$max = $max - $args['duration'];
		}

		if( ! $args['only_start'] && $zero_time !== $end_min ) {
			$end_min = $end_min + $args['duration'] ;
		}

		if( $midnight >= $args['date'] ){
			$min = $min < $args['time'] ? $args['time'] + $args['locked_time'] : $min ;
			$max = $max > $args['time'] ? $max : false ;

			if( ! $args['only_start'] ){
				$end_min = $end_min < $args['time'] + $args['duration'] ? $args['time'] + $args['duration'] : $end_min ;
				$end_max = $end_max > $args['time'] ? $end_max : false ;
			}
		}

		return [
			'min'     => date( $time_format, $min ),
			'max'     => $max ? date( $time_format, $max ) : $max,
			'end_min' => date( $time_format, $end_min ),
			'end_max' => $end_max ? date( $time_format, $end_max ) : $end_max,
		];;
	}

	public static function get_working_hours_list( $args, $returnType = 'string' ) {
		
		$working_hours = Tools::get_time_settings( $args['service'], $args['provider'], 'working_hours' );
		$working_days  = Tools::get_time_settings( $args['service'], $args['provider'], 'working_days' );
		$working_hours = Plugin::instance()->calendar->get_day_schedule( $args['date'], $working_hours, $working_days );
		
		if ( $returnType !== 'string') {
			return $working_hours;
		}

		return self::hours_array_to_list( $working_hours, 'from', 'to' );

	}
	
	public static function get_busy_hours_list( $args, $returnType = 'string' ){
		
		$query = [
			'service'  => ! empty( $args['service'] ) ? $args['service'] : null,
			'provider' => ! empty( $args['provider'] ) ? $args['provider'] : null,
			'date'     => ! empty( $args['date'] ) ? $args['date'] : null,
			'status'   => array_merge( Plugin::instance()->statuses->exclude_statuses() ),
		];

		$query = array_filter( $query );

		$order = [
			'orderby' => 'slot',
			'order'   => 'ASC',
		];

		$working_hours = self::get_working_hours_list( $args, 'array' );
		$buffer_before = Tools::get_time_settings( $args['service'], $args['provider'], 'buffer_before', 0 );
		$buffer_after = Tools::get_time_settings( $args['service'], $args['provider'], 'buffer_after', 0 );
		$start_working_hours = strtotime( $working_hours[ 0 ]['from'], $args['date'] );
		$end_working_hours = strtotime( $working_hours[ count( $working_hours ) - 1 ]['to'], $args['date'] );
		$appointments = Plugin::instance()->db->appointments->query( $query, 0, 0, $order );

		if( ! empty( $appointments ) ){
			foreach ( $appointments as $key => $value ){
				$appointments[ $key ]['slot'] = intval( $value['slot'] );
				$appointments[ $key ]['slot_end'] = intval( $value['slot_end'] );

				if( $start_working_hours !== intval( $value['slot'] ) ){
					$appointments[ $key ]['slot'] -= $buffer_before;

					if( $start_working_hours !== $appointments[ $key ]['slot']
						&& ( 0 === $key || $appointments[ $key - 1 ]['slot_end'] < $appointments[ $key ]['slot'] - $buffer_after )
					){
						$appointments[ $key ]['slot'] -= $buffer_after;
					}
				}
				if( $end_working_hours !== intval( $value['slot_end'] ) ) {
					$appointments[ $key ]['slot_end'] += $buffer_after;

					if( $end_working_hours !== $appointments[ $key ]['slot_end']
						&& ( count( $working_hours ) - 1 === $key || $appointments[ $key ]['slot_end'] + $buffer_before < $appointments[ $key + 1 ]['slot'] - $buffer_before )
					){
						$appointments[ $key ]['slot_end'] += $buffer_before;
					}
				}

			}
		}

		if( $returnType !== 'string'){
			return empty( $appointments ) ? [] : $appointments ;
		}
		
		return self::hours_array_to_list( $appointments, 'slot', 'slot_end' );
	}
	
	public static function hours_array_to_list( $hours_array = [], $key_1, $key_2 ){
		$hours_list  = [];
		$time_format = Plugin::instance()->settings->get('slot_time_format');
		
		foreach ( $hours_array as $time ){
			$_from = 'from' === $key_1 ? strtotime( $time[ $key_1 ] ) : $time[ $key_1 ] ;
			$_to = 'to' === $key_2 ? strtotime( $time[ $key_2 ] ) : $time[ $key_2 ] ;
			
			$from = date( $time_format, $_from );
			$to   = date( $time_format, $_to );
			
			$hours_list[] = $from . ' - ' . $to;
		}
		
		$hours_list = implode( ', ', $hours_list );
		
		return $hours_list;
	}
	
	public static function parse_time_format( $format = null  ){
		if( ! $format ){
			return '';
		}
		
		$parsed_format = $format;
		$mask = [
			'H' => 'H',
			'g' => 'G',
			'i' => 'i',
			'a' => 'K',
			'A' => 'K',
		];
		
		foreach ( $mask as $key => $value ) {
			$parsed_format = str_replace( $key, $value, $parsed_format );
		}
		
		return $parsed_format;
	}

}

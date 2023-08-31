<?php
namespace JET_APB;

use JET_APB\Time_Slots;
use JET_APB\Time_Range;
use JET_APB\Time_Recurring;
use JET_APB\Appointment_Price;

/**
 * Time_Types class
 */
class Time_Types {

	static $close_button = '<div class="jet-apb-calendar-slots__close">&times;</div>';
	
	public static function render_frontend_view( $args = null ){
		
		if ( empty( $args ) ) {
			return '';
		}

		$output = [
			'booking_type' => Plugin::instance()->settings->get( 'booking_type' ),
			'slots' => '',
			'settings' => '',
		];
		
		switch ( $output['booking_type'] ){
			case "range":
				$view = self::get_range_view( $args );

				$output['slots']    = $view[ 'slots' ];
				$output['settings'] = $view[ 'settings' ];
				break;
				
			case "recurring":
				$view = self::get_recurring_view( $args );

				$output['slots']                    = $view[ 'slots' ];
				$output['recurrence_settings_html'] = $view[ 'settings_html' ];
				$output['settings']                 = $view[ 'settings' ];
				break;
				
			default:
				$view = self::get_slots_view( $args );

				$output['slots'] = $view[ 'slots' ];
				break;
		}
		
		if ( empty( $view['slots'] ) ) {
			$output['slots'] = esc_html__( 'No available slots', 'jet-appointments-booking' );
		}

		if ( ! $args['admin'] ) {
			$output['slots'] = sprintf( '<div class="jet-apb-calendar-slots-container">%s</div>', $output['slots'] );
		}

		if ( ! $args['admin'] ) {
			$output['slots'] .= self::$close_button;
		}

		if ( Plugin::instance()->settings->show_timezones() ) {

			if ( ! empty( $args['timezone'] ) ) {
				$tzstring = $args['timezone'];
			} else {

				$current_offset = get_option( 'gmt_offset' );
				$tzstring       = get_option( 'timezone_string' );

				// Remove old Etc mappings. Fallback to gmt_offset.
				if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
					$tzstring = '';
				}

				if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists.
					if ( 0 == $current_offset ) {
						$tzstring = 'UTC+0';
					} elseif ( $current_offset < 0 ) {
						$tzstring = 'UTC' . $current_offset;
					} else {
						$tzstring = 'UTC+' . $current_offset;
					}
				}

			}

			if ( ! $args['admin'] ) {
				$output['slots'] .= apply_filters( 'jet-apb/timezone-picker-html', sprintf(
					'<div class="jet-ab-timezone-picker"><div class="jet-ab-timezone-picker__icon">%2$s</div><select name="timezone_picker">%1$s</select></div>',
					wp_timezone_choice( $tzstring, get_locale() ),
					apply_filters( 'jet-apb/timezone-picker-icon', '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd"><path d="M12.02 0c6.614.011 11.98 5.383 11.98 12 0 6.623-5.376 12-12 12-6.623 0-12-5.377-12-12 0-6.617 5.367-11.989 11.981-12h.039zm3.694 16h-7.427c.639 4.266 2.242 7 3.713 7 1.472 0 3.075-2.734 3.714-7m6.535 0h-5.523c-.426 2.985-1.321 5.402-2.485 6.771 3.669-.76 6.671-3.35 8.008-6.771m-14.974 0h-5.524c1.338 3.421 4.34 6.011 8.009 6.771-1.164-1.369-2.059-3.786-2.485-6.771m-.123-7h-5.736c-.331 1.166-.741 3.389 0 6h5.736c-.188-1.814-.215-3.925 0-6m8.691 0h-7.685c-.195 1.8-.225 3.927 0 6h7.685c.196-1.811.224-3.93 0-6m6.742 0h-5.736c.062.592.308 3.019 0 6h5.736c.741-2.612.331-4.835 0-6m-12.825-7.771c-3.669.76-6.671 3.35-8.009 6.771h5.524c.426-2.985 1.321-5.403 2.485-6.771m5.954 6.771c-.639-4.266-2.242-7-3.714-7-1.471 0-3.074 2.734-3.713 7h7.427zm-1.473-6.771c1.164 1.368 2.059 3.786 2.485 6.771h5.523c-1.337-3.421-4.339-6.011-8.008-6.771"/></svg>' )
				) );
			}
		}
		
		return $output;
	}
	
	public static function get_slots_view( $args =null  ) {
		
		$result = [
			'slots' => Plugin::instance()->calendar->get_date_slots( 
				$args['service'],
				$args['provider'],
				$args['date'],
				$args['time'],
				$args['selected_slots']
			),
			'settings'=> '',
		];
		$result['slots'] = ! empty( $result['slots'] ) ? $result['slots'] : [] ;
		
		if( ! $args['admin'] ) {
			ob_start();
			
			$price_instans = new Appointment_Price( $args );
			$price = $price_instans->get_price();

			$format = Plugin::instance()->settings->get('slot_time_format');
			
			if ( ! empty( $args['timezone'] ) ) {
				Time_Slots::set_timezone( $args['timezone'] );
			}
			
			Time_Slots::generate_slots_html( 
				$result['slots'], 
				$format, 
				['data-price="' . $price['price'] . '"'], 
				$args['date'], 
				$args['service'],
				$args['provider']
			);
			
			$result['slots'] = ob_get_clean();
		}
		
		return $result;
	}
	
	public static function get_range_view( $args =null  ){
		$result = [
			'slots' => '',
			'settings'=> '',
		];

		if( ! $args['admin'] ){
			$result['slots'] = Time_Range::get_range_view( $args );
		}else{
			$result['settings'] = Time_Range::get_settings( $args );
			$result['slots']    =  $result['settings']['min_max_time']['max'] ? true : null;
		}

		return $result;
	}
	
	public static function get_recurring_view( $args =null  ){
		$result = [
			'slots' => '',
			'settings'=> '',
			'settings_html'=> '',
		];
		
		if( ! $args['admin'] ){
			$result = Time_Recurring::get_recurring_view( $args );
		}else{
			$result['slots']    = Time_Recurring::get_slots( $args );
			$result['settings'] = Time_Recurring::get_settings( $args );
		}

		return $result;
	}

}

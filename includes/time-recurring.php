<?php
namespace JET_APB;

use JET_APB\Tools;

/**
 * Time_Recurring class
 */
class Time_Recurring {
	
	public static function get_recurring_view( $args = null  ){
		$slots = self::get_slots( $args );
		
		if( empty( $slots ) ){
			return null;
		}
		
		$settings = self::get_settings( $args );
		$result   = [
			'slots' => self::get_recurrence_switch_html(
				[
					'slots'            => $slots,
					'price'            => $settings['price'],
					'service'          => $args['service'],
					'provider'         => $args['provider'],
					'date'             => $args['date'],
					'recurrence_types' => $settings['recurrence_types'],
				]
			),
			'settings_html' => self::get_recurrence_setings_html(
				[
					'service'                => $args['service'],
					'provider'               => $args['provider'],
					'min_recurring_count'    => $settings['min_recurring_count'],
					'max_recurring_count'    => $settings['max_recurring_count'],
					'recurrence_types'       => $settings['recurrence_types'],
					'recurrence_types_label' => $settings['recurrence_types_label'],
					'week_days'              => $settings['week_days'],
					'work_week_days'         => $settings['work_week_days'],
					'week_days_name'         => $settings['week_days_name'],
				]
			),
			'settings' => $settings
		];
		
		return $result;
	}
	
	public static function get_slots( $args = [] ){
		if( empty( $args ) ){
			return null;
		}
		
		$result = Plugin::instance()->calendar->get_date_slots( $args['service'], $args['provider'], $args['date'], $args['time'], $args['selected_slots'] );
		
		return ! empty( $result ) ? $result : null ;
	}
	
	public static function get_settings( $args = [] ){
		$price_instans  = new Appointment_Price( $args );
		$price          = $price_instans->get_price();
		
		return [
			'price'               => $price['price'],
			'recurrence_types'    => Tools::get_time_settings( $args['service'], $args['provider'], 're_booking', [ 0 => "day" ] ),
			'recurrence_types_label' => [
				'day'   => esc_html__( 'Day', 'jet-appointments-booking' ),
				'week'  => esc_html__( 'Week', 'jet-appointments-booking' ),
				'month' => esc_html__( 'Month', 'jet-appointments-booking' ),
				'year'  => esc_html__( 'Year', 'jet-appointments-booking' ),
			],
			'min_recurring_count' => Tools::get_time_settings( $args['service'], $args['provider'], 'min_recurring_count', 1 ),
			'max_recurring_count' => Tools::get_time_settings( $args['service'], $args['provider'], 'max_recurring_count', 5 ),
			'date_format'         => Tools::date_format_php_to_momentjs( get_option( 'date_format' ) ),
			'time_format'         => Tools::date_format_php_to_momentjs( Plugin::instance()->settings->get('slot_time_format') ),
			'week_day_checked'    => [],
			'week_day_order'      => [
				'sunday'    => 0,
				'monday'    => 1,
				'tuesday'   => 2,
				'wednesday' => 3,
				'thursday'  => 4,
				'friday'    => 5,
				'saturday'  => 6,
			],
			'week_days'          => Plugin::instance()->calendar->get_week_days(),
			'work_week_days'     => Plugin::instance()->calendar->get_available_week_days($args['service'], $args['provider']),
			'week_days_name'     => [
				'monday'    => esc_html__( 'Mo', 'jet-appointments-booking' ),
				'tuesday'   => esc_html__( 'Tu', 'jet-appointments-booking' ),
				'wednesday' => esc_html__( 'We', 'jet-appointments-booking' ),
				'thursday'  => esc_html__( 'Th', 'jet-appointments-booking' ),
				'friday'    => esc_html__( 'Fr', 'jet-appointments-booking' ),
				'saturday'  => esc_html__( 'Sa', 'jet-appointments-booking' ),
				'sunday'    => esc_html__( 'Su', 'jet-appointments-booking' ),
			]
		];
	}
	
	public static function get_recurrence_switch_html( $args = [] ){
		ob_start();
		
		Time_Slots::generate_slots_html(
			$args[ 'slots' ],
			plugin::instance()->settings->get('slot_time_format'),
			[ 'data-price="' . $args['price'] . '"' ],
			$args['date'],
			$args['service']
		);
		
		$time_slots_html= ob_get_clean();
		
		$slot_format = '<div class="jet-apb-calendar-slots">%1$s</div>';
		$html = sprintf( $slot_format,  $time_slots_html );

		if( empty( $args['recurrence_types'] ) ){
			return $html;
		}
		
		$recurrence_switch_format = apply_filters(
			'jet-apb/form/recurrence_app/recurrence_switch_format',
			'<div class="jet-apb-switcher" style="visibility: hidden;">
				<label>%1$s
					<span class="jet-apb-switcher__wrapper">
						<input type="checkbox" class="jet-apb-switcher__input" name="jet_apb_recurrence_app">
						<span class="jet-apb-switcher__panel"></span>
						<span class="jet-apb-switcher__trigger"></span>
					</span>
				</label>
			</div>'
		);
		$html .= sprintf(
			$recurrence_switch_format,
			esc_html__( 'Repeat this appointment', 'jet-appointments-booking' )
		);
		
		return $html;
	}
	
	public static function get_recurrence_setings_html( $args = [] ){
		
		if( empty( $args['recurrence_types'] ) ){
			return '';
		}
		
		$field_format = apply_filters(
			'jet-apb/form/recurrence_app/field_format',
			'<div class="jet-form-col jet-form-col-12 jet-form-field-container %3$s" %4$s>
				<div class="jet-form__label">
					<label class="jet-apb__recurrence-label jet-form__label-text">%1$s</label>
				</div>
				%2$s
			</div>'
		);
		
		$recurrence_list_html = '';
		$recurrence_fields = '';
		
		foreach ( $args['recurrence_types'] as $value ){
			switch ( $value ){
				case 'day':
					$recurrence_list_html .= sprintf( '<option value="%1$s">%2$s</option>', $value, $args['recurrence_types_label'][ $value ] );
					break;
				case 'week':
					$week_days_html = '';
					
					foreach ( $args['week_days'] as $week_day ){
						if ( ! in_array( $week_day, $args['work_week_days'] ) ) {
							$disabled = ' disabled';
							$disabled_class = 'week-day-disabled';
						} else {
							$disabled = '';
							$disabled_class = '';
						}
						
						$week_days_html .= sprintf(
							'<label class="jet-apb__week-day %4$s"><input type="checkbox" name="jet_apb_week_day[%1$s]" value="%1$s" %3$s><span>%2$s</span></label>',
							$week_day,
							$args['week_days_name'][ $week_day ],
							$disabled,
							$disabled_class
						);
					}
					
					if( ! empty( $week_days_html ) ){
						$week_days_html = sprintf(
							'<div class="jet-form__field">%1$s</div>',
							$week_days_html
						);
						
						$recurrence_fields .= sprintf(
							$field_format,
							esc_html__( 'On:', 'jet-appointments-booking' ),
							$week_days_html,
							'select-field jet-apb__week-days jet-apb__optionality-field',
							'style="display: none"'
						);
					}
					
					$recurrence_list_html .= sprintf( '<option value="%1$s">%2$s</option>', $value, $args['recurrence_types_label'][ $value ] );
					break;
				case 'month':
					$recurrence_list_html .= sprintf( '<option value="%1$s">%2$s</option>', $value, $args['recurrence_types_label'][ $value ] );
					break;
				case 'year':
					$recurrence_list_html .= sprintf( '<option value="%1$s">%2$s</option>', $value, $args['recurrence_types_label'][ $value ] );
					break;
			}
		}
		
		$recurrence_types = sprintf(
			'<select name="jet_apb_recurrence_types" class="jet-apb__recurrence-type jet-form__field select-field" >%1$s</select>',
			$recurrence_list_html
		);
		
		$html = sprintf(
			$field_format,
			esc_html__( 'Repeat Every:', 'jet-appointments-booking' ),
			$recurrence_types,
			'select-field',
			''
		);
		
		$html .= $recurrence_fields;
		
		$recurrence_count = sprintf(
			'<input type="number" name="jet_apb_recurrence_count" class="jet-apb__recurrence-count jet-form__field text-field" min="%1$s" max="%2$s" value="%1$s">',
			$args['min_recurring_count'],
			$args['max_recurring_count']
		);
		
		$html .= sprintf(
			$field_format,
			esc_html__( 'Appointment Count:', 'jet-appointments-booking' ),
			$recurrence_count,
			'field-type-number',
			''
		);
		
		return $html;
	}
}

<?php
namespace JET_APB\Cron;

use JET_APB\Plugin;

class Clear_On_Hold extends Base {

	public function __construct() {

		add_action( 'jet-apb/settings/before-update', [ $this, 'clear_scheduled_on_interval_change' ], 10, 3 );

		parent::__construct();
	}

	public function clear_scheduled_on_interval_change( $settings, $setting, $value ) {
		
		if ( 'switch_status_period' !== $setting ) {
			return;
		}

		$old_value = isset( $settings['switch_status_period'] ) ? $settings['switch_status_period'] : false;

		if ( $old_value && $old_value !== $value ) {
			$this->unschedule_event();
		}
	}

	/**
	 * Check if recurrent event is active
	 * 
	 * @return boolean [description]
	 */
	public function is_enabled() {
		return Plugin::instance()->settings->get( 'switch_status' );
	}

	public function get_date_to_clear() {
		
		$interval      = $this->event_interval();
		$schedules     = wp_get_schedules();
		$interval_time = isset( $schedules[ $interval ] ) ? $schedules[ $interval ]['interval'] : false;
		$current       = time();

		if ( $interval_time ) {
			$current = $current - $interval_time;
		}

		return wp_date( 'Y-m-d H:i:s', $current );

	}

	/**
	 * Event callback
	 * 
	 * @return [type] [description]
	 */
	public function event_callback() {

		$from_status = Plugin::instance()->settings->get( 'switch_status_from' );
		$to_status   = Plugin::instance()->settings->get( 'switch_status_to' );

		$appointments = Plugin::instance()->db->appointments->query( array(
			'status'             => $from_status,
			'appointment_date<<' => $this->get_date_to_clear(),
		) );

		if ( ! empty( $appointments ) ) {
			foreach ( $appointments as $appointment ) {
				Plugin::instance()->db->appointments->update( array( 'status' => $to_status ), array( 'ID' => $appointment['ID'] ) );
			}
		}

	}

	/**
	 * Event interval name
	 * 
	 * @return [type] [description]
	 */
	public function event_interval() {
		return Plugin::instance()->settings->get( 'switch_status_period' );
	}

	/**
	 * Event hook name
	 * 
	 * @return [type] [description]
	 */
	public function event_name() {
		return 'jet-apb-cron-clear-on-hold';
	}

}

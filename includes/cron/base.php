<?php
namespace JET_APB\Cron;

abstract class Base {

	public function __construct() {

		add_action( 'init', array( $this, 'init' ), 99 );

	}

	public function init() {
		if ( $this->is_enabled() ) {
			$this->schedule_event();
			add_action( $this->event_name(), array( $this, 'event_callback' ) );
		} else {
			$this->unschedule_event();
		}
	}

	/**
	 * Check if recurrent event is active
	 * 
	 * @return boolean [description]
	 */
	public function is_enabled() {
		return true;
	}

	public function next_scheduled() {
		return wp_next_scheduled( $this->event_name() );
	}

	/**
	 * Ensure event is scheduled if enabled
	 * 
	 * @return [type] [description]
	 */
	public function schedule_event() {
		
		if ( ! $this->next_scheduled() ) {
			
			$scheduled = wp_schedule_event( time(), $this->event_interval(), $this->event_name(), array(), true );

			if ( is_wp_error( $scheduled ) && 'invalid_schedule' === $scheduled->get_error_code() ) {
				
				$schedules = wp_get_schedules();
				
				uasort( $schedules, function( $a, $b ) {
			
					if ( $a['interval'] == $b['interval'] ) {
						return 0;
					}

					return ( $a['interval'] < $b['interval'] ) ? -1 : 1;

				} );

				$schedules = array_keys( $schedules );
				$scheduled = wp_schedule_event( time(), $schedules[0], $this->event_name(), array(), true );

			}

		}
	}

	/**
	 * Unschedule event if it deactivated
	 * 
	 * @return [type] [description]
	 */
	public function unschedule_event() {
		
		$timestamp = $this->next_scheduled();
		
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->event_name() );
		}
		
	}

	/**
	 * Event callback
	 * 
	 * @return [type] [description]
	 */
	abstract public function event_callback();

	/**
	 * Event interval name
	 * 
	 * @return [type] [description]
	 */
	abstract public function event_interval();

	/**
	 * Event hook name
	 * 
	 * @return [type] [description]
	 */
	abstract public function event_name();

}

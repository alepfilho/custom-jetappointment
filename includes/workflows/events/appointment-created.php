<?php
namespace JET_APB\Workflows\Events;

class Appointment_Created extends Base_Event {
	
	/**
	 * Object ID
	 * @return [type] [description]
	 */
	public function get_id() {
		return 'appointment-created';
	}

	/**
	 * Object name
	 * 
	 * @return [type] [description]
	 */
	public function get_name() {
		return __( 'Appointment Created', 'jet-appointments-booking' );
	}

	public function hook() {
		return 'jet-apb/db/create/appointments';
	}

}

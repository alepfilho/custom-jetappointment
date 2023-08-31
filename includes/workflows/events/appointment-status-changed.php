<?php
namespace JET_APB\Workflows\Events;

use JET_APB\Plugin;

class Appointment_Status_Changed extends Base_Event {
	
	/**
	 * Object ID
	 * @return [type] [description]
	 */
	public function get_id() {
		return 'appointment-status-changed';
	}

	public function get_statuses() {
		$statuses = \Jet_Engine_Tools::prepare_list_for_js( Plugin::instance()->statuses->get_all(), ARRAY_A );
		return htmlspecialchars( json_encode( $statuses ) );
	}

	public function register_event_controls() {
		echo '<cx-vui-select
			label="' . __( 'New Status', 'jet-appointments-booking' ) . '"
			description="' . __( 'Trigger event if appointment status changed to selected status', 'jet-appointments-booking' ) . '"
			:wrapper-css="[ \'equalwidth\' ]"
			size="fullwidth"
			:options-list="' . $this->get_statuses() . '"
			v-if="\'appointment-status-changed\' === item.event"
			:value="item.new_status"
			@input="updateItem( $event, \'new_status\' )"
		/>';
	}

	public function can_dispatch( $data = [], $appointment = [] ) {
		
		$new_status = isset( $data['new_status'] ) ? $data['new_status'] : false;

		if ( ! $new_status ) {
			return true;
		}

		return $new_status === $appointment['status'];

	}

	/**
	 * Object name
	 * 
	 * @return [type] [description]
	 */
	public function get_name() {
		return __( 'Appointment Status Changed', 'jet-appointments-booking' );
	}

	public function hook() {
		return 'jet-apb/db/update/appointments';
	}

}

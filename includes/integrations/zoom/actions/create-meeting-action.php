<?php
namespace JET_APB\Integrations\Zoom\Actions;

use JET_APB\Plugin;
use JET_APB\Workflows\Actions\Base_Action;
use JET_APB\Integrations\Manager as Integrations_Manager;
use JET_APB\Integrations\Zoom\API;

class Create_Meeting_Action extends Base_Action {

	public function get_id() {
		return 'create-zoom-meeting';
	}

	public function get_name() {
		return __( 'Create Zoom Meeting', 'jet-appointments-bookin' );
	}

	public function register_action_controls() {
		echo '
		<cx-vui-input
			label="' . __( 'Agenda', 'jet-appointments-booking' ) . '"
			description="' . __( 'The meeting`s agenda. Support appointment macros', 'jet-appointments-booking' ) . '"
			:wrapper-css="[ \'equalwidth\', \'has-macros\' ]"
			size="fullwidth"
			v-if="\'create-zoom-meeting\' === item.actions[ actionIndex ].action_id"
			:value="item.actions[ actionIndex ].zoom_agenda"
			@input="setActionProp( actionIndex, \'zoom_agenda\', $event )"
			ref="zoom_agenda"
		><jet-apb-macros-inserter @input="addActionMacros( actionIndex, \'zoom_agenda\', $event )"/></cx-vui-input>
		<cx-vui-input
			label="' . __( 'Password Length', 'jet-appointments-booking' ) . '"
			description="' . __( 'The length of the meeting password. By default is 8. Password itself will be generated automatically and stored in Appointment Meta Data', 'jet-appointments-booking' ) . '"
			:wrapper-css="[ \'equalwidth\', \'has-macros\' ]"
			size="fullwidth"
			v-if="\'create-zoom-meeting\' === item.actions[ actionIndex ].action_id"
			:value="item.actions[ actionIndex ].zoom_password_length"
			@on-input-change="setActionProp( actionIndex, \'zoom_password_length\', $event.target.value )"
			ref="zoom_password_length"
		></cx-vui-input>
		';
	}
	
	public function do_action( $data = [] ) {
		
		$appointment = $this->get_appointments()[0];
		$agenda = $this->parse_macros( $this->get_settings( 'zoom_agenda' ) );
		$password_length = absint( $this->get_settings( 'zoom_password_length' ) );
		$password_length = ! empty( $password_length ) ? $password_length : 8;
		$password = wp_generate_password( $password_length, false, false );

		$zoom  = Integrations_Manager::instance()->get_integrations( 'zoom' );
		$creds = $zoom->get_data();

		$api = new API( $creds['account_id'], $creds['client_id'], $creds['client_secret'] );

		$args = [
			'agenda'   => $agenda,
			'password' => $password,
			'type'     => 2,
		];

		$start_time = $this->get_meeting_start_time();

		$args = [
			'agenda'     => $agenda,
			'password'   => $password,
			'type'       => 2,
			'start_time' => $start_time['time'],
			'duration'   => $this->get_meeting_duration(),
		];

		if ( ! empty( $start_time['timezone'] ) ) {
			$args['timezone'] = $start_time['timezone'];
		}

		$args = apply_filters( 'jet-apb/integrations/create-zoom-meeting/args', $args );
		$response = $api->post( '/users/me/meetings', $args );

		if ( ! $response ) {
			
			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_join_url',
				'Can`t create Zoom Meeting, please contact website admin'
			);

			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_start_url',
				'Can`t create Zoom Meeting, please contact website admin'
			);

			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_password',
				'Can`t create Zoom Meeting, please contact website admin'
			);

			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_id',
				0
			);
			
		} elseif ( ! empty( $response['start_url'] ) && ! empty( $response['join_url'] ) && ! empty( $response['password'] ) ) {
			
			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_join_url',
				$response['join_url']
			);

			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_start_url',
				$response['start_url']
			);

			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_password',
				$response['password']
			);

			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_id',
				$response['id']
			);

		} elseif ( ! empty( $response['message'] ) ) {
			
			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_join_url',
				$response['message']
			);

			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_start_url',
				$response['message']
			);

			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_password',
				$response['message']
			);

			Plugin::instance()->db->appointments_meta->set_meta(
				$appointment['ID'],
				'zoom_id',
				0
			);
			
		}

	}

	public function get_meeting_duration() {
		
		$appointment = $this->get_appointments()[0];
		$start       = $appointment['slot'];
		$end         = ! empty( $appointment['slot_end'] ) ? $appointment['slot_end'] : false;

		if ( ! $end ) {
			return 60;
		}

		return ceil( ( $end - $start ) / 60 );

	}

	public function get_meeting_start_time() {
		
		$appointment = $this->get_appointments()[0];
		$start       = $appointment['slot'];
		$format      = 'Y-m-d\TH:i:s';

		$timezone  = wp_timezone();
		$tz_string = 'UTC';

		if ( 0 === strpos( $timezone->getName(), '+' ) || 0 === strpos( $timezone->getName(), '-' ) ) {
			$time = get_gmt_from_date( date( $format, $start ), $format ) . 'Z';
		} else {
			$time      = date( $format, $start );
			$tz_string = $timezone->getName();
		}

		return [
			'time'     => $time,
			'timezone' => $tz_string,
		];

	}

}

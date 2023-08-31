<?php
namespace JET_APB\Workflows\Actions;

use JET_APB\Plugin;
use JET_APB\Workflows\Base_Object;

class Webhook extends Base_Action {

	public function get_id() {
		return 'webhook';
	}

	public function get_name() {
		return __( 'Call a Webhook', 'jet-appointments-bookin' );
	}

	public function register_action_controls() {
		echo '<cx-vui-input
			label="' . __( 'Webhook URL', 'jet-appointments-booking' ) . '"
			description="' . __( 'Name of the action to visually identify it in the list', 'jet-appointments-booking' ) . '"
			:wrapper-css="[ \'equalwidth\' ]"
			size="fullwidth"
			v-if="\'webhook\' === item.actions[ actionIndex ].action_id"
			:value="item.actions[ actionIndex ].webhook_url"
			@on-input-change="setActionProp( actionIndex, \'webhook_url\', $event.target.value )"
		/>';
	}
	
	public function do_action() {
		
		$this->fetch_appointments_meta();
		
		$webhook_url = $this->parse_macros( $this->get_settings( 'webhook_url' ) );

		$args = array(
			'body' => $this->prepare_webhook_args( $this->get_appointments()[0] ),
		);

		/**
		 * Filter webhook arguments
		 */
		$args = apply_filters( 'jet-apb/workflows/webhook/request-args', $args, $this );

		if ( $webhook_url ) {
			$response = wp_remote_post( $webhook_url, $args );
		}

		do_action( 'jet-apb/workflows/webhook/after-response', $response, $args, $this );

	}

	public function prepare_webhook_args( $appointment = [] ) {

		if ( empty( $appointment['meta'] ) ) {
			$appointment['meta'] = [];
		}

		$slot_time_format = Plugin::instance()->settings->get( 'slot_time_format' );

		if ( empty( $appointment['meta']['user_local_time'] ) ) {
			$local_time = date( $slot_time_format, $appointment['slot'] );

			if ( ! empty( $appointment['slot_end'] ) ) {
				$local_time .= '-' . date( $slot_time_format, $appointment['slot_end'] );
			}

			$appointment['meta']['user_local_time'] = $local_time;

		}

		if ( empty( $appointment['meta']['user_local_date'] ) ) {
			$appointment['meta']['user_local_date'] = date_i18n( get_option( 'date_format', 'F d, Y' ), $appointment['slot'] );	
		}

		if ( empty( $appointment['meta']['user_timezone'] ) ) {
			$appointment['meta']['user_timezone'] = wp_timezone()->getName();
		}

		if ( empty( $appointment['meta']['_service_title'] ) && ! empty( $appointment['service'] ) ) {
			$appointment['meta']['_service_title'] = get_the_title( $appointment['service'] );
		}

		if ( empty( $appointment['meta']['_provider_title'] ) && ! empty( $appointment['provider'] ) ) {
			$appointment['meta']['_provider_title'] = get_the_title( $appointment['provider'] );
		}

		if ( empty( $appointment['meta']['_service_url'] ) && ! empty( $appointment['service'] ) ) {
			$appointment['meta']['_service_url'] = get_permalink( $appointment['service'] );
		}

		if ( empty( $appointment['meta']['_provider_url'] ) && ! empty( $appointment['provider'] ) ) {
			$appointment['meta']['_provider_url'] = get_permalink( $appointment['provider'] );
		}

		return $appointment;

	}

}

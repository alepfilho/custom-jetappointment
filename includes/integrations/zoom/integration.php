<?php
namespace JET_APB\Integrations\Zoom;

use JET_APB\Integrations\Base_Integration;
use JET_APB\Plugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Define workflows manager class
 */
class Integration extends Base_Integration {

	public function __construct() {
		add_action( 'jet-apb/integrations/after-setup', [ $this, 'after_setup' ] );
	}

	public function on_setup() {
		add_filter( 'jet-apb/workflows/actions/register', [ $this, 'register_zoom_action' ] );
		add_filter( 'jet-apb/display-meta-fields', [ $this, 'register_meta' ] );
		new Macros();
	}

	public function after_setup() {
		$data = $this->get_data();
		if ( ! empty( $data['delete_on_appointment_cancel'] ) ) {
			add_action( 'jet-apb/db/update/appointments', [ $this, 'delete_meeting_on_appointment_cancel' ] );
		}
	}

	public function delete_meeting_on_appointment_cancel( $appointment = [] ) {
		
		if ( empty( $appointment['status'] ) || ! in_array( $appointment['status'], array(
			'cancelled',
			'refunded',
			'failed',
		) ) ) {
			return;
		}

		$meta = Plugin::instance()->db->get_appointments_meta( [ $appointment ] );
		$meta = isset( $meta[0] ) ? $meta[0]['meta'] : false;

		if ( $meta && ! empty( $meta['zoom_id'] ) ) {
			$creds    = $this->get_data();
			$api      = new API( $creds['account_id'], $creds['client_id'], $creds['client_secret'] );
			$response = $api->post( '/meetings/' . $meta['zoom_id'], [], [ 'method' => 'DELETE' ] );

			if ( ! $response ) {
				Plugin::instance()->db->appointments_meta->delete( [ 
					'appointment_id' => $appointment['ID'], 
					'meta_key' => 'zoom_id',
				] );

				Plugin::instance()->db->appointments_meta->delete( [ 
					'appointment_id' => $appointment['ID'], 
					'meta_key' => 'zoom_join_url',
				] );

				Plugin::instance()->db->appointments_meta->delete( [ 
					'appointment_id' => $appointment['ID'], 
					'meta_key' => 'zoom_start_url',
				] );

				Plugin::instance()->db->appointments_meta->delete( [ 
					'appointment_id' => $appointment['ID'], 
					'meta_key' => 'zoom_password',
				] );
			}

		}

	}

	public function register_zoom_action( $workflows ) {
		$workflows->register_action_type( new Actions\Create_Meeting_Action() );
	}
	
	public function get_id() {
		return 'zoom';
	}

	public function get_name() {
		return __( 'Zoom', 'jet-appointments-booking' );
	}

	public function get_description() {
		return __( 'Create Zoom meeting and attach it to appointment', 'jet-appointments-booking' );
	}

	public function assets() {
		wp_enqueue_script(
			'jet-apb-zoom-integration-component',
			JET_APB_URL . 'includes/integrations/zoom/assets/js/data-component.js',
			array( 'wp-api-fetch', 'jquery' ),
			JET_APB_VERSION,
			true
		);

		$timezone = Plugin::instance()->settings->get_current_timezone();

		wp_localize_script( 'jet-apb-zoom-integration-component', 'JetAPBZoomData', [
			'api' => Plugin::instance()->rest_api->get_url( 'appointment-zoom-token', false ),
			'timezoneIsSet' => ! empty( $timezone ) ? true : false,
		] );
	}

	public function get_data_component() {
		return 'jet-apb-zoom-integration';
	}

	public function parse_data( $data = [] ) {
		return [
			'account_id' => isset( $data['account_id'] ) ? $data['account_id'] : $this->get_defaults( 'account_id' ),
			'client_id' => isset( $data['client_id'] ) ? $data['client_id'] : $this->get_defaults( 'client_id' ),
			'client_secret' => isset( $data['client_secret'] ) ? $data['client_secret'] : $this->get_defaults( 'client_secret' ),
			'delete_on_appointment_cancel' => isset( $data['delete_on_appointment_cancel'] ) ? filter_var( $data['delete_on_appointment_cancel'], FILTER_VALIDATE_BOOLEAN ) : true,
		];
	}

	public function get_templates() {
		return [
			'jet-apb-zoom-integration' => JET_APB_PATH . 'includes/integrations/zoom/templates/data-component.php',
		];
	}

	public function register_meta( $fields ) {

		$fields['zoom_start_url'] = [
			'label' => __( 'Zoom Start Meeting URL', 'jet-appointments-booking' ),
			'cb'    => function( $value, $meta_key ) {
				return make_clickable( $value );
			}
		];

		$fields['zoom_join_url'] = [
			'label' => __( 'Zoom Join Meeting URL', 'jet-appointments-booking' ),
			'cb'    => function( $value, $meta_key ) {
				return make_clickable( $value );
			}
		];

		$fields['zoom_password'] = [
			'label' => __( 'Zoom Meeting Password', 'jet-appointments-booking' ),
			'cb'    => false,
		];

		return $fields;
	}
}

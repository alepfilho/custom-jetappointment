<?php
namespace JET_APB\Public_Actions\Actions;

use JET_APB\Plugin;
use JET_APB\Public_Actions\Tokens;

class Cancel extends Confirm {

	public function action_id() {
		return 'cancel';
	}

	public function action_css() {
		echo '.jet-apb-action-result.action-cancel { color: var( --wp--preset--color--vivid-red ); }';
	}

	public function do_action( $appointment = [], $manager = null ) {

		if ( ! empty( $appointment['status'] ) && in_array( $appointment['status'], Plugin::instance()->statuses->invalid_statuses() ) ) {
			return false;
		}

		Plugin::instance()->db->appointments->update( array( 'status' => 'cancelled' ), array( 'ID' => $appointment['ID'] ) );
		
		$message = Plugin::instance()->settings->get( 'cancel_action_message' );

		if ( ! $message ) {
			$message = __( 'Appointment cancelled! If you cancelled it by mistake, you need to book this appointment again.', 'jet-appointments-booking' );
		}

		$manager->set_message( $message );

		Plugin::instance()->db->appointments_meta->delete( [ 
			'appointment_id' => $appointment['ID'], 
			'meta_key'       => Tokens::$token_key,
		] );

		return true;

	}

	public function register_macros( $macros, $manager ) {
		
		$macros['cancel_url'] = [
			'label' => __( 'Cancel Appointment URL', 'jet-appointments-booking' ),
			'cb'    => function( $result = null, $args_str = null ) use ( $manager ) {
				$appointment = $manager->get_macros_object();
				return isset( $appointment['meta']['_cancel_url'] ) ? $appointment['meta']['_cancel_url'] : '';
			}
		];

		return $macros;
	}

	public function action_meta() {
		return [
			'_cancel_url' => [
				'label'   => __( 'Cancel URL', 'jet-appointments-booking' ),
				'get_cb'  => [ $this, 'get_url' ],
				'show_cb' => [ $this, 'show_url' ],
			]
		];
	}

}

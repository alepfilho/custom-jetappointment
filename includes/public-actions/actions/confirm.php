<?php
namespace JET_APB\Public_Actions\Actions;

use JET_APB\Plugin;
use JET_APB\Public_Actions\Tokens;

class Confirm extends Base {

	public function action_id() {
		return 'confirm';
	}

	public function action_css() {
		echo '.jet-apb-action-result.action-confirm { color: var( --wp--preset--color--vivid-green-cyan ); }';
	}

	public function do_action( $appointment = [], $manager = null ) {

		if ( ! empty( $appointment['status'] ) && 'completed' === $appointment['status'] ) {
			return false;
		}

		Plugin::instance()->db->appointments->update( array( 'status' => 'completed' ), array( 'ID' => $appointment['ID'] ) );

		$message = Plugin::instance()->settings->get( 'confirm_action_message' );

		if ( ! $message ) {
			$message = __( 'Appointment confirmed!', 'jet-appointments-booking' );
		}

		$manager->set_message( $message );

		return true;

	}

	public function register_macros( $macros, $manager ) {
		
		$macros['confirm_url'] = [
			'label' => __( 'Confirm Appointment URL', 'jet-appointments-booking' ),
			'cb'    => function( $result = null, $args_str = null ) use ( $manager ) {
				$appointment = $manager->get_macros_object();
				return isset( $appointment['meta']['_confirm_url'] ) ? $appointment['meta']['_confirm_url'] : '';
			}
		];

		return $macros;
	}

	public function get_url( $appointment, $action ) {
		
		$tokens = new Tokens();

		return $tokens->token_url( add_query_arg( [
			'_jet_apb_action' => $this->action_id(),
		], home_url( '/' ) ), $appointment );

	}

	public function show_url( $value, $key ) {
		return make_clickable( $value );
	}

	public function action_meta() {
		return [
			'_confirm_url' => [
				'label'   => __( 'Confirm URL', 'jet-appointments-booking' ),
				'get_cb'  => [ $this, 'get_url' ],
				'show_cb' => [ $this, 'show_url' ],
			]
		];
	}

}

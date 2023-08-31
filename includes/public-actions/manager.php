<?php
namespace JET_APB\Public_Actions;

use JET_APB\Plugin;

if ( ! defined( 'WPINC' ) ) {
	die();
}

/**
 * Public actions manager
 */
class Manager {

	private $action_key  = '_jet_apb_action';
	private $message     = null;
	private $appointment = null;

	public function __construct() {
		
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( $this->is_action_request() ) {
			add_action( 'template_include', [ $this, 'process_action' ], 99 );
		}
		
		$this->register_actions();
		$this->process_tokens_meta();

	}

	public function get_appointment() {
		return $this->appointment;
	}

	public function register_actions() {

		new Actions\Confirm();
		new Actions\Cancel();

		do_action( 'jet-apb/public-actions/register', $this );

	}

	public function process_tokens_meta() {
		add_action( 'jet-apb/form-action/insert-appointment', [ $this, 'save_token_meta' ], 20, 2 );
		add_action( 'jet-apb/display-meta-fields', [ $this, 'show_token_meta' ], 20, 2 );
	}

	public function save_token_meta( $appointment, $action ) {
		$tokens = new Tokens();
		$appointment->set_meta( [
			Tokens::$token_key => $tokens->get_token( $appointment ),
		] );
	}

	public function show_token_meta( $fields = [] ) {

		$fields[ Tokens::$token_key ] = [
			'label' => __( 'Token', 'jet-appointments-booking' ),
			'cb'    => false,
		];

		return $fields;

	}

	public function is_enabled() {
		return Plugin::instance()->settings->get( 'allow_action_links' );
	}

	public function is_action_request() {
		return ! empty( $_GET[ Tokens::$token_key ] ) && $this->get_action();
	}

	public function process_action() {

		$tokens = new Tokens();
		$this->appointment = $tokens->get_appointment_by_token( $_GET[ Tokens::$token_key ] );

		if ( ! $this->appointment ) {
			$this->render_error_page();
			return;
		}

		$result = apply_filters(
			'jet-apb/public-actions/process/' . $this->get_action(),
			$this->get_appointment(),
			$this
		);

		if ( $result ) {
			$this->render_result_page();
		} else {
			$this->render_error_page();
		}

	}

	public function get_message() {
		return $this->message;
	}

	public function set_message( $message = '' ) {
		$this->message = $message;
	}

	public function render_action_result_page( $key = 'action', $message = '' ) {

		$custom_page = apply_filters( 'jet-apb/public-actions/custom-' . $key . '-page-content', false, $this );

		if ( $custom_page ) {
			echo $custom_page;
		} else {
			get_header();

			$this->print_styles();

			printf( 
				'<div class="jet-apb-action-result-container"><div class="jet-apb-action-%3$s action-%2$s">%1$s</div></div>',
				$message,
				esc_attr( $this->get_action() ),
				$key
			);

			get_footer();
		}

		die();

	}

	public function render_error_page() {

		$this->render_action_result_page(
			'error',
			__( 'Token is invalid or was already used.', 'jet-appointments-booking' )
		);

	}

	public function render_result_page() {

		$this->render_action_result_page( 'action', $this->get_message() );

	}

	public function get_action() {
		return ! empty( $_GET[ $this->action_key ] ) ? $_GET[ $this->action_key ] : false;
	}

	public function print_styles() {
		
		ob_start();

		echo '.jet-apb-action-result-container { width: 100%; flex: 0 0 100%; } .jet-apb-action-result { padding: 40px; width: 80vw; margin: 15vh auto; max-width: 480px; text-align: center; border: 1px solid currentColor; } .jet-apb-action-error { padding: 40px; width: 80vw; margin: 15vh auto; max-width: 480px; text-align: center; border: 1px solid currentColor; color: red; }';

		do_action( 'jet-apb/public-actions/print-styles/' . $this->get_action(), $this->get_appointment(), $this );

		printf( '<style>%s</style>', ob_get_clean() );

	}

}

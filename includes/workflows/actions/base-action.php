<?php
namespace JET_APB\Workflows\Actions;

use JET_APB\Workflows\Base_Object;
use JET_APB\Plugin;

abstract class Base_Action extends Base_Object {

	public $settings    = [];
	public $appointemnt = [];

	public function __construct() {
		add_action( 'jet-apb/workflows/action-controls', [ $this, 'register_action_controls' ] );
	}

	public function register_action_controls() {	
	}

	public function setup( $settings = [], $appointemnt = [] ) {
		$this->settings    = $settings;
		$this->appointemnt = $appointemnt;
	}

	public function get_settings( $setting = null ) {
		
		if ( ! $setting ) {
			return $this->settings;
		}

		return isset( $this->settings[ $setting ] ) ? $this->settings[ $setting ] : false;

	}

	public function update_settings( $setting, $value = null ) {
		$this->settings[ $setting ] = $value;
	}

	public function get_appointments() {
		return isset( $this->appointemnt['ID'] ) ? [ $this->appointemnt ] : $this->appointemnt;
	}

	public function fetch_appointments_meta() {
		$this->appointemnt = Plugin::instance()->db->get_appointments_meta( $this->get_appointments() );
	}

	public function parse_single_macros( $message_content = '', $appointment = [] ) {

		Plugin::instance()->macros->set_macros_object( $appointment );
		return Plugin::instance()->macros->do_macros( $message_content );

	}

	public function parse_macros( $string = '' ) {

		$appointments = $this->get_appointments();

		preg_match(
			'/\%(appointmens_list|appointments_list)\%([\s\S]*)\%(appointmens_list_end|appointmens_list_end)\%/',
			$string,
			$appointmens_list_matches 
		);
		
		$appointmens_list_content  = isset( $appointmens_list_matches[2] ) ? $appointmens_list_matches[2] : '';

		if ( ! empty( $appointmens_list_content ) ) {

			$appointmens_list_output_content = '';

			foreach ( $appointments as $appointment ) {
				$appointmens_list_output_content .= $this->parse_single_macros( $appointmens_list_content, $appointment );
			}

			if ( ! empty( $appointmens_list_output_content ) ) {
				$string = str_replace( $appointmens_list_content, $appointmens_list_output_content, $string );
			}
			
		}

		return $this->parse_single_macros( $string, $appointments[0] );

	}
	
	abstract public function do_action();

}

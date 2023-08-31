<?php
namespace JET_APB\Integrations;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class Base_Integration {

	public $data = [];
	public $is_enbaled = false;

	public function setup( $enabled = false, $data = [] ) {
		$this->data       = is_array( $data ) ? $data : [];
		$this->is_enbaled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );

		if ( $this->is_enbaled ) {
			$this->on_setup();
		}

	}

	public function assets() {

	}

	public function on_setup() {

	}

	public function get_data_component() {
		return false;
	}

	public function get_templates() {
		return [];
	}

	public function parse_data( $data = [] ) {
		return $data;
	}

	public function to_array() {
		return [
			'id'          => $this->get_id(),
			'enabled'     => $this->is_enbaled,
			'name'        => $this->get_name(),
			'component'   => $this->get_data_component(),
			'description' => $this->get_description(),
			'data'        => $this->get_data(),
		];
	}

	public function get_data() {
		return $this->parse_data( $this->data );
	}

	public function get_defaults( $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : '';
	}

	abstract public function get_id();

	abstract public function get_name();

	abstract public function get_description();

}

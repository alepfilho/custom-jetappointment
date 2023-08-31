<?php
namespace JET_APB\Workflows;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Workflows {

	private $option_key = 'jet_apb_workflows';
	private $items      = [];

	public function __construct() {
		
		$this->items = $this->create_workflows_from_array( get_option( $this->option_key, [
			[
				'enabled' => false,
				'items'   => [],
			]
		] ) );

	}

	public function dispatch_workflows( $scheduled = false ) {
		foreach ( $this->items as $item ) {
			$item->dispatch_workflow( $scheduled );
		}
	}

	public function create_workflows_from_array( $workflows = [] ) {

		$result = [];

		foreach ( $workflows as $workflow ) {
			$result[] = new Workflow( $workflow );
		}

		return $result;
	}

	public function update_workflows( $workflows = [] ) {
		update_option( $this->option_key, $this->to_array( $this->create_workflows_from_array( $workflows ) ) );
	}

	public function to_array( $items = null ) {

		if ( null === $items ) {
			$items = $this->items;
		}

		return array_map( function( $item ) {
			return $item->to_array();
		}, $items );
	}

}

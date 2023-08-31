<?php
namespace JET_APB\Workflows;

use JET_APB\Plugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Workflow {

	private $enabled = false;
	private $items   = [];

	public function __construct( $data ) {
		
		$this->hash    = ! empty( $data['hash'] ) ? $data['hash'] : 'default';
		$this->enabled = ! empty( $data['enabled'] ) ? filter_var( $data['enabled'], FILTER_VALIDATE_BOOLEAN ) : false;
		$this->items   = ! empty( $data['items'] ) ? $data['items'] : [];

		$this->ensure_items_hash();
		
	}

	public function ensure_items_hash() {
		foreach ( $this->items as $index => $item ) {
			if ( empty( $item['hash'] ) ) {
				$this->items[ $index ]['hash'] = rand( 100000, 999999 );
			}
		}
	}

	public function to_array() {
		return [
			'enabled' => $this->enabled,
			'items'   => $this->items,
		];
	}

	public function dispatch_workflow( $scheduled = false ) {
		
		if ( ! $this->enabled ) {
			return;
		}

		foreach ( $this->items as $item ) {
			
			$this->dispatch_event( $item, $scheduled );
			
		}

	}

	public function dispatch_event( $workflow_item = [], $scheduled = false ) {

		if ( empty( $workflow_item['event'] ) ) {
			return;
		}

		$event_object = Plugin::instance()->workflows->get_event( $workflow_item['event'] );

		if ( $event_object ) {
			if ( $scheduled ) {
				$event_object->dispatch_scheduled( $workflow_item );
			} else {
				$event_object->dispatch( $workflow_item );
			}
		}

	}

}

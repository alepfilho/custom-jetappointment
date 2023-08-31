<?php
namespace JET_APB\Workflows;

use JET_APB\Plugin;

class Actions_Dispatcher {

	private $actions = [];
	private $appointment = [];

	public function __construct( $actions = [], $appointment = [] ) {
		$this->actions     = $actions;
		$this->appointment = $appointment;
	}

	public function run() {
	
		foreach ( $this->actions as $action_arr ) {

			if ( empty( $action_arr['action_id'] ) ) {
				continue;
			}

			$action = Plugin::instance()->workflows->get_action( $action_arr['action_id'] );

			$action->setup( $action_arr, $this->appointment );
			$action->do_action();

		}

	}

}

<?php
namespace JET_APB\Workflows\Events;

use JET_APB\Workflows\Base_Object;
use JET_APB\Workflows\Actions_Dispatcher;
use JET_APB\Plugin;
use JET_APB\Cron\Manager as Cron_Manager;

abstract class Base_Event extends Base_Object {

	public function __construct() {
		add_action( 'jet-apb/workflows/event-controls', [ $this, 'register_event_controls' ] );
	}

	public function register_event_controls() {
		
	}

	public function hook_event_handler( $settings ) {
		add_action( $this->hook(), function( $data ) use ( $settings ) {
			$this->handle_event( $data, $settings );
		} );
	}

	public function can_dispatch( $data = [], $appointment = [] ) {
		return true;
	}

	public function dispatch( $data = [] ) {

		if ( empty( $data['actions'] ) ) {
			return;
		}

		add_action( $this->hook(), function( $appointment ) use ( $data ) {

			if ( ! $this->can_dispatch( $data, $appointment ) ) {
				return;
			}

			switch ( $data['schedule'] ) {
				case 'immediately':
					$actions = new Actions_Dispatcher( $data['actions'], $appointment );
					$actions->run();
					break;
				
				case 'before_appointment':
					
					Plugin::instance()->db->appointments_meta->insert( [
						'appointment_id' => $appointment['ID'],
						'meta_key'       => '_schedule_id_' . $data['hash'],
						'meta_value'     => 1,
					] );

					$this->shcedule_dispatcher();

					break;
			}
		} );
	}

	public function dispatch_scheduled( $data = [] ) {
		
		if ( 'before_appointment' !== $data['schedule'] ) {
			return;
		}

		$meta_table = Plugin::instance()->db->appointments_meta->table();
		$app_table  = Plugin::instance()->db->appointments->table();

		$days  = ! empty( $data['days_before'] ) ? absint( $data['days_before'] ) : 1;
		$hash  = $data['hash'];
		$until = time() + $days * DAY_IN_SECONDS;

		if ( Plugin::instance()->workflows->is_debug() && ! empty( $_GET['date'] ) ) {
			$until = strtotime( $_GET['date'] ) + $days * DAY_IN_SECONDS;
		}

		$appointments = Plugin::instance()->db->appointments_meta->wpdb()->get_results( "SELECT ap.*, am.meta_key FROM $meta_table AS am INNER JOIN $app_table AS ap ON ap.ID = am.appointment_id WHERE am.meta_key = '_schedule_id_$hash' AND ap.date <= $until", ARRAY_A );

		foreach ( $appointments as $appointment ) {

			if ( ! $this->can_dispatch( $data, $appointment ) ) {
				continue;
			}

			if ( Plugin::instance()->workflows->is_debug() ) {
				echo '<br>==============================================<br>';
				echo 'Run actions: ' . $appointment['ID'] . ', ' . date( 'Y-m-d H:i', $appointment['slot'] );
				echo '<br>==============================================<br>';
			}

			$actions = new Actions_Dispatcher( $data['actions'], $appointment );
			$actions->run();

			if ( ! Plugin::instance()->workflows->is_debug() ) {
				Plugin::instance()->db->appointments_meta->delete( [ 
					'appointment_id' => $appointment['ID'], 
					'meta_key' => '_schedule_id_' . $hash 
				] );
			} else {
				echo '<br>==============================================<br>';
				echo 'To delete - ' . $appointment['ID'] . ', ' . date( 'Y-m-d H:i', $appointment['slot'] );
				echo '<br>==============================================<br>';
			}

		}

	}

	public function shcedule_dispatcher() {
		$cron_shecdule = Cron_Manager::instance()->get_schedules( 'jet-apb-events-dispatcher' );
		$cron_shecdule->schedule_event();
	}

	abstract public function hook();

}

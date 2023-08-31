<?php
namespace JET_APB\Cron;

/**
 * Manager manager
 */
class Manager {

	/**
	 * Instance.
	 *
	 * Holds the plugin instance.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @var Plugin
	 */
	public static $instance = null;

	private $schedules = [];

	public function __construct() {

		add_filter( 'jet-apb/admin/helpers/page-config/config', array( $this, 'add_intervals_to_config' ), 10, 2 );

		$this->register_schedule( new Clear_On_Hold() );
		$this->register_schedule( new Events_Dispatcher() );
	}

	public function register_schedule( $schedule ) {
		$this->schedules[ $schedule->event_name() ] = $schedule;
	}

	public function get_schedules( $name = null ) {
		
		if ( ! $name ) {
			return $this->schedules;
		}

		return isset( $this->schedules[ $name ] ) ? $this->schedules[ $name ] : false;
	}

	public function add_intervals_to_config( $config, $page ) {
		
		if ( 'jet-apb-settings' === $page ) {
			$config['switch_intervals'] = $this->get_intervals_for_js();
		}

		return $config;
	}

	public function get_intervals_for_js() {
		
		$result    = [];
		$schedules = wp_get_schedules();

		uasort( $schedules, function( $a, $b ) {
			
			if ( $a['interval'] == $b['interval'] ) {
				return 0;
			}

			return ( $a['interval'] < $b['interval'] ) ? -1 : 1;

		} );

		if ( ! empty( $schedules ) ) {
			foreach ( $schedules as $key => $data ) {
				if ( ! isset( $result[ $data['interval'] ] ) ) {
					$result[ $data['interval'] ] = [
						'value' => $key,
						'label' => $data['display'],
					];
				}
				
			}
		}

		return array_values( $result );

	}

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the plugin class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return Plugin An instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;
	}

}

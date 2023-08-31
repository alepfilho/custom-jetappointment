<?php
namespace JET_APB\Integrations;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Define workflows manager class
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

	private $integrations = [];
	private $data         = null;
	private $option_key   = 'jet_apb_integrations';

	public function __construct() {
		$this->register_integrations();
	}

	public function register_integrations() {

		$this->register_integration( new Zoom\Integration() );

		do_action( 'jet-apb/integrations/register', $this );

		foreach ( $this->get_data() as $id => $data ) {
			if ( ! empty( $data ) && isset( $data['data'] ) ) {
				$integration = $this->get_integrations( $id );

				if ( $integration ) {
					$integration->setup( $data['enabled'], $data['data'] );
				}

			}
		}

		do_action( 'jet-apb/integrations/after-setup', $this );

	}

	public function register_integration( Base_Integration $integration ) {
		$this->integrations[ $integration->get_id() ] = $integration;
	}

	public function update_data( $data = [] ) {
		
		$result = [];

		foreach ( $this->get_integrations() as $integration ) {
			if ( isset( $data[ $integration->get_id() ] ) ) {
				$result[ $integration->get_id() ] = [
					'enabled' => $data[ $integration->get_id() ]['enabled'],
					'data'    => $integration->parse_data( $data[ $integration->get_id() ]['data'] ),
				];
			} else {
				$result[ $integration->get_id() ] = [ 'enabled' => false, 'data' => [] ];
			}
		}

		update_option( $this->option_key, $result, false );
	}

	public function get_integrations( $id = null ) {

		if ( ! $id ) {
			return $this->integrations;	
		}
		
		return isset( $this->integrations[ $id ] ) ? $this->integrations[ $id ] : false;
	}

	public function assets() {
		foreach ( $this->get_integrations() as $integration ) {
			$integration->assets();
		}
	}

	public function get_integrations_for_js() {
		return array_map( function( $item ) {
			return $item->to_array();
		}, $this->get_integrations() );
	}

	public function get_templates() {
		$result = [];
		
		foreach ( $this->get_integrations() as $integration ) {
			$result = array_merge( $result, $integration->get_templates() );
		}

		return $result;
	}

	public function get_data() {
		
		if ( ! $this->data ) {
			
			$data = get_option( $this->option_key );
			
			if ( ! $data ) {
				foreach ( $this->get_integrations() as $integration ) {
					$data[ $integration->get_id() ] = [
						'enabled' => false,
					];
				}
			}

			$this->data = $data;
		}

		return $this->data;

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
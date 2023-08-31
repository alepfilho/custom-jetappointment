<?php
/**
 * Uses JetEngine meta component to process meta
 */
namespace JET_APB\Admin\Cpt_Meta_Box;

use JET_APB\Plugin;
use JET_APB\Time_Slots;

class Providers_Meta extends Base_Vue_Meta_Box {

	/**
	 * Default settings array
	 *
	 * @var array
	 */
	protected $defaults;
	
	/**
	 * Price Settingss Config array
	 *
	 * @var array
	 */
	protected $meta_settings = [
		'price_type'        => '_app_price_service',
		'_app_price'        => 10,
		'_app_price_hour'   => 5,
		'_app_price_minute' => 1,
	];

	/**
	 * Class constructor
	 */
	public function __construct() {
	
		parent::__construct( Plugin::instance()->settings->get( 'providers_cpt' ) );
		
		if ( empty( $this->price_types ) ) {
			$this->price_types = [];
		}

		array_unshift(
			$this->price_types,
			[
				'value' => '_app_price_service',
				'label' => esc_html__( 'Inherit Service Price', 'jet-appointments-booking' ),
			]
		);
		
		// Needed for backward compatibility.
		$this->meta_settings['_app_price']  = isset( $_GET['post'] ) ? get_post_meta( $_GET['post'], '_app_price', true ) : 10 ;

		add_filter( 'jet-engine/relations/registered-relation', [ $this, 'register_providers_relation' ] );
	}

	/**
	 * Regsiter services specific metabox on all services registration
	 *
	 * @param  [type] $meta_boxes_manager [description]
	 * @return [type]                     [description]
	 */
	public function register_providers_relation( $relations ){
		$services_cpt  = Plugin::instance()->settings->get( 'services_cpt' );
		$providers_cpt = Plugin::instance()->settings->get( 'providers_cpt' );

		if ( ! $services_cpt ) {
			return;
		}

		if ( empty( $relations ) ) {
			$relations = [];
		}

		$relations['item-0'] = [
			'name'                => 'services to providers',
			'post_type_1'         => $services_cpt,
			'post_type_2'         => $providers_cpt,
			'type'                => 'many_to_many',
			'post_type_1_control' => 1,
			'post_type_2_control' => 1,
			'parent_relation'     => '',
			'id'                  => 'item-0',
		];

		return $relations;
	}

	/**
	 * Add a meta box to post.
	 */
	public function add_meta_box(){
		if ( ! $this->is_cpt_page() ) {
			return;
		}
		
		add_meta_box(
			'settings_meta_box',
			esc_html__( 'Appointment Settings', 'jet-appointments-booking' ),
			[ $this, 'settings_meta_box_callback' ],
			[ $this->current_screen_slug ],
			'normal',
			'high'
		);

		add_meta_box(
			'schedule_meta_box',
			esc_html__( 'Custom Schedule', 'jet-appointments-booking' ),
			[ $this, 'custom_schedule_meta_box_callback' ],
			[ $this->current_screen_slug ],
			'normal',
			'high'
		);
	}
	
	/**
	 * Require metabox html.
	 */
	public function settings_meta_box_callback(){
		require_once( JET_APB_PATH .'templates/admin/settings-meta-box.php' );
	}
	
	/**
	 * Require metabox html.
	 */
	public function custom_schedule_meta_box_callback(){
		require_once( JET_APB_PATH .'templates/admin/custom-schedule-meta-box.php' );
	}
	
	
}

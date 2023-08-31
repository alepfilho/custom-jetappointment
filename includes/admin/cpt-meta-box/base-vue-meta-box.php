<?php
/**
 * Uses Vue component
 */
namespace JET_APB\Admin\Cpt_Meta_Box;

use JET_APB\Plugin;
use JET_APB\Appointment_Price;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Base_Vue_Meta_Box Class.
 *
 * @since 1.0.0
 */
class Base_Vue_Meta_Box {

	/**
	 * Services custom post type.
	 */
	public $current_screen_slug;
	
	/**
	 * Assets array
	 *
	 * @var array
	 */
	protected $assets = [];
	
	/**
	 * Default settings array
	 *
	 * @var array
	 */
	protected $defaults;
	
	/**
	 * Price type array
	 *
	 * @var array
	 */
	protected $price_types;

	/**
	 * Class constructor
	 */
	public function __construct( $current_screen_slug = '' ) {
	
		$this->current_screen_slug  = $current_screen_slug;

		if( ! $current_screen_slug ){
			return;
		}

		if ( is_admin() ) {
			$this->set_default();
		}
		
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ], 11 );

		add_action( 'admin_enqueue_scripts', [ $this, 'vue_assets' ], 10 );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ], 11 );
		add_action( 'admin_enqueue_scripts', [ $this, 'meta_box_assets' ], 12 );

		add_action( 'admin_footer', [ $this, 'render_vue_templates' ] );

		if( wp_doing_ajax() ){
			add_action( 'wp_ajax_jet_apb_save_post_meta', [ $this, 'save_post_meta' ] );
			add_action( 'wp_ajax_nopriv_jet_apb_save_post_meta',  [ $this, 'save_post_meta' ] );
		}

		add_action( 'jet-engine/meta-boxes/register-instances', [ $this, 'add_meta_data_in_listing' ] );
		add_filter( 'jet-engine/listings/dynamic-field/custom-value', [ $this, 'listing_meta_callback' ], 10, 3 );
	}
	/**
	 * Class slug
	 * @return string
	 */
	public function set_default(){
		$this->price_types = [
			[
				'value' => '_app_price',
				'label' => esc_html__( 'Slot', 'jet-appointments-booking' ),
			],
			[
				'value' => '_app_price_hour',
				'label' => esc_html__( 'Hour', 'jet-appointments-booking' ),
			],
			[
				'value' => '_app_price_minute',
				'label' => esc_html__( 'Minute', 'jet-appointments-booking' ),
			],
		];
		
		$this->defaults['meta_settings'] = $this->meta_settings;

		$this->defaults['custom_schedule'] = $this->get_default_schedule();
	}

	/**
	 * Get default schedule form meta box
	 * 
	 * @return [type] [description]
	 */
	public function get_default_schedule() {
		
		$default = array_merge(
			Plugin::instance()->settings->working_hours,
			[ 'use_custom_schedule' => false ]
		);

		// ensure appointments_range is set by current genral value
		$default['appointments_range'] =  Plugin::instance()->settings->get( 'appointments_range' );

		return $default;
	}

	/**
	 * Class slug
	 * @return string
	 */
	public function slug(){
		return 'jet_apb_post_meta';
	}

	/**
	 * Checks the post page.
	 *
	 * @return boolean [description]
	 */
	public function is_cpt_page() {
		return is_admin() && function_exists( 'jet_engine' ) && get_current_screen()->id === $this->current_screen_slug;
	}

	/**
	 * Add a meta box to post.
	 */
	public function add_meta_box() {}

	/**
	 * add_meta_data_in_listing
	 */
	public function add_meta_data_in_listing( $meta_boxes_manager ) {
		$current_cpt = $this->current_screen_slug;

		if ( ! $current_cpt ) {
			return;
		}

		$meta_in_listing = apply_filters( 'jet-appointments/meta-boxes/listing',
			[
				[
					'title'       => esc_html__( 'Slot Duration', 'jet-appointments-booking' ),
					'name'        => $current_cpt . '__default_slot',
					'object_type' => 'field',
					'type'        => 'text',
				],
				[
					'title'       => esc_html__( 'Buffer Before Slot', 'jet-appointments-booking' ),
					'name'        => $current_cpt . '__buffer_before',
					'object_type' => 'field',
					'type'        => 'text',
				],
				[
					'title'       => esc_html__( 'Buffer After Slot', 'jet-appointments-booking' ),
					'name'        => $current_cpt . '__buffer_after',
					'object_type' => 'field',
					'type'        => 'text',
				],
			]
		);

		$meta_boxes_manager->store_fields(
			$current_cpt,
			$meta_in_listing
		);
	}

	/**
	 * listing_meta_callback
	 */
	public function listing_meta_callback( $value, $settings, $dynamic_field_class_instance ) {

		if ( empty( $settings[ 'dynamic_field_post_meta' ] ) ) {
			return $value;
		}

		$services_cpt        = Plugin::instance()->settings->get( 'services_cpt' );
		$providers_cpt       = Plugin::instance()->settings->get( 'providers_cpt' );
		$meta_key            = str_replace( $this->current_screen_slug . '__', '', $settings[ 'dynamic_field_post_meta' ] );
		$result_format       = apply_filters( 'jet-apb/listings/dynamic-field/value_format', '<div class="jet_apb_list_meta">%1$s <span class="jet_apb_list_meta_value">%2$s</span></div>' );
		$result_title_format = apply_filters( 'jet-apb/listings/dynamic-field/value_title_format', '<span class="jet_apb_list_meta_title">%1$s:</span>' );

		switch ( $meta_key ) {
			case 'default_slot':
			case 'buffer_before':
			case 'buffer_after':
				$value = '';
				$is_provider_meta = false;

				if ( ! empty( $providers_cpt ) ) {
					$is_provider_meta = ( false !== strpos( $settings[ 'dynamic_field_post_meta' ], $providers_cpt ) ) ? true : false;
				}

				switch ( get_post_type() ) {
					case $providers_cpt:
						$posts_ID = $is_provider_meta ? get_the_ID() : Plugin::instance()->tools->get_services_for_provider( get_the_ID() ) ;
					break;

					case $services_cpt:
						$posts_ID = $is_provider_meta ? Plugin::instance()->tools->get_providers_for_service( get_the_ID() ) : get_the_ID() ;
					break;
				}

				if( ! is_array( $posts_ID ) ){
					$posts_ID = [ $posts_ID ];
				}

				foreach ( $posts_ID as $post_object ) {
					$ID = isset( $post_object->ID ) ? $post_object->ID : $post_object ;
					$post_meta  = get_post_meta( $ID, 'jet_apb_post_meta', true );
					$post_title = ( count( $posts_ID ) > 1 ) ? sprintf( $result_title_format, get_the_title( $ID ) ) : '' ;

					if( ! isset( $post_meta[ 'custom_schedule' ] ) || ! $post_meta[ 'custom_schedule' ][ 'use_custom_schedule' ] ){
						$settings_key = $meta_key === 'default_slot' ? $meta_key : 'default_' . $meta_key ;
						$time = Plugin::instance()->settings->get( $settings_key );
					}else{
						$time = $post_meta[ 'custom_schedule' ][ $meta_key ];
					}

					$value .= sprintf( $result_format, $post_title, Plugin::instance()->tools->secondsToTime( $time, 'H:i' ) );
				}

				if ( ! $value ) {
					break;
				}

			break;
		}

		return $value;
	}

	/**
	 * Return default value.
	 *
	 * @return [array] [description]
	 */
	public function meta_box_default_value() {
		return $this->defaults;
	}

	/**
	 * Return values from the database.
	 *
	 * @return [array] [description]
	 */
	public function meta_box_value(){
		$post_ID         = get_the_ID();
		$post_meta       = get_post_meta( $post_ID, $this->slug(), true );
		$post_meta       = is_array( $post_meta ) ? $post_meta : [] ;
		$post_meta['ID'] = $post_ID;

		return $post_meta;
	}

	/**
	 * Parsed data before written to the database and after get from the database.
	 *
	 * @return [array] [description]
	 */
	public function parse_settings( $settings ){
		$settings[ 'custom_schedule' ] = $this->parse_custom_schedule( $settings[ 'custom_schedule' ] );
		$settings[ 'meta_settings' ] = $this->parse_price_settings( $settings[ 'meta_settings' ] );
		
		return $settings;
	}

	
	/**
	 * Parsed data before written to the database and after get from the database.
	 *
	 * @return [array] [description]
	 */
	public function parse_price_settings( $settings ){
		foreach ( $settings as $setting => $value ) {
			switch ( $setting ) {
				case '_app_price_service':
				case '_app_price':
				case '_app_price_hour':
				case '_app_price_minute':
					$value = $value + 0;
					$settings[ $setting ] = $value < 0 ? 0 : $value;
					break;
				
				default:
					$settings[ $setting ] = $value;
					break;
			}
		}

		return $settings;
	}
	
	/**
	 * Parsed data before written to the database and after get from the database.
	 *
	 * @return [array] [description]
	 */
	public function parse_custom_schedule( $settings ){
		foreach ( $settings as $setting => $value ) {
			
			switch ( $setting ) {
				case 'working_days':
				case 'days_off':
					if ( ! is_array( $value ) ) {
						$settings[ $setting ] = false;
					}
					break;
				
				case 'use_custom_schedule':
				case 'use_custom_labels':
				case 'multi_booking':
				case 'several_days':
				case 'only_start':
					$settings[ $setting ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
					break;
				
				case 'buffer_before':
				case 'buffer_after':
				case 'default_slot':
				case 'locked_time':
				case 'max_duration':
				case 'step_duration':
					$settings[ $setting ] = intval( $value );
					break;
				
				case 'min_slot_count':
				case 'max_slot_count':
					$value = ceil( $value );
				$settings[ $setting ] = $value <= 0 ? 1 : $value ;
					break;
				
				default:
					$settings[ $setting ] = $value;
					break;
			}
		}
		
		return $settings;
	}
	/**
	 * Saves metadata to the database.
	 *
	 */
	public function save_post_meta(){

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Access denied', 'jet-appointments-booking' ),
			) );
		}

		$post_meta = ! empty( $_REQUEST['jet_apb_post_meta'] ) ? $_REQUEST['jet_apb_post_meta']: array();

		if ( empty( $post_meta ) || ! isset( $post_meta['ID'] ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Empty data or post ID not found!', 'jet-appointments-booking' ),
			) );
		}
		
		$post_meta = $this->parse_settings( $post_meta );

		// Needed for backward compatibility.
		if( ! empty( $post_meta['meta_settings']['_app_price'] ) ) {
			update_post_meta( $post_meta['ID'] , '_app_price' , $post_meta['meta_settings']['_app_price'] );
		}
		if( ! empty( $post_meta['meta_settings']['_app_price_hour'] ) ){
			update_post_meta( $post_meta['ID'] , '_app_price_hour' , $post_meta['meta_settings']['_app_price_hour'] );
		}
		if( ! empty( $post_meta['meta_settings']['_app_price_minute'] ) ){
			update_post_meta( $post_meta['ID'] , '_app_price_minute' , $post_meta['meta_settings']['_app_price_minute'] );
		}

		$result = update_post_meta( $post_meta['ID'] , 'jet_apb_post_meta' , $post_meta );

		if( ! $result || is_wp_error( $result ) ){
			wp_send_json_error( [
				'message' => esc_html__( 'Failed to save data!', 'jet-appointments-booking' ),
			] );
		}

		wp_send_json_success( [
			'message' => esc_html__( 'Settings saved!', 'jet-appointments-booking' ),
		] );
	}

	/**
	 * Include scripts and styles
	 */
	public function assets() {
		if ( ! $this->is_cpt_page() ) {
			return;
		}

		//Enqueue script
		wp_enqueue_script( 'cx-vue' );
		wp_enqueue_script( 'momentjs' );
		wp_enqueue_script( 'vuejs-datepicker' );

		//Enqueue style
		wp_enqueue_style( 'jet-apb-working-hours' );
		wp_enqueue_style( 'jet-appointments-booking-admin' );
	}

	/**
	 * Include vue scripts.
	 */
	public function vue_assets() {
		if ( ! $this->is_cpt_page() ) {
			return;
		}

		$ui_data = jet_engine()->framework->get_included_module_data( 'cherry-x-vue-ui.php' );
		$ui      = new \CX_Vue_UI( $ui_data );
		$ui->enqueue_assets();
		
		$post_meta     = array_merge(
			wp_parse_args( Plugin::instance()->settings->assets, [ 'price_types' => $this->price_types ] ),
			$this->assets
		);
		$default_value = $this->meta_box_default_value();
		$meta_value    = $this->meta_box_value();
		
		$post_meta['ID'] = $meta_value['ID'];
		
		$meta_settings               = isset( $meta_value['meta_settings'] ) ? $meta_value['meta_settings'] : [] ;
		$post_meta['meta_settings']  = wp_parse_args( $meta_settings, $default_value['meta_settings'] );
		
		$custom_schedule               = isset( $meta_value['custom_schedule'] ) ? $meta_value['custom_schedule'] : [] ;
		$post_meta['custom_schedule']  = wp_parse_args( $custom_schedule, $default_value['custom_schedule'] );
		$post_meta['custom_schedule']['booking_type'] = Plugin::instance()->settings->get( 'booking_type' );
		
		$post_meta = $this->parse_settings( $post_meta );
		$post_meta = wp_parse_args( $post_meta, $this->assets );

		if ( ! empty( $post_meta ) ) {
			wp_localize_script( 'cx-vue', 'jetApbPostMeta', $post_meta );
		}
	}

	/**
	 * Include meta box scripts.
	 */
	public function meta_box_assets() {
		if ( ! $this->is_cpt_page() ) {
			return;
		}

		Plugin::instance()->dashboard->components->appointments_range->assets();

		wp_enqueue_script(
			'jet_apb_post_meta_box',
			JET_APB_URL . 'assets/js/admin/settings.js',
			array( 'wp-api-fetch' ),
			JET_APB_VERSION,
			true
		);
	}

	/**
	 * Page components templates
	 *
	 * @return [type] [description]
	 */
	public function vue_templates() {
		return [
			[
				'dir'  => 'jet-apb-settings',
				'file' => 'settings-working-hours',
			],
			[
				'dir'  => 'jet-apb-settings',
				'file' => 'custom-day-schedule',
			]
		];
	}

	/**
	 * Render vue templates
	 *
	 * @return [type] [description]
	 */
	public function render_vue_templates() {

		Plugin::instance()->dashboard->components->appointments_range->template();
		
		foreach ( $this->vue_templates() as $template ) {
			if ( is_array( $template ) ) {
				$this->render_vue_template( $template['file'], $template['dir'] );
			} else {
				$this->render_vue_template( $template );
			}
		}
	}

	/**
	 * Render vue template
	 *
	 * @return [type] [description]
	 */
	public function render_vue_template( $template, $path = null, $id = null ) {

		if ( ! $path ) {
			$path = $this->slug();
		}

		$file = JET_APB_PATH . 'templates/admin/' . $path . '/' . $template . '.php';

		if ( ! is_readable( $file ) ) {
			return;
		}

		ob_start();
		include $file;
		$content = ob_get_clean();

		printf(
			'<script type="text/x-template" id="jet-apb-%1$s">%2$s</script>',
			$template,
			$content
		);
	}
}

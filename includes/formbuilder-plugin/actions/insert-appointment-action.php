<?php


namespace JET_APB\Formbuilder_Plugin\Actions;

use JET_APB\Form_Handlers\Send_Email_Handler;
use JET_APB\Form_Handlers\Webhook_Handler;
use JET_APB\Insert_Appointment;
use JET_APB\Plugin;
use JET_APB\Resources\Appointment_Collection;
use JET_APB\Vendor\Actions_Core\Smart_Action_It;
use JET_APB\Vendor\Actions_Core\Smart_Action_Trait;
use Jet_Form_Builder\Actions\Events\Gateway_Failed\Gateway_Failed_Event;
use Jet_Form_Builder\Actions\Events\Gateway_Success\Gateway_Success_Event;
use Jet_Form_Builder\Actions\Types\Base;
use Jet_Form_Builder\Classes\Tools;
use Jet_Form_Builder\Gateways\Gateway_Manager;

class Insert_Appointment_Action extends Base implements Smart_Action_It {

	use Smart_Action_Trait;
	use Send_Email_Handler;
	use Webhook_Handler;
	use Insert_Appointment;

	public static $appointments = false;

	public function __construct() {
		parent::__construct();

		add_filter(
			'jet-form-builder/action/webhook/request-args',
			array( $this, 'parse_webhook_args' ),
			10,
			3
		);

		add_filter(
			'jet-form-builder/send-email/message_content',
			array( $this, 'parse_message_content' ),
			10,
			2
		);

		add_action(
			'jet-apb/jet-fb/action/success',
			array( self::class, 'save_appointments' )
		);
	}

	public function unsupported_events(): array {
		return array(
			Gateway_Failed_Event::class,
			Gateway_Success_Event::class,
		);
	}

	public static function save_appointments( $appointments ) {
		self::$appointments = $appointments;
	}

	public function get_id() {
		return 'insert_appointment';
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return esc_html__( 'Insert appointment', 'jet-appointments-booking' );
	}

	/**
	 * @return array
	 */
	public function visible_attributes_for_gateway_editor() {
		return array( 'appointment_service_field', 'appointment_provider_field' );
	}

	/**
	 * @return string
	 */
	public function self_script_name() {
		return 'JetAppointmentActionData';
	}

	public function on_register_in_flow() {
		/** Run this only in JFB 2.X & on catch gateway process */
		if ( ! function_exists( 'jet_fb_gateway_current' )
			|| ! Gateway_Manager::instance()->current_gateway()
		) {
			return;
		}

		$request = jet_fb_action_handler()->request_data;

		if ( ! isset( $request['appointment_id_list'] ) ) {
			return;
		}

		$list = $request['appointment_id_list'] ?? array();
		$list = is_array( $list ) ? $list : Tools::decode_json( $list );

		$collection = new Appointment_Collection( $this );

		foreach ( $list as $appointment_id ) {
			$appointment = Plugin::instance()->db->get_appointment_by( 'ID', $appointment_id );

			$single                 = $collection->add()->set_from_request( $appointment );
			static::$appointments[] = $single->to_array();
		}
	}

	public function action_data() {
		$additional_db_columns = Plugin::instance()->settings->get( 'db_columns' );
		$wc_integration        = Plugin::instance()->settings->get( 'wc_integration' ) && Plugin::instance()->wc->details;
		$post_id               = get_the_ID();
		$checkout_fields       = array();
		$details               = array();
		$nonce                 = '';

		if ( $wc_integration ) {
			$checkout_fields = Plugin::instance()->wc->get_checkout_fields();
			$details         = Plugin::instance()->wc->details->get_details_schema( $post_id );
			$nonce           = wp_create_nonce( Plugin::instance()->wc->details->meta_key );
		}

		return array(
			'columns'        => $additional_db_columns,
			'wc_integration' => $wc_integration,
			'wc_fields'      => $checkout_fields,
			'apartment'      => $post_id,
			'details'        => $details,
			'nonce'          => $nonce,
			'details_types'  => array(
				array(
					'value' => 'service',
					'label' => esc_html__( 'Service name', 'jet-appointments-booking' ),
				),
				array(
					'value' => 'provider',
					'label' => esc_html__( 'Provider name', 'jet-appointments-booking' ),
				),
				array(
					'value' => 'date',
					'label' => esc_html__( 'Date', 'jet-appointments-booking' ),
				),
				array(
					'value' => 'slot',
					'label' => esc_html__( 'Time slot start', 'jet-appointments-booking' ),
				),
				array(
					'value' => 'slot_end',
					'label' => esc_html__( 'Time slot end', 'jet-appointments-booking' ),
				),
				array(
					'value' => 'start_end_time',
					'label' => esc_html__( 'Full time slot', 'jet-appointments-booking' ),
				),
				array(
					'value' => 'date_time',
					'label' => esc_html__( 'Full date and time', 'jet-appointments-booking' ),
				),
				array(
					'value' => 'field',
					'label' => esc_html__( 'Form field', 'jet-appointments-booking' ),
				),
				array(
					'value' => 'add_to_calendar',
					'label' => esc_html__( 'Add to Google calendar link', 'jet-appointments-booking' ),
				),
			),
		);
	}

	/**
	 * @return array
	 */
	public function editor_labels() {
		return array(
			'appointment_service_field'   => esc_html__( 'Service ID field', 'jet-appointments-booking' ),
			'appointment_service_manual'  => esc_html__( 'Manual input Service ID', 'jet-appointments-booking' ),
			'appointment_provider_field'  => esc_html__( 'Provider ID field', 'jet-appointments-booking' ),
			'appointment_provider_manual' => esc_html__( 'Manual input Provider ID', 'jet-appointments-booking' ),
			'appointment_date_field'      => esc_html__( 'Appointment date field', 'jet-appointments-booking' ),
			'appointment_email_field'     => esc_html__( 'User e-mail field', 'jet-appointments-booking' ),
			'appointment_name_field'      => esc_html__( 'User name field', 'jet-appointments-booking' ),
			'appointment_custom_field'    => esc_html__( 'Fields Map', 'jet-appointments-booking' ),
			'appointment_wc_price'        => esc_html__( 'WooCommerce Price field', 'jet-appointments-booking' ),
			'wc_order_details'            => esc_html__( 'WooCommerce order details', 'jet-appointments-booking' ),
			'wc_fields_map'               => esc_html__( 'WooCommerce checkout fields map', 'jet-appointments-booking' ),
			'db_columns_map'              => esc_html__( 'DB columns map:', 'jet-appointments-booking' ),
			'wc_details__type'            => esc_html__( 'Type', 'jet-appointments-booking' ),
			'wc_details__label'           => esc_html__( 'Label', 'jet-appointments-booking' ),
			'wc_details__date_format'     => esc_html__( 'Date format', 'jet-appointments-booking' ),
			'wc_details__time_format'     => esc_html__( 'Time format', 'jet-appointments-booking' ),
			'wc_details__field'           => esc_html__( 'Select form field', 'jet-appointments-booking' ),
			'wc_details__link_label'      => esc_html__( 'Link text', 'jet-appointments-booking' ),
		);
	}

	public function editor_labels_help() {
		return array(
			'appointment_wc_price' => esc_html__(
				'Select field to get total price from. If not selectedprice will be get from post meta value.',
				'jet-appointments-booking'
			),
			'wc_order_details'     => esc_html__(
				'Set up booking-related info you want to add to the WooCommerce orders and e-mails',
				'jet-appointments-booking'
			),
			'wc_fields_map'        => esc_html__(
				'Connect WooCommerce checkout fields to appropriate form fields. 
				This allows you to pre-fill WooCommerce checkout fields after redirect to checkout.',
				'jet-appointments-booking'
			),
		);
	}

}

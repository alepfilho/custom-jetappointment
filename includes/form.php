<?php

namespace JET_APB;

use JET_APB\Form_Fields\Date_Field_Render;
use JET_APB\Form_Fields\Provider_Field_Render;
use JET_APB\Form_Handlers\Send_Email_Handler;
use JET_APB\Form_Handlers\Webhook_Handler;
use JET_APB\Vendor\Actions_Core\Smart_Action_It;
use JET_APB\Vendor\Actions_Core\Smart_Notification_Trait;

/**
 * Form controls and notifications class
 */
class Form implements Smart_Action_It {

	use Smart_Notification_Trait;
	use Send_Email_Handler;
	use Webhook_Handler;
	use Insert_Appointment;

	/**
	 * Check if date field is already rendered
	 *
	 * @var boolean
	 */
	public $date_done = false;

	/**
	 * Check if provider field is already rendered
	 *
	 * @var boolean
	 */
	public $appointment_data = false;

	/**
	 * Constructor for the class
	 */
	public function __construct() {
		new Form_Widget();

		add_filter(
			'jet-engine/forms/booking/field-types',
			array( $this, 'register_form_fields' )
		);

		$date = new Date_Field_Render();
		add_action(
			'jet-engine/forms/booking/field-template/appointment_date',
			array( $date, 'getFieldTemplate' ),
			10, 3
		);

		$provider = new Provider_Field_Render();
		add_action(
			'jet-engine/forms/booking/field-template/appointment_provider',
			array( $provider, 'getFieldTemplate' ),
			10, 3
		);

		add_filter(
			'jet-engine/forms/booking/notification-types',
			array( $this, 'register_notification' )
		);

		add_action(
			'jet-engine/forms/edit-field/before',
			array( $this, 'edit_fields' )
		);

		add_action(
			'jet-engine/forms/booking/notifications/fields-after',
			array( $this, 'notification_fields' )
		);

		add_filter(
			'jet-engine/forms/booking/notification/insert_appointment',
			array( $this, 'do_action' ),
			1, 2
		);

		add_filter(
			'jet-engine/forms/gateways/notifications-before',
			array( $this, 'before_form_gateway' ), 1, 2
		);

		add_action(
			'jet-engine/forms/gateways/on-payment-success',
			array( $this, 'on_gateway_success' ), 10, 3
		);

		add_action(
			'jet-engine/forms/notifications/control_description',
			array( $this, 'appointments_macros' ), 10
		);

		add_filter(
			'jet-engine/forms/booking/email/message_content',
			array( $this, 'parse_message_content' ),
			2, 10
		);

		add_action(
			'elementor/preview/enqueue_scripts',
			array( $this, 'calendar_assets' )
		);

		add_action(
			'jet-engine/forms/booking/after-start-form',
			[ $this, 'add_hidden_inputs' ]
		);

		add_action(
			'jet-apb/form/notification/success',
			[ $this, 'appointments_form_success' ]
		);

		add_filter(
			'jet-engine/forms/booking/notification/webhook/request-args',
			array( $this, 'parse_webhook_args' ),
			3, 10
		);

		add_action(
			'jet-engine/forms/editor/macros-list',
			[ $this, 'macros_list_in_calc' ]
		);

	}

	public function appointments_form_success($value){
		$this->appointment_data = $value;
	}

	/**
	 * The function added macros to the form editor.
	 */
	public function macros_list_in_calc(){
		?>
			</br>
			<div>
				<strong><?php esc_html_e( 'Appointment Macros:', 'jet-appointments-booking' ) ?></strong>
			</div>
			<div>
				- <strong>%FIELD::appointment_date%</strong> - <?php esc_html_e( 'The macro returns the appointment price. This macro works with any appointment settings, multi-bookings, recurring bookings and more.', 'jet-appointments-booking' ) ?>
				</br><b>appointment_date</b> <?php esc_html_e( ' - this is the name of the field, the type of which is "appointment date".', 'jet-booking' ); ?>
			</div>
		<?php
	}

	/**
	 * The function added macros to the form editor.
	 */
	public function appointments_macros(){
		?>
		<div>
			<strong><?php esc_html_e( 'Appointment Macros:', 'jet-appointments-booking' ) ?></strong>
		</div>
		<div>
			- <strong>%service_title%</strong> - <?php esc_html_e( 'Name of the appointment service.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%provider_title%</strong> - <?php esc_html_e( 'Name of the appointment provider.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%service_link%</strong> - <?php esc_html_e( 'Link of the appointment service.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%provider_link%</strong> - <?php esc_html_e( 'Link of the appointment provider.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%appointment_start%</strong> - <?php printf( esc_html__( 'Displays the date and time the appointment started. You can set the format for the date. %1$sRead more%2$s about the time format. For example: %3$s%%appointment_start|format_date(F j, Y g:i)%%%4$s', 'jet-appointments-booking' ), '<a href="https://wordpress.org/support/article/formatting-date-and-time" >', '</a>', '<strong>', '</strong>' ) ?>
		</div>
		<div>
			- <strong>%appointment_end%</strong> - <?php esc_html_e( 'Displays the date and time the appointment end. Also accepts date format.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%user_local_time%</strong> - <?php esc_html_e( 'Displays the time of the appointment in the user timezone. If timezones management are enabled.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%user_local_date%</strong> - <?php esc_html_e( 'Displays the date of the appointment in the user timezone. If timezones management are enabled.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%user_timezone%</strong> - <?php esc_html_e( 'Displays the user`s timezone. If timezones management are enabled.', 'jet-appointments-booking' ) ?>
		</div>

		<div>
			- <strong>%appointment_price%</strong> - <?php esc_html_e( 'Displays the individual price of the appointment booked.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%appointmens_list%</strong>
			<br><i> ... some text ... </i><br>
			<strong>%appointmens_list_end%</strong> - <?php esc_html_e( 'The macro displays a list of booked appointments if multi-booking is enabled in the settings. Other macros can be used within this macro.', 'jet-appointments-booking' ) ?>
		</div>
		<?php
	}

	/**
	 * The function added macros to the form editor.
	 */
	public function add_hidden_inputs($class_instant){
		$appointment_field = false;

		foreach ($class_instant->fields as $field) {
			if( 'appointment_date' === $field['settings']['type'] ){
				$appointment_field = $field['settings'];
				break;
			}
		}

		if( ! $appointment_field ){
			return;
		}

		$input_data = [];
		$service_id = $this->get_service_field_data( $appointment_field );
		$input_data['service_id'] = $service_id["id"];

		if( Plugin::instance()->settings->get( 'providers_cpt' ) ){
			$provider_id = $this->get_provider_field_data( $appointment_field );
			$input_data['provider_id'] = $provider_id["id"];
		}

		foreach ( $input_data as $key => $value ) {
			if( $value ){
				printf(
					'<input class="jet-form__field hidden-field" type="hidden" name="_jet_engine_booking_%1$s" value="%2$s" data-field-name="_jet_engine_booking_%1$s">',
					$key,
					$value
				);
			}
		}
	}

	/**
	 * The function returns the field names from the form.
	 * @param  [type] $fields       [description]
	 * @param  [type] $field_type   [description]
	 * @param  [type] $setting_name [description]
	 * @return [type]               [description]
	 */
	public function get_field_value( $fields, $field_type, $setting_name ){
		$fields_type = array_column( $fields, 'name', 'type' );
		$field_value    = '';

		if( isset( $fields_type[ $field_type ] ) ){
			$field_name = $fields_type[ $field_type ];
			$field_value    = isset( $fields[ $field_name ][ $setting_name ] ) ? $fields[ $field_name ][ $setting_name ] : $field_value ;
		}

		return $field_value;
	}

	/**
	 * Set bookng appointment notification before gateway
	 *
	 * @param  [type] $keep [description]
	 * @param  [type] $all  [description]
	 * @return [type]       [description]
	 */
	public function before_form_gateway( $keep, $all ) {

		foreach ( $all as $index => $notification ) {
			if ( 'insert_appointment' === $notification['type'] && ! in_array( $index, $keep ) ) {
				$keep[] = $index;
			}
		}

		return $keep;

	}

	/**
	 * Finalize booking on internal JetEngine form gateway success
	 *
	 * @return [type] [description]
	 */
	public function on_gateway_success( $form_id, $settings, $form_data ) {

		$appointment_id = isset( $form_data['appointment_id'] ) ? $form_data['appointment_id'] : false;

		if ( ! $appointment_id ) {
			return;
		}

		$appointment = Plugin::instance()->db->get_appointment_by( 'ID', $appointment_id );

		if ( ! $appointment ) {
			return;
		}

		Plugin::instance()->db->appointments->update(
			array( 'status' => 'completed', ),
			array( 'ID' => $appointment_id, )
		);

		Plugin::instance()->db->maybe_exclude_appointment_date( $appointment );

	}

	/**
	 * Register new notification type
	 *
	 * @return [type] [description]
	 */
	public function register_notification( $notifications ) {
		$notifications['insert_appointment'] = __( 'Insert appointment' );
		return $notifications;
	}

	/**
	 * Render additional edit fields
	 *
	 * @return [type] [description]
	 */
	public function edit_fields() {
		include JET_APB_PATH . 'templates/admin/form/edit-fields.php';
	}

	/**
	 * Render additional notification fields
	 *
	 * @return [type] [description]
	 */
	public function notification_fields() {

		$additional_db_columns = Plugin::instance()->settings->get( 'db_columns' );
		$wc_integration        = Plugin::instance()->settings->get( 'wc_integration' );

		if( $wc_integration ){
			$checkout_fields = Plugin::instance()->wc->get_checkout_fields();
		}

		include JET_APB_PATH . 'templates/admin/form/notification-fields.php';
	}

	/**
	 * Register new dates fields
	 *
	 * @return [type] [description]
	 */
	public function register_form_fields( $fields ) {

		$fields['appointment_date']     = esc_html__( 'Appointment date', 'jet-appointments-booking' );
		$fields['appointment_provider'] = esc_html__( 'Appointment provider', 'jet-appointments-booking' );

		return $fields;

	}

	public function enqueue_deps( $listing_id ) {

		if ( ! $listing_id ) {
			return;
		}

		$document      = \Elementor\Plugin::$instance->documents->get( $listing_id );
		$elements_data = $document->get_elements_raw_data();

		$this->enqueue_elements_deps( $elements_data );

	}

	public function enqueue_elements_deps( $elements_data ) {

		foreach ( $elements_data as $element_data ) {

			if ( 'widget' === $element_data['elType'] ) {

				$widget = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $element_data );

				$widget_script_depends = $widget->get_script_depends();
				$widget_style_depends  = $widget->get_style_depends();

				if ( ! empty( $widget_script_depends ) ) {
					foreach ( $widget_script_depends as $script_handler ) {
						wp_enqueue_script( $script_handler );
					}
				}

				if ( ! empty( $widget_style_depends ) ) {
					foreach ( $widget_style_depends as $style_handler ) {
						wp_enqueue_style( $style_handler );
					}
				}

			} else {

				$element  = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $element_data );
				$children = $element->get_children();

				foreach ( $children as $key => $child ) {
					$children_data[ $key ] = $child->get_raw_data();
					$this->enqueue_elements_deps( $children_data );
				}
			}
		}

	}

	public function calendar_assets() {

		$slot_time_format = Plugin::instance()->settings->get( 'slot_time_format' );

		if ( ! $slot_time_format ) {
			$slot_time_format = 'H:i';
		}

		switch ( $slot_time_format ) {
			case 'g:i a':
				$slot_time_format = 'h:mm a';
				break;

			case 'g:i A':
				$slot_time_format = 'h:mm A';
				break;
			
			case 'G:i':
				$slot_time_format = 'H:mm';
				break;

			default:
				$slot_time_format = 'HH:mm';
				break;
		}

		$utc_offset = get_option('gmt_offset');
		$utc_offset = floatval( $utc_offset );
			
		if ( $utc_offset ) {
			$utc_offset = $utc_offset * HOUR_IN_SECONDS;
		}

		$data = array(
			'api'                 => Plugin::instance()->rest_api->get_urls(),
			'week_days'           => Plugin::instance()->calendar->get_week_days(),
			'start_of_week'       => get_option( 'start_of_week', 1 ),
			'available_week_days' => Plugin::instance()->calendar->get_available_week_days(),
			'time_format'         => $slot_time_format,
			'layout'              => Plugin::instance()->settings->get( 'calendar_layout' ),
			'scroll_to_details'   => Plugin::instance()->settings->get( 'scroll_to_details' ),
			'utc_offset'          => $utc_offset,
			'providers'           => Plugin::instance()->tools->get_posts( 'providers', [
				'post_status'    => 'any',
				'posts_per_page' => -1
			] ),
			'services'           => Plugin::instance()->tools->get_posts( 'services', [
				'post_status'    => 'any',
				'posts_per_page' => -1
			] ),
		);

		$multi_booking = Plugin::instance()->settings->get( 'multi_booking' );

		if ( $multi_booking ) {
			$data['multi_booking'] = [
				'state'    => $multi_booking,
				'min'      => Plugin::instance()->settings->get( 'min_slot_count' ),
				'max'      => Plugin::instance()->settings->get( 'max_slot_count' ),
				'selected' => 0
			];
		}

		$days = array(
			'Sun' => esc_html__( 'Sun', 'jet-appointments-booking' ),
			'Mon' => esc_html__( 'Mon', 'jet-appointments-booking' ),
			'Tue' => esc_html__( 'Tue', 'jet-appointments-booking' ),
			'Wed' => esc_html__( 'Wed', 'jet-appointments-booking' ),
			'Thu' => esc_html__( 'Thu', 'jet-appointments-booking' ),
			'Fri' => esc_html__( 'Fri', 'jet-appointments-booking' ),
			'Sat' => esc_html__( 'Sat', 'jet-appointments-booking' ),
		);

		$months = array(
			'January'   => esc_html__( 'January', 'jet-appointments-booking' ),
			'February'  => esc_html__( 'February', 'jet-appointments-booking' ),
			'March'     => esc_html__( 'March', 'jet-appointments-booking' ),
			'April'     => esc_html__( 'April', 'jet-appointments-booking' ),
			'May'       => esc_html__( 'May', 'jet-appointments-booking' ),
			'June'      => esc_html__( 'June', 'jet-appointments-booking' ),
			'July'      => esc_html__( 'July', 'jet-appointments-booking' ),
			'August'    => esc_html__( 'August', 'jet-appointments-booking' ),
			'September' => esc_html__( 'September', 'jet-appointments-booking' ),
			'October'   => esc_html__( 'October', 'jet-appointments-booking' ),
			'November'  => esc_html__( 'November', 'jet-appointments-booking' ),
			'December'  => esc_html__( 'December', 'jet-appointments-booking' ),
		);

		$use_custom_labels = Plugin::instance()->settings->get( 'use_custom_labels' );

		if ( $use_custom_labels ) {

			$custom_labels = Plugin::instance()->settings->get( 'custom_labels' );

			foreach ( $days as $day => $day_label ) {
				$days[ $day ] = ! empty( $custom_labels[ $day ] )? $custom_labels[ $day ] : $day_label;
			}

			foreach ( $months as $month => $month_label ) {
				$months[ $month ] = ! empty( $custom_labels[ $month ] )? $custom_labels[ $month ] : $month_label;
			}
		}

		$data['months'] = array_values( $months );
		$data['shortWeekday'] = array_values( $days );

		// In blocks themes content rendered before wp_enqueue_scripts
		if ( ! wp_script_is( 'jet-ab-front-init', [ 'registered' ] ) ) {
			Plugin::instance()->register_public_assets();
		}

		wp_localize_script( 'jet-ab-front-init', 'JetAPBData', $data );
		wp_enqueue_script( 'moment' );

		if ( Plugin::instance()->settings->show_timezones() ) {
			wp_enqueue_script( 'jet-ab-choices' );
			wp_enqueue_style( 'jet-ab-choices' );
		}

		
		wp_enqueue_script( 'vanilla-calendar' );
		wp_enqueue_script( 'flatpickr' );
		wp_enqueue_script( 'jet-ab-front-init' );

		wp_enqueue_style( 'jet-ab-front-style' );
		wp_enqueue_style( 'vanilla-calendar' );
		wp_enqueue_style( 'flatpickr' );

		if ( wp_doing_ajax() ) {

			$localized_data   = 'JetAPBData = ' . wp_json_encode( $data ) . ';';
			$vanilla_calendar = add_query_arg(
				array( 'ver' => '4.6.9' ),
				JET_APB_URL . 'assets/js/public/vanilla-calendar.js'
			);

			$flatpickr        = add_query_arg(
				array( 'ver' => '4.6.9' ),
				JET_APB_URL . 'assets/lib/flatpickr/flatpickr.js'
			);
			
			$moment           = add_query_arg(
				array( 'ver' => JET_APB_VERSION ),
				JET_APB_URL . 'assets/lib/moment/moment.min.js'
			);

			$init_script       = add_query_arg(
				array( 'ver' => JET_APB_VERSION ),
				JET_APB_URL . 'assets/js/public/appointments-init.js'
			);

			ob_start();
				include_once JET_APB_PATH . 'assets/js/public/appointments-init.js';
			$init_script = ob_get_clean();
			
			printf( "<script>\n%s\n</script>\n", $localized_data );
			printf( "<script src='%s'></script>\n", $moment );

			if ( Plugin::instance()->settings->show_timezones() ) {
				
				$choices = add_query_arg(
					array( 'ver' => JET_APB_VERSION ),
					JET_APB_URL . 'assets/lib/choices/choices.min.js'
				);

				printf( "<script src='%s'></script>\n", $choices );
				printf( "<script src='%s'></script>\n", $moment_tz );

			}	
			
			printf( "<script src='%s'></script>\n", $vanilla_calendar );
			printf( "<script src='%s'></script>\n", $flatpickr );
			printf( "<script>\n%s\n</script>\n", $init_script );

		}

	}

	/**
	 * Return lift of excluded services
	 * @return [type] [description]
	 */
	public function get_allowed_services( $args ) {

		$provider_cpt = Plugin::instance()->settings->get( 'providers_cpt' );

		if ( ! $provider_cpt ) {
			return false;
		}

		if ( $args['id'] ) {
			return Plugin::instance()->tools->get_services_for_provider( $args['id'] );
		} else {
			return $args['form_field'];
		}
	}

	/**
	 * Returns service field data
	 *
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public function get_service_field_data( $args = array() ) {

		$service_id    = false;
		$service       = false;
		$service_field = ! empty( $args['appointment_service_field'] ) ? $args['appointment_service_field'] : 'current_post_id';

		if ( 'current_post_id' === $service_field ) {
			$service_id = get_the_ID();
			$service    = array(
				'id' => $service_id,
			);
		} elseif ( 'manual_input' === $service_field ) {
			$service_id = ! empty( $args['appointment_service_id'] ) ? absint( $args['appointment_service_id'] ) : false;

			if ( $service_id ) {
				$service = array(
					'id' => $service_id,
				);
			}
		} else {

			$field = ! empty( $args['appointment_form_field'] ) ? $args['appointment_form_field'] : false;

			if ( $field ) {
				$service = array(
					'field' => $field,
				);
			} else {
				$service = false;
			}

		}

		return array(
			'id'         => $service_id,
			'form_field' => $service,
		);

	}

	/**
	 * Return parseed provider data from arguments
	 *
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public function get_provider_field_data( $args = array() ) {

		$provider_cpt   = Plugin::instance()->settings->get( 'providers_cpt' );
		$provider_id    = false;
		$provider       = false;
		$provider_field = ! empty( $args['appointment_provider_field'] ) ? $args['appointment_provider_field'] : '';

		if ( ! $provider_cpt ) {
			return array(
				'is_set'     => false,
				'id'         => false,
				'form_field' => false,
			);
		}

		if ( 'current_post_id' === $provider_field ) {
			$provider_id = get_the_ID();
			$provider    = array(
				'id' => $provider_id,
			);
		} elseif ( 'manual_input' === $provider_field ) {
			$provider_id = ! empty( $args['appointment_provider_id'] ) ? absint( $args['appointment_provider_id'] ) : false;
			if ( $provider_id ) {
				$provider = array(
					'id' => $provider_id,
				);
			}
		} else {

			$field = ! empty( $args['appointment_provider_form_field'] ) ? $args['appointment_provider_form_field'] : false;
			if ( $field ) {
				$provider = array(
					'field' => $field,
				);
			} else {
				$provider = false;
			}

		}

		if ( $provider || $provider_id ) {
			$is_set = true;
		} else {
			$is_set = false;
		}

		return array(
			'is_set'     => $is_set,
			'id'         => $provider_id,
			'form_field' => $provider,
		);

	}

}

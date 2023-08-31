<?php
namespace JET_APB;

/**
 * Database manager class
 */
class Google_Calendar {

	public $query_var = 'jet_apb_add_to_calendar';

	public function __construct() {
		if ( ! empty( $_GET[ $this->query_var ] ) ) {
			add_action( 'init', array( $this, 'redirect_to_calendar' ) );
		}

		add_filter( 'jet-engine/listings/dynamic-link/pre-render-link', [ $this, 'set_html_format_for_render' ], 10, 4 );
		add_action( 'jet-apb/form/notification/success', [ $this, 'set_default_cookie' ], 20 );
		add_action( 'jet-appointment/wc-integration/process-order', [ $this, 'set_wc_cookie' ], 10, 3 );

	}
	
	public function set_html_format_for_render( $pre_render_link, $settings, $base_class, $dynamic_link_class_instance ) {
		
		if ( ! empty( $settings['dynamic_link_source'] ) && $this->query_var === $settings['dynamic_link_source'] ) {
			$output_html = '';
			$cookies = $this->get_id_from_cookies();
			$appointment_data = $this->parse_booking_id( $cookies );
			$appointments = Plugin::instance()->db->get_appointments_by( $appointment_data['key'], $appointment_data['ID'] );

			foreach ( $appointments as $appointment ){
				if( empty( $appointment ) ) {
					continue;
				}
				
				$output_html .= $this->render_link( $appointment, $settings, $base_class, $dynamic_link_class_instance );
			}
			
			return $output_html;
		} else {
			return $pre_render_link;
		}
		
	}
	
	public function render_link( $appointment, $settings, $base_class, $dynamic_link_class_instance ) {
		
		$format = '<a href="%1$s" class="%2$s__link"%5$s%6$s%7$s>%3$s%4$s</a>';
		$url = $this->get_internal_link( $appointment );
		
		$service_title    = get_the_title( $appointment['service'] );
		$provider_title   = ! empty( $appointment['provider'] ) ? ', ' . get_the_title( $appointment['provider'] ) : '' ;
		$time_format      = Plugin::instance()->settings->get( 'slot_time_format' );
		$appointment_date = sprintf(
			'%1$s, %2$s - %3$s',
			date_i18n( get_option( 'date_format' ), $appointment['date'] ),
			date_i18n( $time_format, $appointment['slot'] ),
			date_i18n( $time_format, $appointment['slot_end'] )
		);

		$settings['link_label'] = sprintf( '%1$s %2$s%3$s - %4$s ',
			$settings['link_label'],
			$service_title,
			$provider_title,
			$appointment_date
		);
		$label = $dynamic_link_class_instance->get_link_label( $settings, $base_class, $url );
		$icon  = $dynamic_link_class_instance->get_link_icon( $settings, $base_class );
		
		if ( is_wp_error( $url ) ) {
			echo $url->get_error_message();
			return;
		}
		
		$open_in_new = isset( $settings['open_in_new'] ) ? $settings['open_in_new'] : '';
		$rel_attr    = isset( $settings['rel_attr'] ) ? esc_attr( $settings['rel_attr'] ) : '';
		$rel         = '';
		$target      = '';
		
		if ( $rel_attr ) {
			$rel = sprintf( ' rel="%s"', $rel_attr );
		}
		
		if ( $open_in_new ) {
			$target = ' target="_blank"';
		}
		
		if ( ! empty( $settings['hide_if_empty'] ) && empty( $url ) ) {
			return;
		}
		
		if ( ! empty( $settings['url_prefix'] ) ) {
			$url = esc_attr( $settings['url_prefix'] ) . $url;
		}
		
		return sprintf( $format, $url, $base_class, $icon, $label, $rel, $target, '' );
	}

	public function get_secure_key() {

		$key = get_option( $this->query_var );

		if ( ! $key ) {
			$key = time() % 100000;
			update_option( $this->query_var, $key, false );
		}

		return $key;

	}

	public function set_default_cookie( $appointments ) {
		
		if( empty( $appointments ) ){
			return;
		}
		
		$appointments_id = empty( $appointments[0]['group_ID'] )  ? [ "ID" => $appointments[0]['ID'] ] : [ "group_ID" => $appointments[0]['group_ID'] ];

		if ( ! $appointments_id ) {
			return;
		}

		$expire = time() + YEAR_IN_SECONDS;
		$secure = ( false !== strstr( get_option( 'home' ), 'https:' ) && is_ssl() );
		
		setcookie(
			$this->query_var,
			json_encode( $appointments_id ),
			$expire,
			COOKIEPATH ? COOKIEPATH : '/',
			COOKIE_DOMAIN,
			$secure,
			true
		);

	}
	
	public function set_wc_cookie( $order_id, $order, $cart_item ) {
		
		$data_key     = Plugin::instance()->wc->data_key;
		$booking_data = ! empty( $cart_item[ $data_key ] ) ? $cart_item[ $data_key ] : false;
		
		if ( ! $booking_data ) {
			return;
		}
		
		$this->set_default_cookie( $booking_data );
		
	}
	
	public function get_id_from_cookies() {
		return isset( $_COOKIE[ $this->query_var ] ) ? $_COOKIE[ $this->query_var ] : false;
	}
	
	public function redirect_to_calendar() {
		
		$appointment_id = absint( $_GET[ $this->query_var ] );
		
		if ( ! $appointment_id ) {
			wp_die( __( 'Appointment ID not found in the request', 'jet-appointments-booking' ), __( 'Error', 'jet-appointments-booking' ) );
		}
		
		$appointment_id = $this->get_booking_id_from_secure_id( $appointment_id );
		
		
		if ( ! $appointment_id ) {
			wp_die( __( 'Appointment ID not found in the request', 'jet-appointments-booking' ), __( 'Error', 'jet-appointments-booking' ) );
		}
		
		$appointment = Plugin::instance()->db->get_appointment_by( 'ID', $appointment_id );
		
		if ( ! $appointment ) {
			wp_die( __( 'Appointment not found in the database', 'jet-appointments-booking' ), __( 'Error', 'jet-appointments-booking' ) );
		}
		
		$url = $this->get_calendar_url_by_booking( $appointment );
		
		if ( ! $url ) {
			wp_die( __( 'Can`t build add to calendar URL', 'jet-appointments-booking' ), __( 'Error', 'jet-appointments-booking' ) );
		}
		
		wp_redirect( $url );
		die();
	}
	
	public function parse_booking_id( $data = [] ) {
		if( empty( $data ) ){
			return false;
		}

		$output = [
			'key' => '',
			'ID' => '',
		];

		$data = stripcslashes( $data );
		$data = json_decode( $data, true );
		
		$output['key'] = isset( $data['group_ID'] ) ? 'group_ID' : 'ID' ;
		$output['ID']  = isset( $data[ $output['key'] ] ) ? $data[ $output['key']  ] : false ;
		
		return false === $output['ID'] ? false : $output ;
	}
	
	public function get_calendar_url_by_booking( $appointment ) {
		
		$args = array(
			'action'   => 'TEMPLATE',
			'text'     => '',
			'dates'    => '',
			'details'  => '',
			'location' => get_option( 'blogname' ),
		);
		
		$args['text'] = urlencode( sprintf(
			esc_html__( 'Your appointment at "%1$s" with %2$s - %3$s ', 'jet-appointments-booking' ),
			$args['location'],
			get_the_title( $appointment['service'] ),
			get_the_title( $appointment['provider'] )
		) );
		
		$args['dates'] = sprintf(
			'%1$sT%2$sZ/%1$sT%3$sZ',
			date( 'Ymd', $appointment['date'] ),
			date( 'His', $appointment['slot'] ),
			date( 'His', $appointment['slot_end'] )
		);
		
		$args = apply_filters( 'jet-appointment/google-calendar-url/args', $args, $appointment );
		
		return add_query_arg( array_filter( $args ), 'https://calendar.google.com/calendar/render' );
	}

	public function get_booking_id_from_secure_id( $secure_id ) {
		
		$secure_id  = absint( $secure_id );
		$appointment_id = apply_filters( 'jet-appointment/google-calendar-url/booking-id', false, $secure_id, $this );
		
		if ( ! $appointment_id ) {
			$key        = $this->get_secure_key();
			$appointment_id = $secure_id - absint( $key );
		}

		return $appointment_id;

	}

	public function secure_id( $appointment_id ) {

		$secured_id = apply_filters( 'jet-appointment/google-calendar-url/secure-id', false, $appointment_id, $this );

		if ( ! $secured_id ) {
			$key        = $this->get_secure_key();
			$secured_id = $key + absint( $appointment_id );
		}

		return $secured_id;

	}
	
	public function get_internal_link( $appointment = false ) {
		if ( ! $appointment ) {
			$appointment['ID'] = Plugin::instance()->db->appointments->get_queried_item_id();
		}

		if ( ! isset( $appointment['ID'] ) ) {
			return false;
		}
		
		$appointment_id = $this->secure_id( $appointment['ID'] );
		
		return add_query_arg( array( $this->query_var => $appointment_id ), home_url( '/' ) );
	}

}

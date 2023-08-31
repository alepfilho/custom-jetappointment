<?php
namespace JET_APB\Form_Handlers;

use JET_APB\Plugin;

trait Send_Email_Handler {


	/**
	 * The function processes macros before sending an email.
	 *
	 * @param  [type] $message_content [description]
	 * @param  [type] $class_instant   [description]
	 * @return [type]                  [description]
	 */
	public function parse_message_content( $message_content ) {
		
		$appointments = $this->getAppointments();

		if ( ! isset( $appointments[0] ) ) {
			return $message_content;
		}

		preg_match( 
			'/\%(appointmens_list|appointments_list)\%([\s\S]*)\%(appointmens_list_end|appointmens_list_end)\%/',
			$message_content,
			$appointmens_list_matches 
		);
		
		$appointmens_list_content  = isset( $appointmens_list_matches[2] ) ? $appointmens_list_matches[2] : '';

		if ( ! empty( $appointmens_list_content ) ) {

			$appointmens_list_output_content = '';
			$appointments = Plugin::instance()->db->get_appointments_meta( $appointments );

			foreach ( $appointments as $appointment ) {
				$appointmens_list_output_content .= $this->parse_appointments_macros( $appointmens_list_content, $appointment );
			}

			if ( ! empty( $appointmens_list_output_content ) ) {
				$message_content = str_replace( $appointmens_list_content, $appointmens_list_output_content, $message_content );
			}

		}

		$result = $this->parse_appointments_macros( $message_content, $appointments[0] );

		return $result;
	}

	public function parse_appointments_macros( $message_content, $appointment ) {

		Plugin::instance()->macros->set_macros_object( $appointment );
		return Plugin::instance()->macros->do_macros( $message_content );

		/*return preg_replace_callback( '/\%(([a-zA-Z0-9_-]+)(\|([a-zA-Z0-9\(\)\.\,\:\/\s_-]+))*)\%/', function( $match ) use ( $appointment ) {
			switch ( $match[2] ) {
				case 'service_title':
					$ID = $appointment['service'];

					return get_the_title( $ID );

				case 'service_link':
					$ID = $appointment['service'];

					return get_permalink( $ID );

				case 'provider_title':
					$ID = $appointment['provider'];

					return get_the_title( $ID );

				case 'provider_link':
					$ID = $appointment['provider'];

					return get_permalink( $ID );

				case 'appointment_price':
					return $appointment['price'];

				case 'appointment_start':
				case 'appointment_end':
					$format = ( $match[4] ) ? $match[4] : 'format_date(F j, Y g:i)' ;
					$value  = 'appointment_end' === $match[2] ? $appointment[ 'slot_end' ] : $appointment[ 'slot' ] ;
					$slot   = jet_engine()->listings->filters->apply_filters( $value, $format );

					return $slot;

				case 'appointmens_list':
				case 'appointmens_list_end':
					return '';

				case 'user_local_time':
				case 'user_local_date':
				case 'user_timezone':
					$value = isset( $appointment['meta'][ $match[2] ] ) ? $appointment['meta'][ $match[2] ] : '';
					if ( $value && 'user_timezone' === $match[2] ) {
						$value = str_replace( '_', ' ', $value );
					}
					return $value;

				default:
					return $match[0];

			}
		}, $message_content );*/
	}


}
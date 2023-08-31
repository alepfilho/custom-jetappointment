<?php


namespace JET_APB\Form_Handlers;


use JET_APB\Plugin;

trait Webhook_Handler {

	/**
	 * The function.
	 */
	public function parse_webhook_args( $args, $notification, $instance_notification ) {
		$appointment_field_key = $this->getFieldNameByType( 'appointment_date' );

		if( ! $appointment_field_key || empty( $args["body"][ $appointment_field_key ] ) ){
			return $args;
		}

		$multi_booking = Plugin::instance()->settings->get( 'multi_booking' );
		$appointments = json_decode( stripcslashes( $args["body"][ $appointment_field_key ] ) );

		foreach ( $appointments as $key => $appointment ) {
			$appointment->serviceTitle = get_the_title( $appointment->service );
			$appointment->providerTitle = get_the_title( $appointment->provider );

			$appointments[ $key ] = $appointment;
		}

		$first_appointment = $appointments[ 0 ];

		$args["body"][ $appointment_field_key . '_new' ] = $first_appointment;

		if( $multi_booking ){
			$args["body"][ $appointment_field_key . '_list' ] = $appointments;
		}

		$args["body"][ $appointment_field_key ] =  $first_appointment->date . '|' . $first_appointment->slot . '|' . $first_appointment->slotEnd ;

		return $args;
	}

}
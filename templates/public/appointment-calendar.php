<?php
/**
 * Form appointment calendar
 */
?>
<div class="jet-apb-calendar-wrapper">
	<div class="appointment-calendar jet-apb-calendar" data-args="<?php echo htmlspecialchars( json_encode( $dataset ) ) ?>"></div>

	<?php if( 'recurring' === $dataset['booking_type'] ){  ?>
		<div class="jet-apb-recurrence-app-settings-wrapper" style="display: none" >
			<div class="<?php echo $this->scopeClass( '__heading' ) ?>">
				<span class="<?php echo $this->scopeClass( '__label-text' ) ?>"><?php esc_html_e( 'Repeat appointment:', 'jet-appointments-booking' ); ?></span>
			</div>
			<div class="jet-apb-recurrence-app-settings jet-form-row"></div>
		</div>
	<?php } ?>
	<div class="jet-apb-calendar-appointments-list-wrapper" style="display: none">
		<div class="<?php echo $this->scopeClass( '__heading' ) ?>">
			<span class="<?php echo $this->scopeClass( '__label-text' ) ?>"><?php esc_html_e( 'Appointment details:', 'jet-appointments-booking' ); ?></span>
		</div>
		<div class="jet-apb-calendar-appointments-list"></div>
	</div>
	<div class="jet-apb-calendar-notification" style="display: none">
		<div class="jet-apb-calendar-notification-service"><?php esc_html_e( 'Please, select the service first.', 'jet-appointments-booking' ); ?></div>
		<div class="jet-apb-calendar-notification-provider"><?php esc_html_e( 'Please, select the provider first.', 'jet-appointments-booking' ); ?></div>
		<div class="jet-apb-calendar-notification-service-field"><?php esc_html_e( 'Please set service field for current calendar', 'jet-appointments-booking' ); ?></div>
		<div class="jet-apb-calendar-notification-max-slots"><?php esc_html_e( 'Sorry. You have the max number of appointments.', 'jet-appointments-booking' ); ?></div>
	</div>
</div>

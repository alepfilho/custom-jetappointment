<div>
	<template v-if="'slot' === settings.booking_type">
		<cx-vui-radio
			label="<?php _e( 'Calendar layout', 'jet-appointments-booking' ); ?>"
			description="<?php _e( 'Select layout of calendar for the front-end form', 'jet-appointments-booking' ); ?>"
			:options-list="[
				{
					value: 'default',
					label: 'Default',
				},
				{
					value: 'sidebar_slots',
					label: 'Slots in the sidebar',
				},	
			]"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			:value="settings.calendar_layout"
			@input="updateSetting( $event, 'calendar_layout' )"
		></cx-vui-radio>
		<cx-vui-switcher
			label="<?php _e( 'Scroll to Appointment details after select slot', 'jet-appointments-booking' ); ?>"
			description="<?php _e( 'Automatically scrolls page to Appointment details section after selecting slot in the calendar.', 'jet-appointments-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:value="settings.scroll_to_details"
			@input="updateSetting( $event, 'scroll_to_details' )"
		></cx-vui-switcher>
		<cx-vui-switcher
			label="<?php _e( 'Show timezones picker in calendar', 'jet-appointments-booking' ); ?>"
			description="<?php _e( 'Show timezones picker in appointment calendar. Allows users to show slots in selected timezone time.', 'jet-appointments-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:value="settings.show_timezones"
			@input="updateSetting( $event, 'show_timezones' )"
		></cx-vui-switcher>
	</template>
	<template v-else>
		<cx-vui-component-wrapper
			label="<?php _e( 'Calendar layout', 'jet-appointments-booking' ); ?>"
			description="<?php _e( 'Select layout of calendar for the front-end form', 'jet-appointments-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
		><span style="color:#C92C2C;"><?php 
			printf(
				__( 'Available only for <b>Slot</b> <a href="%s">Schedule Type</a>', 'jet-appointments-booking' ),
				admin_url( 'admin.php?page=jet-dashboard-settings-page&subpage=jet-apb-working-hours-settings' )
			);
		?></span></cx-vui-component-wrapper>
		<cx-vui-component-wrapper
			label="<?php _e( 'Show timezones picker in calendar', 'jet-appointments-booking' ); ?>"
			description="<?php _e( 'Show timezones picker in appointment calendar. Allows users to show slots in selected timezone time.', 'jet-appointments-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
		><span style="color:#C92C2C;"><?php 
			printf(
				__( 'Available only for <b>Slot</b> <a href="%s">Schedule Type</a>', 'jet-appointments-booking' ),
				admin_url( 'admin.php?page=jet-dashboard-settings-page&subpage=jet-apb-working-hours-settings' )
			);
		?></span></cx-vui-component-wrapper>
	</template>
</div>

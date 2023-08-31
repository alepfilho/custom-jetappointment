<div class="jet-apb-integration-component">
	<cx-vui-component-wrapper
		:wrapper-css="[ 'error' ]"
		v-if="! timezoneIsSet"
		label="<?php _e( 'Note!', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'You didn`t set your website timezone settings. To create Zoom Meetings with correct date/time, please set your timezone', 'jet-appointments-booking' ); ?> - <a href='<?php echo admin_url( 'options-general.php#timezone_string' ); ?>' target='_blank'><?php _e( 'here', 'jet-appointments-booking' ); ?></a>"
	/>
	<cx-vui-input
		label="<?php _e( 'Account ID', 'jet-appoinmtents-booking' ) ?>"
		description="<?php _e( 'Account ID field of your Zoom App', 'jet-appoinmtents-booking' ) ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.account_id"
		@input="setData( 'account_id', $event )"
	/>
	<cx-vui-input
		label="<?php _e( 'Client ID', 'jet-appoinmtents-booking' ) ?>"
		description="<?php _e( 'Client ID field of your Zoom App', 'jet-appoinmtents-booking' ) ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.client_id"
		@input="setData( 'client_id', $event )"
	/>
	<cx-vui-input
		label="<?php _e( 'Client secret', 'jet-appoinmtents-booking' ) ?>"
		description="<?php _e( 'Client secret field of your Zoom App', 'jet-appoinmtents-booking' ) ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.client_secret"
		@input="setData( 'client_secret', $event )"
	/>
	<cx-vui-component-wrapper
		label="<?php _e( 'Authenticate', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Get an access token by given credentials', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
	>
		<cx-vui-button
			button-style="accent"
			size="mini"
			:disabled="authDisabled()"
			@click="getToken()"
		>
			<template slot="label"><?php esc_html_e( 'Auth', 'jet-appointments-booking' ); ?></template>
		</cx-vui-button>
		<div
			v-if="tokenMessage"
			:class="{
				'validatation-result': true,
				'validatation-result--success': token,
				'validatation-result--error': ! token,
			}"
		>{{ tokenMessage }}</div>
	</cx-vui-component-wrapper>
	<cx-vui-component-wrapper
		label="<?php _e( 'Where to get these credentials?', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'To get Zoom API credentials you need to create App at Zoom Developer portal. Here you can find detailed insturctions', 'jet-appointments-booking' ); ?> - <a href='https://marketplace.zoom.us/docs/guides/build/server-to-server-oauth-app/#create-a-server-to-server-oauth-app' target='_blank'><?php _e( 'Create a Server-to-Server OAuth app', 'jet-appointments-booking' ); ?></a><br><br><?php _e( 'Also please make sure you enabled <b>meeting:master</b> and <b>meeting:write:admin</b> scopes for your App', 'jet-appoinmtents-booking' ); ?>"
	/>
	<cx-vui-switcher
		label="<?php _e( 'Delete meeting on appointment  cancel', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Delete previously created meeting if apporopriate appointment status was changed on Cancelled, Refunded or Failed', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="settings.delete_on_appointment_cancel"
		@input="setData( 'delete_on_appointment_cancel', $event )"
	></cx-vui-switcher>
</div>
<div>
	<cx-vui-select
		label="<?php _e( 'Availability check by', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Select type of slots availability check - through all services or independent by each service', 'jet-appointments-booking' ); ?>"
		:options-list="[
			{
				value: 'global',
				label: '<?php _e( 'Through all services', 'jet-appointments-boooking' ); ?>',
			},
			{
				value: 'service',
				label: '<?php _e( 'By each service', 'jet-appointments-boooking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.check_by"
		@input="updateSetting( $event, 'check_by' )"
	></cx-vui-select>
	<cx-vui-select
		label="<?php _e( 'How to process \'on-hold\' appointments', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Select the way how \'on-hold\' appointments slots will be handled in the calendar. \'on-hold\' appointments used when you integrate appointments with some payment system from JetFormBuilder or WooCommerce', 'jet-appointments-booking' ); ?>"
		:options-list="[
			{
				value: 'invalid',
				label: '<?php _e( 'Keep `on-hold` slots available', 'jet-appointments-boooking' ); ?>',
			},
			{
				value: 'in_progress',
				label: '<?php _e( 'Exclude `on-hold` slots from calendar', 'jet-appointments-boooking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.process_on_hold"
		@input="updateSetting( $event, 'process_on_hold' )"
	></cx-vui-select>
	<cx-vui-switcher
		label="<?php _e( 'Automatically switch appointments status', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Check this to automatically change status for \'pending\' or \'on hold\' appointments to \'failed\' after selected period of time. This is may be useful if you want automatically make available not confirmed slots.', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="settings.switch_status"
		@input="updateSetting( $event, 'switch_status' )"
	></cx-vui-switcher>
	<cx-vui-select
		label="<?php _e( 'Switch interval', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Select switching appointments time interval', 'jet-appointments-booking' ); ?>"
		v-if="settings.switch_status"
		:options-list="getGlobalConfig( 'switch_intervals', [] )"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.switch_status_period"
		@input="updateSetting( $event, 'switch_status_period' )"
	></cx-vui-select>
	<cx-vui-f-select
		label="<?php _e( 'Switch from', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Find appointments with this status', 'jet-appointments-booking' ); ?>"
		v-if="settings.switch_status"
		:options-list="[
			{
				value: 'on-hold',
				label: '<?php echo \Jet_APB\Plugin::instance()->statuses->get_status_label( 'on-hold' ); ?>',
			},
			{
				value: 'pending',
				label: '<?php echo \Jet_APB\Plugin::instance()->statuses->get_status_label( 'pending' ); ?>',
			},
			{
				value: 'processing',
				label: '<?php echo \Jet_APB\Plugin::instance()->statuses->get_status_label( 'processing' ); ?>',
			},	
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:multiple="true"
		:value="settings.switch_status_from"
		@input="updateSetting( $event, 'switch_status_from' )"
	></cx-vui-f-select>
	<cx-vui-select
		label="<?php _e( 'Switch to', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Switch status to this', 'jet-appointments-booking' ); ?>"
		v-if="settings.switch_status"
		:options-list="[
			{
				value: 'failed',
				label: '<?php echo \Jet_APB\Plugin::instance()->statuses->get_status_label( 'failed' ); ?>',
			},
			{
				value: 'cancelled',
				label: '<?php echo \Jet_APB\Plugin::instance()->statuses->get_status_label( 'cancelled' ); ?>',
			},	
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.switch_status_to"
		@input="updateSetting( $event, 'switch_status_to' )"
	></cx-vui-select>
	<cx-vui-switcher
		label="<?php _e( 'Generate Confirmation URLs', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Generate for each appointments unique URLs to confirm or decline appointment. URLs are stored in the Appointment meta data and can be used inside emails or webhooks.', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="settings.allow_action_links"
		@input="updateSetting( $event, 'allow_action_links' )"
	></cx-vui-switcher>
	<cx-vui-textarea
		label="<?php esc_html_e( 'Confirmed Message', 'jet-appointments-booking' ); ?>"
		description="<?php esc_html_e( 'Message to show on appointment confirmation', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.confirm_action_message"
		v-if="true === settings.allow_action_links"
		@on-input-change="updateSetting( $event.target.value, 'confirm_action_message' )"
	></cx-vui-textarea>
	<cx-vui-textarea
		label="<?php esc_html_e( 'Cancelled Message', 'jet-appointments-booking' ); ?>"
		description="<?php esc_html_e( 'Message to show on appointment cancel', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.cancel_action_message"
		v-if="true === settings.allow_action_links"
		@on-input-change="updateSetting( $event.target.value, 'cancel_action_message' )"
	></cx-vui-textarea>
	<cx-vui-switcher
		label="<?php _e( 'Hide Set Up Wizard', 'jet-appointments-booking' ); ?>"
		description="<?php _e( 'Check this to hide Set Up page to avoid unnecessary plugin resets', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="settings.hide_setup"
		@input="updateSetting( $event, 'hide_setup' )"
	></cx-vui-switcher>
</div>

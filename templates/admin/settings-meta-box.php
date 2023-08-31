<div id='jet-apb-settings-meta-box'>
	<cx-vui-input
			v-if="manage_capacity"
			label="<?php esc_html_e( 'Capacity', 'jet-appointments-booking' ); ?>"
			description="<?php esc_html_e( 'Name: _app_capacity.', 'jet-appointments-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			:size="'fullwidth'"
			type="number"
			:min="0"
			:max="100000"
			:step="1"
			:value="settings.meta_settings._app_capacity"
			@input="updateSetting( $event, '_app_capacity' )"
	></cx-vui-input>
	<cx-vui-radio
		label="<?php esc_html_e( 'Price pre', 'jet-appointments-booking' ); ?>"
		:options-list="settings.price_types"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.meta_settings.price_type"
		@input="updateSetting( $event, 'price_type' )"
	></cx-vui-radio>
	<cx-vui-input
		v-if="settings.meta_settings.price_type === '_app_price'"
		label="<?php esc_html_e( 'Price per slot', 'jet-appointments-booking' ) ?>"
		description="<?php esc_html_e( 'Price for the entire slot time', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		type="number"
		:min="0"
		:max="100000"
		:step="0.1"
		:value="settings.meta_settings._app_price"
		@input="updateSetting( $event, '_app_price' )"
	></cx-vui-input>
	<cx-vui-input
		v-if="settings.meta_settings.price_type === '_app_price_hour'"
		label="<?php esc_html_e( 'Price per hour', 'jet-appointments-booking' ); ?>"
		description="<?php esc_html_e( 'Price for one hour. The price will be rounded up if a new hour has started. For example at 1:15, the price will be calculated as 2 hours', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		type="number"
		:min="0"
		:max="100000"
		:step="0.1"
		:value="settings.meta_settings._app_price_hour"
		@input="updateSetting( $event, '_app_price_hour' )"
	></cx-vui-input>
	<cx-vui-input
		v-if="settings.meta_settings.price_type === '_app_price_minute'"
		label="<?php esc_html_e( 'Price per minute', 'jet-appointments-booking' ); ?>"
		description="<?php esc_html_e( 'Price for one minute.', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		type="number"
		:min="0"
		:max="100000"
		:step="0.1"
		:value="settings.meta_settings._app_price_minute"
		@input="updateSetting( $event, '_app_price_minute' )"
	></cx-vui-input>
</div>

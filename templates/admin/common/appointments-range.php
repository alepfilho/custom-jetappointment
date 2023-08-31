<cx-vui-component-wrapper
	label="<?php esc_html_e( 'When users can book appointments', 'jet-appointments-booking' ); ?>"
	description="<?php esc_html_e( 'Set range of dates when you accept new appointments', 'jet-appointments-booking' ); ?>"
	:wrapper-css="[ 'equalwidth' ]"
>
	<cx-vui-radio
		:prevent-wrap="true"
		:options-list="rangesList()"
		:value="range.type"
		@input="setValue( $event, 'type' )"
	></cx-vui-radio>
	<div
		class="jet-apb-days-range"
		v-if="'range' === range.type"
		style="display: flex; padding: 5px 0 0 0;"
	>
		<cx-vui-input
			:prevent-wrap="true"
			:value="range.range_num"
			@input="setValue( $event, 'range_num' )"
			type="number"
			style="flex: 0 0 100px; width: 100px;"
		></cx-vui-input>
		<cx-vui-select
			:prevent-wrap="true"
			style="margin: 0 10px; width: 100px;"
			:options-list="[
				{
					value: 'days',
					label: '<?php _e( 'Day(s)', 'jet-engine' ); ?>',
				},
				{
					value: 'months',
					label: '<?php _e( 'Month(s)', 'jet-engine' ); ?>',
				},
				{
					value: 'years',
					label: '<?php _e( 'Year(s)', 'jet-engine' ); ?>',
				},
			]"
			:value="range.range_unit"
			@input="setValue( $event, 'range_unit' )"
		></cx-vui-select>
	</div>
</cx-vui-component-wrapper>
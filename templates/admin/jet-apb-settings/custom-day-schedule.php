<div class="cx-vui-component cx-vui-component--vertical-fullwidth jet-apb-day-custom-schedule">
	<div class="cx-vui-component__meta">
		<label class="cx-vui-component__label">Custom Schedule</label> 
		<cx-vui-button
			size="mini"
			button-style="default"
			@click="newDaySlot()"
		><span slot="label">+</span></cx-vui-button>
	</div>
	<div class="cx-vui-component__control">
		<div
			class="jet-apb-custom-day__slot"
			v-for="( daySlot, slotIndex ) in schedule"
		>
			<div class="jet-apb-custom-day__slot-time">
				<cx-vui-time
					format="HH:mm"
					label="<?php esc_html_e( 'From', 'jet-appointments-booking' ); ?>"
					size="fullwidth"
					:prevent-wrap="true"
					:wrapper-css="[ 'vertical-fullwidth' ]"
					:value="schedule[ slotIndex ].from"
					@input="setSchedule( $event, slotIndex, 'from' )"
				></cx-vui-time>
				<span class="jet-apb-custom-day__slot-time-separator">-</span>
				<cx-vui-time
					format="HH:mm"
					label="<?php esc_html_e( 'To', 'jet-appointments-booking' ); ?>"
					size="fullwidth"
					:prevent-wrap="true"
					:wrapper-css="[ 'vertical-fullwidth' ]"
					:value="schedule[ slotIndex ].to"
					@input="setSchedule( $event, slotIndex, 'to' )"
				></cx-vui-time>
			</div>
			<div class="jet-apb-working-hours__slot-actions">
				<div class="jet-apb-week-day__slot-delete" style="position:relative;">
					<span
						class="dashicons dashicons-trash"
						@click="confirmDeleteSlot( slotIndex )"
					></span>
					<div
						class="cx-vui-tooltip"
						v-if="deleteSlotTrigger === slotIndex"
					>
						<?php esc_html_e( 'Are you sure?', 'jet-appointments-booking' ); ?>
						<br> <span
							class="cx-vui-repeater-item__confrim-del"
							@click="deleteSlot( slotIndex )"
						><?php
							esc_html_e( 'Yes', 'jet-appointments-booking' );
						?></span>
						/
						<span
							class="cx-vui-repeater-item__cancel-del"
							@click="deleteSlotTrigger = null"
						><?php
							esc_html_e( 'No', 'jet-appointments-booking' );
						?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="jet-apb-workflow__item">
	<div class="jet-apb-workflow__item-header">
		<div class="jet-apb-workflow__item-remove-wrap">
			<div class="jet-apb-workflow__item-remove" @click="confirmDel = ! confirmDel">
				<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M2.28564 14.192V3.42847H13.7142V14.192C13.7142 14.6685 13.5208 15.0889 13.1339 15.4533C12.747 15.8177 12.3005 15.9999 11.7946 15.9999H4.20529C3.69934 15.9999 3.25291 15.8177 2.866 15.4533C2.4791 15.0889 2.28564 14.6685 2.28564 14.192Z"></path><path d="M14.8571 1.14286V2.28571H1.14282V1.14286H4.57139L5.56085 0H10.4391L11.4285 1.14286H14.8571Z"></path>
				</svg>
			</div>
			<div class="cx-vui-tooltip" v-if="confirmDel">
				<?php _e( 'Are you sure?', 'jet-appointments-booking' ); ?>
				<br>
				<span class="cx-vui-repeater-item__confrim-del" @click="onDelete()"><?php _e( 'Yes', 'jet-appointments-booking' ); ?></span>
				/
				<span class="cx-vui-repeater-item__cancel-del" @click="confirmDel = false"><?php _e( 'No', 'jet-appointments-booking' ); ?></span>
			</div>
		</div>
	</div>
	<cx-vui-select
		label="<?php esc_html_e( 'Event', 'jet-appointments-booking' ); ?>"
		description="<?php esc_html_e( 'Select event to trigger a workflow item', 'jet-appointments-booking' ); ?>"
		:options-list="events"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="item.event"
		@input="updateItem( $event, 'event' )"
	/>
	<?php do_action( 'jet-apb/workflows/event-controls' ); ?>
	<cx-vui-select
		label="<?php esc_html_e( 'Start', 'jet-appointments-booking' ); ?>"
		description="<?php esc_html_e( 'Select when start to run current workflow', 'jet-appointments-booking' ); ?>"
		:options-list="schedule"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="item.schedule"
		@input="updateItem( $event, 'schedule' )"
	/>
	<cx-vui-input
		type="number"
		label="<?php esc_html_e( 'Days before', 'jet-appointments-booking' ); ?>"
		description="<?php esc_html_e( 'Run this item in selected number of days before appointment date.', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		min="1"
		max="15"
		:value="item.days_before"
		v-if="'before_appointment' === item.schedule"
		@input="updateItem( $event, 'days_before' )"
	/>
	<div class="cx-vui-inner-panel">
		<cx-vui-repeater
			button-label="<?php _e( '+ New Action', 'jet-appointments-booking' ); ?>"
			button-style="accent"
			button-size="mini"
			:value="item.actions"
			@input="updateActions( $event )"
			@add-new-item="addNewAction"
		>
			<cx-vui-repeater-item
					v-for="( action, actionIndex ) in item.actions"
					:title="getActionTitle( action )"
					:collapsed="isCollapsed( action )"
					:index="actionIndex"
					@clone-item="cloneAction( $event, actionIndex )"
					@delete-item="deleteAction( $event, actionIndex )"
					:key="action.hash"
			>
				<cx-vui-input
					label="<?php _e( 'Action name', 'jet-appointments-booking' ); ?>"
					description="<?php _e( 'Name of the action to visually identify it in the list', 'jet-appointments-booking' ); ?>"
					:wrapper-css="[ 'equalwidth' ]"
					size="fullwidth"
					:value="item.actions[ actionIndex ].title"
					@on-input-change="setActionProp( actionIndex, 'title', $event.target.value )"
				/>
				<cx-vui-select
					label="<?php esc_html_e( 'Action', 'jet-appointments-booking' ); ?>"
					description="<?php esc_html_e( 'Select action to run', 'jet-appointments-booking' ); ?>"
					:options-list="actions"
					:wrapper-css="[ 'equalwidth' ]"
					size="fullwidth"
					:value="item.actions[ actionIndex ].action_id"
					@input="setActionProp( actionIndex, 'action_id', $event )"
				/>
				<?php do_action( 'jet-apb/workflows/action-controls' ); ?>
			</cx-vui-repeater-item>
		</cx-vui-repeater>
	</div>
</div>
<div>
	<div v-for="( workflow, workflowIndex ) in workflows">
		<cx-vui-switcher
			label="<?php _e( 'Active', 'jet-appointments-booking' ); ?>"
			description="<?php _e( 'Enable/disable current workflow', 'jet-appointments-booking' ); ?>"
			:wrapper-css="[ 'equalwidth' ]"
			v-model="workflows[ workflowIndex ].enabled"
		/>
		<div class="jet-apb-worflows__list">
			<jet-apb-workflow-item
				v-for="( item, itemIndex ) in workflow.items"
				v-model="workflows[ workflowIndex ].items[ itemIndex ]"
				@delete="deleteWorkflowItem( workflowIndex, itemIndex )"
			/>
		</div>
		<div class="jet-apb-worflows__new-item">
			<cx-vui-button
				button-style="accent"
				size="mini"
				@click="newWorkflowItem( workflowIndex )"
			>
				<template slot="label">+ <?php esc_html_e( 'New Workflow Item', 'jet-appointments-booking' ); ?></template>
			</cx-vui-button>
		</div>
		<cx-vui-component-wrapper
			:wrapper-css="[ 'raw-fullwidth' ]"
			label="<?php _e( 'Please note!', 'jet-appointments-booking' ); ?>"
			description="<?php _e( 'New Workflow Item will affect only appointments created <b>after</b> adding this Item.', 'jet-appointments-booking' ); ?>"
		/>		
	</div>
</div>
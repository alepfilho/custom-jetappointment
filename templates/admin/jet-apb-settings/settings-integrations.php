<template>
	<div v-for="( integration, id ) in integrations" class="jet-apb-integration">
		<cx-vui-switcher
			:label="integration.name"
			:description="integration.description"
			:wrapper-css="[ 'equalwidth' ]"
			:value="integration.enabled"
			@input="updateIntegrations( id, 'enabled', $event )"
		/>
		<component
			v-if="integration.component && integration.enabled"
			:is="integration.component"
			v-model="integrations[ id ].data"
		/>
	</div>
</template>
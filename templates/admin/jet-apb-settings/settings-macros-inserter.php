<div
	class="jet-query-macros"
	v-click-outside.capture="onClickOutside"
	v-click-outside:mousedown.capture="onClickOutside"
	v-click-outside:touchstart.capture="onClickOutside"
	@keydown.esc="onClickOutside"
	tabindex="-1"
>
	<div class="jet-query-macros__trigger" @click="switchIsActive()">
		<svg v-if="isActive" class="jet-query-macros__trigger-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M14.95 6.46L11.41 10l3.54 3.54-1.41 1.41L10 11.42l-3.53 3.53-1.42-1.42L8.58 10 5.05 6.47l1.42-1.42L10 8.58l3.54-3.53z"/></g></svg>
		<svg v-else class="jet-query-macros__trigger-open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M14 10c2.2 0 4-1.8 4-4s-1.8-4-4-4-4 1.8-4 4 1.8 4 4 4zm-1-5V3h2v2h2v2h-2v2h-2V7h-2V5h2zM9 6c0-1.6.8-3 2-4h-1c-3.9 0-7 .9-7 2 0 1 2.6 1.8 6 2zm1 9c-3.9 0-7-.9-7-2v3c0 1.1 3.1 2 7 2s7-.9 7-2v-3c0 1.1-3.1 2-7 2zm2.8-4.2c-.9.1-1.9.2-2.8.2-3.9 0-7-.9-7-2v3c0 1.1 3.1 2 7 2s7-.9 7-2v-2c-.9.7-1.9 1-3 1-.4 0-.8-.1-1.2-.2zM10 10h1c-1-.7-1.7-1.8-1.9-3C5.7 6.9 3 6 3 5v3c0 1.1 3.1 2 7 2z"/></g></svg>
	</div>
	<div class="jet-query-macros__popup" v-if="isActive">
		<div class="jet-query-macros__content" v-if="editMacros">
			<div class="jet-query-macros__title">
				<span class="jet-query-macros__back" @click="resetEdit()"><?php _e( 'All Macros', 'jet-engine' ); ?></span> > {{ currentMacros.name }}:
			</div>
			<div class="jet-query-macros__controls">
				<div class="jet-query-macros__control" v-for="control in getPreparedControls()">
					<component
						:is="control.type"
						:options-list="control.optionsList"
						:groups-list="control.groupsList"
						:label="control.label"
						:wrapper-css="[ 'mini-label' ]"
						size="fullwidth"
						v-if="checkCondition( control.condition )"
						:value="getControlValue( control )"
						@input="setMacrosArg( $event, control.name )"
					/>
				</div>
			</div>
			<cx-vui-button
				button-style="accent"
				size="mini"
				@click="applyMacros( false, true )"
			><span slot="label"><?php _e( 'Apply', 'jet-engine' ); ?></span></cx-vui-button>
		</div>
		<div class="jet-query-macros__content" v-else>
			<div class="jet-query-macros__list">
				<div class="jet-query-macros-item" v-for="macros in macrosList">
					<div class="jet-query-macros-item__name" @click="applyMacros( macros )">
						<span class="jet-query-macros-item__mark">≫</span>
						{{ macros.name }}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
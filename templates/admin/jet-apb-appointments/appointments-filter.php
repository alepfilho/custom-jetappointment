<div class="jet-apb-filters">
	<div class="cx-vui-panel">
		<div class="jet-apb-nav-row">
			<div class="jet-apb-nav">
				<cx-vui-button
					@click="setMode( 'all' )"
					:button-style="modeButtonStyle( 'all' )"
					size="mini"
				>
					<template slot="label"><?php esc_html_e( 'All', 'jet-appointments-booking' ); ?></template>
				</cx-vui-button>
				<cx-vui-button
					@click="setMode( 'upcoming' )"
					:button-style="modeButtonStyle( 'upcoming' )"
					size="mini"
				>
					<template slot="label"><?php esc_html_e( 'Upcoming', 'jet-appointments-booking' ); ?></template>
				</cx-vui-button>
				<cx-vui-button
					@click="setMode( 'past' )"
					:button-style="modeButtonStyle( 'past' )"
					size="mini"
				>
					<template slot="label"><?php esc_html_e( 'Past', 'jet-appointments-booking' ); ?></template>
				</cx-vui-button>
			</div>
			<div class="jet-apb-filters-actions">
				<div class="jet-apb-filter-search-wrap">
					<svg class="jet-apb-filter-search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24">
						<path d="M23.822 20.88l-6.353-6.354c.93-1.465 1.467-3.2 1.467-5.059.001-5.219-4.247-9.467-9.468-9.467s-9.468 4.248-9.468 9.468c0 5.221 4.247 9.469 9.468 9.469 1.768 0 3.421-.487 4.839-1.333l6.396 6.396 3.119-3.12zm-20.294-11.412c0-3.273 2.665-5.938 5.939-5.938 3.275 0 5.94 2.664 5.94 5.938 0 3.275-2.665 5.939-5.94 5.939-3.274 0-5.939-2.664-5.939-5.939z"/>
					</svg>
					<cx-vui-input
						key="search"
						:wrapper-css="[ 'jet-apb-filter-search' ]"
						v-model="curentFilters.search"
						type="search"
						@on-keyup.enter="updateFilters( $event, 'search', 'search' )"
					/>
					<cx-vui-button
						class="jet-apb-filter-search-submit"
						@click="updateFilters( curentFilters.search, 'search', 'search' )"
						button-style="accent-border"
						size="mini"
					>
						<svg slot="label" xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 20 20"><rect x="0" fill="none" width="20" height="20"/><g><path d="M16 4h2v9H7v3l-5-4 5-4v3h9V4z"/></g></svg>
					</cx-vui-button>
				</div>
				</cx-vui-input>
				<cx-vui-button
					v-if="! hideFilters"
					class="jet-apb-show-filters"
					@click="expandFilters = ! expandFilters"
					button-style="link-accent"
					size="mini"
				>
					<svg slot="label" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" style="margin:0 5px 0 0;"><path d="M19.479 2l-7.479 12.543v5.924l-1-.6v-5.324l-7.479-12.543h15.958zm3.521-2h-23l9 15.094v5.906l5 3v-8.906l9-15.094z" fill="currentColor"/></svg>
					<span slot="label"><?php esc_html_e( 'Filters', 'jet-appointments-booking' ); ?></span>
				</cx-vui-button>
				<cx-vui-button
					class="jet-apb-show-filters"
					@click="showExportPopup = ! showExportPopup"
					button-style="link-accent"
					size="mini"
				>
					<svg slot="label" width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill-rule="evenodd" clip-rule="evenodd" style="margin:0 5px 0 0;"><path d="M23 0v20h-8v-2h6v-16h-18v16h6v2h-8v-20h22zm-12 13h-4l5-6 5 6h-4v11h-2v-11z" fill="currentColor"/></svg>
					<span slot="label" v-if="curentFilters"><?php
						esc_html_e( 'Export', 'jet-appointments-booking' );
					?></span>
				</cx-vui-button>
			</div>
		</div>
		<div class="jet-apb-filters-row" v-if="expandFilters">
			<template v-for="( filter, name ) in filters">
				<cx-vui-component-wrapper
					v-if="isVisible( name, filter, 'date-picker' )"
					:wrapper-css="[ 'jet-apb-filter' ]"
					:label="filter.label"
				>
					<vuejs-datepicker
						input-class="cx-vui-input size-fullwidth"
						:value="curentFilters[ name ]"
						:format="dateFormat"
						:monday-first="true"
						placeholder="<?php esc_html_e( 'dd/mm/yyyy', 'jet-appointments-booking' ); ?>"
						@input="updateFilters( $event, name, filter.type )"
					></vuejs-datepicker>
					<span
						v-if="curentFilters[ name ]"
						class="jet-apb-date-clear"
						@click="updateFilters( '', name, filter.type )"
					>&times; <?php _e( 'Clear', 'jet-appointments-booking' ); ?></span>
				</cx-vui-component-wrapper>
				<cx-vui-select
					v-else-if="isVisible( name, filter, 'select' )"
					:key="name"
					:label="filter.label"
					:wrapper-css="[ 'jet-apb-filter' ]"
					:options-list="prepareObjectForOptions( filter.value )"
					:value="curentFilters[ name ]"
					@input="updateFilters( $event, name, filter.type )"
				>
				</cx-vui-select>
			</template>
			<cx-vui-button
				v-if="curentFilters"
				class="jet-apb-clear-filters"
				@click="clearFilter()"
				button-style="accent-border"
				size="mini"
			>
				<template slot="label"><?php esc_html_e( 'Clear Filters', 'jet-appointments-booking' ); ?></template>
			</cx-vui-button>
		</div>
	</div>
	<cx-vui-popup
		v-model="showExportPopup"
		:ok-label="'<?php _e( 'Export', 'jet-engine' ) ?>'"
		:cancel-label="'<?php _e( 'Cancel', 'jet-engine' ) ?>'"
		@on-cancel="showExportPopup = false"
		@on-ok="doExport"
	>
		<div class="cx-vui-subtitle" slot="title"><?php
			_e( 'Export appointments', 'jet-engine' );
		?></div>
		<cx-vui-select
			slot="content"
			label="<?php _e( 'Appointments to export', 'jet-appointments-booking' ); ?>"
			description="<?php _e( 'Select what appointments to export - all or currently filtered', 'jet-appointments-booking' ); ?>"
			:options-list="[
				{
					value: 'all',
					label: '<?php _e( 'All appointments', 'jet-appointments-booking' ); ?>'
				},
				{
					value: 'filtered',
					label: '<?php _e( 'Filtered appointments', 'jet-appointments-booking' ); ?>'
				}
			]"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-model="exportType"
		></cx-vui-select>
		<cx-vui-select
			slot="content"
			label="<?php _e( 'Export format', 'jet-appointments-booking' ); ?>"
			description="<?php _e( 'Select format of exported data', 'jet-appointments-booking' ); ?>"
			:options-list="[
				{
					value: 'csv',
					label: '<?php _e( 'CSV', 'jet-appointments-booking' ); ?>'
				},
				{
					value: 'ical',
					label: '<?php _e( 'iCal', 'jet-appointments-booking' ); ?>'
				}
			]"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-model="exportFormat"
		></cx-vui-select>
		<cx-vui-select
			slot="content"
			label="<?php _e( 'Service and Provider returns', 'jet-appointments-booking' ); ?>"
			description="<?php _e( 'What information should be returned in service and provider columns', 'jet-appointments-booking' ); ?>"
			v-if="'csv' === exportFormat"
			:options-list="[
				{
					value: 'id',
					label: '<?php _e( 'ID', 'jet-appointments-booking' ); ?>'
				},
				{
					value: 'title',
					label: '<?php _e( 'Title', 'jet-appointments-booking' ); ?>'
				}
			]"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-model="exportDataReturnType"
		></cx-vui-select>
		<cx-vui-input
			slot="content"
			label="<?php _e( 'Slot date format', 'jet-appointments-booking' ); ?>"
			description="<a href='https://wordpress.org/support/article/formatting-date-and-time/' target='_blank'><?php _e( 'Documentation on date and time formatting', 'jet-appointments-booking' ); ?></a>"
			v-if="'csv' === exportFormat"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-model="exportDateFormat"
		></cx-vui-input>
		<cx-vui-input
			slot="content"
			label="<?php _e( 'Slot time format', 'jet-appointments-booking' ); ?>"
			description="<a href='https://wordpress.org/support/article/formatting-date-and-time/' target='_blank'><?php _e( 'Documentation on date and time formatting', 'jet-appointments-booking' ); ?></a>"
			v-if="'csv' === exportFormat"
			:wrapper-css="[ 'equalwidth' ]"
			size="fullwidth"
			v-model="exportTimeFormat"
		></cx-vui-input>
	</cx-vui-popup>
</div>
(function () {
	
	"use strict";

	Vue.component( 'jet-apb-integrations', {
		template: '#jet-dashboard-jet-apb-integrations',
		data() {
			return {
				integrations: window.JetAPBIntegrationsData.integrations,
				apiPath: window.JetAPBIntegrationsData.api,
			}
		},
		watch: {
			integrations: {
				handler( integrationsList ) {
					wp.apiFetch({
						method: 'POST',
						path: this.apiPath,
						data: {
							integrations: integrationsList,
						},
					}).then( ( response ) => {

						this.$CXNotice.add({
							message: 'Integrations Settings Saved!',
							type: 'success',
							duration: 7000,
						});

					});
				},
				deep: true,
			}
		},
		methods: {
			updateIntegrations( id, key, value ) {
				this.$set( this.integrations[ id ], key, value );
			}
		}
	} );

})();
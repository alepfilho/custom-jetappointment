(function () {
	
	"use strict";

	Vue.component( 'jet-apb-zoom-integration', {
		template: '#jet-dashboard-jet-apb-zoom-integration',
		props: [ 'value' ],
		data() {
			return {
				settings: {},
				apiPath: window.JetAPBZoomData.api,
				timezoneIsSet: window.JetAPBZoomData.timezoneIsSet,
				doingAuth: false,
				tokenMessage: '',
				token: null,
			}
		},
		created() {
			if ( this.value.account_id || this.value.client_id || this.value.client_secret ) {
				this.settings = { ...this.value };
			}
		},
		methods: {
			setData( key, value ) {
				this.$set( this.settings, key, value );
				this.settings[ key ] = value;
				this.$emit( 'input', this.settings );
			},
			authDisabled() {
				return this.doingAuth || ! this.settings.account_id || ! this.settings.client_id || ! this.settings.client_secret;
			},
			getToken() {

				this.doingAuth    = true;
				this.tokenMessage = null;

				wp.apiFetch({
					method: 'POST',
					path: this.apiPath,
					data: {
						settings: this.settings,
					},
				}).then( ( response ) => {

					this.doingAuth = false;
					
					if ( response.success ) {
						this.tokenMessage = response.message;
						this.token        = response.token;
					} else {
						this.tokenMessage = response.message;
						this.token        = null;
					}

				}).catch( ( e ) => {
					this.doingAuth    = false;
					this.token        = null;
					this.tokenMessage = e.message;
				} );

			},
		}
	} );

})();
Vue.component( 'jet-apb-appointments-range', {
	template: '#jet-apb-appointments-range',
	props: [ 'value', 'single-page' ],
	data: function() {
		return {
			range: {
				type: 'all',
				range_num: 60,
				range_unit: 'days',
			},
		};
	},
	created: function() {
		if ( this.value ) {
			this.range = JSON.parse( JSON.stringify( this.value ) );
		} else if ( this.singlePage ) {
			this.$set( this.range, 'type', 'inherit' );
		}
	},
	methods: {
		rangesList: function() {
			const rangesList = [
				{
					value: 'all',
					label: 'Any date in the future',
				},
				{
					value: 'range',
					label: 'Limited range from current date',
				},
			];

			if ( this.singlePage ) {
				rangesList.unshift( {
					value: 'inherit',
					label: 'Inherit from global',
				} );
			}
			
			return rangesList;
			
		},
		setValue( value, key ) {
			
			this.$set( this.range, key, value );

			this.$nextTick( () => {
				this.$emit( 'input', this.range );
			} );

		}
	}
} );
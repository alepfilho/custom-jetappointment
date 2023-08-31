(function () {
	
	"use strict";

	Vue.component( 'jet-apb-macros-inserter', {
		name: 'jet-apb-macros-inserter',
		template: '#jet-dashboard-jet-apb-macros-inserter',
		directives: { clickOutside: window.JetVueUIClickOutside },
		data() {
			return {
				isActive: false,
				macrosList: window.JetAPBWorkflowsData.macros_list,
				currentMacros: {},
				editMacros: false,
				result: {},
				advancedSettings: {},
			};
		},
		methods: {
			applyMacros( macros, force ) {

				force = force || false;

				if ( macros ) {
					this.$set( this.result, 'macros', macros.id );
					this.$set( this.result, 'macrosName', macros.name );

					if ( macros.controls ) {
						this.$set( this.result, 'macrosControls', macros.controls );
					}
				}

				if ( macros && ! force && macros.controls ) {
					this.editMacros = true;
					this.currentMacros = macros;
					return;
				}

				this.$emit( 'input', this.formatResult() );

				this.isActive = false;
				this.result = {};

				this.resetEdit();

			},
			switchIsActive() {

				this.isActive = ! this.isActive;

				if ( this.isActive ) {
					if ( this.result.macros ) {
						for (var i = 0; i < this.macrosList.length; i++) {
							if ( this.result.macros === this.macrosList[ i ].id && this.macrosList[ i ].controls ) {
								this.currentMacros = this.macrosList[ i ];
								this.editMacros = true;
							}
						}
					}
				} else {
					this.resetEdit();
				}

			},
			clearResult() {
				this.result = {};
				this.$emit( 'input', '' );
			},
			formatResult() {

				if ( ! this.result.macros ) {
					return;
				}

				let res = '%';
				res += this.result.macros;

				if ( this.result.macrosControls ) {
					for ( const prop in this.result.macrosControls ) {
						res += '|';

						if ( undefined !== this.result[ prop ] ) {
							res += this.result[ prop ];
						}

					}
				}

				res += '%';

				if ( this.result.advancedSettings && ( this.result.advancedSettings.fallback || this.result.advancedSettings.context ) ) {
					res += JSON.stringify( this.result.advancedSettings );
				}

				return res;

			},
			onClickOutside() {
				this.isActive = false;
				this.editMacros = false;
				this.currentMacros = {};
			},
			resetEdit() {
				this.editMacros = false;
				this.currentMacros = {};
			},
			getPreparedControls() {

				let controls = [];

				for ( const controlID in this.currentMacros.controls ) {

					let control     = this.currentMacros.controls[ controlID ];
					let optionsList = [];
					let type        = control.type;
					let label       = control.label;
					let defaultVal  = control.default;
					let groupsList  = [];
					let condition   = control.condition || {};

					switch ( control.type ) {

						case 'text':
							type = 'cx-vui-input';
							break;

						case 'select':

							type = 'cx-vui-select';

							if ( control.groups ) {

								for ( var i = 0; i < control.groups.length; i++) {

									let group = control.groups[ i ];
									let groupOptions = [];

									for ( const optionValue in group.options ) {
										groupOptions.push( {
											value: optionValue,
											label: group.options[ optionValue ],
										} );
									}

									groupsList.push( {
										label: group.label,
										options: groupOptions,
									} );

								}
							} else {
								for ( const optionValue in control.options ) {
									optionsList.push( {
										value: optionValue,
										label: control.options[ optionValue ],
									} );
								}
							}

							break;
					}

					controls.push( {
						type: type,
						name: controlID,
						label: label,
						default: defaultVal,
						optionsList: optionsList,
						groupsList: groupsList,
						condition: condition,
						value: control.default,
					} );

				}

				return controls;
			},
			setMacrosArg( value, arg ) {
				this.$set( this.result, arg, value );
			},
			getControlValue( control ) {

				if ( this.result[ control.name ] ) {
					return this.result[ control.name ];
				} else if ( control.default ) {
					this.setMacrosArg( control.default, control.name );
					return control.default;
				}

			},
			checkCondition( condition ) {

				let checkResult = true;

				condition = condition || {};

				for ( const [ fieldName, check ] of Object.entries( condition ) ) {
					if ( check && check.length ) {
						if ( ! check.includes( this.result[ fieldName ] ) ) {
							checkResult = false;
						}
					} else {
						if ( check != this.result[ fieldName ] ) {
							checkResult = false;
						}
					}
				}

				return checkResult;

			}
		},
	} );

	Vue.component( 'jet-apb-workflow-item', {
		template: '#jet-dashboard-jet-apb-workflow-item',
		props: [ 'value' ],
		data() {
			return {
				item: {},
				confirmDel: false,
				events: window.JetAPBWorkflowsData.events,
				actions: window.JetAPBWorkflowsData.actions,
				schedule: window.JetAPBWorkflowsData.schedule,
			}
		},
		created() {
			this.item = { ...this.value }

			if ( this.item.actions && this.item.actions.length ) {
				for ( var i = 0; i < this.item.actions.length; i++ ) {
					this.$set( this.item.actions[ i ], 'collapsed', true );
				}
			}
		},
		methods: {
			onDelete() {
				this.$emit( 'delete', this.item );
			},
			updateItem( value, key ) {
				this.$set( this.item, key, value );

				this.$nextTick( () => {
					this.$emit( 'input', this.item );
				} );
			},
			updateActions( actions ) {
				this.item.actions = [ ...actions ];
				this.$emit( 'input', this.item );
			},
			addNewAction() {
				this.item.actions.push( {
					action_id: 'send-email',
					collapsed: false,
				} );
			},
			cloneAction( data, index ) {
				var action = { ...this.item.actions[ index ] };
				this.$set( this.item.actions, this.item.actions.length, action );
			},
			deleteAction( data, index ) {
				this.item.actions.splice( index, 1 );
			},
			addActionMacros( index, prop, value ) {
				let currentVal = this.item.actions[ index ][ prop ];
				
				if ( currentVal ) {
					
					let controlEl = this.$refs[ prop ][0].$el.querySelector( '.cx-vui-textarea, .cx-vui-input' );

					if ( ! controlEl ) {
						currentVal = currentVal + ' ' + value;
					} else {
						let startPos = controlEl.selectionStart;
						let endPos   = controlEl.selectionEnd;

						if ( 0 <= startPos ) {
							currentVal = currentVal.substring( 0, startPos ) + value + currentVal.substring( endPos, currentVal.length );
						} else {
							currentVal = currentVal + ' ' + value;
						}

					}
					
				} else {
					currentVal = value;
				}

				this.setActionProp( index, prop, currentVal );
			},
			setActionProp( index, prop, value ) {
				this.$set( this.item.actions[ index ], prop, value );
			},
			isCollapsed( object ) {
				if ( undefined === object.collapsed || true === object.collapsed ) {
					return true;
				} else {
					return false;
				}
			},
			getActionTitle( action ) {
				if ( action.title ) {
					return action.title;
				} else {
					
					for ( var i = 0; i < this.actions.length; i++ ) {
						if ( action.action_id === this.actions[ i ].value ) {
							return this.actions[ i ].label;
						}
					}

					return action.action_id;
				}
			},
		}
	} );

	Vue.component( 'jet-apb-workflows', {
		template: '#jet-dashboard-jet-apb-workflows',
		data() {
			return {
				workflows: window.JetAPBWorkflowsData.workflows,
				apiPath: window.JetAPBWorkflowsData.api.update_workflows,
			}
		},
		watch: {
			workflows: {
				handler( workflowsList ) {
					wp.apiFetch({
						method: 'POST',
						path: this.apiPath,
						data: {
							workflows: workflowsList,
						},
					}).then( ( response ) => {

						this.$CXNotice.add({
							message: 'Workflows Saved!',
							type: 'success',
							duration: 7000,
						});

					});
				},
				deep: true,
			}
		},
		methods: {
			deleteWorkflowItem( workflowIndex, itemIndex ) {
				this.workflows[ workflowIndex ].items.splice( itemIndex, 1 );
			},
			newWorkflowItem( workflowIndex ) {
				this.workflows[ workflowIndex ].items.push( {
					event: '',
					schedule: 'immediately',
					actions: [],
					hash: Math.round( Math.random() * ( 999999 - 100000 ) + 100000 ),
				} );
			}
		}
	} );

})();
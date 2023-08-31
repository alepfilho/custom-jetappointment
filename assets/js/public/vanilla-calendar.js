/*
	Vanilla AutoComplete v0.1
	Copyright (c) 2019 Mauro Marssola
	GitHub: https://github.com/marssola/jet-apb-calendar
	License: http://www.opensource.org/licenses/mit-license.php
*/
var VanillaCalendar = (function () {

	"use strict";

	function VanillaCalendar( options ) {
		let xhr              = null,
			initialized      = false,
			instance         = null,
			instanceContent  = null,
			instanceSidebar  = null,
			instanceSlots    = null, // used only if we has global slots wrapper in sidebar
			instanceInput    = null,
			serviceID        = null,
			serviceField     = null,
			providerID       = null,
			providerField    = null,
			multiBooking     = false,
			notification     = null,
			notificationHTML = '',
			appListWrapper   = null,
			recurrenceSetingsWrapper = null,
			recurringSwitcherEl = null,
			currentTimeZone = null,
			form = null,
			order = [],
			opts = {
				selector: null,
				pastDates: true,
				availableWeekDays: [],
				excludedDates: [],
				datesMode: 'override_full',
				worksDates: [],
				date: new Date(),
				today: null,
				layout: 'default',
				scrollToDetails: false,
				button_prev: null,
				button_next: null,
				month: null,
				month_label: null,
				weekDays: [],
				weekStart: 0,
				service: 0,
				provider: 0,
				providerIsset: false,
				api: '',
				inputName: '',
				isRequired: false,
				allowedServices: false,
				services: false,
				providers: false,
				onSelect: function( data, elem ) {},
				months: [],
				shortWeekday: [],
				namespace: '',
				selectSlot: false,
				bookingType: 'slot',
				timeFormat: 'HH:mm',
				UTCOffset: 0,
			};

		if ( window.sessionStorage ) {
			currentTimeZone = window.sessionStorage.getItem( 'jet-apb-timezone' );
		}

		for ( var k in options ) {
			if ( opts.hasOwnProperty( k ) ) {
				opts[ k ] = options[ k ];
			}
		}

		opts.today = Date.UTC( opts.date.getFullYear(), opts.date.getMonth(), opts.date.getDate(), 0, 0, 0 ) / 1000;
		opts.weekStart = parseInt( opts.weekStart, 10 );

		instance = document.querySelector( opts.selector );

		if ( ! instance ) {
			return;
		}
		
		if ( instance.dataset.calendarInitialized ) {
			return;
		}
		
		instance.dataset.calendarInitialized = true;

		const setNotification = function( inst, html = '' ) {

			if ( window.jetAppNotificationInstance ) {
				return window.jetAppNotificationInstance.outerHTML;
			}
			
			if ( ! inst ) {
				return;
			}

			let parent               = inst.parentElement,
				notificationInstance = parent.querySelector( '.jet-apb-calendar-notification' ),
				output               = notificationInstance ? notificationInstance.outerHTML : html;

			if ( notificationInstance ) {
				window.jetAppNotificationInstance = notificationInstance.cloneNode( true );
			} else {
				return output;
			}

			notificationInstance.remove();

			return output;
		};


		const addEvent = function( el, type, handler, trigger = false ) {

			if ( ! el ) {
				return;
			}
			type = ( el.attachEvent ) ? 'on' + type : type ;

			const event = new Event( type );
			el[( el.attachEvent ) ? 'attachEvent' : 'addEventListener' ]( type, handler );

			if( trigger ){
				el.dispatchEvent(event);
			}

		};

		const removeEvent = function( el, type, handler ){

			if ( ! el ) {
				return;
			}

			if ( el.detachEvent ) {
				el.detachEvent( 'on' + type, handler );
			} else {
				el.removeEventListener( type, handler );
			}
		};

		const getWeekDay = function ( day ) {
			return opts.weekDays[ day ];
		};

		const adjustWeekDay = function( day ) {

			day = day - opts.weekStart;

			if ( 0 > day ) {
				return day + 7;
			} else {
				return day;
			}

		};

		const setDayAvailability = function( el, timestamp, weekDay ) {
			timestamp = timestamp || parseInt( el.dataset.calendarDate, 10 );

			let isAvailable = isAvailableDay(
				{
					date: timestamp,
					worksDates: opts.worksDates,
					datesMode: opts.datesMode,
					offDates: opts.excludedDates,
					offWeekDays: opts.availableWeekDays,
					weekDay: weekDay || el.dataset.weekDay
				}
			);

			el.classList.remove( 'jet-apb-calendar-date--disabled' );

			if ( timestamp <= opts.today - 1 && ! opts.pastDates ) {
				el.classList.add( 'jet-apb-calendar-date--disabled' );
			} else {

				if ( ! isAvailable ) {
					el.classList.add( 'jet-apb-calendar-date--disabled' );
				}

				el.setAttribute( 'data-status', isAvailable );

			}

		};

		const isAvailableDay = function( args = {} ) {
			let {
					worksDates,
					offDates,
					datesMode,
					date,
					offWeekDays,
					weekDay
				} = args,
				isAvailable = true;

			if ( offDates[0] ) {
				for ( let dates in offDates ) {

					if ( date >= offDates[dates].start && date <= offDates[dates].end ) {
						
						// Check specific day off for the service
						if ( offDates[dates].service && parseInt( serviceID ) === offDates[dates].service ) {
							isAvailable = false;
							// immidiately break if is off day
							return isAvailable;
						}

						// Check general day off
						if ( ! offDates[dates].service ) {
							isAvailable = false;
							// immidiately break if is off day
							return isAvailable;
						}

						break;
					}
				}
			}

			if ( worksDates[0] ) {
				for ( let dates in worksDates ) {
					if ( date >= worksDates[dates].start && date <= worksDates[dates].end ){
						isAvailable = true;
						// immideately break if is work day
						return isAvailable;
					} else if ( 'override_days' !== datesMode ) {
						isAvailable = false;
					}
				}
			}

			if ( ! weekDay ) {
				weekDay = getWeekDay( new Date( date * 1000 ).getUTCDay() );
			}

			if ( ! weekDay || ( 0 > offWeekDays.indexOf( weekDay ) ) ) {
				isAvailable = false;
			}

			return isAvailable;
		}


		const createDay = function( date ) {

			var newDayElem     = document.createElement( 'div' );
			var newDayBody     = document.createElement( 'div' );
			var weekDayNum     = adjustWeekDay( date.getDay() );
			var currentWeekDay = getWeekDay( date.getDay() );
			var timestamp      = Date.UTC( date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0 );

			timestamp = timestamp / 1000;

			newDayElem.className = 'jet-apb-calendar-date';

			if ( date.getDate() === 1 ) {
				newDayElem.style.marginLeft = ( weekDayNum * 14.28 ) + '%';
			}

			setDayAvailability( newDayElem, timestamp, currentWeekDay );

			newDayElem.setAttribute( 'data-week-day', currentWeekDay );
			newDayElem.setAttribute( 'data-calendar-date', timestamp );

			if ( timestamp === opts.today ) {
				newDayElem.classList.add( 'jet-apb-calendar-date--today' );
			}

			newDayBody.innerHTML = date.getDate();
			newDayBody.className = 'jet-apb-calendar-date-body';

			newDayElem.appendChild( newDayBody );
			opts.month.appendChild( newDayElem );

			if ( 6 === weekDayNum && 'default' === opts.layout ) {
				opts.month.appendChild( getNewSlotsWrapper() );
			}

		};

		const getNewSlotsWrapper = function() {

			var slotsEl = document.createElement( 'div' );

			slotsEl.className = 'jet-apb-calendar-slots';

			return slotsEl;

		};

		const removeActiveClass = function() {

			instance.querySelectorAll( '.jet-apb-calendar-date--selected' ).forEach( function( el ) {
				el.classList.remove( 'jet-apb-calendar-date--selected' );
			} );

			instance.querySelectorAll( '.jet-apb-calendar-slots' ).forEach( function( el ) {
				el.classList.remove( 'jet-apb-calendar-slots--active' );
				el.innerHTML = '';
			} );

			if( ! multiBooking ){
				opts.selectSlot = false;
				instanceInput.val( '' ).data( 'price', 0 ).trigger( 'change' );
				updateAppointmentList()
			}

			if ( 'recurring' === opts.bookingType ) {
				opts.recurringSettings.recurrenceApp = false;
				recurrenceSetingsWrapper.style.display = 'none' ;
			}
		};

		const selectDate = function( el ) {

			alert('aqquiiii');

			removeActiveClass();
			el.classList.add( 'jet-apb-calendar-date--selected' );

			var slot     = getNextSlot( el ),
				service  = null,
				provider = null,
				datenow  = new Date();

			if ( ! slot ) {
				return;
			}

			slot.classList.add( 'jet-apb-calendar-slots--loading' );
			instance.classList.add( 'jet-apb-calendar--loading' );

			if ( xhr ) {
				xhr.abort();
			}
			if ( opts.service.id ) {
				service = opts.service.id;
			} else if( opts.service.field ) {
				serviceField = document.querySelectorAll( 'input[name="' + opts.service.field + '"]' );

				if ( 1 === serviceField.length ) {
					if ( serviceField[0].value ) {
						serviceID = serviceField[0].value;
					}
				} else if ( 1 < serviceField.length ) {
					for ( let i = 0; i < serviceField.length; i++ ) {
						if ( serviceField[ i ].checked ) {
							serviceID = serviceField[ i ].value;
						}
					};
				}

				service = serviceID;
			} else {
				service = serviceID;
			}

			if ( opts.provider.id ) {
				provider = opts.provider.id;
			} else {
				provider = providerID;
			}

			if ( ! service ) {
				showNotification( 'notification-service' );

				slot.classList.remove( 'jet-apb-calendar-slots--loading' );
				instance.classList.remove( 'jet-apb-calendar--loading' );
				return;
			}

			if ( opts.provider.field && ! providerID ) {

				if ( ! window.elementorFrontend || ! window.elementorFrontend.isEditMode() ) {
					showNotification( 'notification-provider' );
					return;
				}

				slot.classList.remove( 'jet-apb-calendar-slots--loading' );
				instance.classList.remove( 'jet-apb-calendar--loading' );

				if ( ! window.elementorFrontend || ! window.elementorFrontend.isEditMode() ) {
					return;
				}

			}

			xhr = jQuery.ajax({
				url: opts.api.date_slots,
				type: 'POST',
				dataType: 'json',
				data: {
					service: service,
					provider: provider,
					date: el.dataset.calendarDate,
					timezone: currentTimeZone,
					selected_slots: multiBooking ? instanceInput.val() : '',
					timestamp: Math.floor( ( datenow.getTime() - datenow.getTimezoneOffset() * 60 * 1000 ) / 1000 ),
				},
			}).done( function( response ) {
				xhr = false;

				slot.classList.remove( 'jet-apb-calendar-slots--loading' );
				slot.classList.add( 'jet-apb-calendar-slots--active' );

				if( response ){

					opts.bookingType = response.data.booking_type;
					slot.classList.add( 'jet-apb-calendar-type-' + opts.bookingType );

					switch ( opts.bookingType ) {
						case 'range':
							multiBooking = false ;
							setRange( slot, response.data, instance );
						break;

						case 'recurring':
							multiBooking = false ;

							if( response.data.settings ){
								opts.recurringSettings = response.data.settings;
								opts.recurringSettings.multiBooking = {
									max: parseInt( response.data.settings.max_recurring_count ),
									min: parseInt( response.data.settings.min_recurring_count ),
									selected: 1,
								} ;
							}

							setRecurring( slot, response.data, instance );
						break;

						default:
							multiBooking = options.multiBooking ? options.multiBooking : false ;
							setSlots( slot, response.data, instance );
						break;
					}
				}

				instance.classList.remove( 'jet-apb-calendar--loading' );
			} );

		};

		const fragmentFromString = function( strHTML ) {
			return document.createRange().createContextualFragment( strHTML );
		}

		const showNotification = function( notificationClass = '' ) {

			if( ! notificationClass ){
				return;
			}

			notification.classList.add( notificationClass );
			notification.style.display = 'flex';
			setTimeout(function(){
				notification.classList.remove( notificationClass );
				notification.style.display = 'none';
			}, 2000);
		}

		const dateToUTCDate = function( date ) {

			if( typeof date !== 'object'){
				return !1;
			}

			return Date.UTC( date.getFullYear(), date.getMonth(), date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds() );
		}

		const setRange = function( slotsWrapper, data, inst ) {
			slotsWrapper.innerHTML = data.slots;
			initTimezonesPicker( slotsWrapper );

			let timeInput = slotsWrapper.getElementsByClassName( 'jet-apb-time-picker-input' ),
				startTimeInput = slotsWrapper.getElementsByClassName( 'jet-apb-time-picker-input-start' ),
				endTimeInput = slotsWrapper.getElementsByClassName( 'jet-apb-time-picker-input-end' ),
				config = {};

			if( ! timeInput[0] ){
				return;
			}

			config             = JSON.parse( timeInput[0].dataset.config );
			config.defaultDate = new Date( config.defaultDate );
			config.minuteIncrement = parseInt( config.minuteIncrement ) / 60 ;

			config.onClose = function( selectedDates, dateStr, instance ){
				let {
						type,
						price,
						priceType
					} = instance.element.dataset,
					time24hr   = instance.element.dataset.time24hr === 'true' ? false : true ,
					timeRange,
					slot,
					slotEnd,
					friendlySlot,
					friendlySlotEnd,
					duration;

				if ( 'start' === type ) {
					timeRange = calcTimeRange(
						instance.config,
						type,
						selectedDates[0],
						endTimeInput[0] ? endTimeInput[0]._flatpickr.selectedDates[0] : false
					);

					if( endTimeInput[0] ){
						endTimeInput[0]._flatpickr.setDate( timeRange.slotEnd, true);
					}
				} else if ( 'end' === type ) {
					timeRange = calcTimeRange(
						instance.config,
						type,
						startTimeInput[0]._flatpickr.selectedDates[0],
						selectedDates[0]
					);
					startTimeInput[0]._flatpickr.setDate( timeRange.slot, true);
				}

				slot            = dateToUTCDate( timeRange.slot );
				slotEnd         = dateToUTCDate( timeRange.slotEnd );
				friendlySlot    = timeFormat( slot, time24hr );
				friendlySlotEnd = timeFormat( slotEnd, time24hr );
				price           = calcPrice({
					price: price,
					priceType: priceType,
					duration: timeRange.duration,
				});

				setValue( {
					...instance.element.dataset,
					slot: slot / 1000,
					slotEnd: slotEnd / 1000,
					friendlyTime: `${friendlySlot} - ${ friendlySlotEnd }`,
					price: price
				} );
			}

			flatpickr( timeInput, config );

			if ( endTimeInput[0] ) {
				endTimeInput[0]._flatpickr.set( 'minTime', endTimeInput[0]._flatpickr.config.endMinTime );
				endTimeInput[0]._flatpickr.set( 'maxTime', endTimeInput[0]._flatpickr.config.endMaxTime );
				endTimeInput[0]._flatpickr.setDate( new Date( endTimeInput[0].dataset.endTime ), true );
			}
		}

		const timeFormat = function ( date, hour12 ){
			return new Date( date ).toLocaleTimeString( false, { hour12: hour12, hour: '2-digit', minute: '2-digit', timeZone: 'UTC' });
		}

		const calcPrice = function( args = {} ) {
			let {
				price,
				priceType,
				duration
			} = args;

			switch ( priceType ) {
				case '_app_price_hour':
					let hours = Math.ceil( duration / 60 / 60 );

					price = price * hours;
				break;

				case '_app_price_minute':
					let minutes = Math.ceil( duration / 60 );

					price = price * minutes;
				break;
			}

			return price;
		}

		const calcTimeRange = function( config = {}, type = 'start', startDate = false, endDate = false, ) {

			if ( startDate && startDate.toDateString() !== config.defaultDate.toDateString() ) {
				startDate.setFullYear( config.defaultDate.getFullYear() );
				startDate.setMonth( config.defaultDate.getMonth() );
				startDate.setDate( config.defaultDate.getDate() );
			}

			if ( endDate && endDate.toDateString() !== config.defaultDate.toDateString() ) {
				endDate.setFullYear( config.defaultDate.getFullYear() );
				endDate.setMonth( config.defaultDate.getMonth() );
				endDate.setDate( config.defaultDate.getDate() );
			}

			let output = {
					slot: startDate.getTime() / 1000 ,
					slotEnd: ! endDate ? parseInt( config.duration ) : endDate.getTime() / 1000,
					duration: 0
				},
				duration    = config.minDuration ? parseInt( config.minDuration ) : parseInt( config.duration ),
				maxDuration = parseInt( config.maxDuration );

			if( type === 'start' ) {
				if( ! endDate || output.slotEnd - output.slot < duration ){
					output.slotEnd = output.slot + duration;
				} else if( output.slotEnd - output.slot > maxDuration ) {
					output.slotEnd = output.slot + maxDuration;
				}
			} else {
				if( output.slotEnd - output.slot < duration ){
					output.slot = output.slotEnd - duration;
				} else if( output.slotEnd - output.slot > maxDuration ) {
					output.slot = output.slotEnd - maxDuration;
				}
			}

			output.duration = output.slotEnd - output.slot;
			output.slot     = new Date( output.slot * 1000 );
			output.slotEnd  = new Date( output.slotEnd * 1000 );

			return output;
		}

		const initTimezonesPicker = function( slotsWrapper ) {
		
			const timezonesControl = slotsWrapper.querySelector( 'select[name="timezone_picker"]' );
			
			if ( timezonesControl ) {
				
				new Choices( timezonesControl, {
					itemSelectText: '',
				} );
				
				timezonesControl.addEventListener( 'change', function( $event ) {
					
					currentTimeZone = $event.detail.value;

					if ( window.sessionStorage ) {
						window.sessionStorage.setItem( 'jet-apb-timezone', currentTimeZone );
					}

					const currentDay = instance.querySelector( '.jet-apb-calendar-date--selected' );

					if ( currentDay ) {
						selectDate( currentDay );
					}

				} );

			}

		}

		const setRecurring = function( slotsWrapper, data, inst ) {
			let { slots, recurrence_settings_html } = data;

			slotsWrapper.innerHTML = slots;
			initTimezonesPicker( slotsWrapper );

			if( ! recurrence_settings_html){
				return;
			}

			recurrenceSetingsWrapper.querySelector( '.jet-apb-recurrence-app-settings' ).innerHTML = recurrence_settings_html;

			let recurrenceCountNode = recurrenceSetingsWrapper.querySelector( '.jet-apb__recurrence-count' ),
				recurrenceTypeNode = recurrenceSetingsWrapper.querySelector( '.jet-apb__recurrence-type' ),
				weekDayNode = recurrenceSetingsWrapper.querySelector( '.jet-apb__week-days' );

			opts.recurringSettings.recurrenceType = recurrenceTypeNode.value;

			addEvent( slotsWrapper.querySelector( '.jet-apb-switcher__input' ), 'change', showHideRecurrenceSetings );
			addEvent( recurrenceTypeNode, 'change', changeRecurrenceType, true );
			addEvent( recurrenceCountNode, 'change', changeRecurrenceCount );
			addEvent( weekDayNode, 'change', setWeekDay );
			addEvent( document, 'click', slotAdd );
		}

		const showHideRecurrenceSetings = function( e ) {
			opts.recurringSettings.recurrenceApp = e.target.checked;
			recurrenceSetingsWrapper.style.display = opts.recurringSettings.recurrenceApp ? 'block' : 'none' ;

			updateRecurrenceApp();
		}

		const changeRecurrenceType = function( e ) {
			let optionsField = recurrenceSetingsWrapper.querySelector( '.jet-apb__optionality-field' );
			opts.recurringSettings.recurrenceType = e.target.value;

			if( optionsField ){
				optionsField.style.display = 'none';
			}

			switch ( opts.recurringSettings.recurrenceType ) {
				case 'week':
					recurrenceSetingsWrapper.querySelector( '.jet-apb__week-days' ).style.display = 'block';
				break;
			}

			updateRecurrenceApp();
		}

		const changeRecurrenceCount = function( e ) {
			let max   = parseInt( e.target.max ),
				min   = parseInt( e.target.min ),
				value = parseInt( e.target.value );

			if( value < min ){
				e.target.value = min;
			} else if ( value > max ){
				e.target.value = max;
			}

			updateRecurrenceApp();
		}

		const setWeekDay = function( e ) {
			let order = opts.recurringSettings.week_day_order[ e.target.value ],
				inArrayIndex = opts.recurringSettings.week_day_checked.indexOf( order );

			if ( e.target.checked && inArrayIndex === -1) {
				opts.recurringSettings.week_day_checked.push( order );
			}else{
				opts.recurringSettings.week_day_checked.splice(inArrayIndex, 1);
			}

			opts.recurringSettings.week_day_checked = opts.recurringSettings.week_day_checked.sort();

			updateRecurrenceApp();
		}

		const updateRecurrenceApp = function() {
			let count = recurrenceSetingsWrapper.querySelector( '.jet-apb__recurrence-count' ).value,
				selectApp = ! opts.selectSlot ? opts.selectSlot : { ...opts.selectSlot.dataset },
				settings = opts.recurringSettings,
				type = settings.recurrenceType,
				weekOffset = false;

			if ( ! settings.recurrenceApp || isNaN( count ) || ! selectApp ) {
				return;
			}

			instanceInput.val( JSON.stringify( [] ) );
			settings.multiBooking.selected = 0;

			for ( let countUp = 0; countUp < count; countUp++ ) {
				
				let date = false,
					slot, slotEnd, isAvailable = true;


				

				switch ( true ) {
					case 'day' === type:
						date    = addDays( selectApp.date, countUp );
						slot    = addDays( selectApp.slot, countUp );
						slotEnd = addDays( selectApp.slotEnd, countUp );
					break;

					case 'week' === type:
						if ( settings.week_day_checked[0] ) {
							
							weekOffset = getWeekDaysOffset( selectApp.date, settings.week_day_checked, count );

							date    = addDays( selectApp.date, weekOffset[ countUp ] );
							slot    = addDays( selectApp.slot, weekOffset[ countUp ] );
							slotEnd = addDays( selectApp.slotEnd, weekOffset[ countUp ] );

						} else {
							updateAppointmentList();
						}
					break;
					case 'month' === type:
						date    = addMonth( selectApp.date, countUp );
						slot    = addMonth( selectApp.slot, countUp );
						slotEnd = addMonth( selectApp.slotEnd, countUp );
					break;
					case 'year' === type:
						date    = setYear( selectApp.date, countUp );
						slot    = setYear( selectApp.slot, countUp );
						slotEnd = setYear( selectApp.slotEnd, countUp );
					break;
				}

				

				if ( date ) {

					isAvailable = isAvailableDay(
						{
							date: date,
							datesMode: opts.datesMode,
							worksDates: opts.worksDates,
							offDates: opts.excludedDates,
							offWeekDays: opts.availableWeekDays
						}
					);

					if ( isAvailable ) {

						setValue(
							{
								provider: selectApp.provider,
								service: selectApp.service,
								price: selectApp.price,
								date: date,
								slot: slot,
								slotEnd: slotEnd,
								friendlyDate: timestampToDate( date, settings.date_format ),
								friendlyTime: `${ timestampToDate( slot, settings.time_format ) } - ${ timestampToDate( slotEnd, settings.time_format ) }`,
							},
							settings.multiBooking,
							'add'
						);
					}
				}
			}
		}

		const isCurrentInstanceEvent = function( event ) {
			return instance.contains( event.target );
		}

		const addDays = function( date, days ) {
			let output = new Date( date * 1000 );
			output.setUTCDate( output.getUTCDate() + days );
			return output / 1000;
		}

		const addMonth = function( date, months ) {
			let output = new Date( date * 1000 );
			output.setUTCMonth( output.getUTCMonth() + months );

			return output / 1000;
		}

		const setYear = function( date, years ) {
			let output = new Date( date * 1000 );
			output.setUTCFullYear( output.getUTCFullYear() + years );
			return output / 1000;
		}

		const getWeekDaysOffset = function( date, weekDays, count = 0 ) {
			let startDate = moment( ( parseInt( date, 10 ) - parseInt( opts.UTCOffset, 10 ) ) * 1000 ).days(),
				output = [],
				offset;

			for ( let weekDay of weekDays ){
				if( weekDay < startDate ){
					offset = 7 - startDate + weekDay ;
				} else if( weekDay > startDate) {
					offset = weekDay - startDate;
				} else {
					offset = 0
				}
				output.push( offset );
			}

			output = output.sort();

			for ( let i = 0; i < count; i++ ) {
				output.push( output[i] + 7 );
			}

			if( output.length > count ){
				output = output.slice( 0, count );
			}

			return output;
		}

		const timestampToDate = function( timestamp, format ) {
			return moment.unix( timestamp ).utc().format( format );
		}

		const setSlots = function( slotsWrapper, data, inst ) {
			let slotsEvent,
				{ slots } = data;

			slots = fragmentFromString( slots );

			slotsWrapper.appendChild( slots );
			initTimezonesPicker( slotsWrapper );

			slotsEvent = new CustomEvent( 'jet-apb-calendar-slots--loaded', { el: slotsWrapper, slotHtml: slots } );

			window.dispatchEvent( slotsEvent );

			addEvent( document, 'click', slotAdd );

			/*if ( ! isElementInViewport( slotsWrapper ) ) {
				window.scrollTo( {
					top: slotsWrapper.getBoundingClientRect().top,
					behavior: 'smooth',
				} );
			};*/
		
		};

		const slotAdd = function( event ) {
			if ( ! event.target.matches( '.jet-apb-slot' ) ) {
				return;
			}

			opts.selectSlot = event.target;

			if( multiBooking ){
				if( event.target.classList.contains( 'jet-apb-slot--selected' ) ){
					if( multiBooking.selected >= 1 ) {
						setValue(event.target.dataset, multiBooking, 'remove');
						event.target.classList.remove('jet-apb-slot--selected');
					}
				}else{
					if( multiBooking.selected < multiBooking.max ){
						setValue( event.target.dataset, multiBooking, 'add' );
						event.target.classList.add( 'jet-apb-slot--selected' );
					}else{
						showNotification( 'notification-max-slots' );
					}
				}
			}else{
				instance.querySelectorAll( '.jet-apb-slot--selected' ).forEach( function( el ) {
					el.classList.remove( 'jet-apb-slot--selected' );
				} );

				event.target.classList.add( 'jet-apb-slot--selected' );
				setValue( event.target.dataset, multiBooking );
			}

			recurringSwitcherEl = instance.querySelector( '.jet-apb-switcher' );

			if ( recurringSwitcherEl ) {
				recurringSwitcherEl.style.visibility = 'visible';
			}
			

			if( 'recurring' === opts.bookingType ){
				updateRecurrenceApp();
			}
		}

		const slotDelete = function( event ) {
			if ( ! event.target.matches( '.jet-apb-calendar-slot__delete' ) ) {
				return;
			}

			let { slotIndex } = event.target.dataset,
				selectedSlots = instanceInput.val() ? JSON.parse( instanceInput.val() ) : [],
				slotButton    = instance.querySelector( `[data-slot="${ selectedSlots[ slotIndex ].slot }"][data-slot-end="${ selectedSlots[ slotIndex ].slotEnd }"][data-date="${ selectedSlots[ slotIndex ].date }"]` );

			if(slotButton) {
				slotButton.classList.remove('jet-apb-slot--selected');
			}

			setValue( selectedSlots[ slotIndex ], multiBooking, 'remove' );

			return !1;
		}
		
		const isElementInViewport = function( el ) {

			// Special bonus for those using jQuery
			if (typeof jQuery === "function" && el instanceof jQuery) {
				el = el[0];
			}

			var rect = el.getBoundingClientRect();

			return (
				rect.top >= 0 &&
				rect.bottom <= ( window.innerHeight || document.documentElement.clientHeight )
			);
		}

		const updateAppointmentList = function( value = false, field = 'appointment' ) {
			console.log('HJAHAHAHA')
			if( field === 'appointment' || ( field[0] && field[0].dataset.field === 'appointment' ) ){
				let selectedSlots = instanceInput.val() ? JSON.parse( instanceInput.val() ) : [],
					slot,outputHTML   = '',
					wrapperVisibility = selectedSlots.length ? 'flex' : 'none',
					serviceName, providerName, deleteButton;

				for ( const slotIndex in selectedSlots ) {
					slot = selectedSlots[ slotIndex ];
					serviceName  = ! opts.services ? '' : opts.services[slot.service] ;
					providerName = ! opts.provider ? '' : ' - ' + opts.providers[slot.provider] ;
					deleteButton = ! multiBooking ? '' : `<span class="jet-apb-calendar-slot__delete" data-slot-index="${ slotIndex }"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.23529 0L0 1.23529L5.76477 7.00007L0.000132676 12.7647L1.23543 14L7.00007 8.23536L12.7647 14L14 12.7647L8.23536 7.00007L14.0001 1.23529L12.7648 0L7.00007 5.76477L1.23529 0Z" fill="#8A8B8D"/></svg><span>`;

					outputHTML += `
						<div class="jet-apb-appointments-item">
							<div class="jet-apb-item-service-provider">TESTE ${ serviceName } ${ providerName }</div>
							<div class="jet-apb-item-time">${ slot.friendlyDate }</div>
							<div class="jet-apb-item-date">${ slot.friendlyTime }</div>
							${ deleteButton }
						</div>`;
				}

				appListWrapper.querySelector( '.jet-apb-calendar-appointments-list' ).innerHTML = outputHTML;
				appListWrapper.style.display = wrapperVisibility;

				if ( opts.scrollToDetails ) {
					if ( ! isElementInViewport( appListWrapper ) ) {
						appListWrapper.scrollIntoView( {
							behavior: "smooth",
							block: "center",
							inline: "nearest"
						} );
					}
				}

				return outputHTML;
			} else {
				return value;
			}
		}

		const getNextSlot = function( el ) {

			if ( 'default' !== opts.layout ) {
				return instanceSlots;
			} else {
				var nextEl = el.nextSibling;

				if ( ! nextEl ) {
					return null;
				}

				if ( nextEl.classList.contains( 'jet-apb-calendar-slots' ) ) {
					return nextEl;
				} else {
					return getNextSlot( nextEl );
				}
			}			

		};

		const createMonth = function() {
			
			clearCalendar();
			
			var currentMonth = opts.date.getMonth();

			while ( opts.date.getMonth() === currentMonth ) {
				createDay( opts.date );
				opts.date.setDate( opts.date.getDate() + 1 );
			}

			if ( 'default' === opts.layout ) {
				opts.month.appendChild( getNewSlotsWrapper() );
			}

			opts.date.setDate( 1 );
			opts.date.setMonth( opts.date.getMonth() -1 );
			opts.month_label.innerHTML = opts.months[ opts.date.getMonth() ] + ' ' + opts.date.getFullYear();

		};

		const monthPrev = function() {
			opts.date.setMonth( opts.date.getMonth() - 1 );
			createMonth();
		}

		const monthNext = function() {
			opts.date.setMonth( opts.date.getMonth() + 1 );
			createMonth();
		}

		const clearCalendar = function() {
			opts.month.innerHTML = '';
		}

		const createInputs = function() {

			instanceInput = document.createElement( 'input' );

			instanceInput.setAttribute( 'type', 'hidden' );
			instanceInput.setAttribute( 'name', opts.inputName );
			instanceInput.setAttribute( 'data-field-name', opts.inputName );
			instanceInput.setAttribute( 'data-price', '0' );
			instanceInput.setAttribute( 'data-field', 'appointment' );
			instanceInput.classList.add( 'jet-form__field' );
			instanceInput.classList.add( withNamespace( '__field' ) );

			if ( opts.isRequired ) {
				instanceInput.setAttribute( 'required', true );
			}

			instance.appendChild( instanceInput );
			instanceInput = jQuery( instanceInput );

		};

		const createCalendar = function() {
			instanceContent.innerHTML = notificationHTML + `
			<div class="jet-apb-calendar-header">
				<button type="button" class="jet-apb-calendar-btn" data-calendar-toggle="previous"><svg height="24" version="1.1" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M20,11V13H8L13.5,18.5L12.08,19.92L4.16,12L12.08,4.08L13.5,5.5L8,11H20Z"></path></svg></button>
				<div class="jet-apb-calendar-header__label" data-calendar-label="month"></div>
				<button type="button" class="jet-apb-calendar-btn" data-calendar-toggle="next"><svg height="24" version="1.1" viewbox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z"></path></svg></button>
			</div>
			<div class="jet-apb-calendar-week"></div>
			<div class="jet-apb-calendar-body" data-calendar-area="month"></div>`;

			notification = instance.querySelector( '.jet-apb-calendar-notification' );
		}

		const setWeekDayHeader = function() {

			var result = '';

			for ( var i = opts.weekStart; i <= opts.weekStart + 6; i++ ) {

				if ( i <= 6 ) {
					result += '<span>' + opts.shortWeekday[ i ] + '</span>';
				} else {
					result += '<span>' + opts.shortWeekday[ ( i - 7 ) ] + '</span>';
				}

			};

			instance.querySelector( '.jet-apb-calendar-week' ).innerHTML = result;

		}

		const setValue = function( { date, slot, slotEnd, price, friendlyTime, friendlyDate, provider, service, timezone }, multiBooking = false, action = 'add' ) {
			let selectedSlots = instanceInput.val() ? JSON.parse( instanceInput.val() ) : [] ,
				newPrice      = parseFloat( price ),
				_providerID   = parseInt( provider || providerID ),
				_serviceID    = parseInt( service || serviceID ),
				appointment   = { 
					date, 
					slot, 
					slotEnd, 
					price, 
					friendlyTime, 
					friendlyDate,
					timezone,
					'provider': _providerID, 
					'service': _serviceID 
				};

			if( multiBooking ){
				if( 'remove' === action ){
					if( multiBooking.selected > 0 ){
						multiBooking.selected--;

						selectedSlots = selectedSlots.filter(
							function( item ) {
								if( JSON.stringify( item ) !== JSON.stringify( appointment ) ){
									return true;
								}
								return false;
							}
						)

						newPrice = Number ( instanceInput.data( 'price' ) ) - Number( price );
					}
				}else{
					if( multiBooking.selected < multiBooking.max ){
						multiBooking.selected++

						newPrice = Number ( instanceInput.data( 'price' ) ) + Number( price );
						selectedSlots.push( appointment );
					}
				}
			}else{
				if( 'remove' === action ){
					selectedSlots = [];
				}else{
					selectedSlots[0] = appointment;
				}
			}

			instanceInput
				.data( 'price', newPrice )
				.val( JSON.stringify( selectedSlots ) )
				.trigger( 'change' );

			updateAppointmentList();
		}

		const refreshDates = function( newService, newProvider ) {
			instance.classList.add( 'jet-apb-calendar--loading' );
			removeActiveClass();

			xhr = jQuery.ajax({
				url: opts.api.refresh_dates,
				type: 'GET',
				dataType: 'json',
				data: {
					service: newService,
					provider: newProvider,
				},
			}).done( function( response ) {
				xhr = false;
				instance.classList.remove( 'jet-apb-calendar--loading' );

				for ( var k in response.data ) {
					if ( opts.hasOwnProperty( k ) ) {
						opts[ k ] = response.data[ k ];
					}
				};

				instance.querySelectorAll( '.jet-apb-calendar-date' ).forEach( function( el ) {
					setDayAvailability( el );
				} );
			} );
		}

		const maybeRefreshDatesOnInit = function() {
			if ( opts.service.id ) {
				serviceID = opts.service.id;
			} else if ( opts.service.field ) {

				if ( ! serviceField ) {
					serviceField = document.querySelectorAll( '[name="' + opts.service.field + '"]' );
				}
				if ( 1 === serviceField.length ) {
					if ( serviceField.value ) {
						serviceID = serviceField.value;
					}
				} else if ( 1 < serviceField.length ) {

					for ( var i = 0; i < serviceField.length; i++ ) {
						if ( serviceField[ i ].checked ) {
							serviceID = serviceField[ i ].value;
						}
					};
				}
			}

			if ( opts.providerIsset ) {
				if ( opts.provider.id ) {
					providerID = opts.provider.id;
				} else {
					if ( ! providerField ) {
						providerField = document.querySelector( '[name="' + opts.provider.field + '"]' );
					}

					if ( providerField && providerField.value ) {
						providerID = providerField.value;
					}
				}

			}

			if ( serviceID ) {
				refreshDates( serviceID, providerID )
			}

		}

		const withNamespace = function( suffix = '' ) {
			return ( opts.namespace + suffix );
		}

		const className = function( suffix = '' ) {
			return ( '.' + withNamespace( suffix ) );
		}

		this.init = function() {
			
			instance.data

			form                     = instance.closest( 'form' );
			notificationHTML         = setNotification( instance, notificationHTML );
			appListWrapper           = instance.parentElement.querySelector( '.jet-apb-calendar-appointments-list-wrapper' );
			recurrenceSetingsWrapper = instance.parentElement.querySelector( '.jet-apb-recurrence-app-settings-wrapper' );

			if ( ! opts.service ) {
				notification.classList.add( 'service-field' );
				notification.style.display = 'flex';
			}

			instance.classList.add( 'jet-apb-calendar-layout--' + opts.layout );

			instanceContent = document.createElement( 'div' );
			instanceContent.classList.add( 'jet-apb-calendar-content' );

			instance.appendChild( instanceContent );

			createCalendar();

			opts.button_prev = instance.querySelector( '[data-calendar-toggle=previous]' );
			opts.button_next = instance.querySelector( '[data-calendar-toggle=next]' );
			opts.month       = instance.querySelector( '[data-calendar-area=month]' );
			opts.month_label = instance.querySelector( '[data-calendar-label=month]' );

			opts.date.setDate( 1 );
			createInputs();
			setWeekDayHeader();
			maybeRefreshDatesOnInit();

			createMonth();

			if ( 'default' !== opts.layout ) {
				instanceSlots = getNewSlotsWrapper();
				instanceSidebar = document.createElement( 'div' );
				instanceSidebar.classList.add( 'jet-apb-calendar-sidebar' );
				instanceSidebar.appendChild( instanceSlots );
				instance.appendChild( instanceSidebar );
			}

			addEvent( opts.button_prev, 'click', monthPrev );
			addEvent( opts.button_next, 'click', monthNext );
			addEvent( form, 'click', slotDelete );

			window.JetPlugins?.hooks.addFilter(
				'jet.fb.macro.field.value',
				'jet-form-builder',
				updateAppointmentList
			);

			wp?.hooks?.addFilter(
				'jet.fb.macro.field.value',
				'jet-form-builder',
				updateAppointmentList
			);

			addEvent( document, 'click', function( event ) {

				if ( ! isCurrentInstanceEvent( event ) ) {
					return;
				}

				if ( ! event.target.matches( '.jet-apb-calendar-date-body' ) ) {
					return;
				}

				var day = event.target.parentNode;

				if ( ! day.matches( '[data-status="true"]' ) ) {
					return;
				}

				selectDate( day );

			} );

			addEvent( form, 'click', function( event ) {

				if ( ! event.target.matches( '.jet-apb-calendar-slots__close' ) ) {
					return;
				}

				removeActiveClass();

			} );

			if ( opts.service.field ) {

				if ( ! serviceField ) {
					serviceField = document.querySelectorAll( '[name="' + opts.service.field + '"]' );
				}

				if ( serviceField ) {

					if( opts.allowedServices && opts.allowedServices.field && this.isFieldOrder( opts.service.field, 1 ) ) {
						jQuery( '[name="' + opts.allowedServices.field + '"]', form )
							.on( 'change', getServicesForProvider )
							.trigger( 'change' );
					} else {
						setServices();
					}

					function getServicesForProvider( event ) {
						
						let field = event.target,
							isAjax = window.JetAPBisAjax || false;

						jQuery.ajax( {
							url: opts.api.provider_services,
							type: 'GET',
							dataType: 'json',
							data: {
								provider: field.value,
								is_ajax: isAjax,
								namespace: opts.namespace
							},
						} ).done( function( response ) {

							if ( ! response.success || ! response.services[0] ) {
								return;
							}

							opts.allowedServices = response.services;

							setServices();

						} );

						event.preventDefault();
					}

					function setServices() {

						if ( opts.allowedServices && opts.allowedServices[0] ) {
							for ( var i = 0; i < serviceField.length; i++ ) {

								if ( 'INPUT' === serviceField[ i ].nodeName ) {

									if ( 0 > opts.allowedServices.indexOf( serviceField[ i ].value ) ) {
										serviceField[ i ].closest( className( '__field-wrap.radio-wrap' ) ).remove();
									}

								} else {

									var toRemove = [];
									var service = jQuery( serviceField[ i ] );

									for ( var j = 0; j < serviceField[ i ].options.length; j++ ) {

										if ( ! serviceField[ i ].options[ j ].value ) {
											continue;
										}

										if ( 0 > opts.allowedServices.indexOf( serviceField[ i ].options[ j ].value ) ) {
											toRemove.push( serviceField[ i ].options[ j ].value );
										}
									};

									if ( toRemove.length ) {
										for ( var j = 0; j < toRemove.length; j++ ) {
											service.find( 'option[value="' + toRemove[ j ] + '"]' ).remove();
											//serviceField[ i ].remove( toRemove[ j ] );
										};
									}

								}

							}
						}
					}

					function setServiceValue( eventValue, field ) {

						if( ['radio', 'checkbox' ].includes( field.type ) && ! field.checked ){
							return;
						}

						if ( eventValue !== serviceID ) {
							serviceID  = eventValue;
							if ( ! providerID && ! opts.provider.id ) {
								providerID = false;
							}
							refreshDates( serviceID, providerID );
						} else {
							serviceID  = eventValue;

							if ( ! providerID && ! opts.provider.id ) {
								providerID = false;
							}
						}
					}

					for ( var i = 0; i < serviceField.length; i++ ) {
						setServiceValue( serviceField[ i ].value, serviceField[ i ] );

						serviceField[ i ].addEventListener( 'change', function( event ) {
							setServiceValue( event.target.value, event.target );
						}, false );
					}

				}
			}

			if ( opts.provider.field && opts.providerIsset ) {

				if ( opts.provider.field ) {
				
					function setProviderValue( eventValue ) {
						if ( eventValue !== providerID ) {
							providerID = eventValue;
							refreshDates( serviceID, providerID );
						} else {
							providerID = eventValue;
						}
					}

					jQuery( form ).on( 'change', '[name="' + opts.provider.field + '"]', function( event ) {
						setProviderValue( event.target.value );
					} ).trigger( 'change' );


					// Refresh regular provider control
					jQuery( form ).on( 'refresh', '[name="' + opts.provider.field + '"]', function( event ) {
						setProviderValue( '' );
					} );

					// Refresh custom template provider control
					jQuery( form ).on( 'refresh', '.appointment-provider[data-field="' + opts.provider.field + '"]', function( event ) {
						setProviderValue( '' );
					} );

					
				}

			}

			initialized = true;

		}

		this.destroy = function() {
			
			removeEvent( opts.button_prev, 'click', monthPrev );
			removeEvent( opts.button_next, 'click', monthNext );

			clearCalendar();
			
			instance.dataset.calendarInitialized = false;
			instanceContent.innerHTML = '';
			instance.innerHTML = '';

		}

		this.reset = function() {
			initialized = false;
			this.destroy();
			this.init();
		}

		this.set = function( options ) {

			for ( var k in options ) {
				if ( opts.hasOwnProperty( k ) ) {
					opts[ k ] = options[ k ];
				}
			};

			if ( initialized ) {
				this.reset();
			}

		}

		this.isFieldOrder = function( fieldName, index ) {
			
			if ( ! order.length ) {
				return false;
			}

			return index === order.indexOf( fieldName );

		}

		this.setFieldsOrder = function() {

			if ( ! opts.provider.field || ! opts.service.field ) {
				return;
			}

			let $form = jQuery( instance ).closest( 'form' );

			jQuery( '[name="' + opts.provider.field + '"], [name="' + opts.service.field + '"]', $form ).each( function( index, el ) {
				order.push( el.getAttribute( 'name' ) );
			} );

		}

		let dataArgs = instance.dataset.args;

		if ( dataArgs ) {
			dataArgs = JSON.parse( dataArgs );
			this.set( dataArgs );
		}

		this.setFieldsOrder();
		this.init();

	}

	return VanillaCalendar;

})()

window.VanillaCalendar = VanillaCalendar

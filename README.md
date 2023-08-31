# ChangeLog

## 2.0.3
* UPD: Minor improvements in the Apoinmetns list UI/UX;
* FIX: Custom schedule overriding logic;
* FIX: Timepicker does not show already booked time.

## 2.0.2
* ADD: Allow to export appointments;
* ADD: Macros `Custom DB Column Value` to get the data from custom DB columns in Workflows;
* UPD: Compatibility with new WooCommerce REST API checkout;
* FIX: Minor Workflows UI bugs;
* FIX: Service meta macros inside Workflows.

## 2.0.1
* UPD: Allow to set user_name column value from front-end form;
* FIX: Avoid incorrect date calculations for some timezones;
* FIX: Duplicating form field for date selection in the JetPopup;
* FIX: Available slot calculation when system time not matching webiste timezone;
* FIX: Appointments table creation for some cases.

## 2.0.0
* ADD: Workflows functionality;
* ADD: Zoom integration;
* ADD: Timezones picker for the front-end appointments calendar UI;
* ADD: Public actions functionality (confirm and cancel appoinments by URL);
* ADD: Allow to override day schedule with Working Days settings;
* UPD: Admin UI improvements;
* FIX: SQL errors on MySQL 8.0 or higher;
* FIX: Allow to set 0 into service price.

## 1.6.10
* ADD: Allow to automatically change appointemnts status by Cron;
* ADD: Allow two-way synchronization for WC Orders and appointments;
* ADD: Allow to limit allowed for appointment days range;
* FIX: Adding appointments from admin area;
* FIX: Elemenotr Popup compatibility
* FIX: Providers ans Services switch when custom template is used.

## 1.6.9
* FIX: try to create DB tables only if not exists;
* FIX: days off checking;
* FIX: correctly process float values for slots;
* FIX: prevent PHP errors;
* FIX: correctly refresh services and providers list.

## 1.6.8
* ADD: Duration step in time picker settings

## 1.6.7
* FIX: Display appointments on the calendar
* FIX: Option "Availability check by"

## 1.6.6
* FIX: UTC time

## 1.6.5
* FIX: Choice of service

## 1.6.4
* FIX: Form style
* FIX: Choice of provider

## 1.6.3
* FIX: Loading form in ajax pop up
* ADD: Macro processing for Gutenberg. Example: \<!-- JFB_FIELD::date --\>

## 1.6.2
* FIX: Meta `_app_price`

## 1.6.1
* FIX: Manage Capacity

## 1.6.0
* ADD: Added new schedule type - Repeating appointment
* ADD: Added new schedule type - Time Picker appointment
* ADD: New appointment price settings for providers and services
* FIX: Fixed multi booking

## 1.5.8
* FIX: Dynamic Link - Add booking to Google calendar
* FIX: Appointment status if integration with woocommerce is enabled

## 1.5.7
* FIX: Date slots in admin panel

## 1.5.6
* FIX: Labels of statuses in the admin panel

## 1.5.5
* ADD: Compatibility with JetFormBuilder
* FIX: Saving global settings
* FIX: Empty days of weeks in the schedule
* FIX: The price from the calculator field is transferred to the WooCommerce prices
* FIX: Fixed a bug with booking different times with the same provider


## 1.5.1
* FIX: Time slots compatibility with php 7.1

## 1.5.0
* ADD: Multi booking
* ADD: Provider price
* UPD: Integration with woocommerce
* UPD: Notifications `Send Email` and `Call a Webhook`

## 1.4.0
* UPD: Admin panel for appointments
* ADD: Ability to edit and add appointments from the admin panel
* ADD: New views of appointments calendar and timeline.
* ADD: Filter for searching and sorting appointments in the admin panel

## 1.3.3
* FIX: Webhook date data

## 1.3.2
* FIX: Fixed appointment if option "Manage Capacity" is enabled

## 1.3.1
* FIX: Custom Schedule in services and providers

## 1.3.0
* ADD: Plugin settings have been moved to the Crocoblock dashboard
* ADD: Added the Slot Duration, Buffer Before Slot, Buffer After Slot of service and provider in the listing settings
* FIX: Display the name of service and provider in the admin panel if the service service or provider is private or with a password.

## 1.2.6
* ADD: New macros: `%service_link%` `%provider_link%` `%appointment_start%` `%appointment_end%`
* UPD: Timing control for options: Duration, Buffer Time Before Slot, Buffer Time After Slot
* UPD: If the date is fully booked, the `.jet-apb-calendar-date-disabled` class is added to it

## 1.2.5
* UPD: Change edit permissions

## 1.2.4
* FIX: Booking time error in WC details

## 1.2.3
* UPD: Added localization file

## 1.2.2
* FIX: WC product creation

## 1.2.1
* FIX: Saving custom schedule settings in services without a selected provider.

## 1.2.0
* ADD: Added the ability to select the period of working days and days off;
* ADD: Added Custom Schedule for single services and providers;
* ADD: Allow to add appointments details to WooCommerce orders;
* ADD: Added new macros for form email notification %service_title%, %provider_title%;
* ADD: Allow ability for users to add a appointment to their calendar;
* FIX: Fixed minor bugs.

## 1.1.1
* UPD: allow to correctly render appointment form on Ajax;
* UPD: allow to manage DB columns;
* FIX: disable next page button if time slot not selected in the calendar;
* FIX: providers REST API endpoint.

## 1.1.0
* ADD: Allow toi showcase appointments with Listing Grid  widget;
* ADD: Services capacity management;
* ADD: Allow to set custom labels for week days and months;
* ADD: Booking details to WooCommerce order e-mails;
* UPD: Allow to change time format in the calendar slots;
* UPD: Allow to use custom templates for providers select;
* UPD: Allow to correctly use radio field as services select;
* FIX: Appointment date format for e-mail;

## 1.0.0
* Initial release

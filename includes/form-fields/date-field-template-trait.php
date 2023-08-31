<?php


namespace JET_APB\Form_Fields;

use JET_APB\Plugin;

/**
 * @method getArgs( $key = '', $ifNotExist = false )
 * @method isRequired()
 * @method isNotEmptyArg( $key )
 * @method getCustomTemplate( $provider_id, $args )
 * @method scopeClass( $suffix = '' )
 * @method is_block_editor()
 *
 * Trait Date_Field_Template_Trait
 * @package JET_APB
 */
trait Date_Field_Template_Trait {

	use Static_Calendar_Trait;

	/**
	 * Check if date field is already rendered
	 *
	 * @var boolean
	 */
	public $date_done = false;

	public function field_template() {
		if ( ! $this->date_done ) {
			Plugin::instance()->form->calendar_assets();
			$this->date_done = true;
		}

		if ( $this->is_block_editor() ) {
			return $this->render_static_calendar();
		}
		
		$dataset = $this->get_dataset();
		$template_name = $this->get_template_name();
		
		ob_start();
		
			include Plugin::instance()->tools->get_template( $template_name );
		
		return ob_get_clean();
	}
	
	private function get_dataset() {
		
		$service_data  = Plugin::instance()->form->get_service_field_data( $this->getArgs() );
		$provider_data = Plugin::instance()->form->get_provider_field_data( $this->getArgs() );
		$required      = $this->isNotEmptyArg( 'required' );
		
		return [
			'booking_type'      => Plugin::instance()->settings->get( 'booking_type' ),
			'excludedDates'     => Plugin::instance()->calendar->get_off_dates( $service_data['id'], $provider_data['id'] ),
			'datesMode'         => Plugin::instance()->calendar->get_working_days_mode( $service_data['id'], $provider_data['id'] ),
			'worksDates'        => Plugin::instance()->calendar->get_works_dates( $service_data['id'], $provider_data['id'] ),
			'availableWeekDays' => Plugin::instance()->calendar->get_available_week_days( $service_data['id'], $provider_data['id'] ),
			'service'           => $service_data['form_field'],
			'providerIsset'     => $provider_data['is_set'],
			'provider'          => $provider_data['form_field'],
			'inputName'         => $this->getArgs( 'name' ),
			'isRequired'        => $required,
			'allowedServices'   => Plugin::instance()->form->get_allowed_services( $provider_data ),
		];
	}
	private function get_template_name() {
		return 'appointment-calendar.php';
	}
}

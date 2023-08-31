<?php
namespace JET_APB\Admin\Settings;

use JET_APB\Plugin;
use JET_APB\Admin\Settings\Appointment_Settings_Base as Appointment_Settings_Base;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Working_Hours extends Appointment_Settings_Base {

	/**
	 * Returns module slug
	 *
	 * @return void
	 */
	public function get_page_slug() {
		return 'jet-apb-working-hours-settings';
	}

	/**
	 * [get_page_name description]
	 * @return [type] [description]
	 */
	public function get_page_name() {
		return esc_html__( 'Working Hours', 'jet-dashboard' );
	}

	public function page_assets() {
		Plugin::instance()->dashboard->components->appointments_range->assets();
	}

	/**
	 * [page_templates description]
	 * @param  array  $templates [description]
	 * @param  string $subpage   [description]
	 * @return [type]            [description]
	 */
	public function page_templates( $templates = array(), $page = false, $subpage = false ) {
		
		Plugin::instance()->dashboard->components->appointments_range->template();

		$templates['jet-apb-working-hours-settings'] = JET_APB_PATH .'templates/admin/jet-apb-settings/settings-working-hours.php';
		
		ob_start();
		include JET_APB_PATH . 'templates/admin/jet-apb-settings/custom-day-schedule.php';
		$content = ob_get_clean();

		printf(
			'<script type="text/x-template" id="jet-apb-custom-day-schedule">%s</script>',
			$content
		);
		
		return $templates;
	}
}

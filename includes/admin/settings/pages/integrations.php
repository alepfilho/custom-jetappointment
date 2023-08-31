<?php
namespace JET_APB\Admin\Settings;

use JET_APB\Plugin;
use JET_APB\Admin\Settings\Appointment_Settings_Base as Appointment_Settings_Base;
use JET_APB\Integrations\Manager as Integrations_Manager;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Integrations extends Appointment_Settings_Base {

	/**
	 * Returns module slug
	 *
	 * @return void
	 */
	public function get_page_slug() {
		return 'jet-apb-integrations';
	}

	/**
	 * [get_page_name description]
	 * @return [type] [description]
	 */
	public function get_page_name() {
		return esc_html__( 'Integrations', 'jet-appointments-booking' );
	}

	public function page_assets() {

		Integrations_Manager::instance()->assets();
	
		wp_enqueue_script(
			'jet-apb-integrations',
			JET_APB_URL . 'assets/js/admin/integrations.js',
			[ 'wp-api-fetch' ],
			JET_APB_VERSION,
			true
		);

		wp_enqueue_style( 'jet-apb-integrations' );

		wp_localize_script( 'jet-apb-integrations', 'JetAPBIntegrationsData', [
			'integrations' => Integrations_Manager::instance()->get_integrations_for_js(),
			'api'          => Plugin::instance()->rest_api->get_url( 'update-appointment-integrations', false ),
		] );
	}

	/**
	 * [page_templates description]
	 * @param  array  $templates [description]
	 * @param  string $subpage   [description]
	 * @return [type]            [description]
	 */
	public function page_templates( $templates = array(), $page = false, $subpage = false ) {
		
		$templates['jet-apb-integrations'] = JET_APB_PATH . 'templates/admin/jet-apb-settings/settings-integrations.php';
		
		return array_merge( $templates, Integrations_Manager::instance()->get_templates() );
	}
}

<?php
namespace JET_APB\Admin\Settings;

use JET_APB\Plugin;
use JET_APB\Admin\Settings\Appointment_Settings_Base as Appointment_Settings_Base;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Workflows extends Appointment_Settings_Base {

	/**
	 * Returns module slug
	 *
	 * @return void
	 */
	public function get_page_slug() {
		return 'jet-apb-workflows';
	}

	/**
	 * [get_page_name description]
	 * @return [type] [description]
	 */
	public function get_page_name() {
		return esc_html__( 'Workflows', 'jet-appointments-booking' );
	}

	public function page_assets() {
	
		wp_enqueue_script(
			'jet-apb-workflows',
			JET_APB_URL . 'assets/js/admin/workflows.js',
			[ 'wp-api-fetch' ],
			JET_APB_VERSION,
			true
		);

		wp_enqueue_style(
			'jet-engine-query-dynamic-args',
			\Jet_Engine\Query_Builder\Manager::instance()->component_url( 'assets/css/query-builder.css' ),
			array(),
			jet_engine()->get_version()
		);

		wp_localize_script( 'jet-apb-workflows', 'JetAPBWorkflowsData', [
			'workflows'   => Plugin::instance()->workflows->collection->to_array(),
			'events'      => Plugin::instance()->workflows->get_options_for_js( 'events', __( 'Select trigger event...', 'jet-appointments-booking' ) ),
			'actions'     => Plugin::instance()->workflows->get_options_for_js( 'actions', __( 'Select action to run...', 'jet-appointments-booking' ) ),
			'schedule'    => $this->get_schedule_options(),
			'macros_list' => $this->get_macros_for_editor(),
			'api'         => Plugin::instance()->rest_api->get_urls( false ),
		] );
	}

	public function get_macros_for_editor() {

		$res = array();

		foreach ( Plugin::instance()->macros->get_all() as $macros_id => $data ) {

			$macros_data = array(
				'id' => $macros_id,
			);

			if ( ! is_array( $data ) || empty( $data['label'] ) ) {
				$macros_data['name'] = $macros_id;
			} elseif ( ! empty( $data['label'] ) ) {
				$macros_data['name'] = $data['label'];
			}

			if ( is_array( $data ) && ! empty( $data['args'] ) ) {
				$macros_data['controls'] = $data['args'];
			}

			$res[] = $macros_data;

		}

		usort( $res, function ( $a, $b ) {
			return strcmp( $a['name'], $b['name'] );
		} );

		return $res;

	}

	public function get_schedule_options() {
		return [
			[
				'value' => 'immediately',
				'label' => __( 'Immediately', 'jet-appointments-booking' ),
			],
			[
				'value' => 'before_appointment',
				'label' => __( 'Scheduled before appoinment', 'jet-appointments-booking' ),
			],
		];
	}

	/**
	 * [page_templates description]
	 * @param  array  $templates [description]
	 * @param  string $subpage   [description]
	 * @return [type]            [description]
	 */
	public function page_templates( $templates = array(), $page = false, $subpage = false ) {
		
		$templates['jet-apb-macros-inserter'] = JET_APB_PATH . 'templates/admin/jet-apb-settings/settings-macros-inserter.php';
		$templates['jet-apb-workflow-item'] = JET_APB_PATH . 'templates/admin/jet-apb-settings/settings-workflow-item.php';
		$templates['jet-apb-workflows'] = JET_APB_PATH . 'templates/admin/jet-apb-settings/settings-workflows.php';
		
		return $templates;
	}
}

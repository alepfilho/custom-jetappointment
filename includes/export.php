<?php
namespace JET_APB;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Export appointments manager class
 */
class Export {

	protected $action = 'jet_appointments_export';
	protected $domain = null;

	public function __construct() {
		add_action( 'admin_action_jet_appointments_export', array( $this, 'do_export' ) );
	}

	public function do_export() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You don`t have access to this URL', __( 'Error', 'jet-appointments-booking' ) );
		}

		if ( ! $this->verify_nonce() ) {
			wp_die( 'Link is expired. Please reload appointments page and try again.', __( 'Error', 'jet-appointments-booking' ) );
		}

		$type   = ! empty( $_GET['type'] ) ? $_GET['type'] : 'all';
		$format = ! empty( $_GET['format'] ) ? $_GET['format'] : 'csv';

		$appointments = array();

		switch ( $type ) {
			case 'all':
				$appointments = Plugin::instance()->db->appointments->query();
				break;

			case 'filtered':

				$appointments = Plugin::instance()->db->appointments->get_appointments( array(
					'per_page'  => 0,
					'sort'      => ! empty( $_GET['sort'] ) ? wp_unslash( $_GET['sort'] ) : array(),
					'filter'    => ! empty( $_GET['filter'] ) ? wp_unslash( $_GET['filter'] ) : array(),
					'search_in' => ! empty( $_GET['search_in'] ) ? $_GET['search_in'] : false,
					'mode'      => ! empty( $_GET['mode'] ) ? $_GET['mode'] : 'all',
				) );

				break;

		}

		switch ( $format ) {
			case 'csv':
				$this->to_csv( $appointments );
				break;
			
			case 'ical':
				$this->to_ical( $appointments );
				break;
		}

		wp_die( 'Incorrect request data', __( 'Error', 'jet-appointments-booking' ) );

	}

	public function to_ical( $items = array() ) {

		$this->download_headers( 'appointments.ics', 'text/calendar' );

		if ( ! defined( '_ZAPCAL' ) ) {
			require_once JET_APB_PATH . 'includes/vendor/icalendar/zapcallib.php';
		}

		$datestamp = \ZCiCal::fromUnixDateTime() . 'Z';
		$calendar  = new \ZCiCal();

		foreach ( $items as $item ) {
			$this->add_calendar_item( $item, $calendar, $datestamp );
		}

		echo $calendar->export();

		die();

	}

	/**
	 * Returns current domain
	 *
	 * @return [type] [description]
	 */
	public function get_domain() {

		if ( $this->domain ) {
			return $this->domain;
		}

		$find         = array( 'http://', 'https://' );
		$replace      = '';
		$this->domain = str_replace( $find, $replace, home_url() );

		return $this->domain;

	}

	public function add_calendar_item( $item, $calendar, $datestamp ) {

		$hash_string = $item['slot'] . $item['slot_end'] . $item['ID'];
		$uid         = md5( $hash_string ) . '@' . $this->get_domain();
		$event       = new \ZCiCalNode( 'VEVENT', $calendar->curnode );
		$tz          = new \DateTimeZone( 'GMT+0' );
		$start_date  = wp_date( 'Y-m-d H:i:s', $item['slot'], $tz );
		$end_date    = wp_date( 'Y-m-d H:i:s', $item['slot_end'], $tz );

		$data = apply_filters( 'jet-apb/export/ical-item-data', array(
			'uid' => array(
				'node' => 'UID',
				'value' => $uid,
			),
			'dtstart' => array(
				'node' => 'DTSTART;VALUE=DATE-TIME',
				'value' => \ZCiCal::fromSqlDateTime( $start_date ),
			),
			'dtend' => array(
				'node' => 'DTEND;VALUE=DATE-TIME',
				'value' => \ZCiCal::fromSqlDateTime( $end_date ),
			),
			'dtstamp' => array(
				'node' => 'DTSTAMP',
				'value' => $datestamp,
			),
			'summary' => array(
				'node' => 'SUMMARY',
				'value' => get_the_title( $item['service'] ) . ', ' . $item['user_email'],
			),
			'description' => array(
				'node' => 'DESCRIPTION',
				'value' => get_the_excerpt( $item['service'] ),
			),
		), $item, $calendar );

		foreach ( $data as $row ) {
			$event->addNode( new \ZCiCalDataNode( $row['node'] . ':' . $row['value'] ) );
		}

	}

	public function to_csv( $items = array() ) {

		$this->download_headers( 'appointments.csv', 'text/csv' );

		$output        = fopen( 'php://output','w' );
		$headers_added = false;
		$return        = ! empty( $_GET['return'] ) ? $_GET['return'] : 'id';
		$date_format   = ! empty( $_GET['date_format'] ) ? $_GET['date_format'] : 'Y-m-d';
		$time_format   = ! empty( $_GET['time_format'] ) ? $_GET['time_format'] : 'H:i';

		foreach( $items as $item ) {

			if ( 'title' === $return ) {
				$item['service'] = ! empty( $item['service'] ) ? get_the_title( $item['service'] ) : $item['service'];
				$item['provider'] = ! empty( $item['provider'] ) ? get_the_title( $item['provider'] ) : $item['provider'];
			}

			$tz = new \DateTimeZone( 'GMT+0' );

			$item['date']     = wp_date( $date_format, $item['date'], $tz );
			$item['slot']     = wp_date( $time_format, $item['slot'], $tz );
			$item['slot_end'] = wp_date( $time_format, $item['slot_end'], $tz );

			$item = apply_filters( 'jet-apb/export/csv-item-data', $item, $this );

			if ( ! $headers_added ) {

				$headers_added = true;
				$headers       = array_keys( $item );

				fputcsv( $output, $headers );

			}

			fputcsv( $output, $item );

		}

		fclose( $output );
		die();

	}

	/**
	 * Process file download
	 *
	 * @param  [type] $filename [description]
	 * @param string $file [description]
	 *
	 * @return [type]           [description]
	 */
	public function file_download( $filename = null, $file = '', $type = 'application/json' ) {

		$this->download_headers( $filename, $type );

		// Set the file size header
		header( "Content-Length: " . strlen( $file ) );

		echo $file;
		die();

	}

	public function download_headers( $filename = null, $type = 'application/json' ) {

		if ( false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) ) {
			set_time_limit( 0 );
		}

		@session_write_close();

		if ( function_exists( 'apache_setenv' ) ) {
			$variable = 'no-gzip';
			$value    = 1;
			@apache_setenv( $variable, $value );
		}

		@ini_set( 'zlib.output_compression', 'Off' );

		nocache_headers();

		header( "Robots: none" );
		header( "Content-Type: " . $type );
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=\"" . $filename . "\";" );
		header( "Content-Transfer-Encoding: binary" );

	}

	public function get_nonce() {
		return wp_create_nonce( $this->action );
	}

	public function verify_nonce() {
		return wp_verify_nonce( $_REQUEST['_nonce'], $this->action );
	}

	public function base_url() {
		return add_query_arg( array(
			'action' => $this->action
		), admin_url( 'admin.php' ) );
	}

}

<?php
namespace JET_APB\DB;

/**
 * Database manager class
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define DB class
 */
class Appointments extends Base {

	public $defaults = array(
		'status'   => 'pending',
		'provider' => 0,
	);

	/**
	 * Additinal DB columns list
	 *
	 * @var array
	 */
	public $additional_db_columns = array();

	public function get_defaults() {
		$this->defaults['appointment_date'] = wp_date( 'Y-m-d H:i:s' );
		return $this->defaults;
	}

	/**
	 * Return table slug
	 * 
	 * @return [type] [description]
	 */
	public function table_slug() {
		return 'appointments';
	}

	/**
	 * Returns additional DB columns
	 * @return [type] [description]
	 */
	public function get_additional_db_columns() {
		return $this->additional_db_columns;
	}

	/**
	 * Returns currently queried appointment ID
	 *
	 * @return [type] [description]
	 */
	public function get_queried_item_id() {

		$object = jet_engine()->listings->data->get_current_object();

		if ( is_object( $object ) ) {

			if ( isset( $object->post_type ) && 'jet_apb_list' === $object->post_type ) {
				return $object->ID;
			} else {
				return false;
			}

		} elseif ( is_array( $object ) ) {
			return isset( $object['ID'] ) ? $object['ID'] : false;
		} else {
			return false;
		}
	}

	/**
	 * Add new DB column
	 *
	 * @param [type] $column [description]
	 */
	public function add_column( $column ) {
		$this->additional_db_columns[] = $column;
	}

	/**
	 * Ensure provider is passed correctly
	 * 
	 * @param  array  $data [description]
	 * @return [type]       [description]
	 */
	public function sanitize_data_before_db( $data = array() ) {

		$data['provider'] = ! empty( $data['provider'] ) ? absint( $data['provider'] ) : 0;

		return $data;
	}

	/**
	 * Returns columns schema
	 * @return [type] [description]
	 */
	public function schema() {
		return array(
			'ID'               => "bigint(20) NOT NULL AUTO_INCREMENT",
			'group_ID'         => "bigint(20)",
			'status'           => "text",
			'service'          => "text",
			'provider'         => "text",
			'order_id'         => "bigint(20)",
			'user_id'          => "bigint(20)",
			'user_name'        => "text NOT NULL",
			'user_email'       => "text",
			'date'             => "bigint(20) NOT NULL",
			'date_end'         => "bigint(20) NOT NULL",
			'slot'             => "bigint(20) NOT NULL",
			'slot_end'         => "bigint(20) NOT NULL",
			'type'             => "text",
			'appointment_date' => "datetime NOT NULL default '0000-00-00 00:00:00'",
		);
	}

	/**
	 * Create DB table for apartment units
	 *
	 * @return [type] [description]
	 */
	public function get_table_schema() {

		$charset_collate = $this->wpdb()->get_charset_collate();
		$table           = $this->table();

		$default_columns    = $this->schema();
		$additional_columns = $this->get_additional_db_columns();
		$columns_schema     = '';

		foreach ( $default_columns as $column => $desc ) {
			$columns_schema .= $column . ' ' . $desc . ',';
		}

		if ( is_array( $additional_columns ) && ! empty( $additional_columns ) ) {
			foreach ( $additional_columns as $column ) {
				$columns_schema .= $column . ' text,';
			}
		}

		return "CREATE TABLE $table (
			$columns_schema
			PRIMARY KEY (ID)
		) $charset_collate;";

	}

	public function get_appointments( $params = array() ) {

		$offset      = ! empty( $params['offset'] ) ? absint( $params['offset'] ) : 0;
		$per_page    = isset( $params['per_page'] ) ? absint( $params['per_page'] ) : 50;
		$sort        = ! empty( $params['sort'] ) ? $params['sort'] : array();
		$filter      = ! empty( $params['filter'] ) ? $params['filter'] : array();
		$search_in   = ! empty( $params['search_in'] ) ? str_replace( ',', ', ', $params['search_in'] )  : false ;
		$mode        = ! empty( $params['mode'] ) ? $params['mode'] : 'all';

		if ( ! is_array( $sort ) && ! empty( $sort ) ) {
			$sort = json_decode( $sort, true );
		}

		if ( ! is_array( $filter ) && ! empty( $filter ) ) {
			$filter = json_decode( $filter, true );
		}

		$filter = ( ! empty( $filter ) && is_array( $filter ) ) ? array_filter( $filter ) : array();
		$sort   = ( ! empty( $sort ) && is_array( $sort ) ) ? array_filter( $sort ) : array( 'orderby' => 'ID', 'order' => 'DESC', );

		$search = ! empty( $filter['search'] ) ? $filter['search'] : false ;

		if ( $search ) {
			unset( $filter['search'] );
		}

		if ( ! empty( $filter['date'] ) && ! is_int( $filter['date'] ) ) {
			$filter_date = $filter['date'];
			unset( $filter['date'] );

			$filter = array_merge( $filter, $this->parse_date( $filter_date ) );
		}

		switch ( $mode ) {
			
			case 'upcoming':
				$filter['slot>>'] = time();
				break;

			case 'past':
				$filter['slot<<'] = time();
				break;
				
		}

		$appointments = $this->query(
			$filter,
			$per_page,
			$offset,
			$sort,
			$search,
			$search_in
		);

		if ( empty( $appointments ) ) {
			$appointments = array();
		}

		return $appointments;

	}

	public function parse_date( $date ) {

		$output = [];
		$dates_array = explode( '-', $date );

		if ( count( $dates_array ) > 1 ) {
			$output['date>='] = strtotime( $dates_array[0] );
			$output['date<='] = strtotime( $dates_array[1] );
		} else {
			$output['date'] = strtotime( $dates_array[0] );
		}

		return $output;

	}

	/**
	 * Query appointments with capacity counted
	 *
	 * @return [type] [description]
	 */
	public function query_with_capacity( $args = array() ) {

		$table = $this->table();

		$query = "SELECT service, provider, date, slot, slot_end, COUNT( slot ) AS slot_count FROM $table";
		$rel   = 'AND';

		if ( isset( $args['after'] ) ) {
			$after = $args['after'];
			unset( $args['after'] );
			$args['ID>'] = $after;
		}

		if ( isset( $args['before'] ) ) {
			$before = $args['before'];
			unset( $args['before'] );
			$args['ID<'] = $before;
		}

		$query .= $this->add_where_args( $args, $rel );
		$query .= " GROUP BY `slot`;";

		$raw = $this->wpdb()->get_results( $query, ARRAY_A );

		return $raw;

	}

}
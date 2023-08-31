<?php
namespace JET_APB;

/**
 * Statuses class
 */
class Statuses {

	private $statuses = array();

	/**
	 * Install statuses
	 */
	public function __construct() {
		$this->statuses = array(
			'processing' => _x( 'Agendado', 'Order status', 'jet-appointments-booking' ),
			'completed'  => _x( 'Confirmado', 'Order status', 'jet-appointments-booking' ),
			'cancelled'  => _x( 'Cancelado', 'Order status', 'jet-appointments-booking' ),
		);
	}

	/**
	 * Return label of given status
	 * 
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	public function get_status_label( $key ) {
		return isset( $this->statuses[ $key ] ) ? $this->statuses[ $key ] : false;
	}

	/**
	 * Returns all registered statuses
	 *
	 * @return [type] [description]
	 */
	public function get_all() {
		return $this->statuses;
	}

	public function get_schema() {
		return array(
			'valid'       => $this->valid_statuses(),
			'in_progress' => $this->in_progress_statuses(),
			'finished'    => $this->finished_statuses(),
			'invalid'     => $this->invalid_statuses(),
		);
	}

	/**
	 * Returns valid statuses
	 * If appointment has this status - appontment slot is set as not-allowed
	 *
	 * @return [type] [description]
	 */
	public function valid_statuses() {
		return array(
			'pending',
			'processing',
			'completed',
		);
	}

	/**
	 * Returns valid but not finalized statuses
	 *
	 * @return [type] [description]
	 */
	public function in_progress_statuses() {
		
		$in_progress = array(
			'pending',
			'processing',
		);

		if ( 'in_progress' === Plugin::instance()->settings->get( 'process_on_hold' ) ) {
			$in_progress[] = 'on-hold';
		}
		
		return $in_progress;
	}

	/**
	 * Return list of statuses to check availability by
	 * 
	 * @return [type] [description]
	 */
	public function exclude_statuses() {
		return array_unique( array_merge(
			$this->valid_statuses(),
			$this->in_progress_statuses()
		) );
	}

	/**
	 * Returns valid and finished statuses
	 * @return [type] [description]
	 */
	public function finished_statuses() {
		return array_values( array_diff( $this->valid_statuses(), $this->in_progress_statuses() ) );
	}

	/**
	 * Returns invalid statuses
	 * If appointment has this status - appontment slot is set as not-allowed
	 *
	 * @return [type] [description]
	 */
	public function invalid_statuses() {
	
		$invalid = array(
			'cancelled',
			'refunded',
			'failed',
		);

		if ( 'invalid' === Plugin::instance()->settings->get( 'process_on_hold' ) ) {
			$invalid[] = 'on-hold';
		}
		
		return $invalid;

	}

	/**
	 * Get all statuses
	 * @return [type] [description]
	 */
	public function get_statuses() {
		return $this->statuses;
	}

	/**
	 * Get all statuses
	 * @return [type] [description]
	 */
	public function get_statuses_ids() {
		return array_keys( $this->statuses );
	}

}
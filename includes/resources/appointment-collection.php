<?php
namespace JET_APB\Resources;

use JET_APB\Plugin;
use JET_APB\Vendor\Actions_Core\Smart_Action_It;

class Appointment_Collection {

	protected $type       = '';
	protected $columns    = [];
	protected $meta       = [];
	protected $status     = 'pending';
	protected $format     = '';
	protected $group_ID   = 0;
	protected $user_email = '';
	protected $user_name  = '';
	protected $order_id   = 0;

	/** @var Smart_Action_It */
	protected $action;

	/** @var array Appointments list */
	protected $list = array();

	public function __construct( Smart_Action_It $action ) {
		$this->set_action( $action );
		$this->set_wc_status();
		$this->set_gateway_status();
		$this->set_settings_type();
		$this->set_settings_format();
		$this->set_settings_columns();
		$this->set_action_order_id();
	}

	public function set_settings_columns(): Appointment_Collection {
		return $this->set_columns( Plugin::instance()->settings->get( 'db_columns' ) );
	}

	public function set_settings_type(): Appointment_Collection {
		return $this->set_type( Plugin::instance()->settings->get( 'booking_type' ) );
	}

	public function set_wc_status(): Appointment_Collection {
		return $this->has_product_id() ? $this->set_status( 'on-hold' ) : $this;
	}

	public function set_gateway_status(): Appointment_Collection {
		return $this->action->hasGateway() ? $this->set_status( 'on-hold' ) : $this;
	}

	public function set_settings_format(): Appointment_Collection {
		return $this->set_format( Plugin::instance()->settings->get( 'slot_time_format' ) );
	}

	public function set_action_order_id(): Appointment_Collection {
		if ( ! $this->action->hasGateway() ) {
			return $this;
		}

		if( ! empty( $this->action->getRequest( 'inserted_post_id' ) ) ) {
			$this->set_order_id( (int) $this->action->getRequest( 'inserted_post_id' ) );
		}

		return $this;
	}

	public function add(): Appointment {
		$single = new Appointment( $this );

		$single->set_status( $this->status );
		$single->set_type( $this->type );
		$single->set_user_email( $this->user_email );
		$single->set_user_name( $this->user_name );
		$single->set_group_ID( $this->group_ID );
		$single->set_order_id( $this->order_id );
		$single->set_meta( $this->meta );

		$this->list[] = $single;

		return $single;
	}

	/**
	 * @param int $order_id
	 */
	public function set_order_id( int $order_id ): Appointment_Collection {
		$this->order_id = $order_id;

		return $this;
	}

	public function set_group_ID( $group_ID ): Appointment_Collection {
		$this->group_ID = $group_ID;

		return $this;
	}

	public function set_user_email( string $user_email ): Appointment_Collection {
		$this->user_email = $user_email;

		return $this;
	}

	public function set_user_name( string $user_name ): Appointment_Collection {
		$this->user_name = $user_name;

		return $this;
	}

	public function set_format( string $format ): Appointment_Collection {
		$this->format = $format;

		return $this;
	}

	public function set_type( string $type ): Appointment_Collection {
		$this->type = $type;

		return $this;
	}

	public function set_columns( array $columns ): Appointment_Collection {
		$this->columns = $columns;

		return $this;
	}

	public function set_action( Smart_Action_It $action ): Appointment_Collection {
		$this->action = $action;

		return $this;
	}

	public function set_status( string $status ): Appointment_Collection {
		$this->status = $status;

		return $this;
	}

	public function set_meta( $meta ): Appointment_Collection {
		$this->meta = array_merge( $this->meta, $meta );

		return $this;
	}

	public function has_product_id(): bool {
		return Plugin::instance()->wc->get_status() && Plugin::instance()->wc->get_product_id();
	}

	/**
	 * @return string
	 */
	public function get_format(): string {
		return $this->format;
	}

	/**
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * @return Smart_Action_It
	 */
	public function get_action(): Smart_Action_It {
		return $this->action;
	}

	/**
	 * @return array
	 */
	public function get_columns(): array {
		return $this->columns;
	}


}

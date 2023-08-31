<?php
namespace JET_APB\Resources;

class Appointment {

	const SETTER_PREFIX = 'set_';

	/* include this */
	protected $slot_end   = 0;
	protected $date       = 0;
	protected $slot       = 0;
	protected $provider   = 0;
	protected $service    = 0;
	protected $type       = '';
	protected $status     = 'pending';
	protected $group_ID   = 0;
	protected $user_email = '';
	protected $user_name  = '';
	protected $order_id   = 0;

	/* exclude this */
	protected $ID              = 0;
	protected $price           = 0;
	protected $human_read_date = '';

	protected $columns = [];
	protected $meta    = [];

	/** @var Appointment_Collection */
	protected $collection;

	public function __construct( Appointment_Collection $collection ) {
		$this->set_collection( $collection );
	}

	public function set_collection( Appointment_Collection $collection ): Appointment {
		$this->collection = $collection;

		return $this;
	}

	public function set_from_request( array $appointment ): Appointment {
		foreach ( $appointment as $field => $value ) {
			$callback = array( $this, self::SETTER_PREFIX . $this->rename( $field ) );

			if ( ! is_callable( $callback ) ) {
				continue;
			}

			call_user_func( $callback, $value );
		}
		$this->compute_human_date();
		$this->set_from_columns();

		return $this;
	}

	public function set_from_columns(): Appointment {
		$columns = $this->collection->get_columns();

		if ( ! $columns ) {
			return $this;
		}

		foreach ( $columns as $column ) {
			if ( ! empty( $this->columns[ $column ] ) ) {
				continue;
			}

			$field_name = $this->collection->get_action()->getSettings(
				'appointment_custom_field_' . $column,
				false
			);

			if ( $field_name ) {
				$this->columns[ $column ] = esc_attr(
					$this->collection->get_action()->getRequest( $field_name, '' )
				);
			}

		}

		return $this;
	}

	public function compute_human_date(): Appointment {
		$format = $this->collection->get_format();

		$date = sprintf(
			'%1$s, %2$s - %3$s',
			date_i18n( get_option( 'date_format' ), $this->date ),
			date_i18n( $format, $this->slot ),
			date_i18n( $format, $this->slot_end )
		);

		return $this->set_human_read_date( $date );
	}

	public function set_order_id( int $order_id ): Appointment {
		$this->order_id = $order_id;

		return $this;
	}

	public function set_group_ID( $group_ID ): Appointment {
		$this->group_ID = $group_ID;

		return $this;
	}

	public function set_meta( array $meta = [] ): Appointment {
		
		if ( ! empty( $meta ) ) {
			$this->meta = array_merge( $this->meta, $meta );
		}

		return $this;
	}

	public function set_user_email( string $user_email ): Appointment {
		$this->user_email = $user_email;

		return $this;
	}

	public function set_user_name( string $user_name ): Appointment {
		$this->user_name = $user_name;

		return $this;
	}

	public function set_human_read_date( string $human_read_date ): Appointment {
		$this->human_read_date = $human_read_date;

		return $this;
	}

	public function set_slot_end( $slot_end ): Appointment {
		$this->slot_end = intval( $slot_end );

		return $this;
	}

	public function set_date( $date ): Appointment {
		$this->date = intval( $date );

		return $this;
	}


	public function set_provider( $provider ): Appointment {
		$this->provider = intval( $provider );

		return $this;
	}

	public function set_service( int $service ): Appointment {
		$this->service = intval( $service );

		return $this;
	}

	public function set_slot( $slot ): Appointment {
		$this->slot = intval( $slot );

		return $this;
	}

	public function set_type( string $type ): Appointment {
		$this->type = $type;

		return $this;
	}

	public function set_status( string $status ): Appointment {
		$this->status = $status;

		return $this;
	}

	public function set_price( $price ): Appointment {
		$this->price = intval( $price );

		return $this;
	}

	public function set_ID( int $ID ): Appointment {
		$this->ID = $ID;

		return $this;
	}

	protected function rename( string $property ): string {
		switch ( $property ) {
			case 'slotEnd':
				return 'slot_end';
			default:
				return $property;
		}
	}

	public function to_db_array(): array {
		
		$properties = $this->to_array();
		$exclude    = array( 'ID', 'price', 'human_read_date' );

		foreach ( $exclude as $property ) {
			unset( $properties[ $property ] );
		}

		return $properties;
	}

	public function to_array(): array {
		
		$properties = get_object_vars( $this );
		$exclude    = array( 'collection', 'columns' );

		foreach ( $exclude as $property ) {
			unset( $properties[ $property ] );
		}

		return array_merge( $properties, $this->columns );

	}


}

<?php
namespace JET_APB;

use JET_APB\Tools;

/**
 * Appointment_Price class
 */
class Appointment_Price {

	private $args = null;

	private $service_meta = null;

	private $provider_meta = null;

	public function __construct( $args = [] ) {

		if( ! $this->args ){
			$this->args = $args;
		}

		if( ! $this->service_meta ){
			$this->service_meta = $this->get_post_meta( $this->args['service'] );
		}

		if( ! $this->provider_meta ){
			$this->provider_meta = $this->get_post_meta( $this->args['provider'] );
		}
	}

	public function get_post_meta( $post_id ){
		$post_meta = get_post_meta( $post_id, 'jet_apb_post_meta', true );

		if( empty( $post_meta ) ){
			$post_meta = [
				'price_type' => '_app_price',
				'_app_price' => get_post_meta( $post_id, '_app_price', true ),
			];
		}else{
			$post_meta = $post_meta['meta_settings'];
		}

		return $post_meta;
	}

	public function get_price( $fixed_price = true ){
		$type          = $this->get_type();
		$price         = $this->service_meta[ $type ];

		if( $type !== '_app_price_service' && ! empty( $this->provider_meta[ $type ] ) ){
			$price = $this->provider_meta[ $type ];
		}else{
			$type  = ! empty( $this->service_meta['price_type'] ) ?  $this->service_meta['price_type'] : '_app_price' ;
			$price = $this->service_meta[ $type ];
		}

		switch ( $type ) {
			case '_app_price_hour':
				$slot_duration = Tools::get_time_settings( $this->args['service'], $this->args['provider'], 'default_slot', 0 );
				$hours = ceil( $slot_duration / 60 / 60 );

				$price = $fixed_price ? $price * $hours : $price ;
			break;

			case '_app_price_minute':
				$slot_duration = Tools::get_time_settings( $this->args['service'], $this->args['provider'], 'default_slot', 0 );
				$minutes = ceil( $slot_duration / 60 );

				$price = $fixed_price ? $price * $minutes : $price ;
			break;
		}

		return [ 'price' => $price, 'type' => $type ];
	}

	public function get_type(){
		$type          = '_app_price';

		if( ! empty( $this->service_meta['price_type'] ) ){
			$type = $this->service_meta['price_type'];
		}

		if( ! empty( $this->provider_meta['price_type'] ) ){
			$type = $this->provider_meta['price_type'];
		}

		return $type;
	}
}

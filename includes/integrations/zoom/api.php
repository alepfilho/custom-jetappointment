<?php
namespace JET_APB\Integrations\Zoom;

use JET_APB\Integrations\Manager as Integrations_Manager;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Define workflows manager class
 */
class API {
	
	private $auth_url      = 'https://zoom.us/oauth/token';
	private $base_url      = 'https://api.zoom.us/v2/';
	private $account_id    = null;
	private $client_id     = null;
	private $client_secret = null;

	public $last_error = '';

	public function __construct( $account_id = '', $client_id = '', $client_secret = '' ) {
		$this->account_id    = $account_id;
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
	}

	public function get_token() {

		$transient_key = 'jet_apb_zoom_token';
		$token         = get_transient( $transient_key );
		
		if ( ! $token ) {

			$url = add_query_arg( 
				[ 'grant_type' => 'account_credentials', 'account_id' => $this->account_id ], 
				$this->auth_url
			);

			$response = wp_remote_post( $url, [
				'headers' => [
					'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
				],
			] );

			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body, true );

			if ( ! $body ) {
				$this->last_error = 'Can`t connect to Zoom Server please check your credentials';
				return false;
			}

			if ( ! empty( $body['access_token'] ) ) {
				$token = $body['access_token'];
				set_transient( $transient_key, $token, 45 * MINUTE_IN_SECONDS );
			} elseif ( ! empty( $body['error'] ) ) {
				switch ( $body['error'] ) {
					case 'invalid_request':

						if ( 'Bad Request' === $body['reason'] ) {
							$this->last_error = 'Invalid request parameters. Please check your Client ID and Client Secret';
						} elseif ( 'Internal Error' === $body['reason'] ) {
							$this->last_error = 'Invalid request parameters. Please check your Account ID';
						} else {
							$this->last_error = $body['reason'];
						}
						
						break;
					
					default:
						$this->last_error = $body['reason'];
						break;
				}
			} else {
				$this->last_error = 'Unknown Error';
			}

		}

		return $token;
	}

	public function delete() {
		wp_remote_request( 'http://www.example.com/index.php',
			array(
				'method' => 'DELETE'
			)
		);
	}

	public function post( $endpoint, $args = [], $request = [] ) {
		
		$endpoint = ltrim( $endpoint, '/' );
		$token    = $this->get_token();

		if ( ! $token ) {
			return new \WP_Error( 401, $this->last_error );
		}

		$request_args = array_merge( [
			'headers' => [ 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token ],
		], $request );

		if ( ! empty( $args ) ) {
			$request_args['body'] = json_encode( $args );
		}

		if ( empty( $request_args['method'] ) ) {
			$request_args['method'] = 'POST';
		}
		
		$response = wp_remote_request( $this->base_url . $endpoint, $request_args );

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );

		return $body;

	}

}

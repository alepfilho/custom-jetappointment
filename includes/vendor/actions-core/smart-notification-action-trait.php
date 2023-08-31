<?php


namespace JET_APB\Vendor\Actions_Core;


trait Smart_Notification_Action_Trait {

	protected $_requestData;
	protected $_instance;
	protected $_settings;

	public function getRequest( $key = '', $ifNotExist = false ) {
		if ( ! $key ) {
			return $this->_requestData;
		}
		return isset( $this->_requestData[ $key ] ) ? $this->_requestData[ $key ] : $ifNotExist;
	}

	public function issetRequest( $key ) {
		return isset( $this->_requestData[ $key ] );
	}

	public function getInstance() {
		return $this->_instance;
	}

	public function getSettings( $key = '', $ifNotExist = false ) {
		if ( ! $key ) {
			return $this->_settings;
		}
		return isset( $this->_settings[ $key ] ) ? $this->_settings[ $key ] : $ifNotExist;
	}

}
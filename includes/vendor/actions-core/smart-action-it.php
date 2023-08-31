<?php


namespace JET_APB\Vendor\Actions_Core;


interface Smart_Action_It {

	public function run_action();

	public function setRequest( $key, $value );

	public function hasGateway();

	public function getFormId();

	public function filterQueryArgs( callable $callable );

	public function isAjax();

	public function getFieldNameByType( $field_type );

	public function getAppointments();

	public function getRequest( $key = '', $ifNotExist = false );

	public function issetRequest( $key );

	public function getInstance();

	public function getSettings( $key = '', $ifNotExist = false );

}
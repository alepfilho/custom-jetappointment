<?php
namespace JET_APB\Workflows;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class Base_Object {

    /**
     * Object ID
     * @return [type] [description]
     */
    abstract public function get_id();

    /**
     * Object name
     * 
     * @return [type] [description]
     */
    abstract public function get_name();

}

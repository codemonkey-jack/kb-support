<?php

/**
 * Class KBS_Backwards_Compatibility
 * A backwards compatibility class
 */
class KBS_Backwards_Compatibility {

	/**
	 * Holds the class object.
	 *
	 * @since 1.6.0
	 *
	 * @var object
	 */
	public static $instance;


	/**
	 * Primary class constructor.
	 *
	 * @since 1.6.0
	 */
	public function __construct(){

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The KBS_Backwards_Compatibility object.
	 * @since 1.6.0
	 *
	 */
	public static function get_instance(){

		if ( !isset( self::$instance ) && !( self::$instance instanceof KBS_Backwards_Compatibility ) ){
			self::$instance = new KBS_Backwards_Compatibility();
		}

		return self::$instance;

	}

}

KBS_Backwards_Compatibility::get_instance();

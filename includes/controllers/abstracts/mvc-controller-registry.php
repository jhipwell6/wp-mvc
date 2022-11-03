<?php

namespace WP_MVC\Controllers\Abstracts;

if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class MVC_Controller_Registry
{
	protected static $instances = [];

	public static function instance()
	{
		$class = get_called_class();
		if ( ! isset( self::$instances[$class] ) || ! self::$instances[$class] instanceof $class ) {
			self::$instances[$class] = new static();
		}
		return static::$instances[$class];
	}

}

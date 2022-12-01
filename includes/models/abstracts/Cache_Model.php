<?php

namespace WP_MVC\Models\Abstracts;

if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class Cache_Model
{
	use \WP_MVC\Traits\Cacheable_Trait;
	
	public const TTL_INSTANT = 0;
	public const TTL_SHORT = 28800;
	public const TTL_LONG = 108000;
	
	public function get( $prop )
	{
		$getter = "get_{$prop}";
		return $this->{$getter}();
	}
	
	public function set( $prop, $value, $ttl = self::TTL_SHORT )
	{
		$setter = "set_{$prop}";
		return $this->{$setter}( $value, $ttl );
	}
	
	protected function get_prop( $prop )
	{
		if ( ! $this->has_prop( $prop ) )
			return false;

		if ( null === $this->{$prop} || ( is_array( $this->{$prop} ) && empty( $this->{$prop} ) ) ) {
			$this->{$prop} = $this->get_cache( $prop );
		}
		return $this->{$prop};
	}
	
	protected function set_prop( $prop, $value, $ttl = self::TTL_SHORT )
	{
		if ( ! $this->has_prop( $prop ) )
			return false;
		
		$this->{$prop} = $value;
		$this->set_cache( $prop, $value, $ttl );
		return $this->{$prop};
	}
	
	public function has_prop( $prop )
	{
		return property_exists( $this, $prop );
	}
	
}
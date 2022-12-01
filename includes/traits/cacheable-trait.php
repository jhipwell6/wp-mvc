<?php

namespace WP_MVC\Traits;

if ( ! defined( 'ABSPATH' ) )
	exit;

trait Cacheable_Trait
{
	protected function get_cache( $key )
	{
		return json_decode( get_transient( $key ), true );
	}

	protected function set_cache( $key, $data, $ttl = 28800 )
	{
		$data = is_string( $data ) ? $data : json_encode( $data );
		return set_transient( $key, $data, $ttl );
	}

	protected function flush_cache( $key )
	{
		return delete_transient( $key );
	}
}
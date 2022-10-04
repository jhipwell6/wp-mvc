<?php

namespace WP_MVC\Core\Abstracts;

if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class Service
{
	protected $api_url;
	protected $cache_key;
	protected $default_args = array();

	public function __construct()
	{
		
	}

	abstract public function init( $service_class );

	abstract public function import( $items );

	protected function get( $args = array(), $force_update = false )
	{
		$force_update = ! $force_update ? filter_input( INPUT_GET, 'force_update' ) : $force_update;
		$cache_key = $this->get_cache_key() . $this->hash_args( $args );

		// maybe flush cache
		if ( $force_update ) {
			$this->flush_cache( $cache_key );
		} else {
			// check cache
			if ( $cache = $this->get_cache( $cache_key ) ) {
				return $cache;
			}
		}

		$api_url = isset( $args['id'] ) ? $this->get_api_url() . $args['id'] : $this->get_api_url();
		$response = wp_remote_get( $api_url, $args );

		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );

			// set cache
			$this->set_cache( $cache_key, $body );

			return json_decode( $body, true );
		}

		return array();
	}

	protected function get_cache( $key )
	{
		return json_decode( get_transient( $key ), true );
	}

	protected function set_cache( $key, $data )
	{
		$data = is_string( $data ) ? $data : json_encode( $data );
		return set_transient( $key, $data, 28800 );
	}

	protected function flush_cache( $key )
	{
		return delete_transient( $key );
	}

	protected function get_api_url()
	{
		return $this->api_url;
	}

	public function set_api_url( $api_url )
	{
		return $this->api_url = $api_url;
	}

	protected function get_cache_key()
	{
		return $this->cache_key;
	}

	protected function set_cache_key( $cache_key )
	{
		return $this->cache_key = $cache_key;
	}

	protected function get_default_args()
	{
		return $this->default_args;
	}

	public function set_default_args( $default_args = array() )
	{
		return $this->default_args = $default_args;
	}

	protected function get_args( $args = array() )
	{
		return wp_parse_args( $args, $this->get_default_args() );
	}

	protected function hash_args( $args )
	{
		return md5( json_encode( $args ) );
	}

}

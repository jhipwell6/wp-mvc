<?php

namespace WP_MVC\Models\Abstracts;

if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class Abstract_Model
{
	public $id = 0;

	public function get_id()
	{
		return $this->id;
	}

	public function set_id( $id )
	{
		$this->id = absint( $id );
	}

	abstract public function get_hidden();

	abstract protected function get_meta( $prop );

	protected function get_prop( $prop )
	{
		if ( ! $this->has_prop( $prop ) )
			return false;

		if ( null === $this->{$prop} || ( is_array( $this->{$prop} ) && empty( $this->{$prop} ) ) ) {
			if ( is_callable( array( $this, 'is_wp_prop' ) ) && $this->is_wp_prop( $prop ) ) {
				$getter = $this->get_getter( $prop );
				if ( is_callable( array( $this, $getter ) ) ) {
					$this->{$prop} = $this->{$getter}();
				}
			} else {
				$this->{$prop} = $this->get_meta( $prop );
			}
		}
		return $this->{$prop};
	}

	protected function get_props()
	{
		foreach ( get_object_vars( $this ) as $prop => $value ) {
			$getter = $this->get_getter( $prop );
			if ( is_callable( array( $this, $getter ) ) ) {
				$this->{$getter}();
			}
		}
	}

	public function get_property_keys()
	{
		return array_keys( $this->to_array() );
	}

	public function has_prop( $prop )
	{
		return property_exists( $this, $prop );
	}

	protected function get_getter( $prop )
	{
		return is_callable( array( $this, 'map_property' ) ) ? 'get_' . $this->map_property( $prop ) : 'get_' . $prop;
	}

	protected function get_setter( $prop )
	{
		return is_callable( array( $this, 'map_property' ) ) ? 'set_' . $this->map_property( $prop ) : 'set_' . $prop;
	}

	public function to_array( $exclude = array() )
	{
		$exclusions = wp_parse_args( $exclude, $this->get_hidden() );
		$vars = get_object_vars( $this );
		array_walk( $vars, array( $this, 'deep_objects_to_array' ) );
		return array_diff_key( $vars, array_flip( $exclusions ) );
	}

	public function to_json( $exclude = array(), $flags = 0 )
	{
		return wp_json_encode( $this->to_array( $exclude ), $flags );
	}

	public function to_csv_array( $include = array() )
	{
		$include = empty( $include ) ? $this->get_property_keys() : $include;
		$arr = $this->to_array();
		return array_map( function ( $key ) use ( $arr ) {
			return is_array( $arr[$key] ) ? $this->deep_implode( '|', $arr[$key] ) : (string) $arr[$key];
		}, $include );
	}

	private function deep_objects_to_array( &$value, $key )
	{
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				if ( is_object( $v ) ) {
					$value[$k] = $v->to_array();
				}
			}
		}
		return $value;
	}

	private function deep_implode( $separator, $data )
	{
		return implode( $separator, array_map( function ( $value ) use ( $separator ) {
			return is_array( $value ) ? wp_json_encode( $value ) : $value;
		}, $data ) );
	}

}

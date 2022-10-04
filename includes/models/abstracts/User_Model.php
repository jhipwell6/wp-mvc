<?php
namespace WP_MVC\Models\Abstracts;

if ( ! defined('ABSPATH') )	
	exit;

abstract class User_Model
{
	protected $id = 0;
    protected $user_object;
	protected $exists = false;
	
	final public function __construct( $user = 0 )
    {
		if ( is_numeric( $user ) && $user > 0 ) {
			$this->set_id( $user );
		} elseif ( $user instanceof self ) {
			$this->set_id( $user->get_id() );
		} elseif ( $user instanceof \WP_User ) {
			$this->set_id( $user->ID );
        } else {
            // doesn't exist yet
			return $this;
        }
		
		if ( $this->get_id() > 0 ) {
			$this->read();
		}

        return $this;
	}
	
	public function get_id()
	{
		return $this->id;
	}
	
	public function set_id( $id )
	{
		$this->id = absint( $id );
	}
	
	public function get_user_object()
	{
		return $this->user_object;
	}
	
	/*
	 * CRUD Methods
	 */
	public function read( $id = 0 )
	{
		$id = ! $id ? $this->get_id() : $id;
		if ( ! $id ) {
			throw new \Exception( "No " . static::class . " user found with ID: {$id}" );
		}
		
		$user_object = get_user_by( 'ID', $id );
		if ( $user_object ) {
			$this->user_object = $user_object;
			$this->exists = true;

			// fill properties
			$this->get_props();
		}
	}
	
	/*
	 * Helpers
	 */
	protected function get_props()
	{
		foreach ( get_object_vars( $this ) as $prop => $value ) {
			$getter = "get_{$prop}";
			if ( is_callable( array( $this, $getter ) ) ) {
				$this->{$getter}();
			}
		}
	}
	
	protected function get_prop( $prop )
	{
		if ( ! $this->has_prop( $prop ) )
			return false;
		
		if ( null === $this->{$prop} || empty( $this->{$prop} ) ) {
			$this->{$prop} = $this->get_meta( $prop );
		}
        return $this->{$prop};
	}
	
	public function has_prop( $prop )
	{
		return property_exists( $this, $prop );
	}
	
	private function get_meta( $prop )
	{
		// optional ACF support
		if ( function_exists('get_field') ) {
			return get_field( $prop, 'user_' . $this->get_id() );
		} else {
			return get_user_meta( $this->get_id(), $prop, true );
		}
	}
	
	protected function set_prop( $prop, $value )
	{
		if ( $this->has_prop( $prop ) ) {
			$this->{$prop} = $value;
			return $this->{$prop};
		}
		return false;
	}
	
	public function save_meta( $prop, $value )
	{
		// ensures only allowable props are saved
		if ( $this->can_save_meta( $prop, $value ) ) {
			// allow extending classes to hijack per property
			$saver = "save_{$prop}_meta";
			if ( is_callable( array( $this, $saver ) ) ) {
				return $this->{$saver}( $value );
			}

			// optional ACF support
			if ( function_exists('update_field') ) {
				update_field( $prop, $value, 'user_' . $this->get_id() );
			} else {
				update_user_meta( $this->get_id(), $prop, $value );
			}
		}
	}
	
	private function can_save_meta( $prop, $value )
	{
		$setter = "set_$prop";
		return null !== $value && is_callable( array( $this, $setter ) );
	}
}
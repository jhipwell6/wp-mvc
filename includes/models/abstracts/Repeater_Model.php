<?php

namespace WP_MVC\Models\Abstracts;

if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class Repeater_Model extends Abstract_Model
{
	private $raw_data;
	private $Post_Model;
	private $hidden;

	/**
	 * constructor
	 */
	public function __construct( $id, $raw_data, $Post_Model = null )
	{
		$this->set_id( $id );
		$this->set_raw_data( $raw_data );
		if ( $Post_Model ) {
			$this->set_post_model( $Post_Model );
		}

		$this->get_props();
		return $this;
	}

	public function get_hidden()
	{
		return wp_parse_args( $this->hidden, array(
			'hidden',
			'raw_data',
			'Post_Model',
			) );
	}

	protected function get_meta( $prop )
	{
		if ( isset( $this->raw_data[$prop] ) ) {
			return $this->raw_data[$prop];
		}
		return false;
	}

	protected function get_raw_data( $prop = null )
	{
		return ( $prop && isset( $this->raw_data[$prop] ) ) ? $this->raw_data[$prop] : $this->raw_data;
	}

	private function set_raw_data( $raw_data )
	{
		$this->raw_data = $raw_data;
		return $this->raw_data;
	}

	protected function get_post_model()
	{
		return $this->Post_Model;
	}

	private function set_post_model( $Post_Model )
	{
		$this->Post_Model = $Post_Model;
		return $this->Post_Model;
	}

}

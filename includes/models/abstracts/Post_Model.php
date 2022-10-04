<?php

namespace WP_MVC\Models\Abstracts;

if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class Post_Model extends Abstract_Model
{
	private $post_type;
	private $unique_key;
	private $wp_props;
	private $aliases;
	private $hidden;
	protected $post_object;
	protected $updated_at;
	protected $exists = false; // only updated by the "read" method

	final public function __construct( $post = 0 )
	{
		// provided as CONSTANTS in the extending class
		// which forces them to be set
		$this->post_type = static::POST_TYPE;
		$this->unique_key = static::UNIQUE_KEY;
		$this->wp_props = static::WP_PROPS;
		$this->aliases = static::ALIASES;
		$this->hidden = static::HIDDEN;

		if ( is_numeric( $post ) && $post > 0 ) {
			$this->set_id( $post );
		} elseif ( $post instanceof self ) {
			$this->set_id( $post->get_id() );
		} elseif ( $post instanceof \WP_Post ) {
			$this->set_id( $post->ID );
		} else {
			// doesn't exist yet
			return $this;
		}

		if ( $this->get_id() > 0 ) {
			$this->read();
		}

		return $this;
	}

	public function get_post_type()
	{
		return $this->post_type;
	}

	public function get_unique_key()
	{
		return $this->unique_key;
	}

	public function has_unique_key()
	{
		return $this->get_unique_key() != '' ? true : false;
	}

	public function get_wp_props()
	{
		return $this->wp_props;
	}

	public function get_wp_prop( $prop )
	{
		return $this->wp_props[$prop];
	}

	public function get_aliases()
	{
		return $this->aliases;
	}

	public function get_hidden()
	{
		return wp_parse_args( $this->hidden, array(
			'post_type',
			'unique_key',
			'post_object',
			'aliases',
			'hidden',
			'wp_props',
			'exists',
			) );
	}

	public function get_post_object()
	{
		return $this->post_object;
	}

	public function get_post_title()
	{
		$prop = $this->get_wp_prop( 'post_title' );
		if ( null === $this->{$prop} ) {
			$this->{$prop} = get_the_title( $this->get_id() );
		}
		return $this->{$prop};
	}

	public function get_post_content( $apply_filters = false )
	{
		$prop = $this->get_wp_prop( 'post_content' );
		if ( null === $this->{$prop} ) {
			if ( $apply_filters ) {
				$this->{$prop} = apply_filters( 'the_content', get_the_content( null, false, $this->get_id() ) );
			} else {
				$this->{$prop} = get_the_content( null, false, $this->get_id() );
			}
		}
		return $this->{$prop};
	}

	public function get_post_date( $format = '' )
	{
		$prop = $this->get_wp_prop( 'post_date' );
		if ( null === $this->{$prop} ) {
			$this->{$prop} = get_the_date( $format, $this->get_id() );
		}
		return $this->{$prop};
	}

	public function get_updated_at( $format = 'Y-m-d h:i:s' )
	{
		if ( null === $this->updated_at ) {
			$this->updated_at = get_the_modified_time( $format, $this->get_id() );
		}
		return $this->updated_at;
	}

	public function save_post_title( $value )
	{
		$prop = $this->get_wp_prop( 'post_title' );
		$post_id = $this->save_wp_prop( $prop, $value, 'post_title' );
		if ( $post_id ) {
			$this->{$prop} = get_the_title( $post_id );
		}
	}

	public function save_post_content( $value )
	{
		$prop = $this->get_wp_prop( 'post_content' );
		$post_id = $this->save_wp_prop( $prop, $value, 'post_content' );
		if ( $post_id ) {
			$this->{$prop} = apply_filters( 'the_content', get_the_content( null, false, $post_id ) );
		}
	}

	public function save_post_date( $value, $return_format = '' )
	{
		$prop = $this->get_wp_prop( 'post_date' );
		$post_id = $this->save_wp_prop( $prop, $value, 'post_date' );
		if ( $post_id ) {
			$this->{$prop} = get_the_date( $return_format, $post_id );
		}
	}

	private function save_wp_prop( $prop, $value, $wp_prop )
	{
		$prop = $this->get_wp_prop( $wp_prop );
		$getter = "get_{$prop}";
		if ( $this->{$getter}() != '' ) {
			$args = array(
				'ID' => $this->get_id(),
				$wp_prop => $value
			);
			if ( $wp_prop == 'post_date' ) {
				$args['post_date_gmt'] = get_gmt_from_date( $value );
			}
			$post_id = wp_update_post( $args );
			return $post_id;
		}
		return false;
	}

	protected function set_prop( $prop, $value, $allowed_keys = array() )
	{
		if ( $this->has_prop( $prop ) || array_key_exists( $prop, $this->get_aliases() ) ) {
			if ( ! empty( $allowed_keys ) ) {
				$this->{$prop} = $this->sanitize_array( $value, $allowed_keys );
			} else {
				$this->{$prop} = $value;
			}
			return $this->{$prop};
		}
		return false;
	}

	/*
	 * Alias method for set_props
	 */

	public function make( $props )
	{
		$this->set_props( $props );
	}

	public function set_props( $props )
	{
		$props = $this->map_properties( $props );
		foreach ( $props as $prop => $value ) {
			if ( is_null( $value ) || $prop == 'id' ) {
				continue;
			}
			$setter = "set_$prop";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->{$setter}( $value );
			}
		}
	}

	public function save( $args = array() )
	{
		if ( $this->exists ) {
			// update
			return $this->update();
		} else {
			// create
			return $this->create( $args );
		}
	}

	public function create( $args = array() )
	{
		if ( ! $this->should_create() ) {
			return $this;
		}

		$default_args = array(
			'post_type' => $this->get_post_type(),
			'post_status' => 'publish'
		);

		$args = wp_parse_args( $args, $default_args );

		// extract the title from the data and use it to when creating the post
		$title_prop = $this->get_wp_prop( 'post_title' );
		$title_getter = "get_{$title_prop}";
		$args['post_title'] = $this->{$title_getter}() != '' ? $this->{$title_getter}() : ' ';

		// extract the content from the data and use it to when creating the post
		$content_prop = $this->get_wp_prop( 'post_content' );
		$content_getter = "get_{$content_prop}";
		$args['post_content'] = $this->{$content_getter}() != '' ? $this->{$content_getter}() : ' ';

		// extract the date from the data and use it to when creating the post
		$date_prop = $this->get_wp_prop( 'post_date' );
		$date_getter = "get_{$date_prop}";
		$date = $this->to_datetime( $this->{$date_getter}() );
		$args['post_date'] = $date;
		$args['post_date_gmt'] = get_gmt_from_date( $date );

		$post_id = wp_insert_post( $args );

		// Check whether call was successful
		if ( ( ! is_int( $post_id ) ) || 0 === $post_id ) {
			throw new \Exception( 'Invalid post ID returned' );
		}

		$data = $this->to_array();

		$instance = new static( $post_id );
		$instance->set_props( $data );
		$instance->save_all_meta();

		$instance->after_save();

		return $instance;
	}

	public function read( $id = 0 )
	{
		$id = ! $id ? $this->get_id() : $id;
		if ( ! $id ) {
			throw new \Exception( "No " . static::class . " post found with ID: {$id}" );
		}

		// Post Object
		$args = array(
			'post_type' => $this->get_post_type(),
			'p' => $id,
			'post_status' => 'any'
		);
		$query = new \WP_Query( $args );
		$post_object = ( ! empty( $query->posts[0] ) ) ? $query->posts[0] : false;
		if ( $post_object ) {
			$this->post_object = $post_object;
			$this->exists = true;

			// fill properties
			$this->get_props();
		}
	}

	public function update()
	{
		$this->save_all_meta();
		$this->after_save();

		return $this;
	}

	public function delete( $id = 0 )
	{
		$id = $id > 0 ? $id : $this->get_id();
		if ( $id > 0 ) {
			wp_trash_post( $id );
		}
	}

	public function should_create()
	{
		return true;
	}

	public function after_save()
	{
		// can be implemented by the extending class
	}

	public function exists( $skip_lookup = false )
	{
		// if we already read it from the database we know it exists
		if ( $this->exists ) {
			return true;
		}

		if ( $skip_lookup || ! $this->has_unique_key() ) {
			return $this->exists;
		}

		$unique_value = false;
		$unique_key = $this->get_unique_key();
		$getter = "get_{$unique_key}";
		if ( is_callable( array( $this, $getter ) ) ) {
			$unique_value = $this->{$getter}();
		}

		if ( $unique_value === null )
			return false;

		if ( false === $unique_value ) {
			throw new \Exception( "Missing or invalid value provided for checking " . static::class . " existence." );
		}

		// get post by unique key
		$instance = $this->get_by_unique_key( $unique_value );

		return $instance->exists;
	}

	public function equals( $Post )
	{
		$instance = null;
		if ( is_array( $Post ) ) {
			$instance = new static();
			$instance->set_props( $Post );
		} else if ( $Post instanceof static ) {
			$instance = $Post;
		}

		return $instance && ( $this === $instance ) ? true : false;
	}

	public function get_by_unique_key( $unique_value )
	{
		if ( ! $this->has_unique_key() )
			return $this;

		$posts = get_posts( array(
			'post_type' => $this->get_post_type(),
			'posts_per_page' => 1,
			'post_status' => 'any',
			'meta_key' => $this->get_unique_key(),
			'meta_value' => $unique_value
			) );

		$instance = $this;
		if ( ! empty( $posts ) ) {
			$instance = new static( $posts[0]->ID );
		}

		return $instance;
	}

	public function save_all_meta()
	{
		foreach ( get_object_vars( $this ) as $prop => $value ) {
			$this->save_meta( $prop, $value );
		}
	}

	/*
	 * Helpers
	 */

	private function map_properties( $props )
	{
		if ( ! empty( $this->get_aliases() ) ) {
			foreach ( $this->get_aliases() as $key => $new_key ) {
				if ( isset( $props[$key] ) ) {
					$props[$new_key] = $props[$key];
					unset( $props[$key] );
				}
			}
		}

		foreach ( $props as $key => $value ) {
			$new_key = $this->decamelize( $key );
			if ( $new_key != $key ) {
				$props[$new_key] = $props[$key];
				unset( $props[$key] );
			}
		}

		return $props;
	}

	protected function is_wp_prop( $prop )
	{
		return in_array( $prop, array_values( $this->get_wp_props() ) );
	}

	private function sanitize_array( $arr = array(), $allowed_keys = array() )
	{
		if ( empty( $arr ) ) {
			return $arr;
		}

		$arr = ! is_array( $arr ) ? (array) $arr : $arr;
		$data = $this->get_allowed_data( $arr, $allowed_keys );
		return ! empty( $allowed_keys ) ? $this->get_allowed_data( $arr, $allowed_keys ) : $arr;
	}

	protected function decamelize( $string )
	{
		return strtolower( preg_replace( array( '/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/' ), '$1_$2', $string ) );
	}

	private function get_allowed_data( $arr, $allowed_keys = array() )
	{
		if ( empty( $arr ) ) {
			return $arr;
		}

		if ( is_assoc( $arr ) ) {
			return array_intersect_key( $arr, array_flip( $allowed_keys ) );
		}

		$new_arr = array();
		foreach ( $arr as $nested_arr ) {
			$new_arr[] = $this->get_allowed_data( $nested_arr, $allowed_keys );
		}
		return $new_arr;
	}

	protected function get_meta( $prop )
	{
		// optional ACF support
		if ( function_exists( 'get_field' ) ) {
			return get_field( $prop, $this->get_id() );
		} else {
			return get_post_meta( $this->get_id(), $prop, true );
		}
	}

	protected function can_save_meta( $prop, $value )
	{
		$setter = "set_$prop";
		return null !== $value && is_callable( array( $this, $setter ) );
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
			if ( function_exists( 'update_field' ) ) {
				update_field( $prop, $value, $this->get_id() );
			} else {
				update_post_meta( $this->get_id(), $prop, $value );
			}
		}
	}

	/*
	 * sets and saves the value for a given property
	 */

	public function update_prop( $prop, $value )
	{
		$this->set_prop( $prop, $value );
		$this->save_meta( $prop, $value );
	}

	/*
	 * Helpers
	 */

	protected function to_bool( $value )
	{
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	protected function to_datetime( $value, $format = 'Y-m-d h:i:s' )
	{
		if ( $value ) {
			$date = new \DateTime( $value );
			$value = $date->format( $format );
		}
		return $value;
	}

	protected function to_currency( $value, $digits = 2, $no_symbol = false )
	{
		$formatter = new \NumberFormatter( 'en_US', \NumberFormatter::CURRENCY );
		$formatter->setAttribute( \NumberFormatter::FRACTION_DIGITS, $digits );
		if ( $no_symbol ) {
			$formatter->setSymbol( \NumberFormatter::CURRENCY_SYMBOL, '' );
		}
		return $formatter->formatCurrency( floatval( $value ), 'USD' );
	}

}

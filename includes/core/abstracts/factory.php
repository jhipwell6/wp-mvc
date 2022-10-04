<?php

namespace WP_MVC\Core\Abstracts;

use \ArrayAccess;
use \Countable;
use \IteratorAggregate;

if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class Factory implements ArrayAccess, Countable, IteratorAggregate
{
	protected $items = array();

	/**
	 * Get all of the items in the collection.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->items;
	}

	/**
	 * Remove an item from the collection by key.
	 *
	 * @param  mixed  $key
	 * @return void
	 */
	public function forget( $key )
	{
		$this->offsetUnset( $key );
	}

	/**
	 * Get an item from the collection by key.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get( $key, $default = null )
	{
		if ( $this->offsetExists( $key ) ) {
			return $this->items[$key];
		}
		return $default;
	}

	/**
	 * Add item to the collection
	 *
	 * @param  mixed  $item
	 * @return $this
	 */
	public function add( $item )
	{
		$this->items[] = $item;
		return $this;
	}

	/**
	 * Determine if an item exists in the collection by key.
	 *
	 * @param  mixed  $key
	 * @return bool
	 */
	public function has( $key )
	{
		return $this->offsetExists( $key );
	}

	/**
	 * Run a filter over each of the items.
	 *
	 * @param  callable  $callback
	 * @return static
	 */
	public function filter( $callback )
	{
		return array_filter( $this->items, $callback );
	}

	/**
	 * Determine if an item exists in the collection.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $value
	 * @return bool
	 */
	public function contains( $key, $value = null )
	{
		if ( func_num_args() == 2 ) {
			return $this->contains( function ( $k, $item ) use ( $key, $value ) {
					return data_get( $item, $key ) == $value;
				} );
		}

		if ( $this->useAsCallable( $key ) ) {
			return ! is_null( $this->first( $key ) );
		}

		return in_array( $key, $this->items );
	}

	/**
	 * Filter items by the given key value pair.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @param  bool  $strict
	 * @return static
	 */
	public function where( $key, $value, $strict = true )
	{
		$result = $this->filter( function ( $item ) use ( $key, $value, $strict ) {
			return $strict ? data_get( $item, $key ) === $value : data_get( $item, $key ) == $value;
		} );

		if ( count( $result ) === 1 )
			return current( $result );

		return $result;
	}

	/**
	 * Search the collection for a given value and return the corresponding key if successful.
	 *
	 * @param  mixed  $value
	 * @param  bool   $strict
	 * @return mixed
	 */
	public function search( $value, $strict = false )
	{
		return array_search( $value, $this->items, $strict );
	}

	/**
	 * Get the first item from the collection.
	 *
	 * @param  callable   $callback
	 * @param  mixed      $default
	 * @return mixed|null
	 */
	public function first( $callback = null, $default = null )
	{
		if ( is_null( $callback ) ) {
			return count( $this->items ) > 0 ? reset( $this->items ) : null;
		}
		return array_first( $this->items, $callback, $default );
	}

	/**
	 * Get the last item from the collection.
	 *
	 * @return mixed|null
	 */
	public function last()
	{
		return count( $this->items ) > 0 ? end( $this->items ) : null;
	}

	/**
	 * Sort the collection using the given callback.
	 *
	 * @param  callable|string  $callback
	 * @param  int   $options
	 * @param  bool  $descending
	 * @return $this
	 */
	public function sortBy( $callback, $options = SORT_REGULAR, $descending = false )
	{
		$results = array();

		if ( ! $this->useAsCallable( $callback ) ) {
			$callback = $this->valueRetriever( $callback );
		}
		// First we will loop through the items and get the comparator from a callback
		// function which we were given. Then, we will sort the returned values and
		// and grab the corresponding values for the sorted keys from this array.
		foreach ( $this->items as $key => $value ) {
			$results[$key] = $callback( $value, $key );
		}
		$descending ? arsort( $results, $options ) : asort( $results, $options );
		// Once we have sorted all of the keys in the array, we will loop through them
		// and grab the corresponding model so we can set the underlying items list
		// to the sorted version. Then we'll just return the collection instance.
		foreach ( array_keys( $results ) as $key ) {
			$results[$key] = $this->items[$key];
		}
		$this->items = $results;
		return $this;
	}

	public function offsetSet( $offset, $value )
	{
		if ( is_null( $offset ) ) {
			$this->items[] = $value;
		} else {
			$this->items[$offset] = $value;
		}
	}

	public function offsetExists( $offset )
	{
		return isset( $this->items[$offset] );
	}

	public function offsetUnset( $offset )
	{
		unset( $this->items[$offset] );
	}

	public function offsetGet( $offset )
	{
		return isset( $this->items[$offset] ) ? $this->items[$offset] : null;
	}

	public function getIterator()
	{
		return new ArrayIterator( $this->items );
	}

	public function count()
	{
		return count( $this->items );
	}

	/**
	 * Determine if the collection is empty or not.
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty( $this->items );
	}

	/**
	 * Determine if the given value is callable, but not a string.
	 *
	 * @param  mixed  $value
	 * @return bool
	 */
	protected function useAsCallable( $value )
	{
		return ! is_string( $value ) && is_callable( $value );
	}

	/**
	 * Get a value retrieving callback.
	 *
	 * @param  string  $value
	 * @return \Closure
	 */
	protected function valueRetriever( $value )
	{
		return function ( $item ) use ( $value ) {
			return data_get( $item, $value );
		};
	}

}

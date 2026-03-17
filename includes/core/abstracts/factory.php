<?php

namespace WP_MVC\Core\Abstracts;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;
use ArrayIterator;

if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class Factory implements ArrayAccess, Countable, IteratorAggregate
{
	protected $items = [];

	public function all()
	{
		return $this->items;
	}

	public function forget( $key )
	{
		$this->offsetUnset( $key );
	}

	public function get( $key, $default = null )
	{
		return $this->offsetExists( $key ) ? $this->items[$key] : $default;
	}

	public function add( $item )
	{
		$this->items[] = $item;
		return $this;
	}

	public function has( $key )
	{
		return $this->offsetExists( $key );
	}

	public function filter( $callback )
	{
		$instance = new static;
		$instance->items = array_filter( $this->items, $callback );
		return $instance;
	}

	public function map( $callback )
	{
		$instance = new static;
		$instance->items = array_map( $callback, $this->items );
		return $instance;
	}

	public function values()
	{
		$instance = new static;
		$instance->items = array_values( $this->items );
		return $instance;
	}

	public function pluck( $key )
	{
		return array_map( function ( $item ) use ( $key ) {
			return data_get( $item, $key );
		}, $this->items );
	}

	public function keyBy( $key )
	{
		$results = [];

		foreach ( $this->items as $item ) {
			$results[data_get( $item, $key )] = $item;
		}

		$instance = new static;
		$instance->items = $results;

		return $instance;
	}

	public function groupBy( $key )
	{
		$results = [];

		foreach ( $this->items as $item ) {
			$groupKey = data_get( $item, $key );
			$results[$groupKey][] = $item;
		}

		return $results;
	}

	public function contains( $key, $value = null )
	{
		if ( func_num_args() == 2 ) {
			return $this->contains( function ( $item ) use ( $key, $value ) {
					return data_get( $item, $key ) == $value;
				} );
		}

		if ( $this->useAsCallable( $key ) ) {
			return ! is_null( $this->first( $key ) );
		}

		return in_array( $key, $this->items );
	}

	public function where( $key, $value, $strict = true )
	{
		return $this->filter( function ( $item ) use ( $key, $value, $strict ) {
				return $strict ? data_get( $item, $key ) === $value : data_get( $item, $key ) == $value;
			} );
	}

	public function firstWhere( $key, $value, $strict = true )
	{
		foreach ( $this->items as $item ) {

			$match = $strict ? data_get( $item, $key ) === $value : data_get( $item, $key ) == $value;

			if ( $match ) {
				return $item;
			}
		}

		return null;
	}

	public function search( $value, $strict = false )
	{
		return array_search( $value, $this->items, $strict );
	}

	public function first( $callback = null, $default = null )
	{
		if ( is_null( $callback ) ) {
			return count( $this->items ) ? reset( $this->items ) : $default;
		}

		foreach ( $this->items as $key => $item ) {
			if ( $callback( $item, $key ) ) {
				return $item;
			}
		}

		return $default;
	}

	public function last()
	{
		return count( $this->items ) ? end( $this->items ) : null;
	}

	public function sort_by( $callback, $options = SORT_REGULAR, $descending = false )
	{
		$results = [];

		if ( ! $this->useAsCallable( $callback ) ) {
			$callback = $this->valueRetriever( $callback );
		}

		foreach ( $this->items as $key => $value ) {
			$results[$key] = $callback( $value, $key );
		}

		$descending ? arsort( $results, $options ) : asort( $results, $options );

		foreach ( array_keys( $results ) as $key ) {
			$results[$key] = $this->items[$key];
		}

		$this->items = $results;

		return $this;
	}

	public function offsetSet( $offset, $value ): void
	{
		if ( is_null( $offset ) ) {
			$this->items[] = $value;
		} else {
			$this->items[$offset] = $value;
		}
	}

	public function offsetExists( $offset ): bool
	{
		return isset( $this->items[$offset] );
	}

	public function offsetUnset( $offset ): void
	{
		unset( $this->items[$offset] );
	}

	public function offsetGet( $offset ): mixed
	{
		return $this->items[$offset] ?? null;
	}

	public function getIterator(): Traversable
	{
		return new ArrayIterator( $this->items );
	}

	public function count(): int
	{
		return count( $this->items );
	}

	public function is_empty()
	{
		return empty( $this->items );
	}

	protected function useAsCallable( $value )
	{
		return ! is_string( $value ) && is_callable( $value );
	}

	protected function valueRetriever( $value )
	{
		return function ( $item ) use ( $value ) {
			return data_get( $item, $value );
		};
	}
}

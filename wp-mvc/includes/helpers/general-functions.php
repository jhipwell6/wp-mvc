<?php

/**
 * Get an item from an array or object using "dot" notation.
 *
 * @param  mixed   $target
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
if ( ! function_exists('data_get') )
{
    function data_get( $target, $key, $default = null )
    {
        if ( is_null( $key ) ) return $target;
        foreach ( explode( '.', $key ) as $segment )
        {
            if ( is_array( $target ) )
            {
                if ( ! array_key_exists( $segment, $target ) )
                {
                    return $default;
                }
                $target = $target[$segment];
            }
            elseif ( $target instanceof ArrayAccess )
            {
                if ( ! isset( $target[$segment] ) )
                {
                    return $default;
                }
                $target = $target[$segment];
            }
            elseif ( is_object( $target ) )
            {
                if ( ! isset( $target->{$segment} ) )
                {
                    return $default;
                }
                $target = $target->{$segment};
            }
            else
            {
                return $default;
            }
        }
        return $target;
    }
}

/**
 * Return the first element in an array passing a given truth test.
 *
 * @param  array  $array
 * @param  callable  $callback
 * @param  mixed  $default
 * @return mixed
 */
if ( ! function_exists('array_first') )
{
    function array_first( $array, $callback = null, $default = null )
    {
        if ( is_null( $callback ) )
        {
            return count( $array ) > 0 ? reset( $array ) : null;
        }
		foreach ( $array as $key => $value )
        {
            if ( call_user_func( $callback, $key, $value ) ) return $value;
        }
        return value( $default );
    }
}

/**
 * Return the default value of the given value.
 *
 * @param  mixed  $value
 * @return mixed
 */
if ( ! function_exists('value') )
{
    function value( $value )
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

/**
 * Check if an array is associative or has numeric keys
 *
 * @param  mixed  $value
 * @return mixed
 */
if ( ! function_exists('is_assoc') )
{
	function is_assoc( array $arr )
	{
		if ( array() === $arr ) return false;
		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}
}

if ( ! function_exists('prepare_in') )
{
	function prepare_in( $values )
	{
		return implode( ',', array_map( function ( $value ) {
			global $wpdb;
			// Use the official prepare() function to sanitize the value.
			return $wpdb->prepare( '%s', $value );
		}, $values ) );
	}
}

if ( ! function_exists('wpmvc_get_meta_values') )
{
	function wpmvc_get_meta_values( $key = '', $args = array() )
	{
		if ( empty( $key ) )
			return;
		
		$defaults = array(
			'post_type' => 'post',
			'post_status' => array( 'publish' ),
		);
		$args = wp_parse_args( $args, $defaults );
		
		global $wpdb;
		$sql = "
			SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s 
			AND p.post_status IN (%s) 
			AND p.post_type = %s
		";
		$result = $wpdb->get_results( sprintf( $sql, $wpdb->prepare( "%s", $key ), prepare_in( $args['post_status'] ), $wpdb->prepare( "%s", $args['post_type'] ) ) );
		
		$output = array();
		if ( ! empty( $result ) ) {
			foreach ( $result as $row ) {
				$output[ $row->post_id ] = $row->meta_value; 
			}
		}

		return $output;
	}
}

if ( ! function_exists('wpmvc_get_user_values') )
{
	function wpmvc_get_user_values( $key = '', $distinct = true )
	{
		if ( empty( $key ) )
			return;
		
		$DISTINCT = $distinct ? ' DISTINCT' : '';
		
		global $wpdb;
		$sql = "
			SELECT{$DISTINCT} user_id, meta_value FROM {$wpdb->usermeta}
			WHERE meta_key = %s
		";
			
		$result = $wpdb->get_results( sprintf( $sql, $wpdb->prepare( "%s", $key ) ) );
		$output = array();
		if ( ! empty( $result ) ) {
			foreach ( $result as $row ) {
				$output[ $row->user_id ] = $row->meta_value;
			}
		}
		
		return $output;
	}
}
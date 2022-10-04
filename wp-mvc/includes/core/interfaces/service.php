<?php
namespace WP_MVC\Core\Interfaces;

if ( ! defined('ABSPATH') )
    exit;

interface Service
{
	public function get_api_url();
	
	public function get_cache_key();
	
	public function get_default_args();
}
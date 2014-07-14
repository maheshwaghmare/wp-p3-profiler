<?php

// If profiling hasn't started, start it
if ( function_exists( 'get_option' ) && !isset( $GLOBALS['p3_profiler'] ) && basename( __FILE__ ) !=  basename( $_SERVER['SCRIPT_FILENAME'] ) ) {
	$opts = p3_get_option( 'p3-profiler_options' );
	if ( !empty( $opts['profiling_enabled'] ) ) {
		$file = realpath( dirname( __FILE__ ) ) . '/classes/class.p3-profiler.php';
		if ( !file_exists( $file ) ) {
			return;
		}
		@include_once $file;
		declare( ticks = 1 ); // Capture every user function call
		if ( class_exists( 'P3_Profiler' ) ) {
			$GLOBALS['p3_profiler'] = new P3_Profiler(); // Go
		}
	}
	unset( $opts );
}
	
/**
 * Get the user's IP
 * @return string
 */
function p3_profiler_get_ip() {
	static $ip = '';
	if ( !empty( $ip ) ) {
		return $ip;
	} else {
		if ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( !empty ( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$ip = $_SERVER['HTTP_X_REAL_IP'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
}

/**
 * Disable profiling
 * @return void
 */
function p3_profiler_disable() {
	$opts = p3_get_option( 'p3-profiler_options' );
	$uploads_dir = wp_upload_dir();
	$path        = $uploads_dir['basedir'] . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . $opts['profiling_enabled']['name'] . '.json';
	$transient   = p3_get_option( 'p3_scan_' . $opts['profiling_enabled']['name'] );
	if ( false === $transient ) {
		$transient = '';
	}
	file_put_contents( $path, $transient );
	delete_option( 'p3_scan_' . $opts['profiling_enabled']['name'], $transient );
	$opts['profiling_enabled'] = false;
	p3_update_option( 'p3-profiler_options', $opts );
}

/**
 * Same as get_option, but bypass the object cache
 * @param string $option
 */
function p3_get_option( $option ) {
	global $wpdb;
	$option = trim( $option );
	if ( empty( $option ) ) {
		return false;
	}
	if ( defined( 'WP_SETUP_CONFIG' ) ) {
		return false;
	}
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );
	$value = null;
	if ( is_object( $row ) ) {
		$value = $row->option_value;
	}
	return $value;

}

/**
 * Same as update_option, but bypass the object cache
 * @param string $option
 * @param mixed $value
 */
function p3_update_option( $option, $value ) {
	global $wpdb;
	$option = trim( $option );
	$serialized_value = maybe_serialize( $value );
	$wpdb->update( $wpdb->options, array( 'option_value' => $serialized_value ), array( 'option_name' => $option ) );
	return true;
}

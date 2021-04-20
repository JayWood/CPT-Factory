<?php
namespace Com\Plugish\Libraries\CPT_Factory\Functions;

use Com\Plugish\Libraries\CPT_Factory\Factory;

/**
 * Registers a post type using the CPT Core class.
 *
 * @param string $singular Singular label.
 * @param string $plural   Plural label.
 * @param string $slug     Custom post type slug.
 * @param array  $args     Argument overrides.
 */
function register_post_type( string $singular, string $slug, string $plural = '', array $args = [] ): Factory {
	return new Factory( $singular, $slug, $plural, $args );
}

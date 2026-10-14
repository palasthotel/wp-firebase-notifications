<?php


namespace Palasthotel\FirebaseNotifications;


use WP_Error;

class Validation {

	const VALID_PLATFORMS = [ "ios", "android", "web" ];

	/**
	 * @param $values
	 *
	 * @return bool|WP_Error
	 */
	public static function isValidPlatformsArray( $values ) {
		if ( ! is_array( $values ) ) {
			return new WP_Error( 400, "Platforms argument needs to be an array." );
		}
		foreach ( $values as $p ) {
			if ( ! is_string( $p ) ) {
				return new WP_Error( 400, "Platforms array may only contain string values" );
			}
			if ( ! in_array( $p, self::VALID_PLATFORMS ) ) {
				new WP_Error( 400, "Not a valid platform $p" );
			}
		}

		return true;
	}

	public static function isValidConditions( $values ) {
		if ( ! is_array( $values ) ) {
			return false;
		}
		foreach ( $values as $item ) {
			if ( is_string( $item ) && strlen( $item ) > 0 ) {
				continue;
			} else if ( is_array( $item ) ) {
				foreach ( $item as $_item ) {
					if ( is_string( $_item ) && strlen( $_item ) > 0 ) {
						continue;
					}

					return new WP_Error( 400, "syntax error in conditions. String expected..." );
				}
			}

			return new WP_Error( 400, "syntax error in conditions. String or array expected..." );

		}

		return true;
	}

	public static function sanitizeConditions( $values ) {
		foreach ( $values as $index => $item ) {
			if ( is_string( $item ) ) {
				$values[ $index ] = sanitize_text_field( $item );
			} else if ( is_array( $item ) ) {
				foreach ( $item as $_index => $_item ) {
					$item[ $_index ] = sanitize_text_field( $_item );
				}
			}
		}

		return $values;
	}

}
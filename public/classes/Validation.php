<?php


namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


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

	/**
	 * FCM topic names: https://firebase.google.com/docs/cloud-messaging/send-message#send-messages-to-topics
	 */
	const TOPIC_PATTERN = '/^[a-zA-Z0-9\-_.~%]{1,900}$/';

	/**
	 * FCM allows up to five topics in a condition, so four operators.
	 */
	const MAX_OPERATORS = 4;

	/**
	 * A topic condition as the meta box sends it: topic ids alternating with AND/OR,
	 * a nested array for a bracket, e.g. ["news", "AND", ["sport", "OR", "culture"]].
	 *
	 * @param mixed $values
	 *
	 * @return true|WP_Error
	 */
	public static function isValidConditions( $values ) {
		$operators = 0;
		$error     = self::checkConditions( $values, 0, $operators );
		if ( $error instanceof WP_Error ) {
			return $error;
		}
		if ( $operators > self::MAX_OPERATORS ) {
			return new WP_Error( 400, "A condition may have at most " . self::MAX_OPERATORS . " AND/OR operators." );
		}
		return true;
	}

	/**
	 * @param mixed $values
	 * @param int $depth
	 * @param int $operators counts the operators of all levels
	 *
	 * @return true|WP_Error
	 */
	private static function checkConditions( $values, int $depth, int &$operators ) {
		if ( ! is_array( $values ) || count( $values ) === 0 || count( $values ) % 2 === 0 ) {
			return new WP_Error( 400, "Syntax error in conditions: topics have to alternate with AND or OR." );
		}
		foreach ( array_values( $values ) as $i => $item ) {
			if ( $i % 2 ) {
				if ( ! is_string( $item ) || ! in_array( strtoupper( $item ), array( "AND", "OR" ), true ) ) {
					return new WP_Error( 400, "Syntax error in conditions: AND or OR expected." );
				}
				$operators++;
			} else if ( is_string( $item ) ) {
				if ( ! preg_match( self::TOPIC_PATTERN, $item ) ) {
					return new WP_Error( 400, "Syntax error in conditions: invalid topic name." );
				}
			} else if ( is_array( $item ) && $depth === 0 ) {
				$error = self::checkConditions( $item, $depth + 1, $operators );
				if ( $error instanceof WP_Error ) {
					return $error;
				}
			} else {
				return new WP_Error( 400, "Syntax error in conditions: topic or bracket expected." );
			}
		}
		return true;
	}

	/**
	 * Only for conditions that passed isValidConditions().
	 *
	 * @param array $values
	 *
	 * @return array
	 */
	public static function sanitizeConditions( $values ) {
		$sanitized = array();
		foreach ( array_values( $values ) as $item ) {
			$sanitized[] = is_array( $item )
				? self::sanitizeConditions( $item )
				: sanitize_text_field( $item );
		}
		return $sanitized;
	}

}

<?php


namespace Palasthotel\FirebaseNotifications\Component;

/**
 * Class Component
 *
 * @package Palasthotel\WordPress
 * @version 0.1.1
 */
abstract class Component {
	/**
	 * _Component constructor.
	 *
	 */
    public Plugin $plugin;
	public function __construct( \Palasthotel\FirebaseNotifications\Plugin $plugin) {
		$this->plugin = $plugin;
		$this->onCreate();
	}

	/**
	 * overwrite this method in component implementations
	 */
	abstract function onCreate();
}
<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-03-29
 * Time: 10:38
 */

namespace Palasthotel\FirebaseNotifications;


class MetaBox {

	/**
	 * MetaBox constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		add_action( 'add_meta_boxes_post', array( $this, 'add_meta_box' ) );
	}

	/**
	 *  register meta box
	 */
	public function add_meta_box() {
		add_meta_box(
			Plugin::DOMAIN . '-meta-box',
			__( 'Firebase Messaging', Plugin::DOMAIN ),
			array( $this, 'render' ),
			'post',
			"side",
			"high"
		);
	}

	public function render( $post ) {
		$this->plugin->ajax->enqueueApiJs();
		wp_enqueue_script(
			Plugin::DOMAIN."-meta-box",
			$this->plugin->url."/js/meta-box.js",
			array("jquery", $this->plugin->ajax->api_handle),
			1,
			true
			);
		echo "<label>";
		echo __( 'Firebase Messaging', Plugin::DOMAIN ) . "<br/>";
		echo "<div id='firebase-messaging'></div>";
		echo "</label>";

	}


}
<?php


namespace Palasthotel\FirebaseNotifications;


/**
 * @property Plugin plugin
 */
class Assets {

	const HANDLE_FIREBASE_CORE = "firebase-core";
	const HANDLE_FIREBASE_MESSAGING = "firebase-messaging";
	const FB_VERSION = "8.4.3";

	public function __construct(Plugin $plugin){
		$this->plugin = $plugin;
		add_action('init', function(){
			$this->registerFirebase();
			$this->registerFrontendApiScript();
		});
		add_action('admin_init', function(){
			$this->registerAdminApiScript();
		});
	}

	//------------------------------------------------------------
	// register dependencies
	//------------------------------------------------------------
	private function registerFirebase(){
		$version = self::FB_VERSION;
		wp_register_script(
			self::HANDLE_FIREBASE_CORE,
			$this->plugin->url . "/js/firebase-app.$version.js",
			array(),
			$version,
			true
		);
		wp_register_script(
			self::HANDLE_FIREBASE_MESSAGING,
			$this->plugin->url . "/js/firebase-messaging.$version.js",
			array( self::HANDLE_FIREBASE_CORE ),
			$version,
			true
		);
	}
	private function registerDesktopMessaging(){
		wp_register_script(
			Plugin::HANDLE_MESSAGING_JS,
			$this->plugin->url . "/js/desktop-messaging.js",
			array( Assets::HANDLE_FIREBASE_MESSAGING ),
			filemtime( $this->plugin->path . "/js/desktop-messaging.js" )
		);
		wp_localize_script(
			Plugin::HANDLE_MESSAGING_JS,
			"FirebaseMessagingWebapp",
			array(
				"config" => $this->plugin->settings->getWebappConfig( true ),
				"iconUrl" => $this->plugin->settings->getNotificationIconURL(),
				"ajax" => array(
					"subscribe" => admin_url("admin-ajax.php?action=".$this->plugin->ajax->action_subscribe),
					"unsubscribe" => admin_url("admin-ajax.php?action=".$this->plugin->ajax->action_unsubscribe),
					"topics" => admin_url("admin-ajax.php?action=".$this->plugin->ajax->action_topics),
				)
			)
		);
	}
	private function registerFrontendApiScript(){
		$deps = array();
		if($this->plugin->settings->isWebappConfigValid()){
			$this->registerDesktopMessaging();
			$deps[] = Plugin::HANDLE_MESSAGING_JS;
		}
		wp_register_script(
			Plugin::HANDLE_FRONTEND_API_JS,
			$this->plugin->url . "/js/api-frontend.js",
			$deps,
			filemtime( $this->plugin->path . "/js/api-frontend.js"),
			true
		);
	}
	private function registerAdminApiScript(){
		wp_register_script(
			Plugin::HANDLE_ADMIN_API_JS,
			$this->plugin->url . "/js/api-admin.js",
			["jquery"],
			filemtime( $this->plugin->path . "/js/api-admin.js"),
			true
		);
		wp_localize_script(
			Plugin::HANDLE_ADMIN_API_JS,
			"FirebaseNotificationsApi",
			array(
				"ajax_url" => admin_url( 'admin-ajax.php' ),
				"actions" => array(
					"send" => $this->plugin->ajax->action_send,
					"delete" => $this->plugin->ajax->action_delete,
				),
			)
		);
	}

	//------------------------------------------------------------
	// frontend
	//------------------------------------------------------------
	public function enqueueFrontendScript(){
		wp_enqueue_script(
			Plugin::HANDLE_FRONTEND_JS,
			$this->plugin->url . "/js/frontend-firebase-notifications-settings.js",
			array( "jquery", Plugin::HANDLE_FRONTEND_API_JS ),
			filemtime( $this->plugin->path . "/js/frontend-firebase-notifications-settings.js"),
			true
		);
	}
	public function enqueueFrontendTestScript(){
		wp_enqueue_script(
			Plugin::HANDLE_FRONTEND_JS."_test",
			$this->plugin->url . "/js/test.frontend-firebase-notifications-settings.js",
			array( "jquery", Plugin::HANDLE_FRONTEND_API_JS ),
			filemtime( $this->plugin->path . "/js/test.frontend-firebase-notifications-settings.js"),
			true
		);
	}

	//------------------------------------------------------------
	// meta box
	//------------------------------------------------------------
	public function enqueueMetaBoxStyle(){
		wp_enqueue_style(
			Plugin::HANDLE_META_BOX_STYLE,
			$this->plugin->url . "/css/meta-box.css",
			array(),
			filemtime( $this->plugin->path . "/css/meta-box.css" )
		);
	}
	public function enqueueMetaBoxScript($localized){
		wp_enqueue_script(
			Plugin::HANDLE_META_BOX_SCRIPT,
			$this->plugin->url . "/js/meta-box.js",
			array( Plugin::HANDLE_ADMIN_API_JS ),
			filemtime($this->plugin->path."/js/meta-box.js"),
			true
		);
		wp_localize_script(
			Plugin::HANDLE_META_BOX_SCRIPT,
			"FirebaseNotifications_MetaBox",
			$localized
		);
	}

	//------------------------------------------------------------
	// tools page
	//------------------------------------------------------------
	public function enqueueToolsPageScript(){
		wp_enqueue_script(
			Plugin::HANDLE_TOOLS_PAGE_SCRIPT,
			$this->plugin->url."/js/tools-page.js",
			["jquery", Plugin::HANDLE_ADMIN_API_JS],
			filemtime($this->plugin->path."/js/tools-page.js")
		);
	}
	public function enqueueToolsPageStyle(){
		wp_enqueue_style(
			Plugin::HANDLE_TOOLS_PAGE_STYLE,
			$this->plugin->url."/css/tools-page.css",
			[],
			filemtime($this->plugin->path."/css/tools-page.css")
		);
	}

}
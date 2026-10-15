<?php

namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Schedule {
    
    public Plugin $plugin;

	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		add_action( 'admin_init', array( $this, 'start' ) );
		add_action(Plugin::SCHEDULE_SEND_MESSAGED, array($this, 'send_scheduled_messaged'));
	}

	/**
	 * start scheduled event
	 */
	public function start() {
		if ( ! $this->isScheduled() ) {
			wp_schedule_event( time(), 'hourly', Plugin::SCHEDULE_SEND_MESSAGED );
		}
	}

	public function stop(){
		wp_clear_scheduled_hook( Plugin::SCHEDULE_SEND_MESSAGED );
	}

	/**
	 * @return false|int
	 */
	public function isScheduled() {
		return wp_next_scheduled( Plugin::SCHEDULE_SEND_MESSAGED );
	}


	public function send_scheduled_messaged(){
		$messages = $this->plugin->database->getNextScheduledMessages();
		foreach ($messages as $message){
			try{
				$result = $this->plugin->cloudMessagingApi->send($message);
				$message->result = $result;
				$success = $this->plugin->database->setSent( $message->id, $result);
				if(!$success){
					Log::error("Could not mark scheduled notification {$message->id} as sent.");
				}
			} catch (\Exception $e){
				Log::error("Could not send scheduled notification {$message->id}: " . $e->getMessage());
			}
		}
	}


}
<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 2019-04-04
 * Time: 10:48
 */

namespace Palasthotel\FirebaseNotifications;


/**
 * @property \wpdb wpdb
 * @property string $tablename
 * @property string tablename_posts
 */
class Database {

	/**
	 * Database constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb      = $wpdb;
		$this->tablename = $wpdb->prefix . "firebase_notification_messages";
		$this->tablename_posts = $wpdb->prefix . "firebase_notification_messages_from_posts";
	}

	/**
	 * @param Message $message
	 *
	 * @return false|Message
	 */
	function add($message){
		$numberInserted =  $this->wpdb->insert(
			$this->tablename,
			array(
				"conditions" => json_encode($message->conditions),
				"title" => $message->title,
				"body" => $message->body,
				"payload" => json_encode($message->payload),
			),
			array( "%s","%s","%s","%s", "%s")
		);

		if($numberInserted){
			$message->id = $this->wpdb->insert_id;
			do_action(Plugin::ACTION_MESSAGE_ADD, $message);
			return $message;
		}
		return false;
	}

	/**
	 * @param int $message_id
	 *
	 * @return false|int
	 */
	function delete($message_id){
		return $this->wpdb->delete($this->tablename, array("id" => $message_id), array("%d"));
	}

	/**
	 * @param int $message_id
	 *
	 * @param array|object $result
	 *
	 * @return bool|int
	 */
	function setSent($message_id, $result){
		do_action(Plugin::ACTION_MESSAGE_SENT, $message_id, $result);
		return $this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE $this->tablename SET sent = now(), result = %s WHERE id = %d ",
				json_encode($result),
				$message_id
			)
		);
	}

	/**
	 * @param int $message_id
	 * @param int $post_id
	 *
	 * @return false|int
	 */
	function addPostMessage($message_id, $post_id){
		return $this->wpdb->insert(
			$this->tablename_posts,
			array(
				"message_id" => $message_id,
				"post_id" => $post_id,
			),
			array( "%d", "%d")
		);
	}

	/**
	 * get all messages related to a post
	 * @param int $post_id
	 *
	 * @return Message[]
	 */
	function getPostMessages($post_id){
		return array_map(
			array($this, "mapMessage"),
			$this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT * FROM $this->tablename_posts as p LEFT JOIN $this->tablename as m ON (p.message_id = m.id) WHERE p.post_id = %d ORDER BY created DESC", $post_id
				)
			)
		);
	}

	/**
	 * @param $page
	 * @param $limit
	 *
	 * @return Message[]
	 */
	function getAll($page, $limit){
		$offset = $limit*$page;
		return array_map(
			array($this, "mapMessage"),
			$this->wpdb->get_results(
				"SELECT * FROM $this->tablename ORDER BY created DESC LIMIT $offset, $limit"
			)
		);
	}

	/**
	 * @param object $item
	 *
	 * @return Message
	 */
	function mapMessage($item){
		$msg = Message::build(
			json_decode($item->conditions),
			$item->title,
			$item->body,
			json_decode($item->payload)
		);
		$msg->result = json_decode($item->result);
		$msg->created = $item->created;
		$msg->sent = $item->sent;
		return $msg;
	}

	/**
	 * create tables
	 */
	public function create() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( "CREATE TABLE IF NOT EXISTS $this->tablename (
			 id bigint(20) unsigned not null auto_increment,
			 conditions varchar(255) not null,
			 title varchar(255) not null,
			 body text not null,
			 payload text not null,
			 created datetime default now(),
			 sent datetime default null,
			 result text default null,
			 primary key (id),
			 key (title),
			 key (conditions),
			 key (sent),
			 key(created)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;" );

		dbDelta( "CREATE TABLE IF NOT EXISTS $this->tablename_posts (
			 id bigint(20) unsigned not null auto_increment,
			 message_id bigint(20) unsigned not null,
			 post_id bigint(20) unsigned not null,
			 primary key (id),
			 key(message_id),
			 key (post_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;" );

	}

}
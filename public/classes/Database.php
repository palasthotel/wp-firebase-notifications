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
class Database extends Component\Database {

	/**
	 * Database constructor.
	 */
	public function init() {
		$this->tablename = $this->wpdb->prefix . "firebase_notification_messages";
		$this->tablename_posts = $this->wpdb->prefix . "firebase_notification_messages_from_posts";
	}

	/**
	 * @param Message $message
	 *
	 * @return false
	 */
	function add($message){

		$message->created = $this->nowUTC();

		$numberInserted =  $this->wpdb->insert(
			$this->tablename,
			array(
				"conditions" => json_encode($message->conditions),
				"plattforms" => implode(",",$message->plattforms),
				"title" => $message->title,
				"body" => $message->body,
				"payload" => json_encode($message->payload),
				"created" => $message->created,
			),
			array( "%s","%s","%s","%s","%s", "%s")
		);

		if($numberInserted){
			$message->id = $this->wpdb->insert_id;
			return true;
		}
		return false;
	}

	/**
	 * @param int $message_id
	 *
	 */
	function delete($message_id){
		$this->wpdb->delete($this->tablename_posts, array("message_id" => $message_id), array("%d"));
		$this->wpdb->delete($this->tablename, array("id" => $message_id), array("%d"));
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
				"UPDATE $this->tablename SET sent = %s, result = %s WHERE id = %d ",
				$this->nowUTC(),
				json_encode($result),
				$message_id
			)
		);
	}

	/**
	 * @param int $message_id
	 * @param int $schedule_timestamp
	 *
	 * @return false|int
	 */
	function setSchedule($message_id, $schedule_timestamp){
		$datetime = new \DateTime();
		$datetime->setTimezone(new \DateTimeZone("UTC"));
		$datetime->setTimestamp($schedule_timestamp);
		$result = $this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE $this->tablename SET publish = %s WHERE id = %d",
				$datetime->format("Y-m-d H:i:s"),
				$message_id
			)
		);
		return $result;
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
	 * @param int $message_id
	 *
	 * @return bool|Message
	 */
	function getMessage($message_id){
		$result = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM $this->tablename WHERE id = %d", $message_id
			)
		);
		if(!$result) return false;
		return $this->mapMessage($result);
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
	 * @param int $page
	 * @param int $count
	 *
	 * @return Message[]
	 */
	function getAll($page = 0, $count = 10){
		$offset = $count * $page;
		return array_map(
			array($this, "mapMessage"),
			$this->wpdb->get_results(
				"SELECT * FROM $this->tablename ORDER BY created DESC LIMIT $offset, $count"
			)
		);
	}

	/**
	 * @return Message[]
	 */
	function getNextScheduledMessages() {
		return array_map(
			array( $this, "mapMessage" ),
			$this->wpdb->get_results(
				"SELECT * FROM $this->tablename WHERE sent IS NULL AND publish IS NOT NULL AND publish <= UTC_TIMESTAMP()"
			)
		);
	}

	/**
	 * @param bool $include_sent
	 * @param int $page
	 * @param int $count
	 *
	 * @return Message[]
	 */
	function getScheduledMessages( $include_sent = false, $page = 0, $count = 10){
		$offset = $count * $page;
		$where_sent = ($include_sent)? "": " AND sent NOT NULL ";
		return array_map(
			array($this, "mapMessage"),
			$this->wpdb->get_results(
				"SELECT * FROM $this->tablename WHERE publish IS NOT NULL $where_sent ORDER BY publish ASC LIMIT $offset, $count"
			)
		);
	}

	/**
	 * @param int $post_id
	 * @param int $page
	 * @param int $count
	 *
	 * @return Message[]
	 */
	function getByPostId($post_id, $page = 0, $count = 10){
		$offset = $count*$page;
		$sub = "SELECT message_id FROM $this->tablename_posts WHERE post_id = $post_id";
		return array_map(
			array($this, "mapMessage"),
			$this->wpdb->get_results(
				"SELECT * FROM $this->tablename WHERE id IN ($sub) ORDER BY created DESC LIMIT $offset, $count"
			)
		);
	}

	function nowUTC(){
		$dt = new \DateTime();
		$dt->setTimezone(new \DateTimeZone("UTC"));
		return $dt->format("Y-m-d H:i:s");
	}

	/**
	 * @param object $item
	 *
	 * @return Message
	 */
	function mapMessage($item){
		$msg = Message::build(
			explode(",", $item->plattforms),
			json_decode($item->conditions),
			$item->title,
			$item->body,
			json_decode($item->payload, true)
		);
		$msg->id = $item->id;
		$msg->result = json_decode($item->result);
		$msg->created = $item->created;
		$msg->sent = $item->sent;
		$msg->publish = $item->publish;
		return $msg;
	}

	public function createTables() {
		parent::createTables();

		dbDelta( "CREATE TABLE IF NOT EXISTS $this->tablename (
			 id bigint(20) unsigned not null auto_increment,
			 conditions text not null,
			 plattforms varchar(100) not null,
			 title varchar(190) not null,
			 body text not null,
			 payload text not null,
			 created datetime NOT NULL,
			 publish datetime default null,
			 sent datetime default null,
			 result text default null,
			 primary key (id),
			 key (title),
			 key (plattforms),
			 key (sent),
			 key (created),
			 key (publish)
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
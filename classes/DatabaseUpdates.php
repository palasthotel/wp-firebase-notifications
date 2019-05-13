<?php
/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid-Wordpress
 */
namespace Palasthotel\FirebaseNotifications;

/**
 * @property Database database
 */
class DatabaseUpdates {

	public function __construct(Database $db) {
		$this->database = $db;
		add_action('init', array($this, 'init'));
	}

	public function init(){
		$this->performUpdatesIfNeeded();
	}

	public function getCurrentSchemaVersion()
	{
		return get_option(Plugin::OPTION_DB_SCHEMA, 0);
	}
	public function setCurrentSchemaVersion($version){
		update_option(Plugin::OPTION_DB_SCHEMA, $version);
	}

	public function setToLatestSchemaVersion(){
		$this->setCurrentSchemaVersion($this->getNeededSchemaVersion());
	}

	public function getNeededSchemaVersion()
	{
		$methods=$this->getUpdateMethods();
		$i=0;
		foreach($methods as $idx=>$method)
		{
			if($idx>$i)	$i=$idx;
		}
		return $i;
	}

	public function getUpdateMethods()
	{
		$methods=get_class_methods($this);
		$updates=array();
		foreach($methods as $method)
		{
			if(strpos($method, "update_")===0)
			{
				$id=explode("_", $method);
				$id=$id[1];
				$updates[$id]=$method;
			}
		}
		return $updates;
	}

	public function performUpdatesIfNeeded()
	{
		$current_schema=$this->getCurrentSchemaVersion();
		$needed_schema=$this->getNeededSchemaVersion();
		if($current_schema==$needed_schema) return;

		$methods=$this->getUpdateMethods();
		for($i=$current_schema+1;$i<=$needed_schema;$i++)
		{
			$method=$methods[$i];
			$this->$method();
			$this->setCurrentSchemaVersion($i);
		}
	}



	public function update_1()
	{
		$tablename = $this->database->tablename;
		$this->database->wpdb->query("ALTER TABLE $tablename ADD plattforms VARCHAR(100) AFTER id");
		$this->database->wpdb->query("UPDATE $tablename SET plattforms = 'android,ios,web'");
		$this->database->wpdb->query("ALTER TABLE $tablename CHANGE topic conditions VARCHAR(255)");
		$this->database->wpdb->query("UPDATE $tablename SET conditions = concat('[\"',conditions,'\"]')");
	}

}


?>
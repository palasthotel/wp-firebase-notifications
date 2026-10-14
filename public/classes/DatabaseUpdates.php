<?php

namespace Palasthotel\FirebaseNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DatabaseUpdates {
    
    public Database $database;

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



	// Schema changes with the plugin's own table name only, no user input.
	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	public function update_1()
	{
		$wpdb = $this->database->wpdb;
		$tablename = $this->database->tablename;
		$wpdb->query("ALTER TABLE $tablename ADD plattforms VARCHAR(100) AFTER id");
		$wpdb->query("UPDATE $tablename SET plattforms = 'android,ios,web'");
		$wpdb->query("ALTER TABLE $tablename DROP INDEX topic");
		$wpdb->query("ALTER TABLE $tablename CHANGE topic conditions text");
		$wpdb->query("UPDATE $tablename SET conditions = concat('[\"',conditions,'\"]')");
	}

	public function update_2()
	{
		$wpdb = $this->database->wpdb;
		$tablename = $this->database->tablename;
		$wpdb->query("ALTER TABLE $tablename ADD publish DATETIME DEFAULT null AFTER created");
	}

	// phpcs:enable

}

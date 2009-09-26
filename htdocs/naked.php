<?php
require_once dirname (__FILE__) . '/../includes/prepend.php';
$cfg = Config::get_instance();

$vm			= isset ($_GET['vm']) ? $_GET['vm'] : false; 			// vm number
$action		= isset ($_GET['action']) ? $_GET['action'] : false; 		// action to do (see switch)
$target		= isset ($_GET['target']) ? $_GET['target'] : false; 		// target for migration
$domN 		= isset ($_GET['dom0']) ? $_GET['dom0'] : false; 		// dom0 number

// the 'main' div, which is dynamic
// create object in order to play with SQLite database
$db = Db::get_instance();



$dbresult = $db->query('SELECT count(*) FROM dom0');

$db->query('DELETE FROM domU');

foreach ($cfg->domains as $id => $domain) {
	$user = isset($domain['user']) ? $domain['user'] : 'none';
	$password = isset($domain['password']) ? $domain['password'] : 'none';

	try {
		// Create objects and put them into Database
		$dom0 = new Dom0($id, $user, $password);

		if (Model::get_dom0($id) === null) {
			Model::insert_dom0($dom0);
		}
		else {


		// Compare and/or Update/Add/Remove if necessary
		$query = $db->query('SELECT object FROM dom0 WHERE id = "'.sqlite_escape_string($id).'"');
		$number = $query->numRows();

		$domobject = sqlite_escape_string(serialize($dom0));

		if ($number==1) {
			$db->query('UPDATE dom0 SET object="'.$domobject
				.'" WHERE id = "'.sqlite_escape_string($id).'"');
		}
		else {
			$db->query('INSERT INTO dom0 (id, object) VALUES ("'
				.$id.'","'.$domobject.'")');
		}
	}
	catch (Exception $e) {
		echo '<h3>Connection Error: ',  $e->getMessage(), '</h3>';
	}

	if (($vm !== false) && ($domN == $id)) {
		switch ($action) {
			case 'migrate_vm':
				$dom0->migrate_vm($vm, $target, true);
				$vm_name = $dom0->get_vm_name($vm);
				break;
			case 'destroy_vm':
			case 'shutdown_vm':
			case 'start_vm':
			case 'pause_vm':
			case 'unpause_vm':
				$dom0->$action($vm);
				break;
		}
	}
}
// now that the database if filled, we display
$query = $db->query('SELECT object FROM dom0');
$result = $query->fetchAll();
foreach ($result as $dom0) {
	$dom0 = unserialize($dom0[0]);
	$dom0->detect_migrated();
	$dom0->display_table_all_vm();
}


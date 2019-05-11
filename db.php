<?php 

	// Disable those annoying notices
	ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

	// If this doesn't exist, index.php will help the user create it
	if (file_exists('config.php'))
		include_once('config.php');
	
	// Load up a DB connection and set a global var, BUT only if it's not already set
	function db_connect($force=0)
	{
		// Did we do this already?
		if (!$_SESSION['dbconn'] || $force) 
			$_SESSION['dbconn'] = sqlsrv_connect($_SESSION["HOST"], array("Database"=>$_SESSION["DATABASE"], "UID"=>$_SESSION["USER"], "PWD"=>$_SESSION["PASSWORD"]));
		// It was stored before or we just connected, now return
		return $_SESSION['dbconn'];
	}
	
	// Load up a DB connection and set a global var, BUT only if it's not already set
	function auth_connect($force=0)
	{
		// Did we do this already?
		if (!$_SESSION['authconn'] || $force) 
			$_SESSION['authconn'] = sqlsrv_connect($_SESSION["HOST"], array("Database"=>$_SESSION["DATABASEAUTH"], "UID"=>$_SESSION["USER"], "PWD"=>$_SESSION["PASSWORD"]));
		// It was stored before or we just connected, now return
		return $_SESSION['authconn'];
	}
	
	
	// Send the sql string and param array to see the effective query
	function sql_debug($sql_string, array $params = null) {
		if (!empty($params)) {
			$indexed = $params == array_values($params);
			foreach($params as $k=>$v) {
				if (is_object($v)) {
					if ($v instanceof \DateTime) $v = $v->format('Y-m-d H:i:s');
					else continue;
				}
				elseif (is_string($v)) $v="'$v'";
				elseif ($v === null) $v='NULL';
				elseif (is_array($v)) $v = implode(',', $v);

				if ($indexed) {
					$sql_string = preg_replace('/\?/', $v, $sql_string, 1);
				}
				else {
					if ($k[0] != ':') $k = ':'.$k; //add leading colon if it was left out
					$sql_string = str_replace($k,$v,$sql_string);
				}
			}
		}
		return $sql_string;
	}
	
?>
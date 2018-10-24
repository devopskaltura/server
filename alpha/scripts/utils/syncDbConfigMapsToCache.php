<?php

if($argc != 5)
	die ("Usage : $argv[0] <db user name> <db password> <cahce host> <cache port> \n");

chdir(__DIR__.'/../');
require_once(__DIR__ . '/../bootstrap.php');
require_once __DIR__ . '/../../config/cache/kRemoteMemCacheConf.php';

$dbUserName = $argv[1];
$dbPasssword = $argv[2];
$host = $argv[3];
$port = $argv[4];


//get map list from cache
$mapName = 'kRemoteMemCacheConf';
$map=array(host=>$host,port=>$port);

$cache = new kInfraMemcacheCacheWrapper;
if(!$cache->init(array('host'=>$map['host'],'port'=>$map['port'])))
	die ("Fail to connect to cache host {$map['host']} port {$map['port']} ");
$mapListInCache = $cache->get(kRemoteMemCacheConf::MAP_LIST_KEY);

$dbConnection = getPdoConnection();
//Find all exsiting map names in DB
$cmdLine = 'select map_name , host_name from conf_maps where status=1 group by map_name,host_name;';
$mapsInfo = query($dbConnection,$cmdLine);
foreach($mapsInfo as $mapInfo)
{
	$rawMapName =$mapInfo['map_name'];
	if(trim($rawMapName)=='')
	{
		continue;
	}
	$hostNameFilter = $mapInfo['host_name'];
	//get the latest version of this map
	$cmdLine = "select version,content from conf_maps where conf_maps.map_name='$rawMapName' and conf_maps.host_name='$hostNameFilter' and status=1 order by version desc limit 1 ;";

	$output2 = query($dbConnection,$cmdLine);
	$mapName = $rawMapName.kRemoteMemCacheConf::MAP_DELIMITER.$hostNameFilter;
	$version = $output2[0]['version'];
	$content = $output2[0]['content'];
	if(!isset($mapListInCache[$mapName]))
		echo("\nNOTICE - Map {$mapName} is new! adding it to the list in cache\n");
	//check if need to update version
	else if($mapListInCache[$mapName]!=$version)
		echo("\nNOTICE - Map {$mapName} version needs to be updated to {$version}\n");
	else
		echo("\nINFO - Map {$mapName} already found in cache with version {$version}\n");

	$mapListInCache[$mapName]=$version;//set version
	$cache->set($mapName,$content);
}
$mapListInCache['UPDATED_AT']=date("Y-m-d H:i:s");
$cache->set(kRemoteMemCacheConf::MAP_LIST_KEY,$mapListInCache);
//todo reset the generarted key
$chacheKey = kBaseConfCache::generateKey();
$ret = $cache->set(kBaseConfCache::CONF_CACHE_VERSION_KEY,$chacheKey);
if($ret)
	echo("\nKey - {$chacheKey} was added to cache successfully\n");
else
	die("\nFailed inserting key to cache\n");

function getPdoConnection()
{
	$dbMap = kConf::getMap('db');
	if(!$dbMap)
	{
		die('Cannot get db.ini map from configuration!');
	}
	$defaultSource = $dbMap['datasources']['default'];
	$dbConfig = $dbMap['datasources'][$defaultSource]['connection'];
	$dsn = $dbConfig['dsn'];
	$user = $dbConfig['user'];
	$password = $dbConfig['password'];
	$connection = new PDO($dsn, $user, $password);
	return $connection;
}
function query($dbConnection,$commandLine)
{
	echo "executing: {$commandLine}\n";
	$statement = $dbConnection->query($commandLine);
	$output1 = $statement->fetchAll();
	return $output1;
}
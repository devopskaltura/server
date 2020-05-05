<?php

if (count($argv) != 5)
{
	print("USAGE: <partnerId> <storageId> <lastUpdatedAt> <realrun-dryrun> ");
	exit(0);
}

define("BASE_DIR", dirname(__FILE__));
require_once(BASE_DIR.'/../../../alpha/scripts/bootstrap.php');

$partnerId = $argv[1];
$storageId = $argv[2];
$lastUpdatedAt = $argv[3];
$dryRun = $argv[4] != 'realrun';
if (!$storageId)
{
	KalturaLog::debug(" No Stroge Id");
	exit(0);
}


if ($dryRun)
{
	KalturaLog::debug('*************** In Dry run mode ***************');
}
else
{
	KalturaLog::debug('*************** In Real run mode ***************');
}
KalturaStatement::setDryRun($dryRun);

main($partnerId, $storageId, $lastUpdatedAt);

/**
 * @param $partnerId
 * @param $storageId
 * @throws PropelException
 */
function main($partnerId, $storageId, $lastUpdatedAt)
{
	KalturaLog::debug("Running for PartnerId [$partnerId] and storageId [$storageId]");
	$partner = PartnerPeer::retrieveByPK($partnerId);
	if (!$partner)
	{
		KalturaLog::debug("Partner [$partnerId] does not exists ");
		exit(0);
	}

	$lastHandledId = 0;
	//loop in 100 file_syncs cycles
	do
	{
		$criteria = new Criteria(FileSyncPeer::DATABASE_NAME);
		$criteria->add(FileSyncPeer::PARTNER_ID, $partnerId, Criteria::EQUAL);
		$criteria->add(FileSyncPeer::STATUS, FileSync::FILE_SYNC_STATUS_READY, Criteria::EQUAL);
		$criteria->add(FileSyncPeer::DC, kDataCenterMgr::getCurrentDcId(), Criteria::EQUAL);
		$criteria->add(FileSyncPeer::ID, $lastHandledId, Criteria::GREATER_THAN);
		$criteria->add(FileSyncPeer::UPDATED_AT, $lastUpdatedAt, Criteria::LESS_THAN);
		$criteria->addAscendingOrderByColumn(FileSyncPeer::ID);
		$criteria->setLimit(100);

		$fileSyncs = FileSyncPeer::doSelect($criteria);
		KalturaLog::debug("Found: " . count($fileSyncs) . " file syncs to copy");
		foreach ($fileSyncs as $fileSync)
		{
			/** @var FileSync $fileSync */
			KalturaLog::debug('Handling file sync with id ' . $fileSync->getId());
			//create new fileSync With status pending and new storageId
			$newfileSync = $fileSync->copy(true);
			$newfileSync->setStatus(FileSync::FILE_SYNC_STATUS_PENDING);
			$newfileSync->setDc($storageId);
			$newfileSync->save();
			$lastHandledId = $fileSync->getId();
		}
		kMemoryManager::clearMemory();

	} while (count($fileSyncs) > 0);
	KalturaLog::debug("DONE!");
}
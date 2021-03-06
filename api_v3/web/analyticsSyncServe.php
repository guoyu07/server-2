<?php

// bootstrap
require_once(__DIR__ . '/../bootstrap.php');

define('MAX_ITEMS', 2000);

define('PARTNER_SECRET', 'secret');
define('PARTNER_CRM_ID', 'crmId');
define('PARTNER_VERTICAL', 'vertical');

function getPartnerVertical($customData)
{
	if (isset($customData['internalUse']) && $customData['internalUse'])
	{
		return -1;
	}
	else if (isset($customData['verticalClasiffication']) && $customData['verticalClasiffication'] > 0)
	{
		return $customData['verticalClasiffication'];
	}
	else
	{
		return 0;
	}
}

function getPartnerUpdates($updatedAt)
{
	$c = new Criteria();
	$c->addSelectColumn(PartnerPeer::ID);
	$c->addSelectColumn(PartnerPeer::STATUS);
	$c->addSelectColumn(PartnerPeer::ADMIN_SECRET);
	$c->addSelectColumn(PartnerPeer::CUSTOM_DATA);
	$c->addSelectColumn(PartnerPeer::UPDATED_AT);
	$c->add(PartnerPeer::UPDATED_AT, $updatedAt, Criteria::GREATER_EQUAL);
	$c->addAscendingOrderByColumn(PartnerPeer::UPDATED_AT);
	$c->setLimit(MAX_ITEMS);
	PartnerPeer::setUseCriteriaFilter(false);
	$stmt = PartnerPeer::doSelectStmt($c);
	PartnerPeer::setUseCriteriaFilter(true);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$maxUpdatedAt = 0;
	$result = array();
	foreach ($rows as $row)
	{
		$id = $row['ID'];
		$status = $row['STATUS'];
		if ($status == Partner::PARTNER_STATUS_ACTIVE)
		{
			$customData = unserialize($row['CUSTOM_DATA']);
			
			$info = array(
				PARTNER_SECRET => $row['ADMIN_SECRET'],
				PARTNER_CRM_ID => isset($customData['crmId']) ? $customData['crmId'] : '',
				PARTNER_VERTICAL => getPartnerVertical($customData),
			);
			$info = json_encode($info);
		}
		else
		{
			$info = '';
		}
		
		$result[$id] = $info;
		$updatedAt = new DateTime($row['UPDATED_AT']);
		$maxUpdatedAt = max($maxUpdatedAt, (int)$updatedAt->format('U'));
	}
	
	return array('items' => $result, 'updatedAt' => $maxUpdatedAt);
}

function getUserUpdates($updatedAt)
{
	$c = new Criteria();
	$c->addSelectColumn(kuserPeer::ID);
	$c->addSelectColumn(kuserPeer::STATUS);
	$c->addSelectColumn(kuserPeer::PUSER_ID);
	$c->addSelectColumn(kuserPeer::PARTNER_ID);
	$c->addSelectColumn(kuserPeer::UPDATED_AT);
	$c->add(kuserPeer::UPDATED_AT, $updatedAt, Criteria::GREATER_EQUAL);
	$c->addAscendingOrderByColumn(kuserPeer::UPDATED_AT);
	$c->setLimit(MAX_ITEMS);
	kuserPeer::setUseCriteriaFilter(false);
	$stmt = kuserPeer::doSelectStmt($c);
	kuserPeer::setUseCriteriaFilter(true);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$maxUpdatedAt = 0;
	$result = array();
	foreach ($rows as $row)
	{
		$status = $row['STATUS'];
		if ($status != KuserStatus::ACTIVE)
		{
			continue;
		}
		
		$id = $row['ID'];
		$puserId = $row['PUSER_ID'];
		$partnerId = $row['PARTNER_ID'];
		
		$key = md5($partnerId . '_' . strtolower($puserId));
		$result[$key] = $id;
		$updatedAt = new DateTime($row['UPDATED_AT']);
		$maxUpdatedAt = max($maxUpdatedAt, (int)$updatedAt->format('U'));
	}
	
	return array('items' => $result, 'updatedAt' => $maxUpdatedAt);
}

function getLiveUpdates($updatedAt)
{
	// must query sphinx, can be too heavy for the db when the interval is large
	$filter = new entryFilter();
	$filter->setTypeEquel(entryType::LIVE_STREAM);
	$filter->set('_gte_updated_at', $updatedAt);
	$filter->setPartnerSearchScope(baseObjectFilter::MATCH_KALTURA_NETWORK_AND_PRIVATE);
	
	$c = KalturaCriteria::create(entryPeer::OM_CLASS);
	$c->addSelectColumn(entryPeer::ID);
	$c->addSelectColumn(entryPeer::STATUS);
	$c->addSelectColumn(entryPeer::UPDATED_AT);
	$c->addAscendingOrderByColumn(entryPeer::UPDATED_AT);
	$c->setLimit(MAX_ITEMS);
	$c->setMaxRecords(MAX_ITEMS);
	
	$filter->attachToCriteria($c);
	
	entryPeer::setUseCriteriaFilter(false);
	$c->applyFilters();
	$stmt = entryPeer::doSelectStmt($c);
	entryPeer::setUseCriteriaFilter(true);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$maxUpdatedAt = 0;
	$result = array();
	foreach ($rows as $row)
	{
		$id = $row['ID'];
		$status = $row['STATUS'];
		$result[$id] = $status != entryStatus::DELETED ? '1' : '';
		$updatedAt = new DateTime($row['UPDATED_AT']);
		$maxUpdatedAt = max($maxUpdatedAt, (int)$updatedAt->format('U'));
	}

	return array('items' => $result, 'updatedAt' => $maxUpdatedAt);
}

function getDurationUpdates($updatedAt)
{
	$c = new Criteria();
	$c->addSelectColumn(entryPeer::ID);
	$c->addSelectColumn(entryPeer::STATUS);
	$c->addSelectColumn(entryPeer::LENGTH_IN_MSECS);
	$c->addSelectColumn(entryPeer::UPDATED_AT);
	$c->add(entryPeer::UPDATED_AT, $updatedAt, Criteria::GREATER_EQUAL);
	$c->addAscendingOrderByColumn(entryPeer::UPDATED_AT);
	$c->setLimit(MAX_ITEMS);
	entryPeer::setUseCriteriaFilter(false);
	$stmt = entryPeer::doSelectStmt($c);
	entryPeer::setUseCriteriaFilter(true);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$maxUpdatedAt = 0;
	$result = array();
	foreach ($rows as $row)
	{
		$id = $row['ID'];
		$status = $row['STATUS'];
		$duration = intval($row['LENGTH_IN_MSECS'] / 1000);
		$duration = ($status == entryStatus::READY && $duration > 0) ? strval($duration) : '';
		$result[$id] = $duration;
		$updatedAt = new DateTime($row['UPDATED_AT']);
		$maxUpdatedAt = max($maxUpdatedAt, (int)$updatedAt->format('U'));
	}
	
	return array('items' => $result, 'updatedAt' => $maxUpdatedAt);
}

function getCategoryEntryUpdates($updatedAt)
{
	// get the entry ids
	$c = new Criteria();
	$c->addSelectColumn(categoryEntryPeer::ENTRY_ID);
	$c->addSelectColumn(categoryEntryPeer::UPDATED_AT);
	$c->add(categoryEntryPeer::UPDATED_AT, $updatedAt, Criteria::GREATER_EQUAL);
	$c->addAscendingOrderByColumn(categoryEntryPeer::UPDATED_AT);
	$c->setLimit(MAX_ITEMS);
	categoryEntryPeer::setUseCriteriaFilter(false);
	$stmt = categoryEntryPeer::doSelectStmt($c);
	categoryEntryPeer::setUseCriteriaFilter(true);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	$maxUpdatedAt = 0;
	$result = array();
	foreach ($rows as $row)
	{
		$entryId = $row['ENTRY_ID'];
		$result[$entryId] = '';
		$updatedAt = new DateTime($row['UPDATED_AT']);
		$maxUpdatedAt = max($maxUpdatedAt, (int)$updatedAt->format('U'));
	}
	
	// get the categories
	$categoryIdsCol = 'GROUP_CONCAT('.categoryEntryPeer::CATEGORY_FULL_IDS.')';
	$c = new Criteria();
	$c->addSelectColumn(categoryEntryPeer::ENTRY_ID);
	$c->addSelectColumn($categoryIdsCol);
	$c->addGroupByColumn(categoryEntryPeer::ENTRY_ID);
	$c->add(categoryEntryPeer::ENTRY_ID, array_keys($result), Criteria::IN);
	$c->add(categoryEntryPeer::STATUS, CategoryEntryStatus::ACTIVE);
	$stmt = categoryEntryPeer::doSelectStmt($c);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
	// update the categories (an entry that wasn't fetched in the second query will remain empty)
	foreach ($rows as $row)
	{
		$entryId = $row['ENTRY_ID'];
		$categoryIds = $row[$categoryIdsCol];
		$categoryIds = str_replace('>', ',', $categoryIds);
		$categoryIds = implode(',', array_unique(explode(',', $categoryIds)));
		$result[$entryId] = $categoryIds;
	}
	
	return array('items' => $result, 'updatedAt' => $maxUpdatedAt);
}

// parse params
$params = infraRequestUtils::getRequestParams();
$requestType = isset($params['type']) ? $params['type'] : null;
$updatedAt = isset($params['updatedAt']) ? $params['updatedAt'] : 0;
$token = isset($params['token']) ? $params['token'] : '';
if (!kConf::hasParam('analytics_sync_secret') ||
	$token !== md5(kConf::get('analytics_sync_secret') . $updatedAt))
{
	die;
}

// init database
DbManager::setConfig(kConf::getDB());
DbManager::initialize();

$requestHandlers = array(
	'partner' => 'getPartnerUpdates',
	'user' => 'getUserUpdates',
	'live' => 'getLiveUpdates',
	'duration' => 'getDurationUpdates',
	'categoryEntry' => 'getCategoryEntryUpdates',
);

if (isset($requestHandlers[$requestType]))
{
	$result = call_user_func($requestHandlers[$requestType], $updatedAt);
}
else
{
	$result = array('error' => 'bad request');
}

echo json_encode($result);
die;

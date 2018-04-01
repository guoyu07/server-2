<?php
/**
 * @package plugins.reach
 * @subpackage api.objects
 */
class KalturaReachProfile extends KalturaObject implements IRelatedFilterable
{
    const MAX_DICTIONARY_LENGTH = 1000;
	/**
	 * @var int
	 * @readonly
	 * @filter eq,in,order
	 */
	public $id;

	/**
	 * @var int
	 * @readonly
	 */
	public $partnerId;

	/**
	 * @var time
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $createdAt;

	/**
	 * @var time
	 * @readonly
	 * @filter gte,lte,order
	 */
	public $updatedAt;

	/**
	 * @var KalturaReachProfileStatus
	 * @readonly
	 * @filter eq,in
	 */
	public $status;

	/**
	 * @var KalturaReachProfileType
	 * @filter eq,in
	 */
	public $profileType;

	/**
	 * @var KalturaCatalogItemLanguage
	 */
	public $defaultSourceLanguage;

	/**
	 * @var KalturaVendorCatalogItemOutputFormat
	 */
	public $defaultOutputFormat;

	/**
	 * @var KalturaNullableBoolean
	 */
	public $enableMachineModeration;

	/**
	 * @var KalturaNullableBoolean
	 */
	public $enableHumanModeration;

	/**
	 * @var KalturaNullableBoolean
	 */
	public $autoDisplayMachineCaptionsOnPlayer;

	/**
	 * @var KalturaNullableBoolean
	 */
	public $autoDisplayHumanCaptionsOnPlayer;

	/**
	 * @var KalturaNullableBoolean
	 */
	public $enableMetadataExtraction;

	/**
	 * @var KalturaNullableBoolean
	 */
	public $enableSpeakerChangeIndication;

	/**
	 * @var KalturaNullableBoolean
	 */
	public $enableAudioTags;

	/**
	 * @var KalturaNullableBoolean
	 */
	public $enableProfanityRemoval;

	/**
	 * @var int
	 */
	public $maxCharactersPerCaptionLine;
	
	/**
	 * @var KalturaReachProfileContentDeletionPolicy
	 */
	public $contentDeletionPolicy;

	/**
	 * @var KalturaRuleArray
	 */
	public $rules;

	/**
	 * @var KalturaBaseVendorCredit
	 * @requiresPermission update
	 */
	public $credit;

	/**
	 * @var int
	 * @readonly
	 */
	public $usedCredit;

	/**
	 * @var KalturaDictionaryArray
	 */
	public $dictionaries;

	private static $map_between_objects = array
	(
		'id',
		'partnerId',
		'createdAt',
		'updatedAt',
		'status',
		'profileType' => 'type',
		'defaultSourceLanguage',
		'defaultOutputFormat',
		'enableMachineModeration',
		'enableHumanModeration',
		'autoDisplayMachineCaptionsOnPlayer',
		'autoDisplayHumanCaptionsOnPlayer',
		'enableMetadataExtraction',
		'enableSpeakerChangeIndication',
		'enableAudioTags',
		'enableProfanityRemoval',
		'maxCharactersPerCaptionLine',
		'contentDeletionPolicy',
		'rules' => 'rulesArray',
		'credit',
		'usedCredit',
		'dictionaries' => 'dictionariesArray',
	);

	/* (non-PHPdoc)
	 * @see KalturaCuePoint::getMapBetweenObjects()
	 */
	public function getMapBetweenObjects()
	{
		return array_merge(parent::getMapBetweenObjects(), self::$map_between_objects);
	}

	/* (non-PHPdoc)
 	 * @see KalturaObject::toInsertableObject()
 	 */
	public function toInsertableObject($object_to_fill = null, $props_to_skip = array())
	{
		if (is_null($object_to_fill))
			$object_to_fill = new ReachProfile();

		return parent::toInsertableObject($object_to_fill, $props_to_skip);
	}

	public function validateForInsert($propertiesToSkip = array())
	{
		$this->validate();

		return parent::validateForInsert($propertiesToSkip);
	}

	public function validateForUpdate($sourceObject, $propertiesToSkip = array())
	{
		$this->validate($sourceObject);
		return parent::validateForUpdate($sourceObject, $propertiesToSkip);
	}

	private function validateDictionaryLength($data){
		return strlen($data) <= self::MAX_DICTIONARY_LENGTH ? true : false;
	}

	private function validate(ReachProfile $sourceObject = null)
	{
		if (!$sourceObject) //Source object will be null on insert
		{
			$this->validatePropertyNotNull("profileType");
			$this->validatePropertyNotNull("credit");
		}

		//validating dictionary duplications
		$languages = array();
		foreach($this->dictionaries as $dictionary)
		{
			/* @var KalturaDictionary $dictionary */
			if (in_array($dictionary->language, $languages))
				throw new KalturaAPIException(KalturaReachErrors::DICTIONARY_LANGUAGE_DUPLICATION, $dictionary->language);

			if (!$this->validateDictionaryLength($dictionary->data))
				throw new KalturaAPIException(KalturaReachErrors::MAX_DICTIONARY_LENGTH_EXCEEDED , $dictionary->language, self::MAX_DICTIONARY_LENGTH);

			$languages[] = $dictionary->language;
		}

		return;
	}

	public function getExtraFilters()
	{
		return array();
	}

	public function getFilterDocs()
	{
		return array();
	}


	/* (non-PHPdoc)
	 * @see KalturaObject::fromObject()
	 */
	public function doFromObject($dbObject, KalturaDetachedResponseProfile $responseProfile = null)
	{
		/* @var $dbObject ReachProfile */
		parent::doFromObject($dbObject, $responseProfile);

		if($this->shouldGet('credit', $responseProfile) && !is_null($dbObject->getCredit())) 
		{
			$this->credit = KalturaBaseVendorCredit::getInstance($dbObject->getCredit(),$responseProfile);
		}
	}
}
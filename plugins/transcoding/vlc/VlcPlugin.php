<?php
/**
 * @package plugins.vlc
 */
class VlcPlugin extends KalturaPlugin implements IKalturaObjectLoader, IKalturaEnumerator
{
	const PLUGIN_NAME = 'vlc';
	
	public static function getPluginName()
	{
		return self::PLUGIN_NAME;
	}
	
	/**
	 * @param string $baseClass
	 * @param string $enumValue
	 * @param array $constructorArgs
	 * @return object
	 */
	public static function loadObject($baseClass, $enumValue, array $constructorArgs = null)
	{
		if($baseClass == 'KOperationEngine' && $enumValue == KalturaConversionEngineType::VLC)
		{
			if(!isset($constructorArgs['params']) || !isset($constructorArgs['outFilePath']))
				return null;
				
			$params = $constructorArgs['params'];
			return new KOperationEngineVlc($params->vlcCmd, $constructorArgs['outFilePath']);
		}
	
		if($baseClass == 'KDLOperatorBase' && $enumValue == self::getApiValue(VlcConversionEngineType::VLC))
		{
			return new KDLOperatorVlc($enumValue);
		}
		
		return null;
	}

	/**
	 * @param string $baseClass
	 * @param string $enumValue
	 * @return string
	 */
	public static function getObjectClass($baseClass, $enumValue)
	{
		if($baseClass == 'KOperationEngine' && $enumValue == self::getApiValue(VlcConversionEngineType::VLC))
			return 'KOperationEngineVlc';
	
		if($baseClass == 'KDLOperatorBase' && $enumValue == self::getConversionEngineCoreValue(VlcConversionEngineType::VLC))
			return 'KDLOperatorVlc';
		
		return null;
	}
	
	/**
	 * @return array<string> list of enum classes names that extend the base enum name
	 */
	public static function getEnums($baseEnumName = null)
	{
		if(is_null($baseEnumName))
			return array('VlcConversionEngineType');
	
		if($baseEnumName == 'conversionEngineType')
			return array('VlcConversionEngineType');
			
		return array();
	}
	
	/**
	 * @return int id of dynamic enum in the DB.
	 */
	public static function getConversionEngineCoreValue($valueName)
	{
		$value = self::getPluginName() . IKalturaEnumerator::PLUGIN_VALUE_DELIMITER . $valueName;
		return kPluginableEnumsManager::apiToCore('conversionEngineType', $value);
	}
	
	/**
	 * @return string external API value of dynamic enum.
	 */
	public static function getApiValue($valueName)
	{
		return self::getPluginName() . IKalturaEnumerator::PLUGIN_VALUE_DELIMITER . $valueName;
	}
}

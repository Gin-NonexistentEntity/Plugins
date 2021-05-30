<?php
declare(strict_types=1);

namespace RpgItems\gin;

use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
///////////////////////////////////
use RpgItems\gin\Events\ItemEffectEvent;

class RpgItemsManager{
	//
	private static $subFolder="RpgItems";
	//
	private static $cfgFileName ="RpgItemsConfig.yml";
	//
	public static $statsDisplay;
	//@var String
	public static $StonesDisplay;
	//@var $config
	public static $RpgItemsConfig;
	//@var String[]
	public static $translateRarityColor;//=[0=>"§f", 1=>"§2", 2=>"§1", 3=>"§6", 4=>"§4"];
	//@Var arr  BaseStatsList
	public static $defaultStats;
	//  function[]
	private static $executeEffects=null;
	
	private static $riseStatsRate=[10, 20];
	
	public static function loadRpgItemsConfig($plugin){
		$file  =self::$cfgFileName;
		$resourcePath=self::$subFolder. DIRECTORY_SEPARATOR .$file;
		$dataFolderPath = $plugin->getDataFolder() . DIRECTORY_SEPARATOR . self::$subFolder . DIRECTORY_SEPARATOR ;
		
		if(!file_exists($dataFolderPath)) mkdir($dataFolderPath, 6);
		self::$RpgItemsConfig=new Config($dataFolderPath . $file, Config::YAML);
		if(self::$RpgItemsConfig->getAll() == null){
			$plugin->saveResource($resourcePath, true); 
			self::$RpgItemsConfig=new Config($dataFolderPath.$file, Config::YAML);
		}
	}

	public static function saveRpgItemConfig(){
		self::$RpgItemsConfig->save();
	}
	
	public static function init($plugin){
		$plugin->getServer()->getPluginManager()->registerEvents(new ItemEffectEvent($plugin), $plugin);
		self::loadRpgItemsConfig($plugin);
		self::$translateRarityColor=self::$RpgItemsConfig->getNested("translateRarityColor");
		self::$defaultStats	       =self::$RpgItemsConfig->getNested("defaultStats");
		self::$riseStatsRate       =self::$RpgItemsConfig->getNested("riseStatsRate");
		self::$StonesDisplay       =self::$RpgItemsConfig->getNested("StonesDisplay");
		self::$statsDisplay        =self::$RpgItemsConfig->getNested("statsDisplay");
		self::registerEffects();
	}
	
	//@para Item use RpgWeaponTrait || UpgradeStone
	public static function setDefaultStatsToItem(Item $item){
		$name=explode("\\", get_class($item));
		$item->setDefaultStats(self::$defaultStats[$name[count($name)-1]]);
	}
	
	public static function registerEffects(){
		self::$executeEffects["bloodabsorb"]=function(Item $item, $damager, $target){
			$amount = floor($item->getStatsByNestedName("basicstats.dame")*$item->getStatsByNestedName("effects.bloodabsorb")/100);
			$damager->setHealth($damager->getHealth()+$amount);
		};
		
		self::$executeEffects["doubledame"]=function(Item $item, $damager, $target){
			if(self::isProbabilityHit($item->getStatsByNestedName("effects.doubledame"))){
				$target->setHealth($target->getHealth() - $item->getStatsByNestedName("basicstats.dame")); 
			}
		};

		self::$executeEffects["causefire"]=function(Item $item, $damager, $target){
			$statValue=$item->getStatsByNestedName("effects.causefire");
			if(self::isProbabilityHit($statValue)){
				$target->setOnFire((int) ($statValue/10));
			}
		};
		
		self::$executeEffects["causepoison"]=function(Item $item, $damager, $target){
			$statValue=$item->getStatsByNestedName("effects.causepoison");
			if(self::isProbabilityHit($statValue)){
				$target->addEffect(new EffectInstance(Effect::getEffect(19), (int) $statValue*2, 1, true));
			}
		};
		
		self::$executeEffects["makeslow"]=function(Item $item, $damager, $target){
			$statValue=$item->getStatsByNestedName("effects.makeslow");
			if(self::isProbabilityHit($statValue)){
				//$attr = $damager->getAttributeMap()->getAttribute(5);
				//$attr->setValue($attr->getValue() * 0.1, true);
				//TODO
			}
		};
	}
	
	public static function levelUp($item, $player=null){
		$item->riseStatsValueByLevelUp(self::$riseStatsRate[0], self::$riseStatsRate[1]);
	}
	
	public static function applyEffects(Item $item, $damager, $target){
		if(self::$executeEffects==null) self::init();
		$effects=$item->getStatsByNestedName("effects");
		foreach($effects as $effectName => $value){
			if($value > 0 )
				self::$executeEffects[$effectName]($item, $damager, $target);
		}
	}
	
	public static function isProbabilityHit($rate){
		return (mt_rand(0, 1600) <= ($rate<<4));
	}
}
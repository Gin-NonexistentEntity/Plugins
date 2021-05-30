<?php
declare(strict_types=1);

namespace RpgItems\gin\Items;

use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
/////////////////////////////////
use RpgItems\gin\RpgItemsManager;


trait RpgWeaponTrait{
	//@var String
	private $nameOfStatsTag="Stats";
	//@var 
	private $stats=null;
	//@var  
	private $defaultStats=null;
	
	/*convert Nested tag to array type value
	@para0 NamedTag[]
	*/
	public static function parseData($data){
		if(!is_array($data)) return $data;
		foreach($data as $tagName => $tag){
			$data[$tagName] = self::parseData($tag->getValue());
		}
		return $data;
	}
	
	/*convert array type value to Nested tag
	@para0 name of tag
	@para1 value[]
	*/
	public static function parseToTag(String $name, $data){
		if(!is_array($data)){
			if	  (is_int($data))   return new IntTag( $name, $data);
			elseif(is_float($data)) return new FloatTag($name, $data);
			else  return new StringTag( $name, $data);
		}			
		$tag=new CompoundTag($name,[]);
		foreach($data as $statName => $value){
			$tag->setTag(self::parseToTag((String) $statName, $value), true);
		}
		return $tag;
	}

	/*
	@para 0: nested key || single key 
    @para 1: the key is nested or not, defautl: true
	return int || string || [] || null
	*/
	public function getStatsByNestedName($nestedKey = "", $default=null){
		$keys = explode(".", $nestedKey);
		if(($stats=$this->stats)===null){
			if(($tag=$this->getNamedTagEntry($this->nameOfStatsTag))!=null) $stats = $this->parseData($tag->getValue());
			else {$stats=$this->defaultStats; $this->initDefaultStatsTag();}
			$this->stats=$stats;
		}
		if($nestedKey==="") return $stats;
		foreach($keys as $indx => $key){
			$stats=$stats[$key] ?? null;
			if($stats==null) return $default;
		}
		return $stats;
	}
	
	/*//
	public function getStatsByNestedName($nestedKey = "", $getInCache=true){
		$keys = explode(".", $nestedKey);
		$statsTag = $this->getNamedTagEntry($this->nameOfStatsTag) ?? $this->initDefaultStatsTag();
		foreach($keys as $indx => $key){
			$statsTag=$statsTag->getValue() ?? null;
			if(is_array($statsTag)) $statsTag=$statsTag[$key];
			else return $statsTag;// return if $statsTag ==  null || === int || === float || === float
		}
		return self::parseData($statsTag->getValue());
	}
	*/
	
	/*
	set stat's value to nested CompoundTag
	@para0 nested Key (string)
	@para1 value (array, int, ....)
	WARNING old Tag with nestedKey will be overrided so shouldn't use to add tag.
	You need get all values of old tag merge to your new value array before give to @para1.
	*/
	public function setStatValueToTag($nestedKey, $value):bool{
		$keys = explode(".", $nestedKey);
		$statsTag=$this->getNamedTagEntry($this->nameOfStatsTag) ?? $this->initDefaultStatsTag();
		foreach($keys as $indx => $key){
			if($indx+1 === count($keys)) $statsTag->setTag(self::parseToTag((String) $key, $value), true);//end of array
			$statsTag=$statsTag->getTag($key) ?? new CompoundTag($key, []);
		}
		$this->stats=null;//clear stats cache
		return true;
	}
	
	public function initDefaultStatsTag(){
		$statsTag=self::parseToTag($this->nameOfStatsTag, $this->defaultStats);
		$this->setNamedTagEntry($statsTag);
		return $statsTag;
	}
	
	public function setDefaultStats($stats){
		$this->defaultStats=$stats; 
	}
	
	
	public function getDameValue(){
		return (int) $this->getStatsByNestedName("basicstats.dame");
	}
	
	public function onAttackEntity(Entity $victim) : bool{
		$this->reloadDuration();
		return $this->applyDamage(1);
	}
	
	/*
	*/
	public function setCustomName(string $name): Item{
		parent::setCustomName(RpgItemsManager::$translateRarityColor[$this->getStatsByNestedName("basicstats.rarity")].$name);
		return $this;
	}
	
	/*
	*/
	public function chiselStoneFrame():bool{
		$NoCurFrame=count($this->getStatsByNestedName("stones"));
		if($NoCurFrame < ($this->getStatsByNestedName("maxFrame") ?? 5)){
			$this->setStatValueToTag("stones.{$NoCurFrame}", 0);
			$this->reloadDisplay();
			return true;
		}
		return false;
	}
	
	/*
	attach stone by nearest slot and rise dame value
	@para0 level of stone, used to display on lore
	@para1 rise dame rate
	*/
	public function attachStone(int $newStoneLevel, $riseRates):bool{
		$frames=$this->getStatsByNestedName("stones");
		foreach($frames	 as $key => $lv){
			if($lv==0){
				$this->setStatValueToTag("stones.$key", $newStoneLevel);
				$curDame=$this->getStatsByNestedName("basicstats.dame");
				$this->setStatValueToTag("basicstats.dame",round($curDame*(1+$riseRates),4));
				$this->reloadDisplay();
				return true;
			}
		}
		return false;
	}
	
	/*
	detach stone by specify slot and decline dame value
	@para0 slot's index
	@para1 decline rate
	*/
	public function detachStone($slotFrame, $declineRate):bool{
		if($this->getStatsByNestedName("stones.$slotFrame")>0){
			$this->setStatValueToTag("stones.$slotFrame", 0);
			$curDame=$this->getStatsByNestedName("basicstats.dame");
			$this->setStatValueToTag("basicstats.dame", round($curDame/(1+$declineRate),4));
			$this->reloadDisplay();
			return true;
		}
		return false;
	}
	
	/*
	rise stats when level up
	@para0 min rise rate
	@para1 max rise rate
	*/
	public function riseStatsValueByLevelUp(int $start, int $end){
		$this->setStatValueToTag("basicstats.level", $this->getStatsByNestedName("basicstats.level")+1);
		$curDame=$this->getStatsByNestedName("basicstats.dame");
		$this->setStatValueToTag("basicstats.dame",($curDame+round($curDame*mt_rand($start, $end)/100,4)));
		foreach($this->getStatsByNestedName("effects") as $effectName => $value){
			$this->setStatValueToTag("effects.".$effectName, round($value*(1+mt_rand($start, $end)/100),4));
		}
		$this->reloadDisplay();
	}
	
	public function reloadDuration(){
		
	}
	/*update LoreTag*/
	public function reloadDisplay(){
		$listDisplay=[];
		
		$name=explode("\\", get_class($this));
		$name=$name[count($name)-1];
		foreach(RpgItemsManager::$statsDisplay[$name] as $listName => $statList){
			foreach(is_array($statList) ? $statList : [] as $statName => $line){
				$value=$this->getStatsByNestedName($listName.".".$statName);
				$value=is_string($value) ? $value: round($value,1);
				$listDisplay[]=new StringTag($statName, str_ireplace("{value}","{$value}", $line));
			}
		}
		
		//Stone Slots display
		$StonesSlot="";
		foreach($this->getStatsByNestedName("stones") as $slotName => $level){
			$StonesSlot .= RpgItemsManager::$StonesDisplay[$level];
		}
		$listDisplay[]=new StringTag("", $StonesSlot);	
		
		$loreTag=new ListTag(Item::TAG_DISPLAY_LORE,$listDisplay,8);
		$displayTag=$this->getNamedTagEntry(Item::TAG_DISPLAY) ?? new CompoundTag(Item::TAG_DISPLAY,[]);
		$displayTag->setTag($loreTag);
		$this->setNamedTagEntry($displayTag);
		return $this;
	}
	
}
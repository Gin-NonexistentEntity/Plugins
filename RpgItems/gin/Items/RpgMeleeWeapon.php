<?php
declare(strict_types=1);

namespace RpgItems\gin\Items;

use pocketmine\item\Item;
use pocketmine\item\Sword;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
/////////////////////////////////
use RpgItems\gin\RpgItemsManager;


class RpgMeleeWeapon extends Sword{
	
	public const VALUE_REF		="{value}";
	public const TAG_STATS 		="Stats";
	public const STONES_TAG		="stones";
	public const EFFECTS_TAG	="effects";
	public const MAX_STONE_TAG	="maxframe";
	public const DAME_TAG		="basicstats.dame";
	public const LEVEL_TAG		="basicstats.level";
	public const RARITY_TAG		="basicstats.rarity";
	public const DURATION_TAG	="duration";
	public const DURA_MSG		="Duration: ";
	
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
			if(($tag=$this->getNamedTagEntry(self::TAG_STATS))!=null) $stats = $this->parseData($tag->getValue());
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
		$statsTag = $this->getNamedTagEntry(self::TAG_STATS) ?? $this->initDefaultStatsTag();
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
		$statsTag=$this->getNamedTagEntry(self::TAG_STATS) ?? $this->initDefaultStatsTag();
		foreach($keys as $indx => $key){
			if($indx+1 === count($keys)) $statsTag->setTag(self::parseToTag((String) $key, $value), true);//end of array
			$statsTag=$statsTag->getTag($key) ?? new CompoundTag($key, []);
		}
		$this->stats=null;//clear stats cache
		return true;
	}
	
	public function initDefaultStatsTag(){
		$statsTag=self::parseToTag(self::TAG_STATS, $this->defaultStats);
		$this->setNamedTagEntry($statsTag);
		return $statsTag;
	}
	
	public function setDefaultStats($stats){
		$this->defaultStats=$stats; 
	}
	
	
	public function getDameValue(): int{
		return (int) $this->getStatsByNestedName(self::DAME_TAG);
	}
	
	public function getDurability(): int{
		return $this->getMaxDurability() - $this->meta;
	}
	
	/*
	*/
	public function setCustomName(string $name): Item{
		parent::setCustomName(RpgItemsManager::$translateRarityColor[$this->getStatsByNestedName(self::RARITY_TAG)].$name);
		return $this;
	}
	
	/*
	*/
	public function chiselStoneFrame():bool{
		$countFrame=count($this->getStatsByNestedName(self::STONES_TAG));
		if($countFrame < ($this->getStatsByNestedName(self::MAX_STONE_TAG) ?? 5)){
			$this->setStatValueToTag(self::STONES_TAG .".$countFrame", 0);
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
		$frames=$this->getStatsByNestedName(self::STONES_TAG);
		foreach($frames	 as $key => $lv){
			if($lv==0){
				$this->setStatValueToTag(self::STONES_TAG . "."  .$key, $newStoneLevel);
				$curDame=$this->getStatsByNestedName(self::DAME_TAG);
				$this->setStatValueToTag(self::DAME_TAG,round($curDame*(1+$riseRates),4));
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
		if($this->getStatsByNestedName(self::STONES_TAG .".$slotFrame")>0){
			$this->setStatValueToTag(self::STONES_TAG .".$slotFrame", 0);
			$curDame=$this->getStatsByNestedName(self::DAME_TAG);
			$this->setStatValueToTag(self::DAME_TAG, round($curDame/(1+$declineRate),4));
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
		$this->setStatValueToTag(self::LEVEL_TAG, $this->getStatsByNestedName(self::LEVEL_TAG)+1);
		$curDame=$this->getStatsByNestedName(self::DAME_TAG);
		$this->setStatValueToTag(self::DAME_TAG,($curDame+round($curDame*mt_rand($start, $end)/100,4)));
		foreach($this->getStatsByNestedName(self::EFFECTS_TAG) as $effectName => $value){
			$this->setStatValueToTag(self::EFFECTS_TAG .".". $effectName, round($value*(1+mt_rand($start, $end)/100),4));
		}
		$this->reloadDisplay();
	}
	
	public function reloadDuration(){
		$display=$this->getNamedTagEntry(Item::TAG_DISPLAY) ?? $this->reloadDisplay();
		$lore   =$display->getTag(Item::TAG_DISPLAY_LORE) ?? new ListTag(Item::TAG_DISPLAY_LORE, [], 8);
		$lore->offsetSet(0, new StringTag("", self::DURA_MSG . $this->getDurability()));
		$this->setNamedTagEntry($display);
	}
	
	/*update LoreTag*/
	public function reloadDisplay(){
		$listDisplay=[];
		$name=explode("\\", get_class($this));
		$name=$name[count($name)-1];
		
		$listDisplay[]=new StringTag("", self::DURA_MSG);
		foreach(RpgItemsManager::$statsDisplay[$name] as $listName => $statList){
			foreach(is_array($statList) ? $statList : [] as $statName => $line){
				$value=$this->getStatsByNestedName($listName.".".$statName);
				$value=is_string($value) ? $value: round($value,1);
				$listDisplay[]=new StringTag($statName, str_ireplace(self::VALUE_REF, $value, $line));
			}
		}
		
		//Stone Slots display
		$StonesSlot="";
		foreach($this->getStatsByNestedName(self::STONES_TAG) as $slotName => $level){
			$StonesSlot .= RpgItemsManager::$StonesDisplay[$level];
		}
		$listDisplay[]=new StringTag("", $StonesSlot);	
		
		$loreTag=new ListTag(Item::TAG_DISPLAY_LORE,$listDisplay,8);
		$displayTag=$this->getNamedTagEntry(Item::TAG_DISPLAY) ?? new CompoundTag(Item::TAG_DISPLAY,[]);
		$displayTag->setTag($loreTag);
		$this->setNamedTagEntry($displayTag);
		return $displayTag;
	}
	
}
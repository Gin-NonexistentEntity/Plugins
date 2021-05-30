<?php
declare(strict_types=1);

namespace RpgItems\gin\Items;

use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\CompoundTag;
////////////
use RpgItems\gin\RpgItemsManager;

class UpgradeStone extends Item{
	
	private $stoneLevel;
	// array
	private static $riserates;
	
	public function __construct(int $id, int $meta, $itemName, $tier){
		parent::__construct($id, $meta, $itemName);
		$this->stoneLevel=$tier;
		RpgItemsManager::setDefaultStatsToItem($this);
	}
	
	public function setDefaultStats(array $riserates){
		if(self::$riserates==null) self::$riserates=$riserates["riserates"] ?? [];
	}
	
	public function reloadDisplay(){
		$displayList=[];
		$name=$this->getClassName();
		foreach(RpgItemsManager::$statsDisplay[$name] ?? [] as $lineName => $line){
			if(is_array($line)){
				$line=$line["level{$this->stoneLevel}"]?? "";
			}else{
				$value=RpgItemsManager::$defaultStats[$name] ?? [];
				$value=$value[$lineName] ?? [];
				$value=$value["level{$this->stoneLevel}"]??null;
				$line=str_ireplace("{value}", $value, $line);
			}
			$displayList[] = new StringTag("",$line);
		}
		$displayTag=$this->getNamedTagEntry("display") ?? new CompoundTag("display", []);
		$displayTag->setTag(new ListTag("Lore", $displayList,8));
		$this->setNamedTagEntry($displayTag);
	}
	
	public function getLevel(): int{
		return $this->stoneLevel;
	}
	
	public function getUpgradeRate(){
		return self::$riserates["level{$this->getLevel()}"]??0;
	}
	
	public function getClassName(){
		$name=explode("\\", get_class($this));
		return $name[count($name)-1];
	}
}
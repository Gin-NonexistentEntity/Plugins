<?php

declare(strict_types=1);

namespace RpgItems\gin\Items;

use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\block\Block;
use RpgItems\gin\RpgItemsManager;

class RpgSword extends RpgMeleeWeapon {
	//use RpgWeaponTrait;
		//@var int[]
	public function __construct(int $id, int $meta, string $name, int $tier){
		parent::__construct($id, $meta, $name, $tier);
		RpgItemsManager::setDefaultStatsToItem($this);
	}
	
	/*
	*get attack dame
	*/
	public function getAttackPoints() : int{
		return $this->getDameValue();
	}
	
	/*
	*called when attack 
	*/
	public function onAttackEntity(Entity $victim) : bool{
		$res=$this->applyDamage(1);
		$this->reloadDuration();
		return $res;
	}
	
	/*
	*called when detroy block 
	*/
	public function onDestroyBlock(Block $block) : bool{
		return true;
	}
}
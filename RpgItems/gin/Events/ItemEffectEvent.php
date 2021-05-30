<?php
declare(strict_types=1);

namespace RpgItems\gin\Events;
///
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
/////
use RpgItems\gin\RpgItemsManager;
use RpgItems\gin\Items\RpgMeleeWeapon;
//////////////////////////////////TEST
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ItemComponentPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;

class ItemEffectEvent implements Listener {
	//use ItemRegisterManagerTrait;
	
	public function __construct($plugin){
	}
	
	public function onDamage(EntityDamageByEntityEvent $event){
		$item=$event->getDamager()->getInventory()->getItemInHand();
		if($item instanceof RpgMeleeWeapon){
			if($item->getDurability() > 1){
				RpgItemsManager::applyEffects($item, $event->getDamager(), $event->getEntity());
			}else{
				$event->setCancelled();
				$event->getDamager()->sendPopup("§4OUT OF DURABILITY!!!");
			}
		}
	}
	/*
	public function onSendPacket(DataPacketSendEvent $ev){
		if($ev->getPacket() instanceof ModalFormRequestPacket){
			echo $ev->getPacket()->formData;
		}
	}
	*/
}
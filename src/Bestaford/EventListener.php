<?php

namespace Bestaford;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerHungerChangeEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class EventListener implements Listener {
	
	private $plugin;

	function __construct(Users $plugin) {
		$this->plugin = $plugin;
	}
	public function onBlockPlace(BlockPlaceEvent $event) {
		$this->plugin->onInteract($event);
	}
	public function onBlockBreak(BlockBreakEvent $event) {
		$this->plugin->onInteract($event);
	}
	public function onItemHeld(PlayerItemHeldEvent $event) {
		$this->plugin->onInteract($event, false, true);
	}
	public function onDropItem(PlayerDropItemEvent $event) {
		$this->plugin->onInteract($event);
	}
	public function onItemConsume(PlayerItemConsumeEvent $event) {
		$this->plugin->onInteract($event);
	}
	public function onPlayerInteract(PlayerInteractEvent $event) {
		$this->plugin->onInteract($event);
	}
	public function onPlayerBucketFill(PlayerBucketFillEvent $event) {
		$this->plugin->onInteract($event);
	}
	public function onPlayerBucketEmpty(PlayerBucketEmptyEvent $event) {
		$this->plugin->onInteract($event);
	}
	public function onPlayerHungerChange(PlayerHungerChangeEvent $event) {
		$this->plugin->onInteract($event, true, false);
	}
	public function onPlayerChat(PlayerChatEvent $event) {
		$this->plugin->onChat($event);
	}
	public function onEntityDamage(EntityDamageEvent $event) {
		$this->plugin->onDamage($event);
	}
	public function onPlayerJoin(PlayerJoinEvent $event) {
		$this->plugin->onJoin($event);
	}
	public function onPlayerQuit(PlayerQuitEvent $event) {
		$this->plugin->onQuit($event);
	}
	public function onPlayerMove(PlayerMoveEvent $event) {
		$this->plugin->onMove($event);
	}
	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) {
		$this->plugin->onCommandPreprocess($event);
	}
}
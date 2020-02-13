<?php

namespace ColinHDev\Elevator\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\block\Block;

use ColinHDev\Elevator\ElevatorListener;

class PlayerInteractListener extends ElevatorListener implements Listener
{
    public function onPlayerInteract(PlayerInteractEvent $event) {
        if($event->isCancelled()) return;
        if($event->getBlock()->getId() !== Block::DAYLIGHT_SENSOR && $event->getBlock()->getId() !== Block::DAYLIGHT_SENSOR_INVERTED) return;
        if(($plot = $this->getPlugin()->myplot->getPlotByPosition($event->getPlayer()->getPosition())) === null) return;

        if($plot->owner !== $event->getPlayer()->getName() && !$event->getPlayer()->hasPermission("elevator.admin.interact")) {
            if($this->getPlugin()->config->get("helper.privateElevator") !== true) {
                $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("interact.notOwner"));
                return;
            }
            if(!$plot->isHelper($event->getPlayer()->getName())) {
                $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("interact.notOwner"));
                return;
            }
        }

        if (isset($this->getPlugin()->interactCooldown[$event->getPlayer()->getName()])) {
            if ($this->getPlugin()->interactCooldown[$event->getPlayer()->getName()] > time()) {
                $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("interact.toFast"));
                return;
            }
        }

        if($event->getBlock()->getId() === Block::DAYLIGHT_SENSOR) {
            $event->getBlock()->getLevel()->setBlock($event->getBlock()->asVector3(), Block::get(Block::DAYLIGHT_SENSOR_INVERTED));
            $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("interact.success.private"));
            $this->getPlugin()->interactCooldown[$event->getPlayer()->getName()] = time() + $this->getPlugin()->config->get("cooldown.interact");
        }else{
            $event->getBlock()->getLevel()->setBlock($event->getBlock()->asVector3(), Block::get(Block::DAYLIGHT_SENSOR));
            $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("interact.success.public"));
            $this->getPlugin()->interactCooldown[$event->getPlayer()->getName()] = time() + $this->getPlugin()->config->get("cooldown.interact");
        }
    }
}
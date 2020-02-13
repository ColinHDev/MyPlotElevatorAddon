<?php

namespace ColinHDev\Elevator\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\level\Position;

use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\level\sound\AnvilUseSound;

use ColinHDev\Elevator\ElevatorListener;

class PlayerToggleSneakListener extends ElevatorListener implements Listener {

    public function onPlayerToggleSneak(PlayerToggleSneakEvent $event) {
        if(!$event->getPlayer()->isSneaking()) return;
        $block = $event->getPlayer()->getLevel()->getBlock(new Vector3($event->getPlayer()->getX(), $event->getPlayer()->getY(), $event->getPlayer()->getZ()));
        if($block->getId() !== Block::DAYLIGHT_SENSOR && $block->getId() !== Block::DAYLIGHT_SENSOR_INVERTED) return;
        if(($plot = $this->getPlugin()->myplot->getPlotByPosition($event->getPlayer()->getPosition())) === null) return;


        if (isset($this->getPlugin()->cooldown[$event->getPlayer()->getName()])) {
            if ($this->getPlugin()->cooldown[$event->getPlayer()->getName()] > time()) return;
        }

        $searchForPrivate = true;
        if($plot->owner !== $event->getPlayer()->getName() && !$event->getPlayer()->hasPermission("elevator.admin.use")) {
            if($this->getPlugin()->config->get("helper.privateElevator") !== true) {
                $searchForPrivate = false;
            }else if(!$plot->isHelper($event->getPlayer()->getName())) {
                $searchForPrivate = false;
            }
        }


        if($this->getPlugin()->getElevators($block, "down", $searchForPrivate) === 0) {
            $event->getPlayer()->getLevel()->addSound(new AnvilUseSound($event->getPlayer()));
            $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("use.noElevator.down"));
            return;
        }

        $nextElevator = $this->getPlugin()->getNextElevator($block, "down", $searchForPrivate);
        if($nextElevator === null) {
            $event->getPlayer()->getLevel()->addSound(new AnvilUseSound($event->getPlayer()));
            $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("use.floorNotFound"));
            return;
        }
        if($nextElevator === $block) {
            $event->getPlayer()->getLevel()->addSound(new AnvilUseSound($event->getPlayer()));
            $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("use.floorNotSafe"));
            return;
        }


        $pos = new Position($nextElevator->getX() + 0.5, $nextElevator->getY() + 1, $nextElevator->getZ() + 0.5, $nextElevator->getLevel());
        $event->getPlayer()->teleport($pos, $event->getPlayer()->getYaw(), $event->getPlayer()->getPitch());

        $elevators = $this->getPlugin()->getElevators($block, "", $searchForPrivate);
        $floor = $this->getPlugin()->getFloor($nextElevator, $searchForPrivate);
        $event->getPlayer()->getLevel()->addSound(new EndermanTeleportSound($event->getPlayer()));
        $event->getPlayer()->sendTip(str_replace(["%floor%", "%maxFloor%"], [$floor, $elevators], $this->getMessage("tip.down")));

        $this->getPlugin()->cooldown[$event->getPlayer()->getName()] = time() + $this->getPlugin()->config->get("cooldown.teleport");
    }
}
<?php

namespace ColinHDev\Elevator\listener;

use pocketmine\block\BlockLegacyIds;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

use pocketmine\world\sound\AnvilUseSound;
use pocketmine\world\sound\EndermanTeleportSound;

use ColinHDev\Elevator\ElevatorListener;

class PlayerJumpListener extends ElevatorListener implements Listener {

    public function onPlayerJump(PlayerJumpEvent $event) {
        $block = $event->getPlayer()->getWorld()->getBlock(new Vector3($event->getPlayer()->getPosition()->getX(), $event->getPlayer()->getPosition()->getY(), $event->getPlayer()->getPosition()->getZ()));
        if($block->getId() !== BlockLegacyIds::DAYLIGHT_SENSOR && $block->getId() !== BlockLegacyIds::DAYLIGHT_SENSOR_INVERTED) return;
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


        if($this->getPlugin()->getElevators($block, "up", $searchForPrivate) === 0) {
            $event->getPlayer()->broadcastSound(new AnvilUseSound(), [$event->getPlayer()]);
            $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("use.noElevator.up"));
            return;
        }

        $nextElevator = $this->getPlugin()->getNextElevator($block, "up", $searchForPrivate);
        if($nextElevator === null) {
            $event->getPlayer()->broadcastSound(new AnvilUseSound(), [$event->getPlayer()]);
            $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("use.floorNotFound"));
            return;
        }
        if($nextElevator === $block) {
            $event->getPlayer()->broadcastSound(new AnvilUseSound(), [$event->getPlayer()]);
            $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("use.floorNotSafe"));
            return;
        }


        $pos = new Position($nextElevator->getPosition()->getX() + 0.5, $nextElevator->getPosition()->getY() + 1, $nextElevator->getPosition()->getZ() + 0.5, $nextElevator->getPosition()->getWorld());
        $event->getPlayer()->teleport($pos, $event->getPlayer()->getLocation()->getYaw(), $event->getPlayer()->getLocation()->getPitch());

        $elevators = $this->getPlugin()->getElevators($block, "", $searchForPrivate);
        $floor = $this->getPlugin()->getFloor($nextElevator, $searchForPrivate);
        $event->getPlayer()->broadcastSound(new EndermanTeleportSound(), [$event->getPlayer()]);
        $event->getPlayer()->sendTip(str_replace(["%floor%", "%maxFloor%"], [$floor, $elevators], $this->getMessage("tip.up")));

        $this->getPlugin()->cooldown[$event->getPlayer()->getName()] = time() + $this->getPlugin()->config->get("cooldown.teleport");
    }
}
<?php

namespace ColinHDev\Elevator\listener;

use pocketmine\block\BlockLegacyIds;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;

use ColinHDev\Elevator\ElevatorListener;

class BlockBreakListener extends ElevatorListener implements Listener {

    public function onBlockBreak(BlockBreakEvent $event) {
        if($event->isCancelled()) return;
        if($event->getBlock()->getId() !== BlockLegacyIds::DAYLIGHT_SENSOR && $event->getBlock()->getId() !== BlockLegacyIds::DAYLIGHT_SENSOR_INVERTED) return;
        if(($plot = $this->getPlugin()->myplot->getPlotByPosition($event->getPlayer()->getPosition())) === null) return;

        if($plot->owner !== $event->getPlayer()->getName() && !$event->getPlayer()->hasPermission("elevator.admin.remove")) {
            if($this->getPlugin()->config->get("helper.privateElevator") !== true) {
                $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("remove.noPermissions"));
                $event->cancel();
                return;
            }
            if(!$plot->isHelper($event->getPlayer()->getName())) {
                $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("remove.noPermissions"));
                $event->cancel();
                return;
            }
        }

        $event->getPlayer()->sendMessage($this->getPrefix() . $this->getMessage("remove.success"));
    }
}
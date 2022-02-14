<?php

namespace ColinHDev\Elevator;

use pocketmine\block\BlockLegacyIds;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;

use ColinHDev\Elevator\listener\BlockBreakListener;
use ColinHDev\Elevator\listener\BlockPlaceListener;
use ColinHDev\Elevator\listener\PlayerInteractListener;
use ColinHDev\Elevator\listener\PlayerJumpListener;
use ColinHDev\Elevator\listener\PlayerToggleSneakListener;

use MyPlot\MyPlot;
use ColinHDev\CPlot\CPlot;

class Elevator extends PluginBase
{
    public MyPlot|null $plotPlugin;

    /** @var Config $config */
    public Config $config;
    /** @var Config $messages */
    public Config $messages;

    /** @var array $cooldown */
    public array $cooldown = [];
    /** @var array $interactCooldown */
    public array $interactCooldown = [];


    public function onEnable(): void
    {
        //initMyPlot
        $this->plotPlugin = $this->getServer()->getPluginManager()->getPlugin("MyPlot");

        if($this->plotPlugin === null) {
            $this->getLogger()->error("Das Plugin \"MyPlot\" konnte auf diesem Server nicht gefunden werden.");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }


        //initConfig
        if(!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());

        $this->saveResource("config.yml", false);
        $this->saveResource("messages.yml", false);
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->messages = new Config($this->getDataFolder() . "messages.yml", Config::YAML);

        //initPermissions
        $bypassPerm = PermissionManager::getInstance()->getPermission("elevator.*");
        $bypassPerm->addChild("elevator.admin.use", true);
        $bypassPerm->addChild("elevator.admin.interact", true);
        $bypassPerm->addChild("elevator.admin.create", true);
        $bypassPerm->addChild("elevator.admin.remove", true);

        //initListener
        $this->getServer()->getPluginManager()->registerEvents(new BlockBreakListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockPlaceListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerInteractListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerJumpListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerToggleSneakListener($this), $this);
    }



    public function getElevators(Block $block, string $where = "", bool $searchForPrivate = false) : int {
        if(!$searchForPrivate) {
            $blocks = [BlockLegacyIds::DAYLIGHT_SENSOR];
        }else{
            $blocks = [BlockLegacyIds::DAYLIGHT_SENSOR, BlockLegacyIds::DAYLIGHT_SENSOR_INVERTED];
        }
        $count = 0;
        if($where === "up") {
            $y = $block->getPosition()->getY() + 1;
            while ($y < $block->getPosition()->getWorld()->getMaxY()) {
                $blockToCheck = $block->getPosition()->getWorld()->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
                if (in_array($blockToCheck->getId(), $blocks)) {
                    $count = $count + 1;
                }
                $y++;
            }
        }elseif($where === "down") {
            $y = $block->getPosition()->getY() - 1;
            while ($y >= $block->getPosition()->getWorld()->getMinY()) {
                $blockToCheck = $block->getPosition()->getWorld()->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
                if (in_array($blockToCheck->getId(), $blocks)) {
                    $count = $count + 1;
                }
                $y--;
            }
        }else {
            $y = 0;
            while ($y < $block->getPosition()->getWorld()->getMaxY()) {
                $blockToCheck = $block->getPosition()->getWorld()->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
                if (in_array($blockToCheck->getId(), $blocks)) {
                    $count = $count + 1;
                }
                $y++;
            }
        }
        return $count;
    }



    public function getNextElevator(Block $block, string $where = "", bool $searchForPrivate = false) : ?Block {
        if(!$searchForPrivate) {
            $blocks = [BlockLegacyIds::DAYLIGHT_SENSOR];
        }else{
            $blocks = [BlockLegacyIds::DAYLIGHT_SENSOR, BlockLegacyIds::DAYLIGHT_SENSOR_INVERTED];
        }
        $elevator = null;
        if($where === "up") {
            $y = $block->getPosition()->getY() + 1;
            while ($y < $block->getPosition()->getWorld()->getMaxY()) {
                $blockToCheck = $block->getPosition()->getWorld()->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
                if (in_array($blockToCheck->getId(), $blocks)) {
                    $elevator = $blockToCheck;
                    break;
                }
                $y++;
            }
        }else {
            $y = $block->getPosition()->getY() - 1;
            while ($y >= $block->getPosition()->getWorld()->getMinY()) {
                $blockToCheck = $block->getPosition()->getWorld()->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
                if (in_array($blockToCheck->getId(), $blocks)) {
                    $elevator = $blockToCheck;
                    break;
                }
                $y--;
            }
        }
        if ($elevator === null) return null;

        if($this->config->get("checkFloor") !== true) return $elevator;

        $block1 = $elevator->getPosition()->getWorld()->getBlock(new Vector3($elevator->getPosition()->getX(), $elevator->getPosition()->getY() + 1, $elevator->getPosition()->getZ()));
        $block2 = $elevator->getPosition()->getWorld()->getBlock(new Vector3($elevator->getPosition()->getX(), $elevator->getPosition()->getY() + 2, $elevator->getPosition()->getZ()));
        if($block1->getId() !== 0 || $block2->getId() !== 0) return $block;


        $blocksToCheck = [];

        $blocksToCheck[] = $block1->getPosition()->getWorld()->getBlock(new Vector3($block1->getPosition()->getX() + 1, $block1->getPosition()->getY(), $block1->getPosition()->getZ()));
        $blocksToCheck[] = $block1->getPosition()->getWorld()->getBlock(new Vector3($block1->getPosition()->getX() - 1, $block1->getPosition()->getY(), $block1->getPosition()->getZ()));
        $blocksToCheck[] = $block1->getPosition()->getWorld()->getBlock(new Vector3($block1->getPosition()->getX(), $block1->getPosition()->getY(), $block1->getPosition()->getZ() + 1));
        $blocksToCheck[] = $block1->getPosition()->getWorld()->getBlock(new Vector3($block1->getPosition()->getX(), $block1->getPosition()->getY(), $block1->getPosition()->getZ() - 1));

        $blocksToCheck[] = $block2->getPosition()->getWorld()->getBlock(new Vector3($block2->getPosition()->getX() + 1, $block2->getPosition()->getY(), $block2->getPosition()->getZ()));
        $blocksToCheck[] = $block2->getPosition()->getWorld()->getBlock(new Vector3($block2->getPosition()->getX() - 1, $block2->getPosition()->getY(), $block2->getPosition()->getZ()));
        $blocksToCheck[] = $block2->getPosition()->getWorld()->getBlock(new Vector3($block2->getPosition()->getX(), $block2->getPosition()->getY(), $block2->getPosition()->getZ() + 1));
        $blocksToCheck[] = $block2->getPosition()->getWorld()->getBlock(new Vector3($block2->getPosition()->getX(), $block2->getPosition()->getY(), $block2->getPosition()->getZ() - 1));

        $deniedBlocks = [BlockLegacyIds::LAVA, BlockLegacyIds::FLOWING_LAVA, BlockLegacyIds::WATER, BlockLegacyIds::FLOWING_WATER];
        foreach ($blocksToCheck as $blockToCheck) {
            if(in_array($blockToCheck->getId(), $deniedBlocks)) return $block;
        }

        return $elevator;
    }



    public function getFloor(Block $block, bool $searchForPrivate = false) : int {
        if(!$searchForPrivate) {
            $blocks = [BlockLegacyIds::DAYLIGHT_SENSOR];
        }else{
            $blocks = [BlockLegacyIds::DAYLIGHT_SENSOR, BlockLegacyIds::DAYLIGHT_SENSOR_INVERTED];
        }
        $sw = 0;
        $y = -1;
        while ($y < $block->getPosition()->getWorld()->getMaxY()) {
            $y++;
            $blockToCheck = $block->getPosition()->getWorld()->getBlock(new Vector3($block->getPosition()->getX(), $y, $block->getPosition()->getZ()));
            if (!in_array($blockToCheck->getId(), $blocks)) continue;
            $sw++;
            if($blockToCheck === $block) break;
        }
        return $sw;
    }
}

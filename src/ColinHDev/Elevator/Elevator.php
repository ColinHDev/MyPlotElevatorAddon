<?php

namespace ColinHDev\Elevator;

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

class Elevator extends PluginBase
{
    /** @var MyPlot $myplot */
    public $myplot;

    /** @var Config $config */
    public $config;
    /** @var Config $messages */
    public $messages;

    /** @var array $cooldown */
    public $cooldown = [];
    /** @var array $interactCooldown */
    public $interactCooldown = [];


    public function onEnable()
    {
        //initMyPlot
        $this->myplot = $this->getServer()->getPluginManager()->getPlugin("MyPlot");
        if($this->myplot === null) {
            $this->getLogger()->error("Das Plugin \"MyPlot\" konnte auf diesem Server nicht gefunden werden.");
            $this->setEnabled(false);
            return;
        }


        //initConfig
        if(!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());

        $this->saveResource("config.yml", false);
        $this->saveResource("messages.yml", false);
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->messages = new Config($this->getDataFolder() . "messages.yml", Config::YAML);

        //initListener
        $this->getServer()->getPluginManager()->registerEvents(new BlockBreakListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockPlaceListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerInteractListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerJumpListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerToggleSneakListener($this), $this);
    }



    public function getElevators(Block $block, string $where = "", bool $searchForPrivate = false) : int {
        if(!$searchForPrivate) {
            $blocks = [Block::DAYLIGHT_SENSOR];
        }else{
            $blocks = [Block::DAYLIGHT_SENSOR, Block::DAYLIGHT_SENSOR_INVERTED];
        }
        $count = 0;
        if($where === "up") {
            $y = $block->getY() + 1;
            while ($y < $block->getLevel()->getWorldHeight()) {
                $blockToCheck = $block->getLevel()->getBlock(new Vector3($block->getX(), $y, $block->getZ()));
                if (in_array($blockToCheck->getId(), $blocks)) {
                    $count = $count + 1;
                }
                $y++;
            }
        }elseif($where === "down") {
            $y = $block->getY() - 1;
            while ($y >= 0) {
                $blockToCheck = $block->getLevel()->getBlock(new Vector3($block->getX(), $y, $block->getZ()));
                if (in_array($blockToCheck->getId(), $blocks)) {
                    $count = $count + 1;
                }
                $y--;
            }
        }else {
            $y = 0;
            while ($y < $block->getLevel()->getWorldHeight()) {
                $blockToCheck = $block->getLevel()->getBlock(new Vector3($block->getX(), $y, $block->getZ()));
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
            $blocks = [Block::DAYLIGHT_SENSOR];
        }else{
            $blocks = [Block::DAYLIGHT_SENSOR, Block::DAYLIGHT_SENSOR_INVERTED];
        }
        $elevator = null;
        if($where === "up") {
            $y = $block->getY() + 1;
            while ($y < $block->getLevel()->getWorldHeight()) {
                $blockToCheck = $block->getLevel()->getBlock(new Vector3($block->getX(), $y, $block->getZ()));
                if (in_array($blockToCheck->getId(), $blocks)) {
                    $elevator = $blockToCheck;
                    break;
                }
                $y++;
            }
        }else {
            $y = $block->getY() - 1;
            while ($y >= 0) {
                $blockToCheck = $block->getLevel()->getBlock(new Vector3($block->getX(), $y, $block->getZ()));
                if (in_array($blockToCheck->getId(), $blocks)) {
                    $elevator = $blockToCheck;
                    break;
                }
                $y--;
            }
        }
        if ($elevator === null) return null;

        if($this->config->get("checkFloor") !== true) return $elevator;

        $block1 = $elevator->getLevel()->getBlock(new Vector3($elevator->getX(), $elevator->getY() + 1, $elevator->getZ()));
        $block2 = $elevator->getLevel()->getBlock(new Vector3($elevator->getX(), $elevator->getY() + 2, $elevator->getZ()));
        if($block1->getId() !== 0 || $block2->getId() !== 0) return $block;


        $blocksToCheck = [];

        $blocksToCheck[] = $block1->getLevel()->getBlock(new Vector3($block1->getX() + 1, $block1->getY(), $block1->getZ()));
        $blocksToCheck[] = $block1->getLevel()->getBlock(new Vector3($block1->getX() - 1, $block1->getY(), $block1->getZ()));
        $blocksToCheck[] = $block1->getLevel()->getBlock(new Vector3($block1->getX(), $block1->getY(), $block1->getZ() + 1));
        $blocksToCheck[] = $block1->getLevel()->getBlock(new Vector3($block1->getX(), $block1->getY(), $block1->getZ() - 1));

        $blocksToCheck[] = $block2->getLevel()->getBlock(new Vector3($block2->getX() + 1, $block2->getY(), $block2->getZ()));
        $blocksToCheck[] = $block2->getLevel()->getBlock(new Vector3($block2->getX() - 1, $block2->getY(), $block2->getZ()));
        $blocksToCheck[] = $block2->getLevel()->getBlock(new Vector3($block2->getX(), $block2->getY(), $block2->getZ() + 1));
        $blocksToCheck[] = $block2->getLevel()->getBlock(new Vector3($block2->getX(), $block2->getY(), $block2->getZ() - 1));

        $deniedBlocks = [Block::LAVA, Block::FLOWING_LAVA, Block::WATER, Block::FLOWING_WATER];
        foreach ($blocksToCheck as $blockToCheck) {
            if(in_array($blockToCheck->getId(), $deniedBlocks)) return $block;
        }

        return $elevator;
    }



    public function getFloor(Block $block, bool $searchForPrivate = false) : int {
        if(!$searchForPrivate) {
            $blocks = [Block::DAYLIGHT_SENSOR];
        }else{
            $blocks = [Block::DAYLIGHT_SENSOR, Block::DAYLIGHT_SENSOR_INVERTED];
        }
        $sw = 0;
        $y = -1;
        while ($y < $block->getLevel()->getWorldHeight()) {
            $y++;
            $blockToCheck = $block->getLevel()->getBlock(new Vector3($block->getX(), $y, $block->getZ()));
            if (!in_array($blockToCheck->getId(), $blocks)) continue;
            $sw++;
            if($blockToCheck === $block) break;
        }
        return $sw;
    }
}
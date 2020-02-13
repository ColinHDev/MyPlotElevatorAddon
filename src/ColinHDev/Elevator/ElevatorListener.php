<?php

namespace ColinHDev\Elevator;

abstract class ElevatorListener
{
    /** @var Elevator $plugin */
    private $plugin;


    public function __construct(Elevator $plugin)
    {
        $this->plugin = $plugin;
    }


    protected final function getPlugin() : Elevator {
        return $this->plugin;
    }


    protected final function getPrefix() : string {
        return ($this->plugin->messages->get("prefix") . $this->plugin->messages->get("prefixArrows"));
    }

    protected final function getMessage(string $message) : string {
        return $this->plugin->messages->get($message);
    }
}
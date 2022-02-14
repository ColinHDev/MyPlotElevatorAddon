<?php

namespace ColinHDev\Elevator;

abstract class ElevatorListener
{

    public function __construct(private Elevator $plugin) {}


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
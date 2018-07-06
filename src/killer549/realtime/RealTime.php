<?php

/*
 *  ____  _____    _    _   _____ ___ __  __ _____
 * |  _ \| ____|  / \  | | |_   _|_ _|  \/  | ____|
 * | |_) |  _|   / _ \ | |   | |  | || |\/| |  _|
 * |  _ <| |___ / ___ \| |___| |  | || |  | | |___
 * |_| \_\_____/_/   \_\_____|_| |___|_|  |_|_____|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * link: https://github.com/killer549/RealTime
*/

declare(strict_types=1);

namespace killer549\realtime;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class RealTime extends PluginBase{

	/** @var Config */
	public $config;

	/** @var int */
	private $RealTimeTaskId;

	private const PREFIX = "[RealTime] ";

	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->config = new Config($this->getDataFolder(). "config.yml", Config::YAML);
		if($this->config->get("enableTimeChange", true)) {
			$this->startTask();
		}
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
		if($command->getName() === "realtime"){
			if(count($args) === 0){
				$sender->sendMessage($command->getUsage());
				return true;
			}

			switch($args[0]){
				case "enable":
					if($this->config->get("enableTimeChange")){
						$sender->sendMessage(TextFormat::GOLD. self::PREFIX. "Already enabled!!");
					}else{
						$this->config->set("enableTimeChange", true);
						$this->config->save();
						$this->startTask();
						$sender->sendMessage(TextFormat::GOLD. self::PREFIX. "RealTime has been enabled");
					}
					break;

				case "disable":
					if(!$this->config->get("enableTimeChange")){
						$sender->sendMessage(TextFormat::GOLD. self::PREFIX. "Already disabled!!");
					}else{
						$this->config->set("enableTimeChange", false);
						$this->config->save();
						$this->cancelTask();
						$sender->sendMessage(TextFormat::GOLD. self::PREFIX. "RealTime has been disabled");
					}
					break;

				default:
					$sender->sendMessage(TextFormat::RED. $command->getUsage());
			}
		}

		return true;
	}

	public function startTask(): void{
		$this->TimeToggle(true);
		$ticks = (int) $this->config->get("check_every");
		$task = $this->getScheduler()->scheduleRepeatingTask(new ChangeTimeTask($this), $ticks);
		$this->RealTimeTaskId = $task->getTaskId();
	}

	public function cancelTask(): void{
		$this->TimeToggle(false);
		$this->getScheduler()->cancelTask($this->RealTimeTaskId);
	}

	public function TimeToggle(bool $stopTime): void{
		foreach($this->getServer()->getLevels() as $level){
			if($stopTime){
				 $level->stopTime();
			}else{
				$level->startTime();
			}
		}
	}
}
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
use pocketmine\utils\TextFormat;

class RealTime extends PluginBase{

	/** @var array */
	public $config;

	/** @var int */
	private $RealTimeTaskId;

	private const PREFIX = "[RealTime] ";

	public function onEnable(){
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->config = yaml_parse(file_get_contents($this->getDataFolder(). "config.yml"));
		$res_content = yaml_parse(file_get_contents($this->getFile(). "resources/config.yml"));
		$n_content = false;
		foreach($res_content as $key =>$value){
			if(!isset($this->config[$key])){
				$this->config[$key] = $value;
				$n_content = true;
			}
		}

		if($n_content){
			$this->getLogger()->notice("Your configuration settings have been modified. Please review config.yml");
			$this->save();
		}

		if($this->config["enableTimeChange"]) {
			$this->startTask();
		}
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
		if(!$sender->hasPermission("realtime.command.realtime")){
			$sender->sendMessage(TextFormat::RED. "You don't have permission to do this.");
			return true;
		}

		if($command->getName() === "realtime"){
			if(count($args) < 1){
				$sender->sendMessage($command->getUsage());
				return true;
			}

			switch($args[0]){
				case "enable":
				case "start":
					if($this->config["enableTimeChange"]){
						$sender->sendMessage(TextFormat::GOLD. self::PREFIX. "Already enabled!");
						return true;
					}

					$this->config["enableTimeChange"] = true;
					$this->save();
					$this->startTask();
					$sender->sendMessage(TextFormat::GOLD. self::PREFIX. "RealTime has been enabled");
					break;

				case "disable":
				case "stop":
					if(!$this->config["enableTimeChange"]){
						$sender->sendMessage(TextFormat::GOLD. self::PREFIX. "Already disabled!");
						return true;
					}

					$this->config["enableTimeChange"] = false;
					$this->save();
					$this->cancelTask();
					$sender->sendMessage(TextFormat::GOLD. self::PREFIX. "RealTime has been disabled");
					break;

				default:
					$sender->sendMessage(TextFormat::RED. $command->getUsage());
			}
		}

		return true;
	}

	public function startTask(): void{
		$this->TimeToggle(true);
		$ticks = (int) $this->config["check_every"];
		$ticks = min($ticks, 400);
		$day_start_at = $this->config["day_start_time"];
		if($day_start_at >= 24 or $day_start_at < 0){
			$this->getLogger()->notice("Day cannot start at: " . $day_start_at . " . Value will be rested to 8");
			$day_start_at = 8;
		}

		$task = $this->getScheduler()->scheduleRepeatingTask(new ChangeTimeTask($day_start_at), $ticks);
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

	public function save(){
		file_put_contents($this->getDataFolder(). "config.yml" , yaml_emit($this->config, YAML_UTF8_ENCODING));
	}
}
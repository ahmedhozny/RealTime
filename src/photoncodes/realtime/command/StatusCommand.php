<?php

/* Copyright (C) 2018-2020
 *
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
 * link: https://github.com/photoncodes/RealTime
*/

declare(strict_types=1);

namespace photoncodes\realtime\command;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class StatusCommand extends RealtimeCommands{
	public function __construct(){
		$this->setPermission("realtime.command.status");
		$this->setDescription("Returns status about the sun position and current day.");
	}

	public function do(CommandSender $sender, array $args): void{
		if(!$this->getCore()->getSettings()->isCyclingEnabled()){
			$sender->sendMessage(TextFormat::RED."RealTime is currently disabled");

			return;
		}
		$sun_angle = $this->getCore()->getGameSunPosition();
		$sender->sendMessage(TextFormat::DARK_GREEN."Current sun position: ".round($sun_angle, 2)."° (Altitude: ".round(rad2deg(asin(sin(deg2rad($sun_angle)))), 2)."°)");
		if($this->getCore()->getSettings()->isLocationCyclingEnabled()){
			$sender->sendMessage(TextFormat::DARK_GREEN."Real altitude: ".round($this->getCore()->getRealSunPosition(), 2)."°");
		}
		$sunrise = $this->getCore()->getSunriseTime() ? date("H:i", $this->getCore()->getSunriseTime()) : "Not for today";
		$noon = date("H:i", $this->getCore()->getNoonTime());
		$sunset = $this->getCore()->getSunsetTime() ? date("H:i", $this->getCore()->getSunsetTime()) : "Not for today";
		$sender->sendMessage(TextFormat::ITALIC.TextFormat::GREEN."Sunrise -> ".$sunrise);
		$sender->sendMessage(TextFormat::ITALIC.TextFormat::GREEN."Noon    -> ".$noon);
		$sender->sendMessage(TextFormat::ITALIC.TextFormat::GREEN."Sunset  -> ".$sunset);
	}
}
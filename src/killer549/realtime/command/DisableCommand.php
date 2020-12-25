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

namespace killer549\realtime\command;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class DisableCommand extends RealtimeCommands{
	public function __construct(){
		$this->setPermission("realtime.command.disable");
		$this->setDescription("Disables game time from synchronising with real-life factors.");
	}

	public function do(CommandSender $sender, array $args): void{
		if(!$this->getCore()->getSettings()->isCyclingEnabled()){
			$sender->sendMessage(TextFormat::GOLD."Already disabled!");

			return;
		}
		$this->getCore()->cancelTimeSync();
		$sender->sendMessage(TextFormat::GOLD."RealTime has been disabled");
		RealtimeCommands::broadcastCommandMessage($sender, "Disabled realtime synchronisation");
	}
}
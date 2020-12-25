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

use killer549\realtime\RealTime;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class RealtimeCommands extends Command{
	/** @var RealTime */
	private static $core;
	/** @var self[] */
	private static $children = [];

	public function __construct(RealTime $core){
		self::$core = $core;
		self::$children["current"] = new CurrentCommand();
		self::$children["disable"] = new DisableCommand();
		self::$children["enable"] = new EnableCommand();
		self::$children["help"] = new HelpCommand();
		self::$children["reload"] = new ReloadCommand();
		self::$children["status"] = new StatusCommand();
		parent::__construct("realtime", "Realtime main command", null, ["rt"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		$cmd = array_shift($args);
		if($cmd === null) $cmd = "current";
		if(!array_key_exists($cmd, self::$children)){
			$sender->sendMessage(TextFormat::RED."Invalid request. For help: /realtime help");

			return;
		}
		$cmd = self::$children[$cmd];
		if($cmd->testPermission($sender)){
			$cmd->do($sender, $args);
		}
	}

	public function returnAllChildren(): array{
		return self::$children;
	}

	protected function getCore(): RealTime{
		return self::$core;
	}

	protected function do(CommandSender $sender, array $args): void{
	}
}
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

use pocketmine\scheduler\Task;
use pocketmine\Server;

class ChangeTimeTask extends Task{

	/** @var int */
	private $day_start_at = 0;

	public function __construct(int $day_start_at){
		$this->day_start_at = $day_start_at;
	}

	public function onRun(int $currentTick){
		$day = date("N");
		$hours = date("H", time() - ($this->day_start_at * 3600));
		$mins = date("i");
		$secs = date("s");
		$secondsTime = ($hours * 3600) + ($mins * 60) + $secs;

		$tick = (int) floor($secondsTime / 86400 * 24000);
		$phase = ($day - 1) * 24000;
		if($tick >= 24000 - 3000 * $day){
			$phase += 24000;
		}

		foreach(Server::getInstance()->getLevels() as $level){
			$level->setTime($tick + $phase);
		}
	}
}
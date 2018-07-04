<?php


/*
 *  _______     _______ _   _ _____ ____   ___  _   _ _   _ ____  ____  
 * | ____\ \   / / ____| \ | |_   _/ ___| / _ \| | | | \ | |  _ \/ ___| 
 * |  _|  \ \ / /|  _| |  \| | | | \___ \| | | | | | |  \| | | | \___ \ 
 * | |___  \ V / | |___| |\  | | |  ___) | |_| | |_| | |\  | |_| |___) |
 * |_____|  \_/  |_____|_| \_| |_| |____/ \___/ \___/|_| \_|____/|____/ 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * link: https://github.com/killer549/EventSounds
*/

declare(strict_types=1);

namespace killer549\realtime;

use pocketmine\scheduler\Task;

class ChangeTimeTask extends Task{

	/** @var RealTime */
	private $core;
	
	public function __construct(RealTime $core){
		$this->core = $core;
	}
	
	public function onRun(int $currentTick){
		$hours = date("H", time() - 25200);
		$mins = date("i");
		$secs = date("s");
		$timeInSeconds = (($hours * 3600) + ($mins * 60) + $secs);

		$tick = (int) floor($timeInSeconds / 86400 * 24000);
		foreach($this->core->getServer()->getLevels() as $level){
			$level->setTime($tick);
		}
	}
}
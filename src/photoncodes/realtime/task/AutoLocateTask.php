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

namespace photoncodes\realtime\task;

use photoncodes\realtime\RealTime;
use pocketmine\plugin\PluginException;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;

class AutoLocateTask extends AsyncTask{
	/** @var bool|String */
	private $message = null;
	/** @var float */
	private $latitude, $longitude;
	/** @var string */
	private $path;

	public function __construct(float $latitude, float $longitude){
		$this->latitude = $latitude;
		$this->longitude = $longitude;
		$this->path = RealTime::getInstance()->getDataFolder();
	}

	public function onRun(){
		try{
			if(!is_file($this->path."geoplugin.yml") or yaml_parse(file_get_contents($this->path."geoplugin.yml"))["agree"] !== "TRUE"){
				throw new PluginException("[RealTime] You must accept geoPlugin policies. You may disable the auto_locate setting from config.yml");
			}
			$ip = Internet::getIP(true);
			if($ip === false or @fsockopen($ip, 80) === false){
				throw new PluginException("[RealTime] Not connected to the internet.");
			}
			if(($location_details = @file_get_contents("http://www.geoplugin.net/php.gp")) === false){
				throw new PluginException("[RealTime] Unable to connect to server.");
			}
			$location_details = unserialize($location_details);
			$location["latitude"] = (float)$location_details["geoplugin_latitude"];
			$location["longitude"] = (float)$location_details["geoplugin_longitude"];
			if($this->latitude !== $location["latitude"] or $this->longitude !== $location["longitude"]){
				file_put_contents($this->path."location.yml", yaml_emit($location));
				$this->message = "[RealTime] Location details changed. Updating...";
			}
			$this->setResult(true);
		}catch(PluginException $e){
			$this->setResult(false);
			$this->message = $e->getMessage();
		}
	}

	public function onCompletion(Server $server){
		if($this->getResult() === false){
			$server->getLogger()->error($this->message);
			$server->getLogger()->error("[RealTime] Couldn't verify location details.");

			return;
		}
		$this->message === null ?: $server->getLogger()->notice($this->message);
		RealTime::getInstance()->getSettings()->reload();
	}
}
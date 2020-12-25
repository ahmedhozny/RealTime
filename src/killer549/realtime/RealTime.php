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

namespace killer549\realtime;

use killer549\realtime\command\RealtimeCommands;
use killer549\realtime\event\DateChangeEvent;
use killer549\realtime\event\MinuteChangeEvent;
use killer549\realtime\task\AutoLocateTask;
use killer549\realtime\task\VanillaTimeSyncTask;
use killer549\realtime\task\TimeCycleTask;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class RealTime extends PluginBase implements Listener{
	/** @var self */
	private static $instance;
	/** @var SettingsManager */
	private $settings = null;
	/** @var VanillaTimeSyncTask */
	private $RealTimeTask;
	/** @var int */
	private $current_timestamp = 0, $current_date = 0, $current_week_day = 1;
	/** @var float */
	private $fractional_transit;
	/** @var int */
	private $sunriseTime, $noonTime, $sunsetTime;
	/** @var float */
	private $sin_sun_declination, $cos_sun_declination;
	/** @var float */
	private $min_altitude, $max_altitude;
	/** @var Level[] */
	private $enabled_worlds;
	private $mc_tick = 0, $mc_sun_angle = 0, $real_altitude = 0;

	public function onLoad(){
		self::$instance = $this;
		$this->saveResource("geoplugin.yml");
	}

	public function onEnable(){
		$this->settings = new SettingsManager($this);
		if($this->getSettings()->isLocationCyclingEnabled() and $this->settings->isAutoLocateEnabled()){
			$this->getServer()->getAsyncPool()->submitTask($auto_locate_task = new AutoLocateTask($this->settings->getLatitude(), $this->settings->getLongitude()));
		}
		$this->enabled_worlds = $this->settings->getEnabledWorlds() ?? $this->getServer()->getLevels();
		$this->getScheduler()->scheduleRepeatingTask(new TimeCycleTask($this), 20);
		if($this->settings->isCyclingEnabled()){
			$this->startTimeSync();
		}
		$this->getServer()->getCommandMap()->register("Realtime", new RealtimeCommands($this));
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function syncDayInfo(): void{
		$new_day = false;
		if(date("Y-m-d") !== date("Y-m-d", $this->current_date)){
			$new_day = true;
			$this->current_date = strtotime(date("Y-m-d"));
			$this->current_week_day = (int)date("N", $this->current_timestamp);
			(new DateChangeEvent($this, $this->current_date))->call();
		}
		$this->refreshTimestamp();
		if($this->current_timestamp % 60 === 0){
			(new MinuteChangeEvent($this, $this->current_timestamp))->call();
			if(($interval = $this->getSettings()->getBroadcastInterval()) > 0 and $this->current_timestamp % (60 * $interval) === 0){
				$this->broadcastTime();
			}
		}
		$this->mc_tick = $this->settings->isLocationCyclingEnabled() ? $this->cycleByLocation($new_day) : $this->cycleByTime($new_day);
		if($this->mc_tick < 12000){
			$this->mc_tick = ($this->mc_tick - 6000) * 7 / 6 + 6000;
		}elseif($this->mc_tick < 24000){
			$this->mc_tick = ($this->mc_tick - 18000) * 5 / 6 + 18000;
		}
		if($this->mc_tick < 0){
			$this->mc_tick += 24000;
		}
		$phase = $this->current_week_day * 24000;
		if($this->mc_tick >= 24000 - 3000 * $this->current_week_day){
			$phase += 24000;
		}
		$this->mc_tick += $phase;
	}

	private function cycleByTime(bool $new_day = true): int{
		$mc_tick = ($this->current_timestamp - $this->current_date) * 5 / 18 - $this->settings->getZeroTickTime();
		if($new_day){
			$this->updateSunTimings();
		}
		$this->mc_sun_angle = $mc_tick * 360 / 24000;
		$this->mc_sun_angle > 0 ?: $this->mc_sun_angle += 360;

		return (int)$mc_tick;
	}

	private function cycleByLocation(bool $new_day = true): int{
		if($new_day){
			$this->updateSunTimings();
		}
		$true_solar_time = fmod(1440 * ($this->current_timestamp - $this->current_date) / 86400 - 1440 * ($this->fractional_transit) + 720, 1440);
		$hour_angle = $true_solar_time / 4 < 0 ? $true_solar_time / 4 + 180 : $true_solar_time / 4 - 180;
		$zenith = rad2deg(acos($this->settings->getSinLatitude() * $this->sin_sun_declination + $this->settings->getCosLatitude() * $this->cos_sun_declination * cos(deg2rad($hour_angle))));
		$this->real_altitude = 90 - $zenith;
		$mc_sun_altitude = $this->real_altitude < 0 ? -$this->real_altitude / $this->min_altitude : $this->real_altitude / $this->max_altitude;
		if($hour_angle > 0){
			$mc_time = (2 - $mc_sun_altitude) * 6000;
		}elseif($mc_sun_altitude < 0){
			$mc_time = (4 + $mc_sun_altitude) * 6000;
		}else{
			$mc_time = $mc_sun_altitude * 6000;
		}
		$this->mc_sun_angle = $mc_time * 3 / 200;

		return (int)$mc_time;
	}

	public function broadcastTime(): void{
		$mode = $this->getSettings()->getBroadcastMode();
		$time = date("H:i", $this->current_timestamp);
		switch(strtolower($mode)){
			case "popup":
				$this->getServer()->broadcastPopup(TextFormat::GOLD."Current time: ".TextFormat::GRAY.$time);
				break;
			case "tip":
				$this->getServer()->broadcastTip(TextFormat::GOLD."Current time: ".TextFormat::GRAY.$time);
				break;
			case "title":
				$this->getServer()->broadcastTitle(TextFormat::GOLD.$time, TextFormat::GRAY.date("d/m/Y", $this->current_timestamp), 20, 70, 50);
				break;
			case "message":
			default:
				$this->getServer()->broadcastMessage(TextFormat::GOLD."[RealTime] Current time: ".TextFormat::GRAY.$time);
		}
	}

	public function syncVanillaTime(): void{
		foreach($this->enabled_worlds as $level){
			$level->setTime((int)$this->mc_tick);
		}
	}

	public function startTimeSync(): void{
		foreach($this->enabled_worlds as $level){
			$level->stopTime();
		}
		$this->settings->enableCycling();
		$this->getScheduler()->scheduleRepeatingTask($this->RealTimeTask = new VanillaTimeSyncTask($this), 1);
	}

	public function cancelTimeSync(): void{
		foreach($this->enabled_worlds as $level){
			$level->startTime();
		}
		$this->settings->disableCycling();
		$this->RealTimeTask->getHandler()->cancel();
	}

	public function refreshTimestamp(){
		$this->current_timestamp = time();
	}

	public function updateSunTimings(){
		if($this->settings->isLocationCyclingEnabled()){
			$current_year_day = date("z") + 1;
			$this->sin_sun_declination = 0.39795 * cos(deg2rad(0.98563 * ($current_year_day - 173)));
			$this->cos_sun_declination = cos(asin($this->sin_sun_declination));
			$sin_latitude = $this->settings->getSinLatitude();
			$cos_latitude = $this->settings->getCosLatitude();
			$this->noonTime = date_sun_info(time(), $this->settings->getLatitude(), $this->settings->getLongitude())["transit"];
			$this->fractional_transit = ($this->noonTime - $this->current_date) / 86400;
			$this->min_altitude = 90 - rad2deg(acos($sin_latitude * $this->sin_sun_declination - $cos_latitude * $this->cos_sun_declination));
			$this->max_altitude = 90 - rad2deg(acos($sin_latitude * $this->sin_sun_declination + $cos_latitude * $this->cos_sun_declination));
			$resultant_hour_angle = rad2deg(acos(-($sin_latitude * $this->sin_sun_declination) / ($cos_latitude * $this->cos_sun_declination)));
			if(!is_nan($resultant_hour_angle)){
				$resultant_true_solar_time = abs($resultant_hour_angle - 180) * 4;
				$this->sunriseTime = (int)(86400 * ($resultant_true_solar_time - 720 + (1440 * $this->fractional_transit)) / 1440 + $this->current_date);
				$this->sunsetTime = (int)(2 * $this->noonTime - $this->sunriseTime);
			}
		}else{
			$this->noonTime = $this->settings->getNoonTime() + $this->current_date;
			$this->sunriseTime = $this->noonTime - 21600;
			$this->sunsetTime = $this->noonTime + 21600;
		}
	}

	public static function getInstance(): self{
		return self::$instance;
	}

	public function getSettings(): ?SettingsManager{
		return $this->settings;
	}

	public function getCurrentTime(){
		$this->refreshTimestamp();

		return $this->current_timestamp;
	}

	public function getGameSunPosition(): float{
		return $this->mc_sun_angle;
	}

	public function getRealSunPosition(): float{
		return $this->real_altitude;
	}

	public function getNoonTime(): int{
		return $this->noonTime;
	}

	public function getSunriseTime(): ?int{
		return $this->sunriseTime;
	}

	public function getSunsetTime(): ?int{
		return $this->sunsetTime;
	}

	public function onWorldLoad(LevelLoadEvent $event){
		if(empty($this->settings->getAllWorlds())){
			$this->enabled_worlds = $this->getServer()->getLevels();

			return;
		}
		if(in_array($event->getLevel()->getName(), $this->settings->getAllWorlds())){
			$this->enabled_worlds[] = $event->getLevel();
		}
	}

	public function onWorldUnload(LevelUnloadEvent $event){
		if(($key = array_search($event->getLevel(), $this->enabled_worlds)) !== false){
			unset($this->enabled_worlds[$key]);
		}
	}
}
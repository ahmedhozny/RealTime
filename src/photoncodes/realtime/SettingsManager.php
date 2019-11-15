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

namespace photoncodes\realtime;

class SettingsManager{
	private const VERSION = "1.0";
	/** @var RealTime */
	private $core;
	/** @var string */
	private $dir_path;
	/** @var array */
	private $data;
	/** @var array */
	private $location;
	/** @var float */
	private $zero_tick_time;
	/** @var float */
	private $sin_latitude, $cos_latitude;

	public function __construct(RealTime $core){
		$this->core = $core;
		$this->dir_path = $core->getDataFolder();
		$this->initialise();
	}

	private function initialise(): void{
		@mkdir($this->dir_path);
		$this->core->saveDefaultConfig();
		$data = yaml_parse(file_get_contents($this->dir_path."config.yml"));
		$res_content = yaml_parse(stream_get_contents($this->core->getResource("config.yml")));
		if(array_diff_key($res_content, $data) or self::VERSION !== $data["version"]){
			$updated_file = [];
			foreach($res_content as $key => $val){
				$updated_file[$key] = $data[$key] ?? $val;
			}
			$updated_file["version"] = self::VERSION;
			file_put_contents($this->dir_path."config.yml", yaml_emit($updated_file, YAML_UTF8_ENCODING));
			$this->core->getLogger()->notice("Configuration settings has been updated.");
		}
		$this->data = yaml_parse(file_get_contents($this->dir_path."config.yml"));
		if($this->isLocationCyclingEnabled()){
			$this->location = ["latitude" => 0, "longitude" => 0];
			if(!is_file($this->dir_path."location.yml")){
				file_put_contents($this->dir_path."location.yml", yaml_emit($this->location));
			}
			$f_location = yaml_parse(file_get_contents($this->dir_path."location.yml"));
			if(array_diff(["latitude", "longitude"], array_keys($f_location))){
				file_put_contents($this->dir_path."location.yml", yaml_emit($this->location));
			}
			$this->location["latitude"] = $f_location["latitude"];
			$this->location["longitude"] = $f_location["longitude"];
			$this->sin_latitude = sin(deg2rad($this->location["latitude"]));
			$this->cos_latitude = cos(deg2rad($this->location["latitude"]));
		}else{
			$this->zero_tick_time = max(min($this->getNoonTime(), 86400), 0) * 5 / 18 - 6000;
			$this->zero_tick_time > 0 ?: $this->zero_tick_time += 24000;
		}
	}

	private function _get(string $key){
		return $this->data[$key] ?? yaml_parse(stream_get_contents($this->core->getResource("config.yml")))[$key];
	}

	private function _set(string $key, $value): void{
		$this->data[$key] = $value;
		$this->save();
	}

	public function reload(): void{
		$this->initialise();
		$this->core->updateSunTimings();
	}

	public function save(): void{
		file_put_contents($this->dir_path."config.yml", yaml_emit($this->data, YAML_UTF8_ENCODING));
	}

	public function isCyclingEnabled(): bool{
		return $this->_get("enable_time_sync");
	}

	public function getNoonTime(): ?int{
		$time = explode(":", $this->_get("noon_time"));
		$time = array_replace([0 => 0, 1 => 0, 2 => 0], $time);
		if($time[0] < 0 or $time[0] > 24 or $time[1] < 0 or $time[1] > 60 or $time[2] < 0 or $time[2] > 60){
			return null;
		}
		$time = $time[0] * 3600 + $time[1] * 60 + $time[2];

		return $time;
	}

	public function getZeroTickTime(): ?float{
		return $this->zero_tick_time;
	}

	public function isLocationCyclingEnabled(): bool{
		return $this->_get("location_dependant");
	}

	public function isAutoLocateEnabled(): bool{
		return $this->_get("auto_locate");
	}

	public function getEnabledWorlds(): ?array{
		if(empty($this->_get("worlds"))){
			return null;
		}
		$worlds = [];
		foreach($this->_get("worlds") as $world){
			if(($world = $this->core->getServer()->getLevelByName($world)) === null){
				continue;
			}
			$worlds[] = $world;
		}

		return $worlds;
	}

	public function getAllWorlds(): array{
		return $this->_get("worlds");
	}

	public function getLatitude(): float{
		return $this->location["latitude"];
	}

	public function getLongitude(): float{
		return $this->location["longitude"];
	}

	public function getSinLatitude(): float{
		return $this->sin_latitude;
	}

	public function getCosLatitude(): float{
		return $this->cos_latitude;
	}

	public function getBroadcastMode(): string{
		return $this->_get("broadcast_mode");
	}

	public function getBroadcastInterval(): int{
		return $this->_get("broadcast_interval");
	}

	public function enableCycling(): void{
		$this->_set("enable_time_sync", true);
	}

	public function disableCycling(): void{
		$this->_set("enable_time_sync", false);
	}
}
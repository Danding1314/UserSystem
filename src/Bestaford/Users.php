<?php

namespace Bestaford;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\scheduler\CallbackTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class Users extends PluginBase {
	
	private $db, $config, $users = [], $tasks = [];

	public function onEnable() {
		if(!is_dir($this->getDataFolder())) {
			@mkdir($this->getDataFolder());
		}
		$this->saveDefaultConfig();
		$this->config = (new Config($this->getDataFolder()."config.yml", Config::YAML))->getAll();
		$this->db = new Base($this, "users");
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$this->getLogger()->info("https://talk.24serv.pro/u/bestaford");
	}
	public function onJoin($event) {
		$player = $event->getPlayer();
		$name = $player->getName();
		$this->initialize($player);
		if($this->isRegistered($player)) {
			if($this->isLogined($player)) {
				if($this->getClientId($player) == $player->getClientId()) {
					$player->sendMessage($this->config["login_success"]);
				} else {
					$player->sendMessage($this->config["login"]);
					unset($this->users[$name]);
				}
			} else {
				$player->sendMessage($this->config["login"]);
			}
		} else {
			$this->getServer()->broadcastMessage(str_replace("%c", ($this->getUsersCount() + 1), str_replace("%p", $name, $this->config["new_player"])));
			$player->sendMessage($this->config["register"]);
		}
		$taskHandler = $this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask(array($this, "checkPlayer"), array($name)), 20 * 60);
		$this->tasks[$name] = $taskHandler->getTaskId();
	}
	public function onChat($event) {
		$player = $event->getPlayer();
		$name = $player->getName();
		$message = $event->getMessage();
		if(stripos(strtolower($message), strtolower($this->users[$name]["password"])) !== false) {
			$event->setCancelled(true);
			$player->sendMessage($this->config["password_chat"]);
		}
	}
	public function onCommandPreprocess($event) {
		$player = $event->getPlayer();
		$message = trim($event->getMessage());
		if(substr($message, 0, 1) == "/" && ((!$this->isRegistered($player)) || (!$this->isLogined($player)))) {
			$player->sendMessage($this->config["deprecate_commands"]);
			$event->setCancelled(true);
			return false;
		}
		if($this->isRegistered($player)) {
			if(!$this->isLogined($player)) {
				$event->setCancelled(true);
				$this->login($player, $message);
			}
		} else {
			$event->setCancelled(true);
			$this->register($player, $message);
		}
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if(!$sender instanceof Player) {
			$sender->sendMessage("Команда доступна только в игре.");
			return true;
		}
		$name = $sender->getName();
		switch($command->getName()) {
			case "logout":
			unset($this->users[$name]);
			$message = $this->config["logout"];
			$sender->close($message, $message);
			return true;
			case "change":
				if(count($args) < 2) {
					return false;
				}
				$oldPassword = $this->getHashedPassword($args[0]);
				$newPassword = $this->getHashedPassword($args[1]);
				$this->db->prepare("SELECT password FROM users WHERE name = :name");
				$this->db->bind(":name", strtolower($name));
				$this->db->execute();
				if($this->db->get()[0]["password"] == $oldPassword) {
					if($this->isValidPassword($sender, $args[1])) {
						$this->db->prepare("UPDATE users SET password = :password WHERE name = :name");
						$this->db->bind(":name", strtolower($name));
						$this->db->bind(":password", $newPassword);
						$this->db->execute();
						$this->users[$name]["password"] = $args[1];
						$sender->sendMessage($this->config["password_change"]);
					}
				} else {
					$sender->sendMessage($this->config["invalid_password"]);
				}
				return true;
			}
		}
		public function onMove($event) {
			$player = $event->getPlayer();
			if(!$this->isLogined($player)) {
				if(!$player->moveFirst) {
					$player->moveFirst = true;
					$player->sendTitle($this->config["welcome"], $this->config["server_name"], 20, 20, 20);
				} else {
					if(!$player->moveSecond) {
						$player->moveSecond = true;
					} else {
						$event->setCancelled(true);
					}
				}
			}
		}
		public function onQuit($event) {
			$player = $event->getPlayer();
			$this->cancelTask($player);
		}
		public function onDamage($event) {
			$player = $event->getEntity();
			if($player instanceof Player) {
				if(!$this->isLogined($player)) {
					$event->setCancelled(true);
				}
			}
		}
		public function onInteract($event, $cancel = true, $notify = true) {
			$player = $event->getPlayer();
			if(!$this->isLogined($player)) {
				if($notify) {
					$player->sendTitle($this->config["enter_password"]);
				}
				if($cancel) {
					$event->setCancelled(true);
				}
			}
		}
		public function isRegistered($player) {
			$this->db->prepare("SELECT * FROM users WHERE name = :name");
			$this->db->bind(":name", strtolower($player->getName()));
			$this->db->execute();
			return count($this->db->get()) > 0;
		}
		public function isLogined($player) {
			return isset($this->users[$player->getName()]);
		}
		public function getClientId($player) {
			return $this->users[$player->getName()]["cid"];
		}
		public function getUsersCount() {
			$this->db->prepare("SELECT * FROM users");
			$this->db->execute();
			return count($this->db->get());
		}
		public function checkPlayer($name) {
			$player = $this->getServer()->getPlayer($name);
			if($player instanceof Player) {
				if($name == $player->getName()) {
					if(!$this->isLogined($player)) {
						$error = $this->config["not_logined"];
						$player->close($error, $error);
					}
				}
			}
		}
		public function isValidPassword($player, $message) {
			if(mb_strlen($message) < 5) {
				$player->sendMessage($this->config["password_len"]);
				return false;
			}
			$symbols = ["q", "w", "e", "r", "t", "y", "u", "i", "o", "p", "a", "s", "d", "f", "g", "h", "j", "k", "l", "z", "x", "c", "v", "b", "n", "m", "1", "2", "3", "4", "5", "6", "7", "8", "9", "0", ".", "@", "#", "$", "%", "&", "-", "+", "(", ")", "*", ":", "!", "?", ",", "_"];
			foreach(preg_split("//u", $message, null, PREG_SPLIT_NO_EMPTY) as $symbol) {
				if(!in_array(strtolower($symbol), $symbols)) {
					$player->sendMessage($this->config["invalid_symbols"]);
					return false;
				}
			}
			return true;
		}
		private function cancelTask($player) {
			$name = $player->getName();
			if(isset($this->tasks[$name])) {
				$this->getServer()->getScheduler()->cancelTask($this->tasks[$name]);
				unset($this->tasks[$name]);
			}
		}
		private function initialize($player) {
			$player->first = false;
			$player->second = false;
			$player->third = false;
			$player->moveFirst = false;
			$player->moveSecond = false;
		}
		private function getHashedPassword($password) {
			return crypt(md5($password), sha1($this->config["key"]));
		}
		private function login($player, $message) {
			$name = $player->getName();
			$this->db->prepare("SELECT password FROM users WHERE name = :name");
			$this->db->bind(":name", strtolower($name));
			$this->db->execute();
			$password = $this->db->get()[0]["password"];
			if($this->getHashedPassword($message) == $password) {
				$this->users[$name] = ["cid" => $player->getClientId(), "password" => $message];
				$this->cancelTask($player);
				$player->sendMessage($this->config["login_success"]);
			} else {
				$error = $this->config["invalid_password"];
				$error_last = $this->config["invalid_password_last"];
				if(!$player->first) {
					$player->sendMessage($error." ".$error_last."3");
					$player->first = true;
				} else {
					if(!$player->second) {
						$player->sendMessage($error." ".$error_last."2");
						$player->second = true;
					} else {
						if(!$player->third) {
							$player->sendMessage($error." ".$error_last."1");
							$player->third = true;
						} else {
							$player->close($error, $error);
						}
					}
				}
			}
		}
		private function register($player, $message) {
			if(!$this->isValidPassword($player, $message)) {
				return false;
			}
			$name = $player->getName();
			$this->db->prepare("INSERT INTO users (name, password, cid, model, ip) VALUES (:name, :password, :cid, :model, :ip)");
			$this->db->bind(":name", strtolower($name));
			$this->db->bind(":password", $this->getHashedPassword($message));
			$this->db->bind(":cid", $player->getClientId());
			$this->db->bind(":model", $player->getDeviceModel());
			$this->db->bind(":ip", $player->getAddress());
			$this->db->execute();
			$this->users[$name] = ["cid" => $player->getClientId(), "password" => $message];
			$this->cancelTask($player);
			$player->sendMessage(str_replace("%p", $message, $this->config["register_success"]));
		}
	}
<?php
namespace dktapps\Uncensor;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener{

	/** @var string[] */
	private $words = [];
	/** @var string */
	private $regex;
	/** @var Config */
	private $config;

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(file_exists($this->getDataFolder() . "profanity_filter.wlist")){
			$this->words = file($this->getDataFolder() . "profanity_filter.wlist", FILE_IGNORE_NEW_LINES);
			$this->getLogger()->notice("Loaded word list!");
		}else{
			$this->getLogger()->error("Can't find word list! Please extract it from the game and place it in the plugin's data folder.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$this->saveResource("config.yml");
		
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);		
		$this->regex = '/\b(?:' . implode('|', array_map('preg_quote', $this->words)) . ')\b/iu';
	}

	private function unfilter(string $message) : string{
		return preg_replace_callback($this->regex, function($matches){
			// $vowels is the config "Before" value
			// $vowel_accents is the config "After" value
			// Get the config values as an array
			$vowels = $this->config->get("Before");
			$vowel_accents = $this->config->get("After");
			// print_r($this->config->getAll());
			return str_replace($vowels, $vowel_accents, $matches[0]);
		}, $message);
	}
	
	
	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$pk = $event->getPackets()[0]; // [0] for some reason works, and I cba to figure out why it doesn't without it
		if($pk instanceof TextPacket){
			if($pk->type !== TextPacket::TYPE_TRANSLATION){
				$pk->message = $this->unfilter($pk->message);
			}
			foreach($pk->parameters as $k => $param){
				$pk->parameters[$k] = $this->unfilter($pk->parameters[$k]);
			}
		}
	}
}

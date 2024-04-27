<?php
namespace HBIDamian\UncensorAccentEdition;

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
			$this->getLogger()->notice("Loaded profanity filter word list!");
		}else{
			$this->getLogger()->error("Can't find profanity filter word list! Please extract it from the game and place it in the plugin's data folder.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$this->saveResource("config.yml");
		
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);		
		$this->regex = '/\b(?:' . implode('|', array_map('preg_quote', $this->words)) . ')\b/iu';
	}

	private function unfilter(string $message) : string{
		return preg_replace_callback($this->regex, function($matches){
			$vowels = $this->config->get("Before");
			$vowel_accents = $this->config->get("After");
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

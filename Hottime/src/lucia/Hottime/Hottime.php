<?php

namespace lucia\Hottime;

use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;

class Hottime extends PluginBase implements Listener {
	private $conf;
	private $givingqueue = [ ];
	public function onEnable() {
		date_default_timezone_set ( "Asia/Seoul" );
		@mkdir ( $this->getDataFolder () );
		$this->conf = new Config ( $this->getDataFolder () . "config.yml", Config::YAML, [ 
				"dates" => [ 
						"Monday-21:00",
						"Tuesday-22:00" 
				],
				"message" => "@day요일 @hour시 @minute분이 되어 핫타임 아이템이 지급되었습니다.",
				"items" => [ 
						"264:0" => 64 
				] 
		] );
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
		$task = new TickingTask ( $this );
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( $task, 20 * 60 );
	}
	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer ();
		$now = date ( "l-H:i" );
		$items = $this->getHottimeItems ();
		if (in_array ( $now, $this->conf->get ( "dates", [ ] ) ) && ! isset ( $this->givingqueue [$player->getName ()] )) {
			$message = $this->conf->get ( "message" );
			$dayKr = str_replace ( [ 
					"Monday",
					"Tuesday",
					"Wednesday",
					"Thursday",
					"Friday",
					"Saturday",
					"Sunday" 
			], [ 
					"월",
					"화",
					"수",
					"목",
					"금",
					"토",
					"일" 
			], date ( 'l' ) );
			$message = str_replace ( [ 
					"@day",
					"@minute",
					"@hour" 
			], [ 
					$dayKr,
					date ( "i" ),
					date ( "H" ) 
			], $message );
			$player->sendMessage ( TextFormat::RED . TextFormat::ITALIC . "[ Hottime ] " . TextFormat::WHITE . $message );
			foreach ( $items as $item ) {
				$player->getInventory ()->addItem ( $item );
			}
		}
	}
	public function tick() {
		$now = date ( "l-H:i" );
		if (in_array ( $now, $this->conf->get ( "dates", [ ] ) )) {
			$items = $this->getHottimeItems ();
			foreach ( $this->getServer ()->getOnlinePlayers () as $p ) {
				if (isset ( $this->givingqueue [$p->getName ()] )) {
					return true;
				}
				$message = $this->conf->get ( "message" );
				$dayKr = str_replace ( [ 
						"Monday",
						"Tuesday",
						"Wednesday",
						"Thursday",
						"Friday",
						"Saturday",
						"Sunday" 
				], [ 
						"월",
						"화",
						"수",
						"목",
						"금",
						"토",
						"일" 
				], date ( 'l' ) );
				$message = str_replace ( [ 
						"@day",
						"@minute",
						"@hour" 
				], [ 
						$dayKr,
						date ( "i" ),
						date ( "H" ) 
				], $message );
				$p->sendMessage ( TextFormat::RED . TextFormat::ITALIC . "[ Hottime ] " . TextFormat::WHITE . $message );
				foreach ( $items as $item ) {
					$p->getInventory ()->addItem ( $item );
				}
				$this->givingqueue [$p->getName ()] = true;
			}
			return true;
		}
		$this->givingqueue = [ ];
	}
	/**
	 *
	 * @return Item[]
	 */
	public function getHottimeItems() {
		$return = [ ];
		foreach ( $this->conf->get ( "items" ) as $item => $count ) {
			$item = explode ( ":", $item );
			$item = Item::get ( ( int ) $item [0], ( int ) $item [1], ( int ) $count );
			if ($item instanceof Item)
				$return [] = $item;
		}
		return $return;
	}
}
class TickingTask extends Task {
	private $plugin;
	public function __construct(Hottime $plugin) {
		$this->plugin = $plugin;
	}
	public function onRun($currentTick) {
		$this->plugin->tick ();
	}
}
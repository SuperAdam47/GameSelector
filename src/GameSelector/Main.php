<?php

#  !!!  Version 1.0 !!!
#  Features:
#  - Added WaitTask
#  - Added "list" command(but not working now)
#  - Support MCPE 0.15.0/1
#  To-Do(v1.1):
#  - Add multi-commands to GS Items
#  - Fix: remove chest screen from player
#  - Save GS Item on Config!
 

namespace GameSelector;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\CustomInventory;
use pocketmine\inventory\InventoryType;
use pocketmine\inventory\BaseTransaction;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\tile\Chest;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\Plugin;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag; 
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\tile\Tile;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as c;

class Main extends PluginBase implements Listener{
	
	public $mode=0;
        public $added=0;
	public $name="";
	public $prefix="§8[§dGame§aSelector§8]§r";
	
	public function OnEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getLogger()->info($this->prefix.c::GREEN."GameSelector has been Enabled!");
		@mkdir($this->getDataFolder());
		$this->config = new Config($this->getDataFolder() . "Selectors.json", Config::JSON);
		$this->config->save();
	}
	
        public function OnDisable(){
            $this->getServer()->getLogger()->info($this->prefix.c::RED."GameSelector has been Disabled!");
        }
	public function OnCommand(CommandSender $s, Command $cmd, $label, array $args){
		if(empty($args[0])){
			$s->sendMessage($this->prefix.c::RED."You Need help?: /gs help");
		}
		if(!isset($args[0])){unset($sender,$cmd,$label,$args);return false;};
        if($s->isOp() and $s instanceof Player){
		switch($args[0]){
			case "help":
		        $s->sendMessage(c::GRAY."<---------- $this->prefix ---------->");
				$s->sendMessage(c::GREEN."/gs add <name>:§d Add the Selector");
				$s->sendMessage(c::GREEN."/gs additem <SELECTORNAME> <Item ID> <damage>:§d Add item in the Selector");
				$s->sendMessage(c::GREEN."/gs removeitem <SELECTORNAME> <Item ID> <damage>:§d Remove item from the Selector");
				$s->sendMessage(c::GREEN."/gs addcommand <SELECTORNAME> <Item ID> <Command(No /)>:§d Add Command to Selector Items");
				$s->sendMessage(c::GRAY."<---------- $this->prefix ---------->");
				break;
			case "add":
			    if((!empty($args[1])) and !($this->config->getNested("Selectors.$args[1]"))){
					$this->name=$args[1];
					$this->config->save();
					$s->sendMessage($this->prefix.c::GREEN."You select the entity now!");
					$this->mode=1;
				}else{
					$s->sendMessage($this->prefix.c::YELLOW."Usage: /gs add <name>");
				}
				break;
		    case "additem":
			    if($this->config->getNested("Selectors.$args[1]") and (!empty($args[1])) and (!empty($args[2])) and $args[2]>=0){
					$pos=$this->config->getNested("Selectors.$args[1]");
					$x=$pos["x"];
					$y=$pos["y"];
					$z=$pos["z"];
					$chest=$s->getLevel()->getTile(new Vector3($x, $y, $z));
					$chest->getInventory()->addItem(Item::get($args[2], $args[3]));
					$chest->saveNBT();
					$s->sendMessage($this->prefix.c::GOLD.$args[2]." ID's item was added to ".$args[1]);
				}else{
					$s->sendMessage($this->prefix.c::YELLOW."Usage: /gs additem <SELECTORNAME> <Item ID> <damage>");
				}
				break;
		        case "removeitem":
			case "deleteitem":
			case "delitem":
			    if($this->config->getNested("Selectors.$args[1]") and (!empty($args[1])) and (!empty($args[3])) and (!empty($args[2])) and $args[2]>=0){
					$pos=$this->config->getNested("Selectors.$args[1]");
					$x=$pos["x"];
					$y=$pos["y"];
					$z=$pos["z"];
					$chest=$s->getLevel()->getTile(new Vector3($x, $y, $z));
					$chest->getInventory()->removeItem(Item::get($args[2]), $args[3]);
					$chest->saveNBT();
					$s->sendMessage($this->prefix.c::RED.$args[2]." ID's item was removed from the ".$args[1]);
				}else{
					$s->sendMessage($this->prefix.c::YELLOW."Usage: /gs removeitem <SELECTORNAME> <Item ID> <damage>");
				}
				break;
			case "addcommand":
			    if((!empty($args[1])) and (!empty($args[2])) and (!empty($args[3]))){
                                if($s instanceof Player){
                                    if($this->config->getNested("Selectors.$args[1]")){
                                        $co=$this->config->getNested("Selectors.$args[1]");
                                        $chest=$s->getLevel()->getTile(new Vector3($co["x"], $co["y"], $co["z"]));
                                        $cn=$chest->getName();
                                        $ag=$args[2];
                                        if(isset($args[3]["/"])){
                                            $s->sendMessage("Wrong command! No /");
                                        }else{
                                            array_shift($args);
                                            array_shift($args);
                                            array_shift($args);
                                            $command = trim(implode(" ", $args));
                                            $this->config->setNested("Selectors.$cn.Commands.$ag", $command);
                                            $this->config->save();
                                            $s->sendMessage($this->prefix."$cn :Added command to $ag ID item");
                                        }
                                    }
                                }
                            }else{
								$s->sendMessage($this->prefix.c::YELLOW."Usage: /gs addcommand <SELECTORNAME> <Item ID> <Command(No /)>");
							}
				break;
		     case "list":
			$selectors=$s->getLevel()->getTiles();
                         if($selectors instanceof Chest){
                             $cn=$selectors->getName();
                             if($this->config->getNested("Selectors.$cn")){              // NOT WORKING NOW!
                                 shuffle($cn);
			         $s->sendMessage(c::GRAY."<---------- $this->prefix LIST ---------->");
			         $s->sendMessage($cn);
			         $s->sendMessage(c::GRAY."<---------- $this->prefix LIST ---------->");
                             }
                         }
			 break;
                        }
		}else{
                    $s->sendMessage(c::RED."You are not op!");
        }
	}
	
	public function OnDamage(EntityDamageEvent $event){
		if($event instanceof EntityDamageByEntityEvent){
			$player=$event->getDamager();
			$entity=$event->getEntity();
			if($player instanceof Player and $this->mode==1){
				$event->setCancelled(true);
			        $x=round($entity->getX());
			        $y=round($entity->getY() - 3);
			        $z=round($entity->getZ());
					$player->getLevel()->setBlock(new Vector3($x, $y, $z), Block::get(54));
                    $chest = new Chest($player->getLevel()->getChunk($x >> 4, $z >> 4, true), new CompoundTag(false, array(new IntTag("x", $x), new IntTag("y", $y), new IntTag("z", $z), new StringTag("id", Tile::CHEST))));
					$chest->setName($this->name);
                    $chest->saveNBT();
			        $player->getLevel()->addTile($chest);
				   $chest2=new ChestInventory($player->getLevel()->getTile(new Vector3($x, $y, $z)), $player);
				   $ch=$player->getLevel()->getTile(new Vector3($x, $y, $z));
				   $n=$this->name;
				   $ch->saveNBT();
				   $level=$player->getLevel()->getFolderName();
				   $this->config->setNested("Selectors.$n", ["x"=>$x, "y"=>$y, "z"=>$z, "level"=>$level, "Items"=>new ListTag("Items",$ch->getInventory()), "SelectorName"=>$n, "FloatingText"=>false, "FloatingTextName"=>$n]);
				   $this->config->setAll($this->config->getAll());
				   $this->config->save();
				   $player->sendMessage($this->prefix.c::GRAY."Entity Selected!");
				   $this->mode=0;
			}
			$x=round($entity->getX());
			        $y=round($entity->getY() - 3);
			        $z=round($entity->getZ());
			if($player->getLevel()->getTile(new Vector3($x, $y, $z))){
				$chest=$player->getLevel()->getTile(new Vector3($x, $y, $z));
                                $cn=$chest->getName();
				if($this->config->getNested("Selectors.$cn")){
				$event->setCancelled(true);
                                $fname=$this->config->getNested("Selectors.$cn.FloatingTextName");
                                if($this->config->getNested("Selectors.$cn.FloatingText", true)){
                                    $entity->setNameTag($cn);
                                    $entity->saveNBT();
                                }
				$player->addWindow($chest->getInventory());
			}
	             }
		}
	}
	
	public function InventoryTransactionEvent(InventoryTransactionEvent $event){
		$Transaction = $event->getTransaction();
		$Player = null;
		$name = null;
		$chest=null;
		foreach ($Transaction->getInventories() as $inv) {
			if ($inv instanceof PlayerInventory)
				$Player = $inv->getHolder();
			elseif($inv instanceof ChestInventory)
				$name = $inv->getHolder()->getName();
				$chest=$inv->getHolder();
		}
		foreach ($Transaction->getTransactions() as $t) {
			foreach ($this->traderInvTransaction($t) as $nt)
				$added [] = $nt;
		}
		foreach ($added as $item) {
			$SourceItem = $item->getSourceItem();
			$TargetItem = $item->getTargetItem();
                $TargetItemid = $TargetItem->getId();
                $SourceItemid= $SourceItem->getId();
                if($this->config->getNested("Selectors.$name.Commands.$TargetItemid") and $TargetItem->getId()>=0){
                    $event->setCancelled(true);
                    //$Player->getInventory()->clearAll();
                    $Player->removeWindow($chest->getInventory()); // why not working?
                    $command=$this->config->getNested("Selectors.$name.Commands.$TargetItemid");
                    $playername=$Player->getName();
                    $this->newTask($playername, $command);
                }
                if($this->config->getNested("Selectors.$name.Commands.$SourceItemid") and $SourceItem->getId()>=0){
                    $event->setCancelled(true);
                }
		}
		
	}
	
        public function newTask($player, $command){
		$g=new WaitTask($this, $player, $command);
		$h=$this->getServer()->getScheduler()->scheduleRepeatingTask($g, 20);
		$g->setHandler($h);
	}
        
	public function traderInvTransaction($t)
	{
		$src = clone $t->getSourceItem();
		$dst = clone $t->getTargetItem();
		if ($dst->getId() == Item::AIR)
			return [new BaseTransaction($t->getInventory(), $t->getSlot(), clone $t->getTargetItem(), clone $src)];
		if ($src->getId() == Item::AIR)
			return [new BaseTransaction($t->getInventory(), $t->getSlot(), clone $dst, clone $src)];
		if ($dst->getCount() > 0) {
			$dst->setCount(1);
			return [new BaseTransaction($t->getInventory(), $t->getSlot(), clone $t->getTargetItem(), clone $dst)];
		}
		return [];
	}
}
class WaitTask extends PluginTask{
    
    public $time=5;
    private $player;
    private $command;
    private $plugin;
    public $prefix="§8[§dGame§aSelector§8]§r";
    
    public function __construct(Plugin $plugin, $player, $command){
		parent::__construct($plugin);
		$this->main=$plugin;
                $this->player=$player;
                $this->command=$command;
    }
    
    public function OnRun($currentTick){
        $p=$this->main->getServer()->getPlayer($this->player);
        if($this->time>=0){
            $this->time--;
            $p->sendTip("§aLoading.. $this->time");
            if($this->time==0){
                $command=$this->command;
                $playername=$this->player;
                $this->main->getServer()->dispatchCommand(new ConsoleCommandSender(), str_ireplace("{player}", $playername, $command));
                $this->main->getServer()->getScheduler()->cancelTask($this->getTaskId());
            }
        }
    }
}

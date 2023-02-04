<?php

namespace haxney;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\world\Position;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\item\VanillaBlocks;
use pocketmine\item\ItemFactory;

// muqsit uses
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\transaction\SimpleInvMenuTransaction;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuEventHandler;

class Main extends PluginBase implements Listener {
  /*
  * @var data
  * return $config
  */
  public $data;
  
  /*
  /second config
  / @var sData
  / return $blocksConfig
  */
  public $sData = [];

  public function onEnable() : void {
    $this->getLogger()->info("§ahMina ativo com sucesso.");
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->generateConfig();
    if(!InvMenuHandler::isRegistered()){
      InvMenuHandler::register($this);
    }
  }
  
  public function onJoin(PlayerJoinEvent $ev) : void {
    $player = $ev->getPlayer()->getName();
    $blocksConfig = new Config($this->getDataFolder(). "blocksConfig.yml", Config::YAML, [
      
      strtolower($player) => 0
      
      ]);
    $this->sData = $blocksConfig;
      $blocksConfig->save();
  }
  
  public function generateConfig() : void {
    $msg = "deve-se manter o 'dar-picareta' com um valor positivo caso o jogador possa receber uma picareta";
    $config = new Config($this->getDataFolder(). "config.yml", Config::YAML,
    [
      "mundo-Nome" => 0,
      "dar-picareta" => 0,
       "debugMsg" => 0,
      # => $msg
      ]);
      $this->data = $config;
      $config->save();
      if(is_numeric($this->data->get("mundo-Nome"))){
        // $this->getServer()->getLogger()->critical("[hMina]Erro de Configuração na sua yml.");
        throw \Exception ("[hMina]Erro de Configuração na sua yml.", 1);
      }
  }
  
  public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
    switch($cmd->getName()){
      case "mina";
      if(!$sender->hasPermission("mina.perm")){
        $sender->sendMessage("§8[§ahMina§8]§f: Voce §cnao §fpossui permissao para executar este comando. Contate um staff.");
        return false;
      }
      $menu = InvMenu::create(InvMenu::TYPE_CHEST);
      $menu->setName("§eMenu da §aMina!");
      $invMenu = $menu->getInventory();
      
      // setando itens etc
      $playerHead = ItemFactory::getInstance()->get(397, 3, 1);
      $playerHead->setCustomName("§aOlá, Jogador.");
      $playerHead->setLore([
        "§cVoce está no mundo mina! \n",
        "§f{$sender->getName()}"
        ]);
      $invMenu->setItem(11, $playerHead);
      
      $lapis = ItemFactory::getInstance()->get(21, 0, 1);
      $lapis->setCustomName("§eChecar informações!");
      $lapis->setLore([
        "§aSua quantidade de blocos quebrados§f:  {$this->sData->get(strtolower($sender->getName()))} \n",
        "§cObs: BLOCOS QUEBRADOS DESDE A PRIMEIRA VEZ"
        ]);
      $invMenu->setItem(13, $lapis);
      
      $bar = ItemFactory::getInstance()->get(101, 0, 1);
      $bar->setCustomName("§cSair do Mundo Mina.");
      $invMenu->setItem(15, $bar);
      
      $paper = ItemFactory::getInstance()->get(339, 0, 1);
      $paper->setCustomName("§eObter recursos.");
      $paper->setLore([
        "§aClique aqui para ganhar uma picareta! \n",
        "§cOBS: apenas se a opção estiver permitida."
        ]);
      $invMenu->setItem(12, $paper);
      $menu->send($sender);
      
$menu->setListener(function(SimpleInvMenuTransaction $transaction) : InvMenuTransactionResult {
        $player = $transaction->getPlayer();
        if($transaction->getItemClicked()->getId() === 21){
          return $transaction->discard();
        }
        if($transaction->getItemClicked()->getId() === 339 && $this->data->get("dar-picareta") === true){
          $player->sendMessage($this->data->get("dar-picareta"));
          $pick = ItemFactory::getInstance()->get(278, 0, 1);
          $player->getInventory()->addItem($pick);
          $player->sendMessage("§aItem recebido.");
          return $transaction->discard();
        }
        if($transaction->getItemClicked()->getId() === 101){
          $worldName = $this->data->get("mundo-Nome");
          $player->teleport($this->getServer()->getWorldManager()->getWorldByName($worldName)->getSafeSpawn());
          $player->sendMessage("§cVoce saiu do Mundo Mina com §asucesso!");
         return $transaction->discard();
        }
        if($transaction->getItemClicked()->getId() === 397 && $transaction->getItemClicked()->getMeta() === 3){
          return $transaction->discard();
        }
        return $transaction->continue();
      });
      break;
    }
    return true;
  }
  
  public function addBlock(BlockBreakEvent $ev) : void {
    $player = $ev->getPlayer()->getName();
    $player = strtolower($player);
    $block = $ev->getBlock();
    $config = new Config($this->getDataFolder() . "blocksConfig.yml", Config::YAML);
    $current = $config->get($player, 0);
    $config->set($player, $current + 1);
    $config->save();
  }
}
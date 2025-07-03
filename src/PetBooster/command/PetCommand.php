<?php

namespace PetBooster\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use PetBooster\Main;

class PetCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("pet", "Kelola pet kamu", "/pet");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cCommand ini hanya bisa dipakai oleh pemain.");
            return;
        }

        if (isset($args[0]) && strtolower($args[0]) === "menu") {
            $this->plugin->getPetManager()->sendManageUI($sender);
        } else {
            $sender->sendMessage("§eGunakan §6/pet menu §euntuk membuka menu pet.");
        }
    }
}

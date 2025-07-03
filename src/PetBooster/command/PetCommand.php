<?php

namespace PetBooster\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\player\Player;
use PetBooster\Main;

class PetCommand implements CommandExecutor {

    public function __construct(private Main $plugin) {}

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cCommand ini hanya bisa dipakai oleh pemain.");
            return true;
        }

        if (isset($args[0]) && strtolower($args[0]) === "menu") {
            $this->plugin->getPetManager()->sendManageUI($sender);
        } else {
            $sender->sendMessage("§eGunakan §6/pet menu §euntuk membuka menu pet.");
        }

        return true;
    }
}

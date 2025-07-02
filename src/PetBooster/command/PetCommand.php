<?php

namespace PetBooster\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use PetBooster\Main;
use jojoe77777\FormAPI\SimpleForm;

class PetCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("pet", "Buka menu Pet", "/pet");
        $this->setPermission("pet.command.use");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cGunakan perintah ini di dalam game.");
            return true;
        }

        $this->sendPetUI($sender);
        return true;
    }

    public function sendPetUI(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;

            $petManager = $this->plugin->getPetManager();

            switch ($data) {
                case 0:
                    if ($petManager->hasActivePet($player)) {
                        $player->sendMessage("§cPet kamu sudah aktif!");
                    } else {
                        $type = $petManager->getActivePetType($player);
                        if ($type === null) {
                            $player->sendMessage("§cKamu belum memiliki pet aktif.");
                        } else {
                            $petManager->spawnPet($player, $type);
                            $player->sendMessage("§aPet telah dipanggil!");
                        }
                    }
                    break;

                case 1:
                    if (!$petManager->hasActivePet($player)) {
                        $player->sendMessage("§cTidak ada pet aktif.");
                    } else {
                        $petManager->despawnPet($player);
                        $player->sendMessage("§6Pet disembunyikan.");
                    }
                    break;

                case 2:
                    $petManager->sendPetListUI($player);
                    break;

                case 3:
                    $petManager->sendManageUI($player);
                    break;
            }
        });

        $form->setTitle("§l🐾 Pet Menu");
        $form->setContent("§7Kelola petmu dengan mudah:");
        $form->addButton("§a📤 Panggil Pet");
        $form->addButton("§c📥 Sembunyikan Pet");
        $form->addButton("§e📋 Daftar Pet");
        $form->addButton("§b⚙️ Kelola Pet");

        $player->sendForm($form);
    }
}

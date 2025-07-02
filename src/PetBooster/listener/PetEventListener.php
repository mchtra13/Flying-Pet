<?php

namespace PetBooster\listener;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use PetBooster\Main;
use pocketmine\block\Crops;
use pocketmine\block\Carrots;
use pocketmine\block\Potatoes;
use pocketmine\block\Beetroot;
use pocketmine\player\Player;

class PetEventListener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $player->getInventory()->getItemInHand();

        $petManager = $this->plugin->getPetManager();
        $lang = $this->plugin->getLang();

        if (!$petManager->hasActivePet($player)) return;

        $type = $petManager->getActivePetType($player);
        $xp = 0;
        $applyBonus = false;
        $langKey = "";

        $blockName = strtolower($block->getName());

        switch (strtolower($type)) {
            case "cow": // FARMING
                if (
                    $block instanceof Crops ||
                    $block instanceof Carrots ||
                    $block instanceof Potatoes ||
                    $block instanceof Beetroot
                ) {
                    $xp = 5;
                    $applyBonus = true;
                    $langKey = "pet-bonus-farming";
                }
                break;

            case "creeper": // MINING
                if (str_contains($blockName, "ore")) {
                    $xp = 7;
                    $applyBonus = true;
                    $langKey = "pet-bonus-mining";
                }
                break;

            case "enderman": // CHOPPING
                if (str_contains($blockName, "log") || str_contains($blockName, "wood")) {
                    $xp = 4;
                    $applyBonus = true;
                    $langKey = "pet-bonus-chopping";
                }
                break;
        }

        if ($xp > 0) {
            $petManager->addXp($player, $xp);
        }

        if ($applyBonus) {
            $multiplier = $petManager->getMultiplier($player);

            if ($multiplier > 1.0) {
                $drops = $block->getDropsForCompatibleTool($item);

                foreach ($drops as $drop) {
                    $bonus = clone $drop;
                    $bonus->setCount((int) round($drop->getCount() * ($multiplier - 1)));
                    if ($bonus->getCount() > 0) {
                        $player->getInventory()->addItem($bonus);
                    }
                }

                if ($langKey !== "") {
                    $msg = $lang->get($langKey, ["multiplier" => $multiplier]);
                    $player->sendPopup("§a" . $msg);
                }
            }
        }
    }
}

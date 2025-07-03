<?php

namespace PetBooster\listener;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDeathEvent;
use PetBooster\Main;
use pocketmine\block\BlockTypeIds;
use pocketmine\entity\Living;
use pocketmine\player\Player;

class PetEventListener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $type = $this->plugin->getPetManager()->getActivePetType($player);
        if ($type !== null) {
            $this->plugin->getPetManager()->spawnPet($player, $type);
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $player->getInventory()->getItemInHand();

        $petManager = $this->plugin->getPetManager();
        $lang = $this->plugin->getLang();

        if (!$petManager->hasActivePet($player)) return;

        $type = strtolower($petManager->getActivePetType($player));
        $blockName = strtolower($block->getName());

        $xp = 0;
        $applyBonus = false;
        $langKey = "";

        switch ($type) {
            case "creeper": // Mining
                if (str_contains($blockName, "ore")) {
                    $xp = 7;
                    $applyBonus = true;
                    $langKey = "pet-bonus-mining";
                }
                break;

            case "skeleton": // Double XP
                if (str_contains($blockName, "stone") || str_contains($blockName, "ore")) {
                    $xp = 10;
                    $langKey = "pet-bonus-xp";
                }
                break;

            case "piglin": // Extra gold
                if (str_contains($blockName, "gold")) {
                    $xp = 8;
                    $applyBonus = true;
                    $langKey = "pet-bonus-gold";
                }
                break;

            case "dragon": // Bonus uang 2x
                $xp = 3;
                $langKey = "pet-bonus-money";
                break;

            case "wither": // Durability simulasi
                $xp = 4;
                $player->sendPopup("§7Pet Wither memperkuat alatmu!");
                break;

            case "zombie":
                return; // zombie hanya bonus di mob kill

            case "player": // Farming
                $cropIds = [
                    BlockTypeIds::WHEAT,
                    BlockTypeIds::CARROTS,
                    BlockTypeIds::POTATOES,
                    BlockTypeIds::BEETROOTS,
                ];
                if (in_array($block->getTypeId(), $cropIds)) {
                    $xp = 5;
                    $applyBonus = true;
                    $langKey = "pet-bonus-farming";
                }
                break;

            case "enderman": // Chopping
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

    public function onEntityDeath(EntityDeathEvent $event): void {
        $entity = $event->getEntity();
        $killer = $entity instanceof Living ? $entity->getLastDamageCause()?->getDamager() : null;

        if ($killer instanceof Player) {
            $petManager = $this->plugin->getPetManager();
            $lang = $this->plugin->getLang();

            if (!$petManager->hasActivePet($killer)) return;

            $type = strtolower($petManager->getActivePetType($killer));

            if ($type === "zombie") {
                $xp = 5;
                $petManager->addXp($killer, $xp);

                $multiplier = $petManager->getMultiplier($killer);
                if ($multiplier > 1.0) {
                    $drops = $event->getDrops();
                    foreach ($drops as $drop) {
                        $bonus = clone $drop;
                        $bonus->setCount((int) round($drop->getCount() * ($multiplier - 1)));
                        if ($bonus->getCount() > 0) {
                            $killer->getInventory()->addItem($bonus);
                        }
                    }

                    $msg = $lang->get("pet-bonus-mob", ["multiplier" => $multiplier]);
                    $killer->sendPopup("§e" . $msg);
                }
            }
        }
    }
}

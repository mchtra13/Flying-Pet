<?php

namespace PetBooster\utils;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use PetBooster\Main;
use PetBooster\entity\FlyingPet;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\Server;
use pocketmine\world\World;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\BossBar;

class PetManager {

    private Main $plugin;
    private array $activePets = []; // [playerName => FlyingPet]
    private array $bossBars = [];   // [playerName => BossBar]

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function hasActivePet(Player $player): bool {
        return isset($this->activePets[$player->getName()]);
    }

    public function getActivePetType(Player $player): ?string {
        $config = $this->plugin->getPetConfig();
        return $config->getNested("players." . $player->getName() . ".active_pet");
    }

    public function spawnPet(Player $player, string $type): void {
        $location = $player->getLocation()->add(0, 1.5, 0);

        $nbt = EntityDataHelper::parseLocation($location);
        $pet = new FlyingPet($location, $nbt);
        $pet->setOwningPlayer($player);
        $pet->setPetType($type);
        $pet->spawnToAll();

        $this->activePets[$player->getName()] = $pet;

        $this->sendBossbar($player);
    }

    public function despawnPet(Player $player): void {
        $name = $player->getName();

        if (isset($this->activePets[$name])) {
            $this->activePets[$name]->flagForDespawn();
            unset($this->activePets[$name]);
        }

        if (isset($this->bossBars[$name])) {
            $this->bossBars[$name]->removePlayer($player);
            unset($this->bossBars[$name]);
        }
    }

    public function sendBossbar(Player $player): void {
        $type = $this->getActivePetType($player);
        if (!$type) return;

        $config = $this->plugin->getPetConfig();
        $level = $config->getNested("players." . $player->getName() . ".pets.$type.level", 1);
        $xp = $config->getNested("players." . $player->getName() . ".pets.$type.xp", 0);
        $needed = $this->getXpRequired($level);

        $title = $this->plugin->getLang()->get("pet-bossbar", [
            "type" => ucfirst($type),
            "level" => $level,
            "xp" => $xp,
            "needed" => $needed
        ]);

        $percent = min(1.0, $xp / $needed);

        if (isset($this->bossBars[$player->getName()])) {
            $bar = $this->bossBars[$player->getName()];
            $bar->setTitle($title);
            $bar->setPercentage($percent);
        } else {
            $bar = BossBar::create($title, $percent);
            $bar->addPlayer($player);
            $this->bossBars[$player->getName()] = $bar;
        }
    }

    public function addXp(Player $player, int $amount): void {
        $type = $this->getActivePetType($player);
        if (!$type) return;

        $config = $this->plugin->getPetConfig();
        $path = "players." . $player->getName() . ".pets.$type";

        $xp = $config->getNested("$path.xp", 0) + $amount;
        $level = $config->getNested("$path.level", 1);
        $needed = $this->getXpRequired($level);

        while ($xp >= $needed) {
            $xp -= $needed;
            $level++;
            $msg = $this->plugin->getLang()->get("pet-level-up", ["type" => ucfirst($type), "level" => $level]);
            $player->sendMessage($msg);
            $needed = $this->getXpRequired($level);
        }

        $config->setNested("$path.xp", $xp);
        $config->setNested("$path.level", $level);
        $config->save();

        $this->sendBossbar($player);
    }

    public function getXpRequired(int $level): int {
        return 100 + ($level * 50);
    }

    public function getMultiplier(Player $player): float {
        $type = $this->getActivePetType($player);
        if (!$type) return 1.0;

        $config = $this->plugin->getPetConfig();
        $level = $config->getNested("players." . $player->getName() . ".pets.$type.level", 1);

        return match (true) {
            $level >= 30 => 3.0,
            $level >= 20 => 2.5,
            $level >= 10 => 2.0,
            $level >= 5  => 1.5,
            default      => 1.0,
        };
    }

    public function sendPetListUI(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;

            $config = $this->plugin->getPetConfig();
            $pets = $config->getNested("players." . $player->getName() . ".pets", []);

            $petTypes = array_keys($pets);
            $selected = $petTypes[$data] ?? null;
            if ($selected !== null) {
                $config->setNested("players." . $player->getName() . ".active_pet", $selected);
                $config->save();

                $msg = $this->plugin->getLang()->get("pet-change-active", ["type" => ucfirst($selected)]);
                $player->sendMessage($msg);
            }
        });

        $form->setTitle("§l📋 " . $this->plugin->getLang()->get("pet-list-title"));
        $form->setContent($this->plugin->getLang()->get("pet-list-content"));

        $config = $this->plugin->getPetConfig();
        $pets = $config->getNested("players." . $player->getName() . ".pets", []);

        foreach ($pets as $type => $data) {
            $form->addButton("§b" . ucfirst($type) . " §7[Lvl {$data["level"]}]");
        }

        $player->sendForm($form);
    }

    public function sendManageUI(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;

            if ($data === 0) {
                $this->sendPetListUI($player);
            } else {
                $player->sendMessage("§c" . $this->plugin->getLang()->get("pet-manage-back"));
            }
        });

        $form->setTitle("§l⚙️ " . $this->plugin->getLang()->get("pet-manage-title"));
        $form->addButton($this->plugin->getLang()->get("pet-manage-button-select"));
        $form->addButton($this->plugin->getLang()->get("pet-manage-button-back"));

        $player->sendForm($form);
    }
}

<?php

namespace PetBooster\utils;

use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\entity\EntityDataHelper;
use PetBooster\Main;
use PetBooster\entity\FlyingPet;
use jojoe77777\FormAPI\SimpleForm;
use xenialdan\apibossbar\BossBar;

class PetManager {

    private Main $plugin;
    private array $activePets = [];
    private array $bossBars = [];

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
        $baseLocation = $player->getLocation();

        $nbt = EntityDataHelper::createBaseNBT(
            $baseLocation->add(0, 1.5, 0),
            null,
            $baseLocation->getYaw(),
            $baseLocation->getPitch()
        );

        $pet = new FlyingPet($baseLocation, $nbt);
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
            $bar = new BossBar();
            $bar->setTitle($title);
            $bar->setPercentage($percent);
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
            $player->sendMessage("§e🎉 Pet {$type} naik ke level $level!");
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
        $config = $this->plugin->getPetConfig();
        $pets = $config->getNested("players." . $player->getName() . ".pets", []);

        $form = new SimpleForm(function (Player $player, ?int $data) use ($pets) {
            if ($data === null) return;

            $petTypes = array_keys($pets);
            $selected = $petTypes[$data] ?? null;
            if ($selected !== null) {
                $config = $this->plugin->getPetConfig();
                $config->setNested("players." . $player->getName() . ".active_pet", $selected);
                $config->save();

                $player->sendMessage("§a✅ Pet aktif sekarang: §b" . ucfirst($selected));
                $this->sendBossbar($player);
            }
        });

        $form->setTitle("📋 Daftar Pet");
        $form->setContent("Pilih pet untuk diaktifkan:");

        foreach ($pets as $type => $data) {
            $form->addButton("§b" . ucfirst($type) . " §7[Lvl {$data["level"]}]");
        }

        $player->sendForm($form);
    }

    public function sendManageUI(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) return;

            match ($data) {
                0 => $this->sendPetListUI($player),
                1 => $this->sendPetShopUI($player),
                default => $player->sendMessage("§cKembali ke menu utama.")
            };
        });

        $form->setTitle("⚙️ Kelola Pet");
        $form->addButton("🟢 Pilih Pet Aktif");
        $form->addButton("🛒 Beli Pet di Toko");
        $form->addButton("❌ Kembali");

        $player->sendForm($form);
    }

    public function sendPetShopUI(Player $player): void {
        $shop = new Config($this->plugin->getDataFolder() . "petshop.yml", Config::YAML);
        $items = $shop->getAll();

        $form = new SimpleForm(function (Player $player, ?int $data) use ($shop, $items) {
            if ($data === null) return;

            $keys = array_keys($items);
            $selected = $keys[$data] ?? null;
            if ($selected !== null) {
                $price = $items[$selected]["price"];

                $eco = $this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                if ($eco !== null && $eco->myMoney($player) >= $price) {
                    $eco->reduceMoney($player, $price);

                    $config = $this->plugin->getPetConfig();
                    $config->setNested("players." . $player->getName() . ".pets.$selected.level", 1);
                    $config->setNested("players." . $player->getName() . ".pets.$selected.xp", 0);
                    $config->save();

                    $player->sendMessage("§a🎉 Kamu berhasil membeli pet §b" . ucfirst($selected));
                } else {
                    $player->sendMessage("§cUangmu tidak cukup untuk membeli pet ini!");
                }
            }
        });

        $form->setTitle("🛒 Pet Shop");

        foreach ($items as $name => $data) {
            $text = "§e" . ucfirst($name) . " §f(§a{$data["price"]}§f)";
            $image = $data["icon"] ?? "";
            $form->addButton($text, $image !== "" ? 1 : -1, $image);
        }

        $player->sendForm($form);
    }
}

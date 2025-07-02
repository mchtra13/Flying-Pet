<?php

namespace PetBooster;

use pocketmine\plugin\PluginBase;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Location;
use pocketmine\world\World;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Language;
use PetBooster\entity\FlyingPet;
use PetBooster\utils\PetManager;
use PetBooster\command\PetCommand;
use PetBooster\listener\PetEventListener;

class Main extends PluginBase {

    use SingletonTrait;

    private Config $petConfig;
    private PetManager $petManager;
    private Language $language;

    protected function onLoad(): void {
        self::setInstance($this);
    }

    protected function onEnable(): void {
        // Buat folder lang & simpan file
        @mkdir($this->getDataFolder() . "lang/");
        $this->saveResource("lang/en.yml");
        $this->saveResource("lang/id.yml");

        // Load bahasa, default EN. Ganti "en" jadi "id" kalau ingin Indonesia
        $this->language = new Language("en", $this->getDataFolder() . "lang/", "en");

        // Buat folder plugin
        @mkdir($this->getDataFolder());

        // Load atau buat pets.yml
        $this->petConfig = new Config($this->getDataFolder() . "pets.yml", Config::YAML);

        // Inisialisasi manajer pet
        $this->petManager = new PetManager($this);

        // Register entity FlyingPet
        EntityFactory::getInstance()->register(
            FlyingPet::class,
            function(World $world, CompoundTag $nbt): FlyingPet {
                return new FlyingPet(EntityDataHelper::parseLocation($nbt, $world), $nbt);
            },
            ['FlyingPet']
        );

        // Daftarkan command & event listener
        $this->getServer()->getCommandMap()->register("pet", new PetCommand($this));
        $this->getServer()->getPluginManager()->registerEvents(new PetEventListener($this), $this);

        $this->getLogger()->info("§a[PetBooster] Diaktifkan!");
    }

    protected function onDisable(): void {
        $this->petConfig->save();
        $this->getLogger()->info("§c[PetBooster] Dinonaktifkan!");
    }

    public function getLang(): Language {
        return $this->language;
    }

    public function getPetConfig(): Config {
        return $this->petConfig;
    }

    public function getPetManager(): PetManager {
        return $this->petManager;
    }
}

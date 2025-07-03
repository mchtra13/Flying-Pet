<?php

namespace PetBooster;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\EntityDataHelper;
use pocketmine\world\World;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use PetBooster\utils\Lang;
use PetBooster\command\PetCommand;
use PetBooster\entity\FlyingPet;
use PetBooster\utils\PetManager;
use PetBooster\listener\PetEventListener;

class Main extends PluginBase {

    use SingletonTrait;

    private Config $petConfig;
    private PetManager $petManager;
    private Lang $language;

    protected function onLoad(): void {
        self::setInstance($this);
    }

    protected function onEnable(): void {
        @mkdir($this->getDataFolder() . "lang/");
        $this->saveResource("lang/en.yml");
        $this->saveResource("lang/id.yml");

        $this->language = new Lang($this->getDataFolder() . "lang/", "en");

        @mkdir($this->getDataFolder());
        $this->petConfig = new Config($this->getDataFolder() . "pets.yml", Config::YAML);

        $this->petManager = new PetManager($this);
        
        // Buat petshop.yml jika belum ada
       if (!file_exists($this->getDataFolder() . "petshop.yml")) {
    $this->saveResource("petshop.yml");
}

        EntityFactory::getInstance()->register(
            FlyingPet::class,
            function(World $world, CompoundTag $nbt): FlyingPet {
                return new FlyingPet(EntityDataHelper::parseLocation($nbt, $world), $nbt);
            },
            ['FlyingPet']
        );
        
        $this->getServer()->getCommandMap()->register("pet", new PetCommand($this));
        $this->getServer()->getPluginManager()->registerEvents(new PetEventListener($this), $this);

        $this->getLogger()->info("§a[PetBooster] Diaktifkan!");
    }

    protected function onDisable(): void {
        $this->petConfig->save();
        $this->getLogger()->info("§c[PetBooster] Dinonaktifkan!");
    }

    public function getLang(): Lang {
        return $this->language;
    }

    public function getPetConfig(): Config {
        return $this->petConfig;
    }

    public function getPetManager(): PetManager {
        return $this->petManager;
    }
}

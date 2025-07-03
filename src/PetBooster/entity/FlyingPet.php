<?php

namespace PetBooster\entity;

use pocketmine\entity\Living;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\math\Vector3;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\world\particle\HappyVillagerParticle;
use pocketmine\world\sound\PopSound;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\block\utils\MobHeadType;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class FlyingPet extends Living {

    private ?Player $owner = null;
    private string $petType = "default";

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1.0, 1.0); // Lebar & tinggi entity
    }

    public static function getNetworkTypeId(): string {
        return EntityIds::ARMOR_STAND;
    }

    public function getName(): string {
        return "FlyingPet";
    }

    public function setOwningPlayer(Player $player): void {
        $this->owner = $player;
    }

    public function setPetType(string $type): void {
        $this->petType = $type;
        $this->updateAppearance();
    }

    public function updateAppearance(): void {
        if ($this->isClosed()) return;

        $headType = match (strtolower($this->petType)) {
            "creeper" => MobHeadType::CREEPER(),
            "zombie" => MobHeadType::ZOMBIE(),
            "skeleton" => MobHeadType::SKELETON(),
            "wither" => MobHeadType::WITHER_SKELETON(),
            "dragon" => MobHeadType::DRAGON(),
            "piglin" => MobHeadType::PIGLIN(),
            default => MobHeadType::PLAYER(),
        };

        $helmet = VanillaItems::MOB_HEAD()->setHeadType($headType);
        $this->getArmorInventory()->setHelmet($helmet);
    }

    public function onUpdate(int $currentTick): bool {
        $hasUpdated = parent::onUpdate($currentTick);

        if ($this->owner !== null && !$this->isClosed() && $this->owner->isOnline()) {
            $target = $this->owner->getLocation()->add(0, 2, 0);
            $dx = $target->getX() - $this->location->getX();
            $dy = $target->getY() - $this->location->getY();
            $dz = $target->getZ() - $this->location->getZ();
            $this->setMotion(new Vector3($dx * 0.2, $dy * 0.2, $dz * 0.2));

            if ($currentTick % 10 === 0) {
                $this->location->getWorld()->addParticle($this->location->asVector3(), new HappyVillagerParticle());
            }
        }

        return $hasUpdated;
    }

    public function attack(EntityDamageEvent $source): void {
        $source->cancel(); // pet tidak bisa diserang
    }

    public function getDrops(): array {
        return []; // tidak drop apapun
    }

    public function flagForDespawn(): void {
        parent::flagForDespawn();
        if ($this->owner !== null) {
            $this->getWorld()->addSound($this->getPosition(), new PopSound());
        }
    }

    public function getNameTag(): string {
        return "§bPet: " . ucfirst($this->petType);
    }

    public function isNameTagVisible(): bool {
        return true;
    }

    public function getArmorInventory(): ArmorInventory {
        return parent::getArmorInventory();
    }

    public function saveNBT(): CompoundTag {
        $nbt = parent::saveNBT();
        $nbt->setString("petType", $this->petType);
        return $nbt;
    }

    public function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);
        if ($nbt->getTag("petType") !== null) {
            $this->petType = $nbt->getString("petType");
        }
        $this->setNameTagAlwaysVisible();
        $this->setScale(0.8);
        $this->updateAppearance();
    }
}

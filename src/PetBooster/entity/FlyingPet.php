<?php

namespace PetBooster\entity;

use pocketmine\entity\Living;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\EntityDataHelper;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\world\particle\HappyVillagerParticle;
use pocketmine\world\sound\PopSound;
use pocketmine\item\VanillaItems;
use pocketmine\item\ItemIds;
use pocketmine\inventory\ArmorInventory;
use pocketmine\scheduler\ClosureTask;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class FlyingPet extends Living {

    private ?Player $owner = null;
    private string $petType = "default";

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1.0, 1.0); // width, height
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

        $helmet = match (strtolower($this->petType)) {
            "cow" => VanillaItems::LEATHER_HELMET(), // or use custom head
            "creeper" => VanillaItems::CHAINMAIL_HELMET(),
            "enderman" => VanillaItems::IRON_HELMET(),
            default => VanillaItems::GOLDEN_HELMET(),
        };

        $this->getArmorInventory()->setHelmet($helmet);
    }

    public function onUpdate(int $currentTick): bool {
        $hasUpdated = parent::onUpdate($currentTick);

        if ($this->owner !== null && !$this->isClosed() && $this->owner->isOnline()) {
            $targetPos = $this->owner->getPosition()->add(0, 2, 0);
            $this->motion = $targetPos->subtractVector($this->getPosition())->multiply(0.2);
            $this->setMotion($this->motion);

            // Partikel bonus
            if ($currentTick % 10 === 0) {
                $this->getWorld()->addParticle($this->location->asVector3(), new HappyVillagerParticle());
            }
        }

        return $hasUpdated;
    }

    public function attack(EntityDamageEvent $source): void {
        // Tidak bisa diserang
        $source->cancel();
    }

    public function getDrops(): array {
        return []; // tidak drop
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
        $this->setScale(0.8); // kecil
        $this->updateAppearance();
    }
}

# 🐾 PetBooster

PetBooster is a PocketMine-MP plugin that adds flying pet companions with level-based bonuses.

## ✨ Features

- Flying pet that follows the player using ArmorStand with animal head
- Each pet type has its own unique role:
  - 🐄 Cow → Farming (crop bonus)
  - 🧨 Creeper → Mining (ore bonus)
  - 🧱 Enderman → Chopping (wood bonus)
- Bonus drops and money scale with pet level
- Pet XP system with automatic level-up
- Real-time BossBar showing pet status
- Multi-language support: English (`en`) and Indonesian (`id`)
- Full UI to manage, select, summon, and despawn pets
- Particle effects when pet is active

## 🔧 Commands

| Command | Description            |
|---------|------------------------|
| `/pet`  | Opens the pet manager UI |

## 📂 Files

- `pets.yml` – stores all player pet data
- `lang/en.yml` and `lang/id.yml` – language translations

## 📌 Requirements

- PocketMine-MP API 5.x
- [FormAPI](https://github.com/jojoe77777/FormAPI)

## 📥 Installation

1. Download or clone this plugin to your `plugins/` folder.
2. Make sure you have FormAPI installed.
3. Start your server and enjoy the pets!

## 📜 License

This plugin is custom-made. Do not redistribute without permission.

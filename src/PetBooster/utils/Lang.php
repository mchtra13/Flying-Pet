<?php

namespace PetBooster\utils;

use pocketmine\utils\Config;

class Lang {

    private array $messages = [];
    private string $language;

    public function __construct(string $folder, string $language = "en") {
        $this->language = $language;

        $file = $folder . $language . ".yml";
        if (!file_exists($file)) {
            $file = $folder . "en.yml"; // fallback
        }

        $config = new Config($file, Config::YAML);
        $this->messages = $config->getAll();
    }

    public function get(string $key, array $replacements = []): string {
        $msg = $this->messages[$key] ?? $key;

        foreach ($replacements as $search => $replace) {
            $msg = str_replace("{" . $search . "}", (string)$replace, $msg);
        }

        return $msg;
    }

    public function getLang(): string {
        return $this->language;
    }
}

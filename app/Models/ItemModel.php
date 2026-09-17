<?php

declare(strict_types=1);

namespace App\Models;

class ItemModel
{
    /**
     * Restituisce il percorso assoluto del file JSON
     */
    private static function getDataPath(): string
    {
        return BASEPATH . '/data/content.json';
    }

    /**
     * Recupera tutti gli elementi (es. per la pagina archivio/servizi)
     */
    public static function getAll(): array
    {
        $file = self::getDataPath();
        
        if (!file_exists($file)) {
            // Logica di fallback se il file non esiste
            error_log("File JSON non trovato: " . $file);
            return [];
        }

        $json = file_get_contents($file);
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Trova un singolo elemento partendo dal suo slug
     */
    public static function getBySlug(string $slug): ?array
    {
        $items = self::getAll();
        
        foreach ($items as $item) {
            if (isset($item['slug']) && $item['slug'] === $slug) {
                return $item;
            }
        }
        
        return null;
    }
}
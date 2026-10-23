<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Versione "automatica" dell'app: cambia da sola quando cambia un file JS/CSS/manifest.
 * Nessuna colonna nel DB e nessun numero da ricordarsi di aggiornare a mano.
 */
final class AppVersion
{
    public static function current(): string
    {
        $files = array_merge(
            glob(BASEPATH . '/public/assets/js/*.js') ?: [],
            glob(BASEPATH . '/public/assets/css/*.css') ?: [],
            [BASEPATH . '/public/manifest.json']
        );

        $parts = [];
        foreach ($files as $file) {
            if (is_file($file)) {
                $parts[] = basename($file) . ':' . filemtime($file);
            }
        }
        sort($parts);

        return substr(md5(implode('|', $parts)), 0, 12);
    }
}

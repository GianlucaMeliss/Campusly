<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppVersion;
use App\Core\Http;

class ApiController
{
    public function getAppVersion(): void
    {
        Http::json(
            ['version' => AppVersion::current()],
            200,
            ['Cache-Control' => 'no-store, max-age=0']
        );
    }
}

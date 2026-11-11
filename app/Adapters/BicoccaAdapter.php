<?php
declare(strict_types=1);

namespace App\Adapters;

class BicoccaAdapter extends StataleAdapter
{
    protected const API_URL = 'https://gestioneorari.didattica.unimib.it/PortaleStudentiUnimib/grid_call.php';
    protected const UNI_LABEL = 'Bicocca';
}

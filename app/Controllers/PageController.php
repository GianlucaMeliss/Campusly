<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\ItemModel;

final class PageController
{
    public function about(): void
    {
        View::render('pages/about', [
            'pageTitle' => 'About',
            'pageDescription' => 'Pagina istituzionale base del template.',
            'pageCss' => 'about',
        ]);
    }

    /**
     * Mostra l'elenco di tutti gli elementi (es. pagina "I nostri servizi")
     */
    public function archive(): void
    {
        $items = ItemModel::getAll();

        View::render('pages/archive', [
            'pageTitle' => 'I nostri servizi',
            'pageDescription' => 'Esplora le nostre soluzioni.',
            'items' => $items
        ]);
    }

    /**
     * Mostra la pagina di dettaglio del singolo elemento (es. servizio specifico)
     */
    public function showItem(string $slug): void
    {
        $item = ItemModel::getBySlug($slug);

        if (!$item) {
            $this->notFound();
            return;
        }

        View::render('pages/single', [
            'pageTitle' => $item['title'],
            'pageDescription' => $item['short_desc'],
            'item' => $item
        ]);
    }

    public function notFound(): void
    {
        http_response_code(404);

        View::render('pages/404', [
            'pageTitle' => '404 - Page not found',
            'pageDescription' => 'La pagina richiesta non è disponibile.',
        ]);
    }
} 
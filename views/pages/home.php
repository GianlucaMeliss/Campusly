<?php
$basePath = rtrim($basePath ?? '', '/');
$homeUrl = ($basePath !== '' ? $basePath : '') . '/';
$aboutUrl = ($basePath !== '' ? $basePath : '') . '/chi-siamo';
$sectionsUrl = ($basePath !== '' ? $basePath : '') . '/#sections';
?>

<section class="hero">
    <div class="container">
        <p class="eyebrow">Starter template</p>
        <h1>Base neutra per siti e servizi web.</h1>
        <p class="lead">
            Questo progetto è pensato per essere duplicato, adattato e personalizzato
            rapidamente per clienti, brand o prodotti differenti.
        </p>

        <div class="hero-actions">
            <a href="<?= htmlspecialchars($aboutUrl) ?>" class="btn btn-primary">Scopri la struttura</a>
            <a href="<?= htmlspecialchars($sectionsUrl) ?>" class="btn btn-secondary">Vedi le sezioni base</a>
        </div>
    </div>
</section>

<section id="sections" class="section">
    <div class="container">
        <div class="section-heading">
            <h2>Cosa include questa base</h2>
            <p>Solo gli elementi essenziali per partire in modo ordinato.</p>
        </div>

        <div class="grid grid-3">
            <article class="card">
                <h3>Core pulito</h3>
                <p>Bootstrap semplice, router minimale e rendering con layout condivisi.</p>
            </article>

            <article class="card">
                <h3>Pagine neutre</h3>
                <p>Contenuti placeholder chiari, senza copy verticale o riferimenti di settore.</p>
            </article>

            <article class="card">
                <h3>Facile da estendere</h3>
                <p>La struttura è pronta per aggiungere servizi, pagine, componenti e dati dinamici.</p>
            </article>
        </div>
    </div>
</section>

<section class="section section-muted">
    <div class="container narrow">
        <div class="section-heading">
            <h2>Come usarlo</h2>
        </div>

        <p>
            Duplica il progetto, aggiorna la configurazione, sostituisci i testi placeholder
            e poi costruisci sopra moduli, contenuti e componenti specifici per il caso reale.
        </p>
    </div>
</section>
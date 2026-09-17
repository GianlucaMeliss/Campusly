<?php
$basePath = rtrim($basePath ?? '', '/');
$homeUrl = ($basePath !== '' ? $basePath : '') . '/';
$aboutUrl = ($basePath !== '' ? $basePath : '') . '/chi-siamo';
?>

<section class="section">
    <div class="container narrow">
        <div class="error-page">
            <p class="eyebrow">Errore 404</p>
            <h1>Pagina non trovata</h1>
            <p class="lead">
                Il contenuto richiesto non è disponibile oppure l’indirizzo inserito non è corretto.
            </p>

            <div class="hero-actions">
                <a href="<?= htmlspecialchars($homeUrl) ?>" class="btn btn-primary">Torna alla home</a>
                <a href="<?= htmlspecialchars($aboutUrl) ?>" class="btn btn-secondary">Vai a chi siamo</a>
            </div>
        </div>
    </div>
</section>
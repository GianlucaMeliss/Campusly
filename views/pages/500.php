<?php
$basePath = rtrim($basePath ?? '', '/');
$homeUrl = ($basePath !== '' ? $basePath : '') . '/';
?>

<section class="section">
    <div class="container narrow">
        <div class="error-page" style="text-align: center; padding: 4rem 0;">
            <p class="eyebrow" style="color: var(--col-primary); font-weight: 600;">Errore 500</p>
            <h1>Qualcosa è andato storto</h1>
            <p class="lead">
                Abbiamo riscontrato un errore tecnico imprevisto. I nostri sistemi hanno appena registrato il problema e ce ne stiamo occupando.
            </p>

            <div class="hero-actions" style="margin-top: 2rem; justify-content: center;">
                <a href="<?= htmlspecialchars($homeUrl) ?>" class="btn btn-primary">Riprova tornando alla Home</a>
            </div>
        </div>
    </div>
</section>
<section class="page-hero">
    <div class="container narrow">
        <a href="/" class="hero-back-btn">
            <i data-lucide="arrow-left"></i> Torna alla Home
        </a>
        <h1><?= htmlspecialchars($pageTitle ?? 'I nostri servizi') ?></h1>
        <p class="lead">
            <?= htmlspecialchars($pageDescription ?? 'Esplora le nostre soluzioni.') ?>
        </p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="grid grid-3">
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                    <article class="card">
                        <div class="card-icon" style="margin-bottom: 1rem; color: var(--col-primary);">
                            <i data-lucide="<?= htmlspecialchars($item['icon'] ?? 'box') ?>" style="width: 32px; height: 32px;"></i>
                        </div>
                        <h3><?= htmlspecialchars($item['title']) ?></h3>
                        <p><?= htmlspecialchars($item['short_desc'] ?? '') ?></p>
                        
                        <a href="<?= htmlspecialchars($app['site']['base_path'] ?? '') ?>/servizi/<?= htmlspecialchars($item['slug']) ?>" class="btn btn-secondary" style="margin-top: 1rem; display: inline-block;">
                            Scopri di più
                        </a>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Nessun elemento trovato al momento.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
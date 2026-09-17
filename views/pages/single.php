<section class="page-hero">
    <div class="container narrow">
        <a href="<?= htmlspecialchars($app['site']['base_path'] ?? '') ?>/servizi" class="hero-back-btn">
            <i data-lucide="arrow-left"></i> Torna ai servizi
        </a>
        
        <div style="margin-top: 2rem;">
            <i data-lucide="<?= htmlspecialchars($item['icon'] ?? 'box') ?>" style="width: 48px; height: 48px; color: var(--col-primary); margin-bottom: 1rem;"></i>
            <h1><?= htmlspecialchars($item['title']) ?></h1>
            <p class="lead"><?= htmlspecialchars($item['short_desc'] ?? '') ?></p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container narrow">
        <div class="content-body" style="font-size: 1.1rem; line-height: 1.8; color: var(--col-text-main);">
            <p><?= nl2br(htmlspecialchars($item['content'] ?? '')) ?></p>
        </div>
        
        <div style="margin-top: 4rem; text-align: center;">
            <button onclick="openContactModal()" class="btn btn-primary">
                Richiedi informazioni
            </button>
        </div>
    </div>
</section>

<?php 
// Assicurati che il file contact-modal.php esista, altrimenti rimuovi questa riga o crealo
$modalPath = BASEPATH . '/views/components/contact-modal.php';
if (file_exists($modalPath)) {
    include $modalPath; 
}
?>
<style>
    .legal-container {
        max-width: 800px;
        margin: 60px auto 100px;
        padding: 60px 50px;
        background: var(--col-white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-soft);
    }
    
    @media (max-width: 768px) {
        .legal-container { padding: 40px 24px; margin: 40px 20px 80px; }
    }

    .legal-header { text-align: center; margin-bottom: 50px; padding-bottom: 30px; border-bottom: 1px solid #E2E8F0; }
    .legal-header h1 { color: var(--col-primary); font-size: clamp(2rem, 4vw, 2.5rem); }
    
    .legal-content { color: var(--col-text-main); line-height: 1.8; font-size: 1.05rem; }
    .legal-content h2 { color: var(--col-primary); margin: 40px 0 15px; font-size: 1.5rem; }
    .legal-content h3 { color: var(--col-primary-light); margin: 30px 0 10px; font-size: 1.25rem; }
    .legal-content p { margin-bottom: 20px; color: var(--col-text-light); }
    .legal-content ul { margin-bottom: 20px; padding-left: 20px; color: var(--col-text-light); }
    .legal-content li { margin-bottom: 10px; }
    
    .legal-back { display: inline-flex; align-items: center; gap: 8px; color: var(--col-text-light); font-weight: 600; margin-bottom: 20px; transition: var(--transition-smooth); }
    .legal-back:hover { color: var(--col-primary); transform: translateX(-5px); }
</style>

<div class="legal-container">
    <a href="/" class="legal-back">
        <i data-lucide="arrow-left"></i> Torna alla Home
    </a>

    <header class="legal-header">
        <h1><?= htmlspecialchars($titolo) ?></h1>
    </header>

    <div class="legal-content">
        <?= $contenuto ?>
    </div>
</div>
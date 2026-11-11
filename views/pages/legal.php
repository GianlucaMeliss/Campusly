<style>
    .legal-container {
        max-width: 800px;
        margin: 60px auto 100px;
        padding: 60px 50px;
        background: var(--surface);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-soft);
    }
    
    @media (max-width: 768px) {
        .legal-container { padding: 40px 24px; margin: 40px 20px 80px; }
    }

    .legal-header { text-align: center; margin-bottom: 40px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color); }
    .legal-header h1 { color: var(--primary-color); font-size: clamp(1.8rem, 4vw, 2.4rem); }
    
    .legal-content { color: var(--text-primary); line-height: 1.8; font-size: 1.02rem; }
    .legal-content h2 { color: var(--primary-color); margin: 32px 0 12px; font-size: 1.35rem; }
    .legal-content h3 { color: var(--text-primary); margin: 24px 0 10px; font-size: 1.15rem; }
    .legal-content p { margin-bottom: 18px; color: var(--text-secondary); }
    .legal-content ul { margin-bottom: 18px; padding-left: 20px; color: var(--text-secondary); }
    .legal-content li { margin-bottom: 8px; }
    
    .legal-back { display: inline-flex; align-items: center; gap: 8px; color: var(--text-secondary); font-weight: 600; margin-bottom: 20px; }
    .legal-back:hover { color: var(--primary-color); }
</style>

<div class="legal-container">
    <a href="<?= htmlspecialchars(BASE_PATH) ?>/" class="legal-back">
        ← Torna alla Home
    </a>

    <header class="legal-header">
        <h1><?= htmlspecialchars($titolo ?? 'Informativa Legale') ?></h1>
    </header>

    <div class="legal-content">
        <?= $contenuto ?? '' ?>
    </div>
</div>

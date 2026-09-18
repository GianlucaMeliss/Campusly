<?php
// Helper per i percorsi sicuri
$url = $url ?? function(string $path = '/') use ($basePath): string {
    return rtrim($basePath ?? '', '/') . '/' . ltrim($path, '/');
};

$isLoggedIn = isset($_SESSION['user_id']);
?>

<section class="hero" style="text-align: center; padding: 4rem 1rem; background: var(--bg-main);">
    <div class="container narrow">
        <img src="<?= htmlspecialchars($url('/img/logo-dark.png')) ?>" alt="Campusly Logo" style="height: 80px; margin-bottom: 1.5rem;">
        
        <h1 style="color: var(--primary-color); margin-bottom: 1rem;">Benvenuto su Campusly</h1>
        <p class="lead" style="color: var(--text-secondary); font-size: 1.1rem; line-height: 1.6; margin-bottom: 2rem;">
            Il tuo orario universitario sempre sincronizzato.<br>
            Aggiungi eventi, nascondi i corsi che non frequenti e tieni tutto sotto controllo.
        </p>

        <div class="hero-actions" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <?php if ($isLoggedIn): ?>
                <a href="<?= htmlspecialchars($url('/dashboard')) ?>" style="padding: 12px 24px; background: var(--primary-color); color: white; border-radius: 8px; text-decoration: none; font-weight: bold; box-shadow: var(--shadow-soft);">
                    Vai al tuo Calendario
                </a>
            <?php else: ?>
                <a href="<?= htmlspecialchars($url('/login')) ?>" style="padding: 12px 24px; background: var(--primary-color); color: white; border-radius: 8px; text-decoration: none; font-weight: bold; box-shadow: var(--shadow-soft);">
                    Accedi
                </a>
                <a href="<?= htmlspecialchars($url('/register')) ?>" style="padding: 12px 24px; background: transparent; color: var(--primary-color); border: 2px solid var(--primary-color); border-radius: 8px; text-decoration: none; font-weight: bold;">
                    Crea un Account
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
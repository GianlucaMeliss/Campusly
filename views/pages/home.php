<?php
$url = $url ?? function(string $path = '/') use ($basePath): string {
    return rtrim($basePath ?? '', '/') . '/' . ltrim($path, '/');
};
$isLoggedIn = isset($_SESSION['user_id']);
?>

<div class="home-wrapper">
    <section class="hero-section">
        <div class="container hero-content fade-in-up">
            <div class="hero-badge">🚀 Il tuo calendario intelligente</div>
            <h1 class="hero-title">Organizza la tua vita universitaria con <span class="text-primary">Campusly</span></h1>
            <p class="hero-subtitle">
                Sincronizza le tue lezioni, nascondi i corsi che non frequenti e aggiungi i tuoi eventi personali. Tutto in cloud, accessibile da qualsiasi dispositivo.
            </p>
            
            <div class="hero-actions">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= htmlspecialchars($url('/dashboard')) ?>" class="btn btn-primary btn-lg">Vai al tuo Calendario</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($url('/register')) ?>" class="btn btn-primary btn-lg">Inizia Gratuitamente</a>
                    <a href="<?= htmlspecialchars($url('/login')) ?>" class="btn btn-outline btn-lg">Accedi</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card fade-in-up delay-1">
                    <div class="feature-icon">🔄</div>
                    <h3>Sincronizzazione Cloud</h3>
                    <p>Inizia su PC e continua su smartphone. Il tuo orario e le tue impostazioni ti seguono ovunque.</p>
                </div>
                <div class="feature-card fade-in-up delay-2">
                    <div class="feature-icon">👁️</div>
                    <h3>Calendario Pulito</h3>
                    <p>Nascondi le materie del tuo anno che non frequenti e visualizza solo ciò che è davvero importante per te.</p>
                </div>
                <div class="feature-card fade-in-up delay-3">
                    <div class="feature-icon">📅</div>
                    <h3>Eventi Personali</h3>
                    <p>Aggiungi sessioni di studio in biblioteca o esami direttamente nel calendario, affiancandoli alle lezioni ufficiali.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Script specifico per le animazioni della Home -->
<script src="<?= htmlspecialchars($url('/assets/js/home.js')) ?>"></script>
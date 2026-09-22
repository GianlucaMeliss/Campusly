<?php
$url = $url ?? function(string $path = '/') use ($basePath): string {
    return rtrim($basePath ?? '', '/') . '/' . ltrim($path, '/');
};
$isLoggedIn = isset($_SESSION['user_id']);
?>

<div class="home-layout fade-in">
    <div class="container narrow" style="max-width: 800px; padding: 40px 20px;">
        
        <header style="text-align: center; margin-bottom: 50px;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 60px; background: var(--primary-color); border-radius: 16px; margin-bottom: 20px; color: white;">
                <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <h1 style="font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 800; color: var(--text-primary); line-height: 1.1; margin-bottom: 16px;">
                L'orario universitario,<br>ma senza impazzire.
            </h1>
            <p style="font-size: 1.1rem; color: var(--text-secondary); line-height: 1.6; max-width: 600px; margin: 0 auto;">
                Basta scorrere PDF infiniti. Scegli il tuo corso, togli le materie che non frequenti e aggiungi le tue ore di studio. Tutto sempre aggiornato sul tuo telefono.
            </p>
        </header>

        <div style="display: flex; gap: 15px; justify-content: center; margin-bottom: 60px; flex-wrap: wrap;">
            <?php if ($isLoggedIn): ?>
                <a href="<?= htmlspecialchars($url('/dashboard')) ?>" class="btn-custom btn-fill">Apri il mio Calendario</a>
            <?php else: ?>
                <a href="<?= htmlspecialchars($url('/register')) ?>" class="btn-custom btn-fill">Crea il tuo profilo</a>
                <a href="<?= htmlspecialchars($url('/login')) ?>" class="btn-custom btn-ghost">Accedi</a>
            <?php endif; ?>
        </div>

        <!-- Riprendiamo lo stile delle tue card evidenziate -->
        <div style="display: grid; gap: 20px;">
            <div class="highlight-box" style="border-left-color: #3b82f6;">
                <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; color: var(--text-primary);">Togli il rumore</h3>
                <p style="margin: 0; font-size: 0.95rem; color: var(--text-secondary);">
                    Non segui un esame a scelta? Nascondilo dal calendario con un click. Vedrai solo le lezioni in cui devi davvero essere presente.
                </p>
            </div>

            <div class="highlight-box" style="border-left-color: #10b981;">
                <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; color: var(--text-primary);">Eventi personali</h3>
                <p style="margin: 0; font-size: 0.95rem; color: var(--text-secondary);">
                    Gruppi di studio, esami, o un caffè in pausa. Inserisci i tuoi appuntamenti direttamente tra una lezione ufficiale e l'altra.
                </p>
            </div>

            <div class="highlight-box" style="border-left-color: #8b5cf6;">
                <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; color: var(--text-primary);">Fatto per gli studenti</h3>
                <p style="margin: 0; font-size: 0.95rem; color: var(--text-secondary);">
                    Il progetto è nato per necessità. Se imposti la Dark Mode o cambi telefono, il tuo account si ricorderà tutto.
                </p>
            </div>
        </div>
        
    </div>
</div>

<!-- Script specifico per le animazioni della Home -->
<script src="<?= htmlspecialchars($url('/assets/js/home.js')) ?>"></script>
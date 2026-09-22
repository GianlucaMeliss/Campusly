<<?php
$isLoggedIn = isset($_SESSION['user_id']);
?>

<div class="campusly-home fade-in">
    <!-- Hero Section Principale -->
    <section class="hero-section">
        <div class="container hero-grid">
            
            <!-- Colonna Testo -->
            <div class="hero-text">
                <div class="hero-badge">✨ Il tuo nuovo calendario</div>
                <h1 class="hero-title">L'orario universitario,<br><span class="text-gradient">senza il caos.</span></h1>
                <p class="hero-subtitle">
                    Campusly trasforma i portali lenti e i PDF illeggibili in un'agenda smart, sempre in tasca. Nascondi le materie che non segui, aggiungi i tuoi esami e sincronizza tutto in cloud.
                </p>
                
                <div class="hero-cta">
                    <?php if ($isLoggedIn): ?>
                        <!-- Aggiornato con $route() -->
                        <a href="<?= htmlspecialchars($route('/dashboard')) ?>" class="btn btn-primary">Apri il tuo Calendario</a>
                    <?php else: ?>
                        <!-- Aggiornato con $route() -->
                        <a href="<?= htmlspecialchars($route('/register')) ?>" class="btn btn-primary">Inizia gratis</a>
                        <a href="<?= htmlspecialchars($route('/login')) ?>" class="btn btn-secondary">Accedi</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Colonna Visiva (Mockup fatto in CSS) -->
            <div class="hero-visual">
                <div class="floating-card card-1">
                    <div class="fc-icon" style="background: var(--gradient-brand);">📅</div>
                    <div class="fc-content">
                        <h4>Esame di Analisi</h4>
                        <span>Oggi, 10:00 - Aula Magna</span>
                    </div>
                </div>
                
                <div class="floating-card card-2 highlight-box">
                    <div class="fc-icon" style="background: #10b981;">📚</div>
                    <div class="fc-content">
                        <h4>Gruppo di Studio</h4>
                        <span>Domani, 14:30 - Biblioteca</span>
                    </div>
                </div>
                
                <div class="floating-card card-3">
                    <div class="fc-icon" style="background: var(--text-muted);">👁️</div>
                    <div class="fc-content">
                        <h4 style="color: var(--text-muted); text-decoration: line-through;">Fisica (Non frequentato)</h4>
                        <span>Nascosto dal calendario</span>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Sezione Features (Sotto la Hero) -->
    <section class="features-section">
        <div class="container">
            <div class="features-grid">
                <div class="highlight-box feature-box">
                    <div class="feature-icon-wrapper" style="color: var(--brand-fuchsia); background: rgba(232, 62, 140, 0.1);">📱</div>
                    <h3>Multi-dispositivo</h3>
                    <p>Inizia su PC, continua su smartphone. Il tuo account salva preferenze, colori e appuntamenti in cloud.</p>
                </div>
                
                <div class="highlight-box feature-box">
                    <div class="feature-icon-wrapper" style="color: var(--brand-purple); background: rgba(124, 77, 255, 0.1);">🎯</div>
                    <h3>Zero distrazioni</h3>
                    <p>Non segui un corso a scelta? Spegnilo dal calendario con un click. Vedi solo quello che conta davvero.</p>
                </div>
                
                <div class="highlight-box feature-box">
                    <div class="feature-icon-wrapper" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">☕</div>
                    <h3>La tua vita</h3>
                    <p>Inserisci i tuoi impegni personali, pause caffè o turni di lavoro direttamente tra una lezione ufficiale e l'altra.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Manteniamo $url() per lo script JS così il cache-busting continua a funzionare! -->
<script src="<?= htmlspecialchars($url('/assets/js/home.js')) ?>"></script>
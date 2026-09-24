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
    <!-- Sezione Features (Layout Bento Box) -->
    <section class="features-section">
        <div class="container">
            <div class="section-header" style="text-align: center; margin-bottom: 60px;">
                <h2 style="font-size: 2.8rem; letter-spacing: -1px; margin-bottom: 15px;">Tutto il tuo semestre,<br>sotto controllo.</h2>
                <p style="color: var(--text-secondary); font-size: 1.15rem; max-width: 600px; margin: 0 auto;">Dimentica i vecchi PDF della segreteria. Campusly è progettato per darti il potere di filtrare, personalizzare e organizzare il tuo tempo.</p>
            </div>

            <div class="bento-grid">
                <!-- Card 1: Focus Principale Larga -->
                <div class="bento-card bento-large highlight-box">
                    <div class="bento-content">
                        <div class="bento-icon" style="color: var(--brand-fuchsia); background: rgba(232, 62, 140, 0.1);">🎯</div>
                        <h3>Nascondi i corsi a scelta che non segui</h3>
                        <p>Il tuo piano di studi è unico. Con un semplice click puoi spegnere le materie che non ti interessano e pulire la tua settimana da lezioni inutili o sovrapposte.</p>
                    </div>
                    <!-- Elemento visivo decorativo (Mockup astratto) -->
                    <div class="bento-visual">
                        <div class="mockup-row"><span style="background: var(--border-color); width: 20px; height: 20px; border-radius: 4px; display: inline-block;"></span> Matematica (Nascosto)</div>
                        <div class="mockup-row active"><span style="background: #10b981; width: 20px; height: 20px; border-radius: 4px; display: inline-block;"></span> Diritto Privato (Visibile)</div>
                        <div class="mockup-row active"><span style="background: #10b981; width: 20px; height: 20px; border-radius: 4px; display: inline-block;"></span> Economia (Visibile)</div>
                    </div>
                </div>

                <!-- Card 2: Piccola in alto a destra -->
                <div class="bento-card highlight-box">
                    <div class="bento-content">
                        <div class="bento-icon" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">☕</div>
                        <h3>Aggiungi la tua vita</h3>
                        <p>Studio in biblioteca, riunioni o turni lavorativi. Inserisci i tuoi eventi personali direttamente tra una lezione ufficiale e l'altra.</p>
                    </div>
                </div>

                <!-- Card 3: Piccola in basso a destra -->
                <div class="bento-card highlight-box">
                    <div class="bento-content">
                        <div class="bento-icon" style="color: var(--brand-purple); background: rgba(124, 77, 255, 0.1);">☁️</div>
                        <h3>Sempre in Sync</h3>
                        <p>Preferenze, colori e appuntamenti viaggiano in cloud. Inizia su PC, continua su iPhone in modo trasparente.</p>
                    </div>
                </div>
                
                <!-- Card 4: Larga a tutta larghezza (Call to action secondaria) -->
                <div class="bento-card bento-wide highlight-box" style="background: var(--text-primary); border: none; align-items: center;">
                    <div class="bento-content wide-flex">
                        <div>
                            <h3 style="color: white; margin-bottom: 8px;">Esporta nel tuo calendario preferito</h3>
                            <p style="color: var(--text-secondary); margin: 0;">Scarica la tua settimana in formato ICS e importala su Apple Calendar, Google Calendar o Outlook con un solo tap.</p>
                        </div>
                        <button id="demo-export" class="btn" style="background: white; color: var(--text-primary);">Scarica .ICS</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Manteniamo $url() per lo script JS così il cache-busting continua a funzionare! -->
<script src="<?= htmlspecialchars($url('/assets/js/home.js')) ?>"></script>
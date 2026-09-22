<section class="section">
    <div class="container narrow">
        <div class="highlight-box">
            <h1 class="page-title">Configura il tuo Calendario</h1>
            <p class="page-subtitle">Seleziona la tua università e inserisci i codici del tuo corso di laurea.</p>

            <!-- FIX ROUTE -->
            <form action="<?= htmlspecialchars($route('/onboarding')) ?>" method="POST" class="campusly-form">
                
                <div class="form-group">
                    <label>Università</label>
                    <select name="university_id" required>
                        <option value="1">Università dell'Insubria (Cineca UP)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nome del tuo Corso (es. Informatica 1° Anno)</label>
                    <input type="text" name="course_name" required placeholder="Es. Informatica">
                </div>

                <div class="guide-box">
                    <h3>Guida: Come trovare i tuoi codici Insubria</h3>
                    <ol>
                        <li>Vai sul portale orari dell'Insubria e cerca il tuo corso.</li>
                        <li>Nella barra degli indirizzi (URL), vedrai qualcosa come: <br>
                            <code>.../Impegni?linkCalendarioId=<strong>6a69b9d...</strong>&clienteId=<strong>59f051...</strong></code>
                        </li>
                        <li>Copia quei due codici e incollali qui sotto!</li>
                    </ol>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Link Calendario ID</label>
                        <input type="text" name="link_calendario_id" required placeholder="es. 6a69b9...">
                    </div>
                    <div class="form-group">
                        <label>Cliente ID</label>
                        <input type="text" name="cliente_id" required placeholder="es. 59f051...">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Salva e Vai al Calendario</button>
            </form>
        </div>
    </div>
</section>
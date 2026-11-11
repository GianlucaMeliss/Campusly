# 🤖 AI Developer Instructions per Campusly

## 📌 Panoramica del Progetto
**Campusly** è un'applicazione web e PWA in PHP 8.4+ vanilla con architettura MVC custom e database MySQL (`InnoDB`, `utf8mb4`), progettata per aggregare gli orari universitari (Cineca, EasyStaff Statale/Bicocca, USI), filtrare le materie non seguite, gestire eventi personali in UTC e confrontare le disponibilità nei Gruppi di Studio.

---

## 🏗️ Architettura & Cartelle
- `/app/Adapters/` - Adapter per le API degli atenei (`UniversityAdapterInterface`, `HttpClient`, `CinecaAdapter`, `StataleAdapter`, `BicoccaAdapter`, `UsiAdapter`, `PoliMiAdapter`). Ogni adapter implementa `buildRequest()`, `parseResponse()` e `getSchedule()`.
- `/app/Controllers/` - Controller HTTP e API suddivisi per dominio:
  - `AuthController` (login, registrazione, logout POST, onboarding, password dimenticata/reset).
  - `ProfileController` (profilo utente, rimozione corso, sync tema).
  - `CalendarApiController` (calendario, eventi personali con validazione range, corsi nascosti).
  - `GroupApiController` (creazione/ingresso/uscita gruppi, privacy con whitelist, calendario di gruppo).
  - `OnboardingApiController` (corsi per ateneo, curriculum, segnalazioni rate-limited).
  - `ContactController` (form contatti con honeypot, log consensi cookie con rotazione CSV, CSP report).
  - `ApiController` (`getAppVersion`).
- `/app/Core/` - Componenti di sistema (`Router`, `View`, `Database`, `Model`, `ErrorHandler`, `Csrf`, `Http`, `RateLimiter`, `AppVersion`, `Str`).
- `/app/Models/` - Accesso ai dati MySQL via PDO prepared statements (`UserModel`, `GroupModel`, `PersonalEventModel`, `HiddenCourseModel`).
- `/app/Services/` - `ScheduleService` (validazione date, chiave cache `inizio`+`fine`+hash corsi, scrittura atomica, esecuzione parallela `curl_multi`, fallback stale-on-error).
- `/config/` - `app.php` (default non sensibili + merge automatico con `config/local.php`), `local.example.php` (template configurazione locale), `routes.php`.
- `/data/cache/` - Microcache JSON degli orari settimanali (protetta da `.htaccess`).
- `/database/` - `schema.sql` (schema completo) e `/database/migrations/NNN_*.sql` (migrazioni incrementali).
- `/tests/` - Suite di test a zero dipendenze (`php tests/run.php`) e fixture JSON in `/tests/fixtures/`.

---

## ⚙️ Regole Fondamentali

1. **Zero Dipendenze esterne:** resta su PHP vanilla. Niente Composer o framework in produzione.
2. **`BASEPATH` vs `BASE_PATH`:**
   - `BASEPATH` = percorso **su disco** della root del progetto (da usare per file, cache, log, `require`).
   - `BASE_PATH` = prefisso **URL** (`''` o `/Campusly`), da usare esclusivamente per link e redirect HTTP.
3. **Configurazione & Segreti (`config/local.php`):**
   - Non committare mai credenziali DB o indirizzi email reali in `config/app.php`.
   - Tutti i segreti risiedono in `config/local.php` (ignorato da Git).
   - Non costruire mai link assoluti (es. reset password) usando `$_SERVER['HTTP_HOST']`: usa sempre `$appConfig['site']['url']`.
4. **Sicurezza POST, CSRF e Rate Limiting:**
   - Ogni richiesta `POST` viene verificata centralmente da `Csrf::verifyOrFail()` in `public/index.php`.
   - Ogni endpoint POST sensibile deve applicare `App\Core\RateLimiter` (`login`, `register`, `forgot`, `join`, `group_create`, `onboarding`, `contact`, `cookielog`, `csp`).
   - Usa `App\Core\Http::json()`, `Http::input()`, `Http::requireUserId()` e `Http::clientIp()`.
5. **Timezone & Date:**
   - Il bootstrap (`public/index.php`) imposta `date_default_timezone_set('UTC')`. Salva e filtra gli eventi personali in UTC con `gmdate()`.
6. **Database & Test:**
   - Ogni modifica al DB richiede una nuova migrazione in `database/migrations/` e l'aggiornamento di `database/schema.sql`.
   - Prima di ogni commit esegui `php tests/run.php` e verifica la sintassi con `php -l`.

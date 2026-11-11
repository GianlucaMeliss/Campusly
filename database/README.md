# Database & Migrazioni di Campusly

## Struttura dei file
1. `schema.sql`: crea da zero l'intero database di Campusly (tutte le tabelle, indici e chiavi esterne).
2. `migrations/001_security_and_improvements.sql`: aggiunge `rate_limits`, `password_resets` e gli indici anti-duplicato (`already applied` nel dump del 11/11/2026).
3. `migrations/002_password_changed_at.sql`: aggiunge `users.password_changed_at` per invalidare le sessioni attive al cambio password.
4. `migrations/003_fix_usi_curriculum_url.sql`: corregge l'URL USI nel curriculum `id = 38` dei dati di produzione.

## Ordine di applicazione
- **Nuova installazione su DB vuoto:** importa solo `database/schema.sql`.
- **Aggiornamento DB esistente:** importa in ordine le migrazioni non ancora applicate in `database/migrations/` dalla scheda **SQL** o **Importa** di phpMyAdmin.

## Regola di manutenzione
Ogni modifica allo schema del database richiede obbligatoriamente:
1. Un nuovo file numerato in `database/migrations/NNN_descrizione.sql`.
2. L'aggiornamento contestuale di `database/schema.sql`.

<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

define('BASEPATH', dirname(__DIR__));
define('BASE_PATH', '');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = BASEPATH . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$passed = 0;
$failed = 0;

function assertTrue(bool $cond, string $label): void
{
    global $passed, $failed;
    if ($cond) {
        $passed++;
    } else {
        $failed++;
        fwrite(STDERR, "FAIL: {$label}\n");
    }
}

function assertSame($expected, $actual, string $label): void
{
    assertTrue($expected === $actual, sprintf('%s (expected %s, got %s)', $label, var_export($expected, true), var_export($actual, true)));
}

// 1. CinecaAdapter
$cineca = new \App\Adapters\CinecaAdapter();
$reqCineca = $cineca->buildRequest('2026-10-12T00:00:00Z', '2026-10-18T23:59:59Z', [
    'linkCalendarioId' => 'ABC123',
    'clienteId'        => 'CLI456',
]);
assertSame('POST', $reqCineca['method'], 'Cineca buildRequest method');
$parsedCineca = $cineca->parseResponse((string)file_get_contents(__DIR__ . '/fixtures/cineca/sample.json'), 200);
assertSame(1, count($parsedCineca), 'Cineca parseResponse count');
assertSame('ANALISI MATEMATICA', $parsedCineca[0]['nome'], 'Cineca nome');
assertSame('C', $parsedCineca[0]['stato'], 'Cineca stato');

// 2. StataleAdapter
$statale = new \App\Adapters\StataleAdapter();
$parsedStatale = $statale->parseResponse((string)file_get_contents(__DIR__ . '/fixtures/statale/sample.json'), 200);
assertSame(1, count($parsedStatale), 'Statale parseResponse count');
assertSame('DIRITTO PRIVATO', $parsedStatale[0]['nome'], 'Statale nome');
assertSame('Aula 201', $parsedStatale[0]['risorse'][0]['aula']['descrizione'], 'Statale aula');
assertSame('Festa del Perdono', $parsedStatale[0]['risorse'][0]['aula']['edificio']['descrizione'], 'Statale edificio');
assertSame('C', $parsedStatale[0]['stato'], 'Statale stato confermato');

// 3. BicoccaAdapter
$bicocca = new \App\Adapters\BicoccaAdapter();
$parsedBicocca = $bicocca->parseResponse((string)file_get_contents(__DIR__ . '/fixtures/bicocca/sample.json'), 200);
assertSame('A', $parsedBicocca[0]['stato'], 'Bicocca stato annullato');
assertSame('U6-01', $parsedBicocca[0]['risorse'][0]['aula']['descrizione'], 'Bicocca aula');

// 4. UsiAdapter (incluso test errore su HTTP != 200)
$usi = new \App\Adapters\UsiAdapter();
$parsedUsi = $usi->parseResponse(
    (string)file_get_contents(__DIR__ . '/fixtures/usi/sample.json'),
    200,
    '2026-10-12T00:00:00Z',
    '2026-10-18T23:59:59Z'
);
assertSame(1, count($parsedUsi), 'USI parseResponse count');
assertSame('Architettura dei Calcolatori', $parsedUsi[0]['nome'], 'USI nome');
assertSame('Neri M.', $parsedUsi[0]['risorse'][0]['docente']['cognome'], 'USI docente');

$usiErrorThrown = false;
try {
    $usi->parseResponse('Server Error', 500);
} catch (\Throwable) {
    $usiErrorThrown = true;
}
assertTrue($usiErrorThrown, 'USI lancia eccezione su HTTP 500');

// 5. ScheduleService: cacheKey e validateDateRange
$service = new \App\Services\ScheduleService();
$courses = [['external_course_id' => '{"corso":"INF"}']];
$key1 = $service->cacheKey('2026-10-12T00:00:00Z', '2026-10-18T23:59:59Z', $courses);
$key2 = $service->cacheKey('2026-10-12T00:00:00Z', '2026-10-25T23:59:59Z', $courses);
assertTrue($key1 !== $key2, 'ScheduleService cacheKey distingue fine diversa');

$invalidRangeThrown = false;
try {
    $service->validateDateRange('2026-10-20T00:00:00Z', '2026-10-10T00:00:00Z');
} catch (\InvalidArgumentException) {
    $invalidRangeThrown = true;
}
assertTrue($invalidRangeThrown, 'validateDateRange rifiuta fine precedente a inizio');

$tooLongRangeThrown = false;
try {
    $service->validateDateRange('2026-01-01T00:00:00Z', '2026-03-01T00:00:00Z');
} catch (\InvalidArgumentException) {
    $tooLongRangeThrown = true;
}
assertTrue($tooLongRangeThrown, 'validateDateRange rifiuta intervallo oltre 35 giorni');

// 6. GroupModel::generateInviteCode
$invite = \App\Models\GroupModel::generateInviteCode();
assertSame(12, strlen($invite), 'Lunghezza codice invito 12');
assertSame(1, preg_match('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{12}$/', $invite), 'Alfabeto codice invito');

// 7. Http::anonymizeIp
assertSame('192.168.1.0', \App\Core\Http::anonymizeIp('192.168.1.123'), 'Anonimizzazione IPv4');
assertSame('2001:db8:85a3::', \App\Core\Http::anonymizeIp('2001:db8:85a3::8a2e:370:7334'), 'Anonimizzazione IPv6');

echo "OK - Test superati: {$passed} | Falliti: {$failed}\n";
exit($failed > 0 ? 1 : 0);

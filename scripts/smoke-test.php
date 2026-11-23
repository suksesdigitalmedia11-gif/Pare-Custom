<?php
/**
 * Smoke test read-only: menembak semua route GET dengan sesi login nyata.
 * Hanya GET — tidak mengubah data. Jalankan dari CLI, JANGAN di web root.
 *
 *   php scripts/smoke-test.php <role> <output.json> [app_root]
 *
 * Contoh: php scripts/smoke-test.php owner /tmp/owner.json /path/to/app
 */
/**
 * Smoke-test harness (read-only). Menembak SEMUA route GET dengan sesi login
 * nyata lewat HTTP kernel → middleware + controller + view ikut teruji.
 *
 * Pakai: php _smoke.php <role> <output.json>
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }

$root = $argv[3] ?? __DIR__;
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

$role  = $argv[1] ?? 'owner';
$outFile = $argv[2] ?? ($root.'/smoke-'.$role.'.json');

// Netralkan rate limiter agar harness tidak kena 429 (bukan bagian yang diuji)
config(['rate_limit.pos_per_minute' => 1000000, 'rate_limit.login_per_minute' => 1000000]);
RateLimiter::clear('');

// ---- Role nyata di DB -------------------------------------------------
$roleNames = [];
try { $roleNames = \Spatie\Permission\Models\Role::pluck('name')->all(); } catch (\Throwable $e) {}
$wanted = $role;
if (! in_array($wanted, $roleNames, true)) {
    foreach ($roleNames as $r) {
        if (str_replace(['-','_',' '], '', strtolower($r)) === str_replace(['-','_',' '], '', strtolower($role))) { $wanted = $r; break; }
    }
}
$user = null;
try { $user = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', $wanted))->first(); } catch (\Throwable $e) {}
if (! $user) { $user = \App\Models\User::query()->orderBy('id')->first(); }
if (! $user) { fwrite(STDERR, "Tidak ada user\n"); exit(2); }

// ---- Sesi login nyata (cookie dienkripsi seperti EncryptCookies) ------
$guardKey = 'login_web_'.sha1('Illuminate\Auth\SessionGuard');
$sessionName = config('session.cookie');
$sid = bin2hex(random_bytes(20));
$session = $app->make('session')->driver();
$session->setId($sid);
$session->start();
$session->put($guardKey, $user->getAuthIdentifier());
$session->put('_token', bin2hex(random_bytes(20)));
$session->save();
$cookieValue = $app->make('encrypter')->encrypt($sid, false);

// ---- Semua route GET -------------------------------------------------
$router = $app->make('router');
$routes = [];
foreach ($router->getRoutes() as $route) {
    if (! in_array('GET', $route->methods(), true)) continue;
    if (str_contains($route->uri(), 'logout')) continue;
    $routes[] = $route->uri();
}
$routes = array_values(array_unique($routes));
sort($routes);

$lookup = [
    'user' => fn() => $user->id,
    'product' => fn() => \App\Models\Product::query()->value('id'),
    'shift' => fn() => \App\Models\Shift::query()->orderByDesc('id')->value('id'),
    'sales_order' => fn() => \App\Models\SalesOrder::query()->orderByDesc('id')->value('id'),
    'order' => fn() => \App\Models\SalesOrder::query()->orderByDesc('id')->value('id'),
    'id' => fn() => \App\Models\SalesOrder::query()->orderByDesc('id')->value('id') ?? 1,
];
$resolve = fn(string $uri) => preg_replace_callback('/\{(\w+)(\?)?\}/', function ($m) use ($lookup) {
    try { $v = isset($lookup[$m[1]]) ? $lookup[$m[1]]() : null; } catch (\Throwable $e) { $v = null; }
    return $v ?: '1';
}, $uri);

// ---- Tembak ----------------------------------------------------------
$results = [];
foreach ($routes as $uri) {
    $path = '/'.ltrim($resolve($uri), '/');
    try {
        $request = Illuminate\Http\Request::create($path, 'GET');
        $request->cookies->set($sessionName, $cookieValue);
        $response = $kernel->handle($request);
        $code = $response->getStatusCode();
        $results[$uri] = $code;
        if ($code >= 500) {
            $body = @$response->getContent();
            if (preg_match('/<title>([^<]{0,120})<\/title>/i', (string)$body, $mm)) {
                $results[$uri] = ['code' => $code, 'title' => trim($mm[1])];
            }
        }
    } catch (\Throwable $e) {
        $results[$uri] = ['code' => 'EXC', 'err' => substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 110)];
    }
}

file_put_contents($outFile, json_encode($results, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

$counts = [];
foreach ($results as $s) { $k = is_array($s) ? (string)$s['code'] : (string)$s; $counts[$k] = ($counts[$k] ?? 0) + 1; }
ksort($counts);
echo "USER={$user->email} ROLE={$wanted} ROUTES=".count($results)."\n";
echo "ROLES_DB=".implode(',', $roleNames)."\n";
foreach ($counts as $s => $n) { echo "  {$s} => {$n}\n"; }
echo "--- yang bukan 200/302 ---\n";
foreach ($results as $u => $s) {
    if (is_array($s) || ! in_array($s, [200, 302], true)) {
        echo "  [".(is_array($s) ? $s['code'] : $s)."] /{$u}".(is_array($s) && isset($s['title']) ? ' :: '.$s['title'] : '').(is_array($s) && isset($s['err']) ? ' :: '.$s['err'] : '')."\n";
    }
}

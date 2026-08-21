<?php

declare(strict_types=1);

use PHPAML\Autoloader;
use PHPAML\Container;
use PHPAML\Http\Request;
use PHPAML\Http\Response;
use PHPAML\WebApplication;
use PHPAML\Routing\Router;
use PHPAML\Middleware\MiddlewareInterface;
use PHPAML\Validation\Validator;

$root = dirname(__DIR__);
$installedAutoloader = $root . '/runtime/autoload.php';
if (is_file($installedAutoloader)) {
    require_once $installedAutoloader;
} else {
    require_once $root . '/runtime/framework/Autoloader.php';
    Autoloader::register([
        'PHPAML\\' => $root . '/runtime/framework',
        'App\\' => $root . '/src',
    ]);
}

$tests = [];

function test(string $name, Closure $test): void
{
    global $tests;
    $tests[$name] = $test;
}

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

interface TestContract
{
}

final class TestService implements TestContract
{
}

final class RouteTestController
{
    public function show(Request $request): Response
    {
        return Response::json(['id' => $request->attribute('id')]);
    }
}

final class RouteTestMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Closure $next): Response
    {
        return $next($request)->withHeader('X-Test-Middleware', 'active');
    }
}

test('Request normalise la méthode, le chemin et les entrées', function (): void {
    $request = new Request('post', '/contact/', ['page' => '1'], ['name' => 'AML']);
    expect($request->method() === 'POST', 'La méthode HTTP est incorrecte.');
    expect($request->path() === '/contact', 'Le chemin est incorrect.');
    expect($request->input('name') === 'AML', 'Le corps de la requête est inaccessible.');
});

test('Response JSON définit le contenu et le type attendus', function (): void {
    $response = Response::json(['ok' => true], 201);
    expect($response->status() === 201, 'Le statut est incorrect.');
    expect($response->content() === '{"ok":true}', 'Le JSON est incorrect.');
    expect(str_contains($response->headers()['Content-Type'], 'application/json'), 'Le type JSON manque.');
});

test('Response construit une redirection', function (): void {
    $response = Response::redirect('/connexion', 303);
    expect($response->status() === 303, 'Le statut de redirection est incorrect.');
    expect($response->headers()['Location'] === '/connexion', 'La destination est incorrecte.');
});

test('Container construit automatiquement une classe sans dépendance', function (): void {
    $service = (new Container())->get(stdClass::class);
    expect($service instanceof stdClass, 'Le conteneur ne construit pas le service.');
});

test('Container associe une interface et conserve un singleton', function (): void {
    $container = new Container();
    $container->singleton(TestContract::class, TestService::class);
    $first = $container->get(TestContract::class);
    $second = $container->get(TestContract::class);
    expect($first instanceof TestService, 'L’interface n’est pas résolue.');
    expect($first === $second, 'Le singleton n’est pas conservé.');
});

test('Router gère paramètres, noms et middleware par route', function (): void {
    $router = new Router(new Container());
    $router->add('GET', '/users/{id}', [RouteTestController::class, 'show'], [RouteTestMiddleware::class], 'users.show');
    $response = $router->dispatch(new Request('GET', '/users/42'));
    expect($response->content() === '{"id":"42"}', 'Le paramètre dynamique est incorrect.');
    expect($response->headers()['X-Test-Middleware'] === 'active', 'Le middleware de route manque.');
    expect($router->url('users.show', ['id' => 7]) === '/users/7', 'La route nommée est incorrecte.');
});

test('Router gère les groupes et retourne 405', function (): void {
    $router = new Router(new Container());
    $router->group('/api', [
        'GET /users/{id}' => [RouteTestController::class, 'show'],
    ]);
    expect($router->dispatch(new Request('GET', '/api/users/8'))->status() === 200, 'Le groupe de routes est incorrect.');
    $response = $router->dispatch(new Request('POST', '/api/users/8'));
    expect($response->status() === 405, 'La mauvaise méthode doit retourner 405.');
    expect($response->headers()['Allow'] === 'GET', 'L’en-tête Allow est incorrect.');
});

test('Validator applique les règles courantes', function (): void {
    $validator = new Validator();
    expect($validator->validate(['email' => 'test@example.com'], ['email' => ['required', 'email']]), 'Une adresse valide est refusée.');
    expect(!$validator->validate(['email' => 'invalide'], ['email' => ['email']]), 'Une adresse invalide est acceptée.');
    expect(isset($validator->errors()['email']), 'L’erreur de validation manque.');
});

test('WebApplication présente l’API à la racine', function (): void {
    $config = \PHPAML\Config\ApplicationConfig::load(dirname(__DIR__));
    $response = (new WebApplication($config))->handle(new Request('GET', '/'));
    expect($response->status() === 200, 'La route principale ne retourne pas 200.');
    expect(str_contains($response->content(), 'PHPAML Movies API'), 'La présentation JSON de l’API manque.');
    expect(str_contains($response->headers()['Content-Type'] ?? '', 'application/json'), 'La racine doit retourner du JSON.');
    expect(($response->headers()['X-Frame-Options'] ?? null) === 'DENY', 'Les en-têtes de sécurité manquent.');
});

test('L’API liste, recherche et pagine les films', function (): void {
    $config = \PHPAML\Config\ApplicationConfig::load(dirname(__DIR__));
    $config['database']['dsn'] = 'sqlite::memory:';
    $application = new WebApplication($config);
    $response = $application->handle(new Request('GET', '/api/v1/movies', ['q' => 'Villeneuve', 'per_page' => '1']));
    $payload = json_decode($response->content(), true, 512, JSON_THROW_ON_ERROR);
    expect($response->status() === 200, 'La liste des films ne retourne pas 200.');
    expect(count($payload['data']) === 1, 'La pagination de la recherche est incorrecte.');
    expect($payload['meta']['total'] === 2, 'La recherche par réalisateur est incorrecte.');
    expect(($response->headers()['Access-Control-Allow-Origin'] ?? null) === '*', 'L’en-tête CORS manque.');
});

test('L’API retourne une fiche et une erreur JSON explicite', function (): void {
    $config = \PHPAML\Config\ApplicationConfig::load(dirname(__DIR__));
    $config['database']['dsn'] = 'sqlite::memory:';
    $application = new WebApplication($config);
    $movie = $application->handle(new Request('GET', '/api/v1/movies/1'));
    $missing = $application->handle(new Request('GET', '/api/v1/movies/999'));
    expect(str_contains($movie->content(), 'Dune: Part Two'), 'La fiche du film est incorrecte.');
    expect($missing->status() === 404, 'Un film absent doit retourner 404.');
    expect(str_contains($missing->content(), 'MOVIE_NOT_FOUND'), 'Le code d’erreur JSON manque.');
});

test('L’API expose les genres disponibles', function (): void {
    $config = \PHPAML\Config\ApplicationConfig::load(dirname(__DIR__));
    $config['database']['dsn'] = 'sqlite::memory:';
    $response = (new WebApplication($config))->handle(new Request('GET', '/api/v1/genres'));
    expect(str_contains($response->content(), 'Science Fiction'), 'Le catalogue des genres est incomplet.');
});

test('WebApplication retourne une réponse 404', function (): void {
    $config = \PHPAML\Config\ApplicationConfig::load(dirname(__DIR__));
    $response = (new WebApplication($config))->handle(new Request('GET', '/inconnue'));
    expect($response->status() === 404, 'Une route inconnue doit retourner 404.');
});

$passed = 0;
$failed = 0;

foreach ($tests as $name => $testCase) {
    try {
        $testCase();
        fwrite(STDOUT, "✓ {$name}" . PHP_EOL);
        $passed++;
    } catch (Throwable $error) {
        fwrite(STDERR, "✗ {$name}: {$error->getMessage()}" . PHP_EOL);
        $failed++;
    }
}

fwrite(STDOUT, PHP_EOL . "{$passed} réussi(s), {$failed} échec(s)." . PHP_EOL);
exit($failed === 0 ? 0 : 1);

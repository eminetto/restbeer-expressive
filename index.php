<?php
use Aura\Router\RouterFactory;
use Zend\Expressive\AppFactory;
use Zend\Expressive\Router\Aura as AuraBridge;
use RestBeer\Auth;
use RestBeer\Format;

$loader = require __DIR__.'/vendor/autoload.php';
$loader->add('RestBeer', __DIR__.'/src');


$auraRouter = (new RouterFactory())->newInstance();
$router = new AuraBridge($auraRouter);
$api = AppFactory::create(null, $router);

$db = new PDO('sqlite:beers.db');

$beers = array(
    'brands' => array('Heineken', 'Guinness', 'Skol', 'Colorado'),
    'styles' => array('Pilsen' , 'Stout')
);

$api->get('/', function ($request, $response, $next) {
    $response->getBody()->write('Hello, beers of world!');
    return $response;
});

$api->get('/brand', function ($request, $response, $next) use ($beers) {
    $response->getBody()->write(implode(',', $beers['brands']));
    
    return $next($request, $response);
});

$api->get('/style', function ($request, $response, $next) use ($beers) {
    $response->getBody()->write(implode(',', $beers['styles']));
    
    return $next($request, $response);
});

$api->get('/beer{/id}', function ($request, $response, $next) use ($beers) {
    $id = $request->getAttribute('id');
    if ($id == null) {
        $response->getBody()->write(implode(',', $beers['brands']));
        return $next($request, $response);
    }
    
    $key = array_search($id, $beers['brands']);
    if ($key === false) {
        return $response->withStatus(404);
    }
    $response->getBody()->write($beers['brands'][$key]);
    
    return $next($request, $response);

});

$api->post('/beer', function ($request, $response, $next) use ($db) {
    $db->exec(
        "create table if not exists beer (id INTEGER PRIMARY KEY AUTOINCREMENT, name text not null, style text not null)"
    );
    $data = $request->getParsedBody();
    if (! isset($data['name']) || ! isset($data['style'])) {
        return new JsonResponse('Missing parameters', 400);
    }
    //@TODO: clean form data before insert into the database ;)   
    $stmt = $db->prepare('insert into beer (name, style) values (:name, :style)');
    $stmt->bindParam(':name',$data['name']);
    $stmt->bindParam(':style', $data['style']);
    $stmt->execute();
    $data['id'] = $db->lastInsertId();
    
    $response->getBody()->write(implode(',', $data));
    
    return $next($request, $response);

});

$app = AppFactory::create();
$app->pipe(new Auth());
$app->pipe($api);
$app->pipe(new Format());
$app->run();
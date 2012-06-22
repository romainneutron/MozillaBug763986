<?php

namespace Neutron;

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

session_cache_limiter('');

$app = new Application();

$app['debug'] = true;

$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../../views',
));


$datas = array(
    'robert.jpg' => array(
        'etag'          => md5(filemtime(__DIR__ . '/../../config/robert.jpg') . 'robert.jpg'),
        'last_modified' => \DateTime::createFromFormat('U', filemtime(__DIR__ . '/../../config/robert.jpg')),
    ),
    'herman.jpg'    => array(
        'etag'          => md5(filemtime(__DIR__ . '/../../config/herman.jpg') . 'herman.jpg'),
        'last_modified' => \DateTime::createFromFormat('U', filemtime(__DIR__ . '/../../config/herman.jpg')),
    ),
);

$app->get('/', function(Application $app) use ($datas) {

        return $app['twig']->render('index.html.twig', array('datas' => $datas));
    });

$app->get('/refresh-images', function(Application $app) use ($datas) {

        return $app['twig']->render('pictures.html.twig', array('datas' => $datas));
    });

$app->get('/update-etags', function(Application $app) use ($datas) {

        $success = false;
        try {
            $imagine = new \Imagine\Gd\Imagine();
            $prefix = __DIR__ . '/../../config/';
            foreach (array('herman.original.jpg', 'robert.original.jpg') as $original) {
                $image = $imagine->open($prefix . $original);
                $image->draw()->ellipse(
                    new \Imagine\Image\Point(mt_rand(0, 50), mt_rand(0, 50)), new \Imagine\Image\Box(mt_rand(50, 200), mt_rand(50, 200)), new \Imagine\Image\Color(array(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255))), true
                );
                $image->save($prefix . str_replace('.original', '', $original));
            }
            $success = true;
        } catch (\Exception $e) {

        }

        return new JsonResponse(array('success' => $success));
    });

$app->get('/image/{image_id}', function($image_id, Application $app, Request $request) use ($datas) {

        if ( ! isset($datas[$image_id])) {
            throw new \Exception('Invalid image id');
        }

        $response = new Response();
        $response->setPrivate();

	$response->setProtocolVersion('1.1');

        $response->setEtag($datas[$image_id]['etag']);
        $response->setLastModified($datas[$image_id]['last_modified']);

        $response->headers->addCacheControlDirective('must-revalidate', true);
        if ( ! $response->isNotModified($request)) {
            $response->headers->set('content-type', 'image/jpeg');
            $response->setContent(file_get_contents(__DIR__ . '/../../config/' . $image_id));
        }

        return $response;
    })->assert('image_id', '(herman|robert)\.jpg');

return $app;

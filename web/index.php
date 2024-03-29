<?php
/**
 * Created by PhpStorm
 * User: bartek
 * Date: 30.05.14
 * Time: 19:12
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

//rejestracja kontrolerów
$app->mount('/', new Controller\IndexController());
$app->mount('/index/', new Controller\IndexController());
$app->mount('/user/', new Controller\UserController());
$app->mount('/auth/', new Controller\AuthController());

$app['debug'] = true;

//registration of providers

//twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => '/home/epi/11_krawczyk/public_html/chat/src/views/',
));

//translations
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallbacks' => array('en'),
));

//db register and config
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'dbs.options' => array (
        'mysql_read' => array(
            'driver'    => 'pdo_mysql',
            'host'      => 'localhost',
            'dbname'    => '11_krawczyk',
            'user'      => '11_krawczyk',
            'password'  => 'P7s3c6a9f7',
            'charset'   => 'utf8',
        )
    )));

//session
$app->register(new Silex\Provider\SessionServiceProvider());

//do generowanie urli i funkcji path
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());

use User\UserProvider;

//security
$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
//        'main' => array(
//            'pattern' => '^/',
//            'anonymous' => true
//        ),
        'admin' => array(
            'pattern' => '^/.*$',
            'form' => array(
                'login_path' => '/auth/login',
                'check_path' => '/user/login_check',
                'default_target_path'=> '/user/users/1',
                'username_parameter' => 'form[username]',
                'password_parameter' => 'form[password]',
            ),
            'logout'  => true,
            'anonymous' => true,
            'logout' => array('logout_path' => '/auth/logout'),
            'users' => $app->share(function() use ($app) {
                    return new User\UserProvider($app);
                }),
        ),
    ),
    'security.access_rules' => array(
        array('^/.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        array('^/auth.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        array('^/user/users/.+$', 'ROLE_ADMIN')
    ),
    'security.role_hierarchy' => array(
        'ROLE_ADMIN' => array('ROLE_USER'),

    ),
));

//echo $app['security.encoder.digest']->encodePassword('admin', '');

$app->run();

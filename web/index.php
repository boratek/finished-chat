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
//$app->mount('/index/', new Controller\IndexController());
$app->mount('/user/', new Controller\UserController());
$app->mount('/auth/', new Controller\AuthController());

$app['debug'] = true;

//registration of providers

//twig
$app->register(
    new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../src/views/',
    )
);

//translations
$app->register(
    new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallbacks' => array('en'),
    )
);

//db register and config
$app->register(
    new Silex\Provider\DoctrineServiceProvider(), array(
    'dbs.options' => array (
        'mysql_read' => array(
            'driver'    => 'pdo_mysql',
            'host'      => 'localhost',
            'dbname'    => '11_krawczyk',
            'user'      => '11_krawczyk',
            'password'  => 'P7s3c6a9f7',
            'charset'   => 'utf8',
        )
    ))
);

//session
$app->register(
    new Silex\Provider\SessionServiceProvider()
);

//do generowanie urli i funkcji path
$app->register(
    new Silex\Provider\UrlGeneratorServiceProvider()
);
$app->register(
    new Silex\Provider\FormServiceProvider()
);
$app->register(
    new Silex\Provider\ValidatorServiceProvider()
);

$app->register(
    new Silex\Provider\TranslationServiceProvider(), array(
        'locale_fallbacks' => array('pl'),
    )
);

//translator
use Symfony\Component\Translation\Loader\YamlFileLoader;

$app['translator'] = $app->share(
    $app->extend(
        'translator', function($translator, $app)
        {
        $translator->addLoader('yaml', new YamlFileLoader());

        $translator->addResource(
            'yaml', __DIR__ . '/../src/locales/pl/pl.yml', 'pl'
        );
        return $translator;
        }
    )
);

$lang = "pl";
if ($app['session']->get('current_language')) {
    $lang = $app['session']->get('current_language');
}

foreach (glob(__DIR__ . '/locales/'. $lang . '/*.yml') as $locale) {
    $app['translator']->addResource('yaml', $locale, $lang);
}

/* sets current language */
$app['translator']->setLocale($lang);

$app['translator.domains'] = array(
    'validators' => array(
        'pl' => array(
            'This value should not be blank.' =>
                'To pole nie może być puste.',
        ),
    ),
);

use User\UserProvider;

//security
$app->register(
    new Silex\Provider\SecurityServiceProvider(), array(
        'security.firewalls' => array(
            'admin' => array(
                'pattern' => '^/.*$',
                'form' => array(
                    'login_path' => '/auth/login',
                    'check_path' => '/auth/check',
                    'default_target_path' => '/auth/check',
                    'username_parameter' => 'form[username]',
                    'password_parameter' => 'form[password]',
                ),
                'logout'  => true,
                'anonymous' => true,
                'logout' => array('logout_path' => '/auth/logout'),
                'users' => $app->share(
                    function() use ($app) {
                    return new User\UserProvider($app);
                    }
                ),
            ),
            'default' => array(
                'pattern' => '^/.*$',
                'form' => array(
                    'login_path' => '/auth/login',
                    'check_path' => '/auth/check',
                    'default_target_path' => '/auth/check',
                    'username_parameter' => 'form[username]',
                    'password_parameter' => 'form[password]',
                ),
                'logout'  => true,
                'anonymous' => true,
                'logout' => array('logout_path' => '/auth/logout'),
                'users' => $app->share(
                    function() use ($app) {
                    return new User\UserProvider($app);
                    }
                ),
            ),
        ),
        'security.access_rules' => array(
            array('^/index.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/auth/.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            array('^/user/users/.+$', 'ROLE_ADMIN'),
            array('^/user/users+$', 'ROLE_ADMIN'),
            array('^/user/view/.+$', 'ROLE_ADMIN'),
            array('^/user/change_role/.+$', 'ROLE_ADMIN'),
            array('^/user/show_messages/.+$', 'ROLE_ADMIN'),
            array('^/user/profile/.+$', 'ROLE_USER')
        ),
        'security.role_hierarchy' => array(
            'ROLE_ADMIN' => array('ROLE_USER')
        ),
    )
);

//echo $app['security.encoder.digest']->encodePassword('bart', '');

$app->run();

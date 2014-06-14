<?php
/**
 * Created by PhpStorm.
 * User: bartek
 * Date: 30.05.14
 * Time: 19:18
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 *
 * Class IndexController
 *
 * @class IndexController
 * @package Controller
 * @author EPI
 * @link epi.uj.edu.pl
 * @uses Silex\ControllerProviderInterface
 * @uses Silex\Application
 */

class IndexController implements ControllerProviderInterface
{

    /**
     * connect.
     *
     * @access public
     * @param \Silex\Application $app
     * @return object controller
     */

    public function connect(Application $app)
    {
        $indexController = $app['controllers_factory'];
        $indexController->get('/', array($this, 'index'));
        return $indexController;
    }

    /**
     * index action.
     *
     * @access public
     * @param \Silex\Application $app
     * @return twig template render
     */
    public function index(Application $app)
    {
        //root direction
        return $app['twig']->render('index.twig');
    }
}

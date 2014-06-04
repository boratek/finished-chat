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
use Symfony\Component\Validator\Constraints as Assert;
use Model\UsersModel;

/**
 *
 * Class AuthController
 *
 * @class AuthController
 * @package Controller
 * @author EPI
 * @link epi.uj.edu.pl
 * @uses Silex\ControllerProviderInterface
 * @uses Silex\Application
 */

class AuthController implements ControllerProviderInterface
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
        $authController = $app['controllers_factory'];
        $authController->match('/login', array($this, 'login'))->bind('/login');
        $authController->match('/logout', array($this, 'logout'))->bind('/logout');
        $authController->match('/check', array($this, 'check'))->bind('/check');

        return $authController;
    }

    /**
     * login action.
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */

    public function login(Application $app, Request $request)
    {
        $data = array();

        $form = $app['form.factory']->createBuilder('form')
            ->add('username', 'text', array('label' => 'Username', 'data' => $app['session']->get('_security.last_username')))
            ->add('password', 'password')
           // ->add('login', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
                $app['session']->getFlashBag()->add('success', array('title' => 'Ok', 'content' => 'Login is successfull.'));
            } else {
                $app['session']->getFlashBag()->add('error', array('title' => 'FALSE', 'content' => 'Login is not successfull. Please try again.'));
            }

        return $app['twig']->render('auth/login.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * logout action.
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */

    public function logout(Application $app, Request $request)
    {
	    $app['session']->clear();
        $app['session']->getFlashBag()->add('success', array('title' => 'Ok', 'content' => 'Logout is successfull.'));

        return $app->redirect('/');
    }

    public function check(Application $app, Request $request)
    {
       $user = $app['security']->getToken();
        //var_dump($user);
        var_dump($user->getRoles());


        if ($user->getRoles() == 'ROLE_ADMIN') {
            $mess = 'Hello Admin';
        }

        return $app->redirect('../user/users/1');
    }

}

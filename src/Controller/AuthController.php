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
        $form = $app['form.factory']->createBuilder('form')
            ->add('username', 'text', array('label' => 'Username', 'data' => $app['session']->get('_security.last_username')))
            ->add('password', 'password')
            ->getForm();

        $form->handleRequest($request);

         return $app['twig']->render(
             'auth/login.twig', array(
             'form' => $form->createView(),
           )
         );
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
        $app['session']->getFlashBag()->add('success', array('title' => 'Ok', 'content' => 'You have been succesfully logged out.'));

        return $app->redirect($app['url_generator']->generate('/index'), 301);
    }

    /**
     * check action.
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return redirect
     */
    public function check(Application $app, Request $request)
    {
        $username = $app['security']->getToken()->getUsername();
        $userRole = $app['security']->isGranted('ROLE_USER');
        $adminRole = $app['security']->isGranted('ROLE_ADMIN');

        if ($adminRole == 1) {

            $app['session']->getFlashBag()->add('message', array('title' => 'Ok', 'type' => 'success', 'content' => 'Hello, Admin!'));
            return $app->redirect('../../web/user/users/1');

        } elseif (($userRole == 1) && (empty($adminRole))) {

            $app['session']->getFlashBag()->add('message', array('title' => 'Ok', 'type' => 'success', 'content' => 'You are successfully logged in'));
            return $app->redirect('../../web/user/profile/' . $username . '/chat');

        } else {
            $app['session']->getFlashBag()->add('error', array('title' => 'ARG', 'type' => 'error', 'content' => 'You are not allowed to see this page'));
            return $app->redirect('../../../../~11_krawczyk/chat/web/index');
        }
    }
}

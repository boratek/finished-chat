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
        return $app->redirect('../~11_krawczyk/chat/web/index');
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
        var_dump($username);
        var_dump('user_role: ' . $userRole);
        var_dump('admin_role: ' . $adminRole);

        //admin$username = $app['security']->getToken()->getUsername();
//        $login = $app['security']->getToken();
//        $admin = $app['security']->isGranted('ROLE_ADMIN');
//        $user = $app['security']->isGranted('ROLE_USER');
//        var_dump('user: ' . $user);
//        var_dump($admin);
//        var_dump($login);
//        $username = $login->getUsername();
//        $login = $login->getRoles();
//        $password = $login->getPassword();
//        var_dump($password);
//        var_dump($login);
//        var_dump($username);
//die;

        if ($app['security']->isGranted('ROLE_ADMIN')) {
            $app['session']->getFlashBag()->add('success', array('title' => 'Ok', 'content' => 'Hello, Admin!'));
            return $app->redirect('../user/users/1');

        } elseif ($app['security']->isGranted('ROLE_USER')) {

            $app['session']->getFlashBag()->add('success', array('title' => 'Ok', 'content' => 'You are successfully logged in.'));
            return $app->redirect('../user/profile/' . $username . '/chat');

        } else {
            $app['session']->getFlashBag()->add('info', array('title' => 'ARG', 'content' => 'You are not allowed to see this page.'));
            return $app->redirect('../../../../~11_krawczyk/chat/web/index');
        }


    }

}

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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Model\UsersModel;

/**
 *
 * Class UsersController
 *
 * @class UsersController
 * @package Controller
 * @author EPI
 * @link epi.uj.edu.pl
 * @uses Silex\ControllerProviderInterface
 * @uses Silex\Application
 */

class UserController implements ControllerProviderInterface
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
        $userController = $app['controllers_factory'];
        $userController->match('/profile/{login}', array($this, 'index'))->bind('/profile');
        $userController->match('/profile/{login}/chat', array($this, 'chat'));
        $userController->match('/profile/{login}/display', array($this, 'display'));
        $userController->match('/register', array($this, 'register'))->bind('/register');
        $userController->get('/users/{page}', array($this, 'users'))->value('page', 1)->bind('/users/');
        $userController->match('/delete/{id}', array($this, 'delete'))->bind('/user/delete');
        $userController->get('/view/{id}', array($this, 'view'))->bind('/user/view');

        return $userController;
    }

    /**
     * index action.
     *
     * @access public
     * @param \Silex\Application $app
     * @param $login
     * @return twig template render
     */

    public function index(Application $app, $login)
    {
       // $this->_isLoggedIn($app); // limit access

        $userModel = new UsersModel($app);

        $user = $userModel->getUser($login);

        return $app['twig']->render('user/profile.twig', array('user' => $user ));
    }

    /**
     * chat action.
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $login
     * @return twig template render
     */

    public function chat(Application $app, Request $request, $login)
    {
        //$this->_isLoggedIn($app); // limit access

        $data = array(
            'message' => ''
        );

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add('message', 'text')
            ->getForm();

        $form->bind($request);

        $data = $form->getData();

        $message= $data['message'];

        $newMessage = $app['db']->executeUpdate(
            "INSERT INTO chat (chat_id, posted_on, login, message) VALUES (0, NOW(), '" . $login . "', '" . $message . "')"
        );

        return $app['twig']->render(
            'user/chat.twig', array('form' => $form->createView(),
            'login' => $login)
        );
    }

    /**
     * display action.
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */

    public function display(Application $app, Request $request)
    {
       // $this->_isLoggedIn($app); // limit access

        $displayMessages = $app['db']->fetchAll('SELECT * FROM chat ORDER BY chat_id DESC LIMIT 10');

        return $app['twig']->render('user/display.twig', array('display_messages' => $displayMessages ));
    }

    /**
     * users action.
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */

    public function users(Application $app, Request $request)
    {
        //$this->_isLoggedIn($app); // limit access

        if ($app['security']->isGranted('ROLE_USER')) {
            $username = $app['security']->getToken()->getUsername();
            return $app->redirect('../user/profile/' . $username . '/chat');
        }

        $pageLimit = 3;
        $page = (int) $request->get('page', 1);
        $userModel = new UsersModel($app);
        $pagesCount = $userModel->countUsersPages($pageLimit);

        if (($page < 1) || ($page > $pagesCount)) {
            $page = 1;
        }

        $users = $userModel->getUsersPage($page, $pageLimit, $pagesCount);
        $paginator = array('page' => $page, 'pagesCount' => $pagesCount);

        return $app['twig']->render('/user/users.twig', array('users' => $users, 'paginator' => $paginator ));
    }

    /**
     * register action.
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */

    public function register(Application $app, Request $request)
    {
        // some default data for when the form is displayed the first time
        $data = array(
            'name' => 'Your name',
            'email' => 'Your email',
            'login' => 'Your nick',
            'password' => 'Your password',
            'password2' => 'Rewrite your password'
        );

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add('name', 'text', array('constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' =>5)))))
            ->add('email', 'text', array('constraints' => new Assert\Email()))
            ->add('login', 'text', array('constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' =>5)))))
            ->add('password', 'password', array('constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' =>5)))))
            ->add('password2', 'password', array('constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' =>5)))))
            ->getForm();

        if ('POST' == $request->getMethod()) {
            $form->bind($request);

            //$form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $login = $form->get('login')->getData();
                $password = $form->get('password')->getData();
                $passwordRepeat = $form->get('password2')->getData();

                if ($password == $passwordRepeat) {
                    $register = new UsersModel($app);
                    $newUser = $register->registerUser($data, $app);

                    // redirect to user profile
                    $app['session']->getFlashBag()->add('message', array('title' => 'OK', 'content' => 'You are registered.'));
                    return $app->redirect('profile/' . $login );
                } else {
                    $app['session']->getFlashBag()->add('error', array('title' => 'FALSE', 'content' => 'Password must be set and must be repeated.'));
                }
            }

        }
        // display the form
        return $app['twig']->render('user/register.twig', array('form' => $form->createView()));
    }

    /**
     * delete action.
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */

    public function delete(Application $app, Request $request)
    {
        $userId = (int) $request->get('user_id', 0);

        $userModel = new UsersModel($app);

        $user = $userModel->deleteUser($userId);

        $app['session']->getFlashBag()->add('success', array('title' => 'OK', 'content' => 'User has been succesfully deleted.'));

        return $app->redirect($app['url_generator']->generate('/users/'), 301);
    }

    /**
     * view action.
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */

    public function view(Application $app, Request $request)
    {
       // $this->_isLoggedIn($app); // limit access

        $userId = (int) $request->get('user_id', 0);

        $userModel = new UsersModel($app);

        $user = $userModel->viewUser($userId);

        return $app['twig']->render('user/view.twig', array('user' => $user));
    }

    /**
     * check if user is logged.
     *
     * @access public
     * @param \Silex\Application $app
     * @return twig template render
     */

    protected function _isLoggedIn(Application $app)
    {
        if (null === $user = $app['session']->get('user')) {
            return $app->redirect('/auth/login');
        }
    }

}

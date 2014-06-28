<?php

/**
 * UserController.php
 * @author Bartosz Krawczyk
 * @date 2014
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Model\UsersModel;

/**
 *
 * Class UserController
 *
 * @class UserController
 * @package Controller
 * @author EPI
 * @link epi.uj.edu.pl
 * @uses Silex\ControllerProviderInterface
 * @uses Silex\Application
 */
class UserController implements ControllerProviderInterface
{

    /**
     * connect
     *
     * @access public
     * @param \Silex\Application $app
     * @return object controller
     */
    public function connect(Application $app)
    {
        $userController = $app['controllers_factory'];
        $userController->match(
            '/profile/{login}', array(
                $this, 'index')
        )->bind('/profile');
        $userController->match(
            '/profile/{login}/change_data', array(
                $this, 'change_data')
        )->bind('/change_data');
        $userController->match('/profile/{login}/chat', array($this, 'chat'));
        $userController->match(
            '/profile/{login}/display', array(
            $this, 'display')
        );
        $userController->match(
            '/register', array(
                $this, 'register')
        )->bind('/register');
        $userController->get(
            '/users/{page}', array(
                $this, 'users')
        )->value('page', 1)->bind('/users/');
        $userController->match(
            '/delete/{id}', array(
                $this, 'delete')
        )->bind('/user/delete');
        $userController->get(
            '/view/{id}', array(
                $this, 'view')
        )->bind('/user/view');
        $userController->match(
            '/change_role/{id}', array(
                $this, 'change_role')
        )->bind('/user/change_role');
        $userController->match(
            '/show_messages/{id}', array(
                $this, 'show_messages')
        )->bind('/user/show_messages');
        $userController->match(
            '/delete_message/{id}', array(
                $this, 'delete_message')
        )->bind('/user/show_messages/delete_message');
        $userController->match(
            '/show_all_messages', array(
                $this, 'show_all_messages')
        )->bind('/user/show_all_messages');

        return $userController;
    }

    /**
     * index action
     *
     * @access public
     * @param \Silex\Application $app
     * @param $login
     * @return twig template render
     */
    public function index(Application $app, $login)
    {
        $userModel = new UsersModel($app);

            $user = $userModel->getUser($login);

            return $app['twig']->render(
                'user/profile.twig', array(
                    'user' => $user,
                    'login' => $login
                )
            );
    }

    /**
     * chat action
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $login
     * @return twig template render
     */
    public function chat(Application $app, Request $request, $login)
    {

        $form = $app['form.factory']->createBuilder(
            'form', null, array('csrf_protection' => false)
        )
            ->add('message', 'text')
            ->getForm();

            $form->bind($request);

            $data = $form->getData();

            $message= $data['message'];

            try {
            $userModel = new UsersModel($app);
            $result = $userModel->addMessage($login, $message);

                if (!$result) {
                    throw new Exception(
                        'ARG,
                        something went wrong with saving message.
                        Please try later'
                    );
                }
            } catch (Exception $e) {
                echo $e->getMessage(), "\n";
                return $app->redirect('profile/' . $login);
            }

        return $app['twig']->render(
            'user/chat.twig', array('form' => $form->createView(),
            'login' => $login)
        );
    }

    /**
     * display action
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */
    public function display(Application $app, Request $request)
    {
        $userModel = new UsersModel($app);

        try{
            $displayMessages = $userModel->displayMessages();

                if (!$displayMessages) {
                    throw new Exception(
                        'ARG#!,
                        Something went wrong with displaying messages.
                        Please come back later'
                    );
                }
        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
        }

        return $app['twig']->render(
            'user/display.twig', array(
                'display_messages' => $displayMessages
            )
        );
    }

    /**
     * users list action
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */
    public function users(Application $app, Request $request)
    {

        $pageLimit = 5;
        $page = (int) $request->get('page', 1);

        try {

            $userModel = new UsersModel($app);
            $pagesCount = $userModel->countUsersPages($pageLimit);

                if ($pagesCount == 0) {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'type' => 'error',
                            'title' => 'ARG#!',
                            'content' =>
                                'Surprise, Hudson, we have some problem'
                        )
                    );

                    throw new Exception('ARG#!, there are no pages of users');
                }

                if (($page < 1) || ($page > $pagesCount)) {
                    $page = 1;
                }

            $users = $userModel->getUsersPage($page, $pageLimit, $pagesCount);
            $paginator = array('page' => $page, 'pagesCount' => $pagesCount);

        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
        }
        return $app['twig']->render(
            '/user/users.twig', array(
                'users' => $users,
                'paginator' => $paginator
            )
        );
    }

    /**
     * change user role
     *
     * @access public
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function change_role(Application $app, Request $request)
    {
        $userId = (int) $request->get('id', 0);

        $currentUser = new UsersModel($app);
        $currentUser = $currentUser->getUserById($userId);

        if (!$currentUser) {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'error',
                    'title' => 'ARG#!',
                    'content' =>
                        'Nice try, but user with these id does not exist'
                )
            );

            return $app->redirect(
                $app['url_generator']->generate('/users/'), 301
            );
        }

        $userCurrentRole = $currentUser[0]['role_id'];
        $login = $currentUser[0]['login'];
        $roleToChoice = array();

        if ($userCurrentRole == 1) {
            $roleToChoice[2] = 'User';
            $userCurrentRoleName = 'Admin';
        } else {
            $roleToChoice[1] = 'Admin';
            $userCurrentRoleName = 'User';
        }

        $form = $app['form.factory']->createBuilder('form')
            ->add(
                'role', 'choice', array(
                'choices' => $roleToChoice,
                'required'    => false,
                'empty_value' => $userCurrentRoleName,
                'empty_data'  => null,
                )
            )
            ->getForm();

        $form->handleRequest($request);

        $data = $form->getData();

        $userModel = new UsersModel($app);

        if ($form->isValid()) {

            if ($data['role'] == null) {
                $data['role'] = $userCurrentRole;

                $app['session']->getFlashBag()->add(
                    'message', array(
                        'type' => 'info',
                        'title' => 'HEY',
                        'content' => 'You have not changed user role')
                );

                return $app->redirect(
                    $app['url_generator']->generate('/users/'), 301
                );
            }

            try{

                $result = $userModel->changeRole($userId, $data);

                    if (!$result) {
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'error',
                                'title' => 'ARG#!',
                                'content' =>
                                    'Surprise, Hudson, there is some problem'
                            )
                        );

                        throw new Exception(
                            'ARG, Surprise,
                            there is some problem with data base'
                        );

                    } else {
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'title' => 'OK',
                                'content' => 'You have changed user role')
                        );

                        return $app->redirect(
                            $app['url_generator']->generate('/users/'), 301
                        );
                    }
            } catch (Exception $e) {
                echo $e->getMessage(), "\n";
            }
        }
        return $app['twig']->render(
            'user/change_role.twig', array(
                'form' => $form->createView(),
                'userCurrentRoleName' => $userCurrentRoleName,
                'login' => $login
            )
        );
    }

    /**
     * change user data
     *
     * @access public
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function change_data(Application $app, Request $request)
    {
        $userLogin = (string)$request->get('login');

        $user = new UsersModel($app);
        $userExistingData = $user->getUserByLogin($userLogin);

        $form = $app['form.factory']->createBuilder('form', $userExistingData)
            ->add(
                'name', 'text', array(
                    'constraints' => array(
                        new Assert\Length(array('min' =>5))
                    )
                )
            )
            ->add(
                'email', 'text', array(
                    'constraints' => array(
                        new Assert\Email()
                    )
                )
            )
            ->add(
                'login', 'text', array(
                    'constraints' => array(
                        new Assert\Length(array('min' =>5))
                    )
                )
            )
            ->add(
                'password', 'password', array(
                    'constraints' => array(
                        new Assert\Length(array('min' =>5))
                    )
                )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $data = $this->compareData($data, $userExistingData);

            $userModel = new UsersModel($app);

            try {

                $result = $userModel->changeUserData($userLogin, $data, $app);

                    if (1 == $result) {
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'success',
                                'title' => 'OK',
                                'content' =>
                                    'You have changed your data correctly')
                        );

                       return $app->redirect(
                           $app['url_generator']->generate('/login'), 301
                       );

                    } elseif (0 == $result) {
                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'info',
                                'title' => 'HEY',
                                'content' =>
                                    'You have not changed your data')
                        );

                    } else {

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'type' => 'error',
                                'title' => 'ARG#!',
                                'content' =>
                                    'Surprise,
                            there is some problem with data base')
                        );

                        throw new Exception(
                            'ARG, Surprise,
                            there is some problem with data base'
                        );
                    }

            } catch (Exception $e) {
                echo $e->getMessage(), "\n";
            }

        }

        return $app['twig']->render(
            'user/change_data.twig', array(
                'form' => $form->createView(),
                'login' => $userLogin
            )
        );
    }

    /**
     * register of new user
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */
    public function register(Application $app, Request $request)
    {

        $form = $app['form.factory']->createBuilder('form')

        ->add(
            'name', 'text', array('label' => 'Username', 'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('min' =>5))
                )
            )
        )
        ->add(
            'email', 'text', array(
                    'label' => 'Email',
                    'constraints' => new Assert\Email()
                )
        )
        ->add(
            'login', 'text', array('label' => 'Login', 'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('min' =>5))
                )
            )
        )
        ->add(
            'password', 'password', array('constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('min' =>5))
                )
            )
        )
        ->add(
            'password_repeat', 'password'
        )
        ->getForm();

        if ('POST' == $request->getMethod()) {
            $form->bind($request);

            //$form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $login = $form->get('login')->getData();
                $newUser = new UsersModel($app);

                $checkPassword = $this->checkPassword($data);

                if ($checkPassword) {
                    try {
                        $register = $newUser->registerUser($data, $app);
                        if ( 0 == $register) {
                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'title' => 'ARGH#!',
                                    'type' => 'error',
                                    'content' =>
                                        'Something went wrong with registering')
                            );

                           throw new Exception(
                               'ARG#!,
                               there is some problem with registering,
                               please try again later'
                           );

                        } elseif (2 == $register) {
                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'title' => 'ARGH#!',
                                    'type' => 'error',
                                    'content' =>
                                        'We have these data! Give us some new!')
                            );

                            return $app->redirect(
                                 $app['url_generator']->generate('/index'), 301
                            );
                        } else {
                            // redirect to user profile
                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'title' => 'OK',
                                    'type' => 'success',
                                    'content' =>
                                        'You are registered, please login')
                            );
                            return $app->redirect('profile/' . $login);
                        }

                    } catch (Exception $e) {
                      echo $e->getMessage(), "\n";
                      $app['session']->getFlashBag()->add(
                          'message', array(
                            'title' => 'ARGH#!',
                            'type' => 'error',
                            'content' =>
                                'Something went wrong with registering')
                      );
                    }

                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'title' => 'ARGH#!',
                            'type' => 'error',
                            'content' => 'The password must be repeated')
                    );
                }
            }
        }

        return $app['twig']->render(
            'user/register.twig', array(
                'form' => $form->createView())
        );
    }

    /**
     * delete user
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */
    public function delete(Application $app, Request $request)
    {
        $userId = (int) $request->get('id', 0);

        $currentUser = new UsersModel($app);
        $currentUser = $currentUser->getUserById($userId);

        if (!$currentUser) {
            $app['session']->getFlashBag()->add(
                'message', array(
                    'type' => 'error',
                    'title' => 'ARG#!',
                    'content' =>
                        'Nice try, but user with these id does not exist'
                )
            );

            return $app->redirect(
                $app['url_generator']->generate('/users/'), 301
            );
        }

        $form = $app['form.factory']->createBuilder(
            'form', null, array('csrf_protection' => false)
        )
            ->add(
                'delete', 'choice', array(
                'choices' => array(1 => 'Tak', 0 => 'Nie'),
                'required'    => false,
                )
            )
            ->getForm();

        $form->bind($request);
        $login = $currentUser[0]['login'];

        if ('POST' == $request->getMethod()) {
            if ($form->isValid()) {

                $data = $form->getData();

                if ( 1 == $data['delete']) {

                    $userModel = new UsersModel($app);

                    try {

                        $delete = $userModel->deleteUser($userId);

                        if (0 == $delete) {
                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'title' => 'ARG#!',
                                    'type' => 'error',
                                    'content' => 'User is not deleted')
                            );

                            throw new Exception('Cannot find the user');
                        } else {
                            $app['session']->getFlashBag()->add(
                                'message', array(
                                    'title' => 'OK',
                                    'type' => 'success',
                                    'content' => 'User has been succesfully deleted')
                            );

                            return $app->redirect($app['url_generator']->generate('/users/'), 301);
                        }

                    } catch (Exception $e) {
                        echo $e->getMessage(), "\n";
                    }
                } else {
                    $app['session']->getFlashBag()->add(
                        'message', array(
                            'title' => 'HEY',
                            'type' => 'info',
                            'content' => 'User is not deleted')
                    );

                    return $app->redirect($app['url_generator']->generate('/users/'), 301);
                }
            }
        }

        return $app['twig']->render(
            'user/delete.twig', array(
                'form' => $form->createView(),
                'login' => $login
            )
        );

    }

    /**
     * view particular user
     *
     * @access public
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return twig template render
     */
    public function view(Application $app, Request $request)
    {
        $userId = (int) $request->get('id', 0);

        $userModel = new UsersModel($app);

        try {
            $user = $userModel->viewUser($userId);
            if (0 == $user) {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'title' => 'ARG#!',
                        'type' => 'error',
                        'content' => 'Hmm, this user is gone')
                );

                throw new Exception('Cannot find the user');
            }
        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
        }


        return $app['twig']->render('user/view.twig', array('user' => $user));
    }

    /**
     * show messages of user
     *
     * @access public
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function show_messages(Application $app, Request $request)
    {
        $userId = (int) $request->get('id', 0);

        $userModel = new UsersModel($app);

        try {
            $messages = $userModel->showUserMessages($userId);

            if (!$messages) {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'title' => 'ARG#!',
                        'type' => 'error',
                        'content' => 'Hmm, this users messages are gone')
                );

                throw new Exception('Cannot find the users messages');
            }
        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
        }


        return $app['twig']->render(
            'user/show_messages.twig', array(
                'messages' => $messages)
        );
    }

    /**
     * show all messages of user by date
     *
     * @access public
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function show_all_messages(Application $app, Request $request)
    {
        $userModel = new UsersModel($app);
        $dates = $userModel->getMessagesDates();
        $newDates = array();

        foreach ($dates as $key => $date) {
            $date = $dates[$key]['posted_on'];
            $newDates[$date] = $date;
        }



        $form = $app['form.factory']->createBuilder('form')
            ->add(
                'date', 'choice', array(
                    'choices' => $newDates,
                    'required'    => false,
                )
            )
            ->getForm();

        if ('POST' == $request->getMethod()) {
            $form->bind($request);

            //$form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $mess = new UsersModel($app);

                try {

                    $allMessages = $mess->selectAllMessagesByDate($data);

                    if (!$allMessages) {

                        $app['session']->getFlashBag()->add(
                            'message', array(
                                'title' => 'ARG#!',
                                'type' => 'error',
                                'content' => 'Hmm, there is not messages')
                        );

                        throw new Exception('Cannot find the messages');
                    }
                } catch (Exception $e) {
                    echo $e->getMessage(), "\n";
                }
            }
        }

        return $app['twig']->render(
            'user/show_all_messages.twig', array(
                'form' => $form->createView(),
                'messages' => $allMessages
            )
        );
    }


    /**
     * delete chosen message of user
     *
     * @access public
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete_message(Application $app, Request $request)
    {
        $messId = (int) $request->get('id', 0);

        $userModel = new UsersModel($app);

        try {
            $deleteMessage = $userModel->deleteMessage($messId);

            if (!$deleteMessage) {
                $app['session']->getFlashBag()->add(
                    'message', array(
                        'title' => 'ARG#!',
                        'type' => 'error',
                        'content' => 'Message is not deleted')
                );

                throw new Exception('Cannot find the message');
            } else {
                $app['session']->getFlashBag()->add(
                    'message', array(
                                'title' => 'OK',
                                'type' => 'success',
                                'content' =>
                                    'Message has been succesfully deleted')
                );
            }

        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
        }

        return $app->redirect($app['url_generator']->generate('/users/'), 301);
    }

    /**
     * check if user is logged
     *
     * @access protected
     * @param \Silex\Application $app
     * @return twig template render
     */
    protected function _isLoggedIn(Application $app)
    {
        if (null === $user = $app['session']->get('user')) {
            return $app->redirect('/auth/login');
        }
    }


    /**
     * compare if existed data are equaly with data from form
     *
     * @access protected
     * @param $data
     * @param $userExistingData
     * @return mixed
     */
    protected function compareData($data, $userExistingData)
    {
        $compare = array_diff($data, $userExistingData);

        foreach ($compare as $key => $compared) {
            if ($compared == null) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * check if passwords are equally
     *
     * @access protected
     * @param $data
     * @return int
     */
    protected function checkPassword($data)
    {
        if ($data['password'] != $data['password_repeat']) {
            return 0;
        } else {
            return 1;
        }
    }
}

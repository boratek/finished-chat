<?php
/**
 * Created by PhpStorm.
 * User: bartek
 * Date: 30.05.14
 * Time: 19:19
 */

namespace User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

use Model\UsersModel;

/**
 * Class UserProvider
 *
 * @class UserProvider
 * @package Provider
 * @author EPI
 * @link epi.uj.edu.pl
 * @uses Doctrine\DBAL\DBALException
 * @uses Silex\Application
 */

class UserProvider implements UserProviderInterface
{
    /**
     * Database access object.
     *
     * @access protected
     * @var $_app Doctrine\DBAL
     */

    protected $_app;

    /**
     * Class constructor.
     *
     * @access public
     * @param Application $app Silex application object
     */

    public function __construct($app)
    {
        $this->_app = $app;
    }

    /**
     * Load user by username.
     *
     * @access public
     * @param $login
     * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @return object User
     */

    public function loadUserByUsername($login)
    {
        $userModel = new UsersModel($this->_app);
        $user = $userModel->loadUserByLogin($login);
        return new User($user['login'], $user['password'], $user['roles'], true, true, true, true);
    }

    /**
     * refresh User.
     *
     * @access public
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     * @throws \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     * @return array User array
     */

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }
        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * support Class.
     *
     * @access public
     * @param string $class
     * @return class Symfony\Component\Security\Core\User\User
     */

    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}
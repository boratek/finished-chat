<?php
/**
 * Created by PhpStorm.
 * User: bartek
 * Date: 30.05.14
 * Time: 19:19
 */

namespace Model;

use Doctrine\DBAL\DBALException;
use Silex\Application;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

/**
 *
 * Class UsersModel
 *
 * @class UsersModel
 * @package Model
 * @author EPI
 * @link epi.uj.edu.pl
 * @uses Doctrine\DBAL\DBALException
 * @uses Silex\Application
 */
class UsersModel
{

    /**
     * Database access objects.
     *
     * @access protected
     * @var $_db Doctrine\DBAL
     * @var $_app Doctrine\DBAL
     */
    protected $_app;
    protected $_db;

    /**
     * Class constructor.
     *
     * @access public
     * @param Application $app Silex application object
     */
    public function __construct(Application $app)
    {
        $this->_app = $app;
        $this->_db = $app['db'];
    }

    /**
     * Load user by login.
     *
     * @access public
     * @param $login
     * @throws
     *      \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @return array User array
     */
    public function loadUserByLogin($login)
    {
        $data = $this->getUserByLogin($login);

        if (!$data) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        }

        $roles = $this->getUserRoles($data['id']);

        if (!$roles) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $login)
            );
        }

        $user = array(
            'login' => $data['login'],
            'password' => $data['password'],
            'roles' => $roles
        );

        return $user;
    }

    /**
     * Add new message
     *
     * @param $login
     * @param $message
     * @return mixed
     */
    public function addMessage($login, $message)
    {
        $sql = 'INSERT INTO chat (chat_id, posted_on, login, message)
                VALUES (0, NOW(), ?, ?)';
        return $this->_db->executeQuery($sql, array((string) $login, $message));
    }

    /**
     * Display messages
     *
     * @return mixed
     */
    public function displayMessages()
    {
        $sql = 'SELECT * FROM chat ORDER BY chat_id DESC LIMIT 10';
        return $this->_db->fetchAll($sql);
    }

    /**
     * Get User by login.
     *
     * @access public
     * @param $login
     * @throws
     *      \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @return array User data array
     */
    public function getUserByLogin($login)
    {
        $sql = 'SELECT * FROM chat_users WHERE login = ? LIMIT 1';
        return $this->_db->fetchAssoc($sql, array((string) $login));
    }

    /**
     * Get User roles.
     *
     * @access public
     * @param $userId
     * @internal param $login
     * @return array User roles array
     */
    public function getUserRoles($userId)
    {
        $sql = '
            SELECT
                chat_roles.role
            FROM
                chat_users_roles
            INNER JOIN
                chat_roles
            ON chat_users_roles.role_id=chat_roles.id
            WHERE
                chat_users_roles.user_id = ?
            ';

        $result = $this->_db->fetchAll($sql, array((string) $userId));

        $roles = array();
        foreach ($result as $row) {
            $roles[] = $row['role'];
        }

        return $roles;
    }

    /**
     * Count Users pages.
     *
     * @access public
     * @param $limit
     * @internal param $login
     * @return integer pagesCount
     */
    public function countUsersPages($limit)
    {
        $pagesCount = 0;
        $sql = 'SELECT COUNT(*) as pages_count FROM chat_users';
        $result = $this->_db->fetchAssoc($sql);
        if ($result) {
            $pagesCount = ceil($result['pages_count']/$limit);
        }
        return $pagesCount;
    }

    /**
     * Get User page.
     *
     * @access public
     * @param $page
     * @param $limit
     * @param $pagesCount
     * @internal param $login
     * @return object statement
     */
    public function getUsersPage($page, $limit, $pagesCount)
    {
        if (($page <= 1) || ($page > $pagesCount)) {
            $page = 1;
        }
        $sql = 'SELECT u.id, u.name, u.email, u.login, r.role_id
                FROM chat_users u, chat_users_roles r
                WHERE u.id <> 1
                AND u.id = r.user_id LIMIT :start, :limit';
        /*$sql = 'SELECT u.id, u.name, u.login, u.email, r.role_id
                FROM chat_users u, chat_users_roles r
                WHERE r.role_id = 2
                AND u.id = r.user_id
                LIMIT :start, :limit';
        */

        $statement = $this->_db->prepare($sql);
        $statement->bindValue('start', ($page-1)*$limit, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    /**
     * register User.
     *
     * @access public
     * @param $data
     * @param $app
     * @internal param $login
     * @return array User array
     */
    public function registerUser($data, $app)
    {
        try{
            $password = $app['security.encoder.digest']->encodePassword(
                $data['password'], ''
            );

            $startTransaction = "START TRANSACTION";

            $startingTheTransaction = $this->_db
                ->executeQuery($startTransaction);

            $insertIntoChatUsers =
                'INSERT INTO chat_users (id, name, login, email, password)
                 VALUES (0, ?, ?, ?, ?)';

            $insertingUser = $this->_db
                ->executeQuery(
                    $insertIntoChatUsers, array(
                    $data['name'],
                    $data['login'],
                    $data['email'],
                    $password
                    )
                );

            $selectLatestId = 'SELECT @user_id := max(id) FROM chat_users';

            $findLatestUserId = $this->_db->executeQuery($selectLatestId);

            $insertIntoUsersRoles =
                'INSERT INTO chat_users_roles(id, user_id, role_id)
                 VALUES (0, @user_id, 2)';

            $insertingRole = $this->_db->executeQuery($insertIntoUsersRoles);

            $commitTransaction = "COMMIT";

            $endOfTransaction = $this->_db->executeQuery($commitTransaction);

            if ((!$startingTheTransaction)
                && (!$insertingUser)
                && (!$findLatestUserId)
                && (!$insertingRole)
                && (!$endOfTransaction)) {

                throw new Exception('Problem with registering user');

                $result = 0;
            } else {
                $result = 1;
            }
        } catch (Exception $e) {
            echo $e->getMessage(); "\n";
        }

        return $result;
    }

    /**
     * Get User.
     *
     * @access public
     * @param $login
     * @throws
     *      \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     * @return array User array
     */
    public function getUser($login)
    {
        $sql = 'SELECT id, name, login, email
                FROM chat_users
                WHERE login = ? LIMIT 1';
        $result = $this->_db->fetchAll($sql, array((string) $login));
        return $result;
    }

    /**
     * @param $userId
     * @param $data
     * @return mixed
     */
    public function changeRole($userId, $data)
    {
        $role = $data['role'];

        try {
            $startTransaction = "START TRANSACTION";

            $this->_db->executeQuery($startTransaction);

            $updateRole ='UPDATE chat_users_roles
                          SET role_id = ?
                          WHERE user_id = ? LIMIT 1';

            $result = $this->_db
                ->executeQuery($updateRole, array($role, $userId));

            $commitTransaction = "COMMIT";

            $this->_db->executeQuery($commitTransaction);

                if (!$result) {
                    throw new Exception('Problem with changing user role');
                    $result = 0;
                } else {
                    $result = 1;
                }
        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
        }
        return $result;
    }

    /**
     * Change User Data
     *
     * @param $userLogin
     * @param $data
     * @param $app
     * @internal param $id
     * @return mixed
     */
    public function changeUserData($userLogin, $data, $app)
    {
        try {
            $id = $this->getUserId($userLogin);

            $id = $id['id'];

                if (!$id) {
                    throw new Exception(
                        sprintf('Username "%s" does not exist.', $userLogin)
                    );
                }
        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
        }

        $updateQuery = $this->prepareUpdateQuery($data, $id, $app);

        $result = $this->_db->executeUpdate($updateQuery);

        return $result;
    }

    /**
     * Prepare Query to change user data
     *
     * @param $data
     * @param $id
     * @param $app
     * @return string
     */
    public function prepareUpdateQuery($data, $id, $app)
    {

        $updateQuery = 'UPDATE chat_users SET ';

        foreach ($data as $item => $value) {
            if ($value != '') {
                if ($item == 'password') {
                    $value = $app['security.encoder.digest']
                        ->encodePassword($value, '');
                }

                $updateQuery = $updateQuery . $item . ' = \'' . $value . '\', ';
            }
        }

        $updateQuery = rtrim($updateQuery, ', ');
        $updateQuery = $updateQuery . ' WHERE id = ' . $id;

        return $updateQuery;
    }

    /**
     * view User
     *
     * @access public
     * @param $userId
     * @internal param $userId
     * @return mixed $user
     */
    public function viewUser($userId)
    {
        try {
            $user = $this->getUserById($userId);

            if (!$user) {
                throw new Exception(
                    sprintf('Username "%d" does not exist.', $userId)
                );
            }
        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
        }

        return $user;
    }

    /**
     * Get User by ID
     *
     * @access public
     * @param $userId
     * @internal param $userId
     * @return mixed
     */
    public function getUserById($userId)
    {
        $sql = 'SELECT id, name, login, email
                FROM chat_users
                WHERE id = ? LIMIT 1';

        return $this->_db->fetchAll($sql, array((int) $userId));
    }

    /**
     * Get User ID
     *
     * @access public
     * @param $login
     * @internal param $userId
     * @return integer $id
     */
    public function getUserId($login)
    {
        $sql = "SELECT `id` FROM chat_users WHERE login = ? LIMIT 1";
        $id = $this->_db->fetchAssoc($sql, array((string) $login));
        return $id;
    }


    /**
     * Delete User
     *
     * @access public
     * @param $userId
     * @internal param $userId
     * @return remove user data from database
     */
    public function deleteUser($userId)
    {
        try {
            $startTransaction = "START TRANSACTION";

            $this->_db->executeQuery($startTransaction);

            $sql = 'DELETE FROM chat_users_roles WHERE user_id = ? LIMIT 1';

            $deleteFromUsersRoles = $this->_db
                ->executeQuery($sql, array((int) $userId));

            $sql = 'DELETE FROM chat_users WHERE id = ? LIMIT 1';

            $deleteFromUsers = $this->_db
                ->executeQuery($sql, array((int) $userId));

            $commitTransaction = "COMMIT";

            $this->_db->executeQuery($commitTransaction);

                if ($deleteFromUsers && $deleteFromUsersRoles) {
                    $result = 1;
                } else {
                    $result = 0;
                    throw new Exception('Problem with database');
                }
        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
        }

        return $result;
    }

    /**
     * Show all messages of user
     *
     * @param $userId
     * @return mixed
     */
    public function showUserMessages($userId)
    {
        $user = $this->getUserById($userId);

        $userLogin = $user[0]['login'];

        $sql = 'SELECT * FROM chat WHERE login = ?';
        $messages = $this->_db->fetchAll($sql, array((string) $userLogin));
        return $messages;
    }

    /**
     * Delete chosen message
     *
     * @param $messId
     * @return mixed
     */
    public function deleteMessage($messId)
    {
        $sql = 'DELETE FROM chat WHERE chat_id = ? LIMIT 1';
        $delete = $this->_db->executeQuery($sql, array((int) $messId));
        return $delete;
    }
}
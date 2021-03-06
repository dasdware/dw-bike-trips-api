<?php

namespace DW\BikeTrips\API;

use Exception;
use GraphQL\Error\Error;
use \Firebase\JWT\JWT;
use Medoo\Medoo;

class Context
{
    // /**
    //  * @var Medoo\Medoo
    //  */
    public $db;

    public $server_config;

    public function __construct(Medoo $db, $server_config)
    {
        $this->db = $db;
        $this->server_config = $server_config;
    }

    function login($email, $password)
    {
        global $login_config, $jwt_config;
        if (empty($email)) {
            throw new Error('Cannot log in: No username given.');
        } else if (empty($password)) {
            throw new Error('Cannot log in: No password given.');
        } else {
            $user = $this->db->select(
                'users',
                ['id'],
                [
                    'email' => $email,
                    'password' => hash($login_config['hash_algorithm'], $password)
                ]
            );

            if (!$user) {
                throw new Error("Cannot log in: Unknown combination of email and password");
            }

            $now = date('U');
            $token = [
                'iss' => $jwt_config['issuer'],
                'aud' => $jwt_config['audience'],
                'iat' => $now,
                'nbf' => $now,
                'exp' => $now + $jwt_config['expiration'],
                'data' => ['user_id' => $user[0]['id']]
            ];

            return JWT::encode($token, $jwt_config['signing']['key'], $jwt_config['signing']['algorithm']);
        }
    }

    function current_user_id()
    {
        global $jwt_config;

        if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
            throw new Error('This resource requires authorization');
        } else {
            $authorization = $_SERVER['HTTP_AUTHORIZATION'];
            if (substr($authorization, 0, 7) === 'Bearer ') {
                $authorization = substr($authorization, 7);
                try {
                    $jwt = JWT::decode($authorization, $jwt_config['signing']['key'], array($jwt_config['signing']['algorithm']));
                    return (int) $jwt->data->user_id;
                } catch (Exception $e) {
                    throw new Error('Cannot authorize: ' . $e->getMessage());
                }
            } else {
                throw new Error('Cannot authorize: Invalid authorization header');
            }
        }
    }

    function current_user_info()
    {
        $userinfos = $this->db->select(
            'users',
            ['email', 'firstname', 'lastname'],
            ['id' => $this->current_user_id()]
        );

        if (count($userinfos) !== 1) {
            throw new Error("Could not resolve user id to a single user");
        }

        return $userinfos[0];
    }
}

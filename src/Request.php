<?php

namespace Saffyre;

/**
 * The Request class represents the current HTTP request. It consolidates all information gathered from the request into
 * one place. This includes query string variables, POST data, raw request body data, HTTP headers, and some information
 * from $_SERVER that relates to the current request.
 *
 * In contrast to the Controller class, the information presented by this class is *always* related to the underlying
 * HTTP request that generated this execution. The Controller class, on the other hand, may represent information
 * related to a nested request (i.e. a manual call to Controller::run()).
 *
 * @package Saffyre
 */
class Request {

    private static $Q;

    public static function init()
    {
        self::$Q = new Q();
    }

    public static function body() {
        return file_get_contents('php://input');
    }

    public static function get($name) {
        return self::$Q->get->{$name};
    }

    public static function post($name) {
        return self::$Q->post->{$name};
    }

    public static function cookie($name) {
        return self::$Q->cookie->{$name};
    }
}

Request::init();

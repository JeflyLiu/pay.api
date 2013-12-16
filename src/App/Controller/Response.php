<?php
namespace App\Controller;

class Response
{
    /**
     * @param $prefix
     * @param $resource
     * @param $method
     * @param $params
     * @return mixed
     */
    public static function accessor($prefix, $resource, $method = null, $params = null)
    {
        $default = 'index';
        $method = ($method === null && $default) ? $default : $method;
        $_method = ($prefix == null) ? $method : $prefix . ucfirst($method);

        $class = self::load($resource);

        if (method_exists($class, $_method))
        {
            return $params !== null ? $class->$_method($params) : $class->$_method();
        }
        elseif (method_exists($class, $method))
        {
            return $params !== null ? $class->$method($params) : $class->$method();
        }

        return self::render(404);
    }

    /**
     * @param $resource
     * @return mixed
     */
    public static function load($resource)
    {
        $class = __NAMESPACE__ . '\\' . ucfirst($resource);
        
        if (! class_exists($class))
        {
            return self::render(404);
        }

        return new $class();
    }

    public static function render($status = 200, array $data = array(), $allow = array()) {
        $app = \Slim\Slim::getInstance();

        $app->response()->status(intval($status));
        $app->response()->header('Content-Type', 'application/json');

        $app->response()->body(json_encode($data));

        if (!empty($allow))
        {
            $app->response()->header('Allow', strtoupper(implode(',', $allow)));
        }

        $app->stop();
    }

}
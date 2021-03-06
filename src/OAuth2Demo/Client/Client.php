<?php

namespace OAuth2Demo\Client;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\Provider\SessionServiceProvider;

class Client implements ControllerProviderInterface
{
    /**
     * function to set up the container for the Client app
     */
    public function setup(Application $app)
    {
        // create session object and start it
        $app->register(new SessionServiceProvider());

        if (!$app['session']->isStarted()) {
            $app['session']->start();
        }

        // create curl object and ensure it runs on default port
        $port = is_numeric($_SERVER['SERVER_PORT']) ? intval($_SERVER['SERVER_PORT']) : 80;
        $app['curl'] = new Http\Curl(array('http_port' => $port));

        // add twig extension
        $app['twig']->addExtension(new Twig\JsonStringifyExtension());

        // load parameters configuration
        $this->loadParameters($app);
    }

    /**
     * Connect the controller classes to the routes
     */
    public function connect(Application $app)
    {
        // set up the service container
        $this->setup($app);

        // Load routes from the controller classes
        $routing = $app['controllers_factory'];

        Controllers\Homepage::addRoutes($routing);
        Controllers\ReceiveAuthorizationCode::addRoutes($routing);
        Controllers\RequestToken::addRoutes($routing);
        Controllers\RequestResource::addRoutes($routing);
        Controllers\ReceiveImplicitToken::addRoutes($routing);

        return $routing;
    }

    /**
     * Load the parameters configuration
     */
    private function loadParameters(Application $app)
    {
        $parameterFile = __DIR__.'/../../../data/parameters.json';
        if (!file_exists($parameterFile)) {
            // allows you to customize parameter file
            $parameterFile = $parameterFile.'.dist';
        }
        $app['environments'] = array();
        if (!$parameters = json_decode(file_get_contents($parameterFile), true)) {
            throw new \Exception('unable to parse parameters file: '.$parameterFile);
        }
        // we are using an array of configurations
        if (!isset($parameters['client_id'])) {
            $app['environments'] = array_keys($parameters);
            $env = $app['session']->get('config_environment');
            $parameters = isset($parameters[$env]) ? $parameters[$env] : array_shift($parameters);
        }
        $app['parameters'] = $parameters;
    }
}

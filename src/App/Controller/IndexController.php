<?php
/**
 * @since  11.08.2015
 * @author Ed Posinitskiy <eddiespb@gmail.com>
 */

namespace App\Controller;

use Monolog\Logger;
use Slim\Slim;

/**
 * Class IndexController
 *
 * @author Ed Posinitskiy <eddiespb@gmail.com>
 */
class IndexController
{

    /**
     * @var Slim
     */
    protected $app;

    /**
     * IndexController constructor.
     *
     * @param Slim $app
     */
    public function __construct(Slim $app)
    {
        $this->app = $app;
    }

    public function index()
    {
        /** @var Logger $logger */
        $logger = $this->app->log;
        $logger->log(Logger::INFO, 'routing through new router hook :: ' . var_export(func_get_args(), true));
        $this->app->render('index.html');
    }

}

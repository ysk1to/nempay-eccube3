<?php

namespace Plugin\SimpleNemPay\ServiceProvider;

use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class SimpleNemPayServiceProvider implements ServiceProviderInterface
{

    public function register(BaseApplication $app)
    {
        // Setting
        $app->match('/' . $app["config"]["admin_route"] . '/plugin/SimpleNemPay/config', '\\Plugin\\SimpleNemPay\\Controller\\Admin\\ConfigController::index')->bind('plugin_SimpleNemPay_config');

        // shopping
        $app->match('/shopping/simple_nem_pay', '\Plugin\SimpleNemPay\Controller\NemShoppingController::index')->bind('shopping_nem_pay');
        $app->match('/shopping/simple_nem_pay/back', '\Plugin\SimpleNemPay\Controller\NemShoppingController::back')->bind('shopping_nem_pay_back');

        // Service
        $app['eccube.plugin.simple_nempay.service.nem_request'] = $app->share(function () use ($app) {
            return new \Plugin\SimpleNemPay\Service\NemRequestService($app);
        });
        $app['eccube.plugin.simple_nempay.service.nem_shopping'] = $app->share(function () use ($app) {
            return new \Plugin\SimpleNemPay\Service\NemShoppingService($app, $app['eccube.service.cart'], $app['eccube.service.order']);
        });
        $app['eccube.plugin.simple_nempay.service.nem_mail'] = $app->share(function () use ($app) {
            return new \Plugin\SimpleNemPay\Service\NemMailService($app);
        });

        // Repository
        $app['eccube.plugin.simple_nempay.repository.nem_info'] = $app->share(function () use ($app) {
            $nemInfoRepository = $app['orm.em']->getRepository('Plugin\SimpleNemPay\Entity\NemInfo');
            $nemInfoRepository->setApplication($app);

            return $nemInfoRepository;
        });
        $app['eccube.plugin.simple_nempay.repository.nem_order'] = $app->share(function () use ($app) {
            $nemOrderRepository = $app['orm.em']->getRepository('Plugin\SimpleNemPay\Entity\NemOrder');
            $nemOrderRepository->setApplication($app);

            return $nemOrderRepository;
        });
        $app['eccube.plugin.simple_nempay.repository.nem_history'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\SimpleNemPay\Entity\NemHistory');
        });
        
        // form
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\SimpleNemPay\Form\Type\SimpleNemPayType($app);
            return $types;
        }));

        // log file
        $app['monolog.simple_nempay'] = $app->share(function ($app) {

            $logger = new $app['monolog.logger.class']('SimpleNemPay');

            $file = $app['config']['root_dir'] . '/app/log/SimpleNemPay.log';
            $RotateHandler = new RotatingFileHandler($file, $app['config']['log']['max_files'], Logger::INFO);
            $RotateHandler->setFilenameFormat(
                'SimpleNemPay_{date}',
                'Y-m-d'
            );

            $logger->pushHandler(
                new FingersCrossedHandler(
                    $RotateHandler,
                    new ErrorLevelActivationStrategy(Logger::INFO)
                )
            );

            return $logger;
        });
    }

    public function boot(BaseApplication $app)
    {
    }
}

 ?>

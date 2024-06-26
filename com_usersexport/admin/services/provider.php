<?php
/**
 * @package     Usersexport
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 Absolute Management SIA. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Semantyca\Component;

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {

	public function register(Container $container): void {
		$container->registerServiceProvider(new MVCFactory('\\Semantyca\\Component\\Usersexport'));
		$container->registerServiceProvider(new ComponentDispatcherFactory('\\Semantyca\\Component\\Usersexport'));
		$container->set(
			ComponentInterface::class,
			function (Container $container) {
				$component = new MVCComponent($container->get(ComponentDispatcherFactoryInterface::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				return $component;
			}
		);
	}
};
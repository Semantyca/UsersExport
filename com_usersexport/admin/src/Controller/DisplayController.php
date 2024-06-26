<?php
/**
 * @package     Usersexport
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 Absolute Management SIA. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace Semantyca\Component\Usersexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\BaseController;
use Semantyca\Component\Usersexport\Administrator\Helper\Constants;

class DisplayController extends BaseController
{
    protected $default_view = 'dashboard';

    public function display($cachable = false, $urlparams = array())
    {
        try
        {
            $app = Factory::getApplication();
            $user = $app->getIdentity();

            if ($user->guest || !$user->authorise('core.manage', 'com_usersexport'))
            {
                //$app->redirect(Route::_('index.php?option=com_users&view=login', false));
                //return false;
                throw new NotAllowed(Text::_('JERROR_ALERTNOAUTHOR'), 403);
            }

            $view = $this->getView('Dashboard', 'html');
            $view->set('js_bundle', $this->getBundleFromManifest());
            $view->display();
        }
        catch (\Exception $e)
        {
            Log::add($e->getMessage(), Log::ERROR, Constants::COMPONENT_NAME);
        }
    }

    private function getBundleFromManifest(): ?string
    {
        $manifestPath = JPATH_ADMINISTRATOR . '/components/com_usersexport/assets/bundle/manifest.json';

        if (!file_exists($manifestPath)) {
            return null;
        }

        $manifestContent = file_get_contents($manifestPath);
        $manifest = json_decode($manifestContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return isset($manifest['main.js']) ? 'bundle/' . $manifest['main.js'] : null;
    }
}

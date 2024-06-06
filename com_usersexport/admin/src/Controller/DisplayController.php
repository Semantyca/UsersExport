<?php

namespace Semantyca\Component\Usersexport\Administrator\Controller;

defined('_JEXEC') or die;

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
            $view = $this->getView('Dashboard', 'html');
            $view->set('js_bundle', $this->getDynamicScriptUrl('js'));
            $view->display();
        }
        catch (\Exception $e)
        {
            Log::add($e->getMessage(), Log::ERROR, Constants::COMPONENT_NAME);
        }
    }

    private function getDynamicScriptUrl($type): ?string
    {
        $relativeDirectory = "/components/com_usersexport/assets/bundle";
        $directory = JPATH_ADMINISTRATOR . $relativeDirectory;
        $prefix = "bundle-";

        if (!file_exists($directory) || !is_dir($directory))
        {
            return null;
        }

        $files = scandir($directory);
        foreach ($files as $file)
        {
            if (strpos($file, $prefix) === 0 && pathinfo($file, PATHINFO_EXTENSION) === $type)
            {
                return "bundle/" . $file;
            }
        }

        return null;
    }
}



<?php

namespace Semantyca\Component\Usersexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Exception;

class UsersController extends BaseController
{
    public function findAll()
    {
        $app = Factory::getApplication();
        header('Content-Type: application/json');
        try
        {
            // Pagination parameters
            $currentPage  = $this->input->getInt('page', 1);
            $itemsPerPage = $this->input->getInt('limit', 10);

            $model  = $this->getModel('Users', 'Administrator', ['ignore_request' => true]);

            $users = $model->getUsers($currentPage, $itemsPerPage);

            echo new JsonResponse($users);
        }
        catch (Exception $e)
        {
            http_response_code(500);
            echo new JsonResponse($e->getMessage(), 'error', true);
        }
        finally
        {
            $app->close();
        }
    }
}

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
            $currentPage  = $this->input->getInt('page', 1);
            $itemsPerPage = $this->input->getInt('size', 5);
            $fields       = $this->input->get('fields', [], 'array');

            $model  = $this->getModel('Users', 'Administrator', ['ignore_request' => true]);

            $users = $model->getUsers($currentPage, $itemsPerPage, $fields);

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

    public function getAvailableFields()
    {
        $app = Factory::getApplication();
        header('Content-Type: application/json');
        try
        {
            $model = $this->getModel('Users', 'Administrator', ['ignore_request' => true]);
            $fields = $model->getAvailableFields();
            echo new JsonResponse($fields);
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

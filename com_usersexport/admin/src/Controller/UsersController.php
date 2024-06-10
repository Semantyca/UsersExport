<?php
namespace Semantyca\Component\Usersexport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Exception;

class UsersController extends BaseController
{
    /**
     * @throws Exception
     * @since 1.0
     */
    public function findAll(): void
    {
        $app = Factory::getApplication();
        header('Content-Type: application/json');
        try
        {
            $currentPage  = $this->input->getInt('page', 1);
            $itemsPerPage = $this->input->getInt('size', 5);
            $fieldsString = $this->input->get('fields', '', 'string');
            $search       = $this->input->getString('search', '');
            $start        = $this->input->getString('start', '');
            $end          = $this->input->getString('end', '');

            $startDate = !empty($start) ? date('Y-m-d', $start / 1000) : '';
            $endDate = !empty($end) ? date('Y-m-d', $end / 1000) : '';

            $fields = array_map('trim', explode(',', $fieldsString));

            $model = $this->getModel('Users', 'Administrator', ['ignore_request' => true]);

            $users = $model->getUsers($currentPage, $itemsPerPage, $fields, $search, $startDate, $endDate);

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

    /**
     * @throws Exception
     * @since 1.0
     */
    public function getAvailableFields(): void
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

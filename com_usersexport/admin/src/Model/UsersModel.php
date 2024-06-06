<?php

namespace Semantyca\Component\Usersexport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class UsersModel extends BaseDatabaseModel
{
    public function getUsers($currentPage = 1, $itemsPerPage = 10)
    {
        $db     = $this->getDatabase();
        $query  = $db->getQuery(true);
        $offset = ($currentPage - 1) * $itemsPerPage;

        $query
            ->select([
                $db->quoteName('id', 'key'),
                $db->quoteName('name'),
                $db->quoteName('username'),
                $db->quoteName('email'),
                $db->quoteName('registerDate')
            ])
            ->from($db->quoteName('#__users'))
            ->order('registerDate desc')
            ->setLimit($itemsPerPage, $offset);

        $db->setQuery($query);
        $users = $db->loadObjectList();

        $queryCount = $db->getQuery(true)
            ->select('COUNT(' . $db->quoteName('id') . ')')
            ->from($db->quoteName('#__users'));
        $db->setQuery($queryCount);
        $count   = $db->loadResult();
        $maxPage = (int) ceil($count / $itemsPerPage);

        return [
            'docs'    => $users,
            'count'   => $count,
            'current' => $currentPage,
            'maxPage' => $maxPage
        ];
    }

}

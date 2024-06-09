<?php

namespace Semantyca\Component\Usersexport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class UsersModel extends BaseDatabaseModel
{
    public function getUsers($currentPage = 1, $itemsPerPage = 10, $fields = [])
    {
        $db     = $this->getDatabase();
        $query  = $db->getQuery(true);
        $offset = ($currentPage - 1) * $itemsPerPage;

        // Ensure fields have the correct format and handle table aliases
        $userFields = array_map(function($field) {
            return ($field === '#__users.password') ? 'REPEAT("*", 5) AS password' : str_replace('#__users.', 'u.', $field);
        }, array_filter($fields, function($field) {
            return strpos($field, '#__users.') !== false;
        }));

        // Construct the select part of the query for users
        $query->select($userFields)
            ->from($db->quoteName('#__users', 'u'))
            ->order('u.registerDate DESC')
            ->setLimit($itemsPerPage, $offset);

        $db->setQuery($query);
        $users = $db->loadObjectList();

        // Add notes and groups information
        foreach ($users as $user) {
            $user->notes = $this->getUserNotes($user->id);
            $user->groups = $this->getUserGroups($user->id);
        }

        // Count query
        $queryCount = $db->getQuery(true)
            ->select('COUNT(' . $db->quoteName('u.id') . ')')
            ->from($db->quoteName('#__users', 'u'));
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

    private function getUserNotes($userId)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('n.subject')
            ->from($db->quoteName('#__user_notes', 'n'))
            ->where($db->quoteName('n.user_id') . ' = ' . (int) $userId);

        $db->setQuery($query);
        return $db->loadColumn();
    }

    private function getUserGroups($userId)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select('g.title')
            ->from($db->quoteName('#__usergroups', 'g'))
            ->join('INNER', $db->quoteName('#__user_usergroup_map', 'ugm') . ' ON ' . $db->quoteName('g.id') . ' = ' . $db->quoteName('ugm.group_id'))
            ->where($db->quoteName('ugm.user_id') . ' = ' . (int) $userId);

        $db->setQuery($query);
        return $db->loadColumn();
    }



    public function getAvailableFields(): array
    {
        $db = $this->getDatabase();
        $app = Factory::getApplication();
        $dbName = $app->get('db'); // Get the database name from Joomla configuration

        // Get the table prefix
        $prefix = $app->get('dbprefix');

        // Replace #__ with the actual table prefix
        $tables = [
            $prefix . 'users',
            $prefix . 'user_profiles',
            $prefix . 'user_notes',
            $prefix . 'usergroups'
        ];

        $query = $db->getQuery(true);

        $query
            ->select([
                $db->quoteName('TABLE_NAME'),
                $db->quoteName('COLUMN_NAME')
            ])
            ->from($db->quoteName('INFORMATION_SCHEMA.COLUMNS'))
            ->where($db->quoteName('TABLE_NAME') . ' IN (' . implode(',', array_map([$db, 'quote'], $tables)) . ')')
            ->where($db->quoteName('TABLE_SCHEMA') . ' = ' . $db->quote($dbName))
            ->order([$db->quoteName('TABLE_NAME'), $db->quoteName('COLUMN_NAME')]);

        $db->setQuery($query);
        $columns = $db->loadObjectList();

        $options = [];
        $tables = [];

        foreach ($columns as $column) {
            $tableNameWithPrefix = str_replace($prefix, '#__', $column->TABLE_NAME); // Replace actual prefix with #__
            $tableNameWithoutPrefix = str_replace($prefix, '', $column->TABLE_NAME); // Remove the actual prefix for label
            if (!isset($tables[$tableNameWithPrefix])) {
                $tables[$tableNameWithPrefix] = [
                    'label' => $tableNameWithoutPrefix,
                    'key' => $tableNameWithPrefix,
                    'children' => []
                ];
            }
            $tables[$tableNameWithPrefix]['children'][] = [
                'label' => $column->COLUMN_NAME,
                'key' => $tableNameWithPrefix . '.' . $column->COLUMN_NAME
            ];
        }

        foreach ($tables as $table) {
            $options[] = $table;
        }

        return $options;
    }


}

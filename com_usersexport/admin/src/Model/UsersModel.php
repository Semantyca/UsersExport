<?php

namespace Semantyca\Component\Usersexport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class UsersModel extends BaseDatabaseModel
{
    public function getUsers($currentPage = 1, $itemsPerPage = 5, $fields = [])
    {
        $db     = $this->getDatabase();
        $query  = $db->getQuery(true);
        $offset = ($currentPage - 1) * $itemsPerPage;

        // Default fields from the users table
        if (empty($fields)) {
            $fields = [
                'u.id',
                'u.name',
                'u.username',
                'u.email',
                'REPEAT("*", 5) AS password', // Masking password with 5 asterisks
                'u.block',
                'u.sendEmail',
                'u.registerDate',
                'u.lastvisitDate',
                'u.activation',
                'u.params',
                'u.lastResetTime',
                'u.resetCount',
                'u.otpKey'
            ];
        } else {
            // Replace the password field with 5 asterisks if it is present in the fields array
            $fields = array_map(function($field) {
                return ($field === 'u.password') ? 'REPEAT("*", 5) AS password' : $field;
            }, $fields);
        }

        // Adding fields for notes and groups
        $fields[] = "GROUP_CONCAT(DISTINCT n.subject SEPARATOR ', ') AS notes";
        $fields[] = "GROUP_CONCAT(DISTINCT g.title SEPARATOR ', ') AS groups";

        // Construct the select part of the query
        $query->select($fields)
            ->from($db->quoteName('#__users', 'u'))
            ->leftJoin($db->quoteName('#__user_profiles', 'p') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('p.user_id'))
            ->leftJoin($db->quoteName('#__user_notes', 'n') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('n.user_id'))
            ->leftJoin($db->quoteName('#__user_usergroup_map', 'ugm') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('ugm.user_id'))
            ->leftJoin($db->quoteName('#__usergroups', 'g') . ' ON ' . $db->quoteName('ugm.group_id') . ' = ' . $db->quoteName('g.id'))
            ->group([
                'u.id',
                'u.name',
                'u.username',
                'u.email',
                'u.password',
                'u.block',
                'u.sendEmail',
                'u.registerDate',
                'u.lastvisitDate',
                'u.activation',
                'u.params',
                'u.lastResetTime',
                'u.resetCount',
                'u.otpKey'
            ])
            ->order('u.registerDate DESC')
            ->setLimit($itemsPerPage, $offset);

        $db->setQuery($query);
        $users = $db->loadObjectList();

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

    public function getAvailableFields()
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
            if (!isset($tables[$column->TABLE_NAME])) {
                $tables[$column->TABLE_NAME] = [
                    'label' => $column->TABLE_NAME,
                    'key' => $column->TABLE_NAME,
                    'children' => []
                ];
            }
            $tables[$column->TABLE_NAME]['children'][] = [
                'label' => $column->TABLE_NAME . '.' . $column->COLUMN_NAME,
                'key' => $column->TABLE_NAME . '.' . $column->COLUMN_NAME
            ];
        }

        foreach ($tables as $table) {
            $options[] = $table;
        }

        return $options;
    }
}

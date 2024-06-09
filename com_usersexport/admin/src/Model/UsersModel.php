<?php

namespace Semantyca\Component\Usersexport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class UsersModel extends BaseDatabaseModel
{
    public function getUsers($currentPage = 1, $itemsPerPage = 10, $fields = [])
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $offset = ($currentPage - 1) * $itemsPerPage;

        // Separate fields based on the table
        $userFields = [];
        $profileFields = [];
        $noteFields = [];
        $groupFields = [];

        foreach ($fields as $field) {
            if (strpos($field, '#__users.') === 0) {
                $userFields[] = str_replace('#__users.', 'u.', $field);
            } elseif (strpos($field, '#__user_profiles.') === 0) {
                $profileFields[] = str_replace('#__user_profiles.', 'p.', $field);
            } elseif (strpos($field, '#__user_notes.') === 0) {
                $noteFields[] = str_replace('#__user_notes.', 'n.', $field);
            } elseif (strpos($field, '#__usergroups.') === 0) {
                $groupFields[] = str_replace('#__usergroups.', 'g.', $field);
            }
        }

        // Ensure password field is masked if included
        $userFields = array_map(function($field) {
            return ($field === 'u.password') ? 'REPEAT("*", 5) AS password' : $field;
        }, $userFields);

        // Construct the select part of the query for users
        $query->select($userFields)
            ->from($db->quoteName('#__users', 'u'))
            ->order('u.registerDate DESC')
            ->setLimit($itemsPerPage, $offset);

        $db->setQuery($query);
        $users = $db->loadObjectList();

        // Fetch and add notes, groups, and profiles information based on the requested fields
        foreach ($users as $user) {
            $user->notes = $this->getUserNotes($user->id, $noteFields);
            $user->groups = $this->getUserGroups($user->id, $groupFields);
            $user->profiles = $this->getUserProfiles($user->id, $profileFields);
        }

        // Count query
        $queryCount = $db->getQuery(true)
            ->select('COUNT(' . $db->quoteName('u.id') . ')')
            ->from($db->quoteName('#__users', 'u'));
        $db->setQuery($queryCount);
        $count = $db->loadResult();
        $maxPage = (int) ceil($count / $itemsPerPage);

        return [
            'docs'    => $users,
            'count'   => $count,
            'current' => $currentPage,
            'maxPage' => $maxPage
        ];
    }

    private function getUserNotes($userId, $fields)
    {
        if (empty($fields)) {
            return [];
        }
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($fields)
            ->from($db->quoteName('#__user_notes', 'n'))
            ->where($db->quoteName('n.user_id') . ' = ' . (int) $userId);

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    private function getUserGroups($userId, $fields)
    {
        if (empty($fields)) {
            return [];
        }
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($fields)
            ->from($db->quoteName('#__usergroups', 'g'))
            ->join('INNER', $db->quoteName('#__user_usergroup_map', 'ugm') . ' ON ' . $db->quoteName('g.id') . ' = ' . $db->quoteName('ugm.group_id'))
            ->where($db->quoteName('ugm.user_id') . ' = ' . (int) $userId);

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    private function getUserProfiles($userId, $fields)
    {
        if (empty($fields)) {
            return [];
        }
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($fields)
            ->from($db->quoteName('#__user_profiles', 'p'))
            ->where($db->quoteName('p.user_id') . ' = ' . (int) $userId);

        $db->setQuery($query);
        return $db->loadObjectList();
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

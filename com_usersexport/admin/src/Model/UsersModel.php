<?php

namespace Semantyca\Component\Usersexport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class UsersModel extends BaseDatabaseModel
{
    public function getUsers($currentPage = 1, $itemsPerPage = 10, $fields = [], $search = '', $start = '', $end = ''): array
    {
        $db = $this->getDatabase();
        $query = $db->createQuery();
        $offset = ($currentPage - 1) * $itemsPerPage;

        $userFields = [];
        $profileFields = [];
        $noteFields = [];
        $groupFields = [];

        foreach ($fields as $field) {
            if (str_starts_with($field, '#__users.')) {
                $userFields[] = str_replace('#__users.', 'u.', $field);
            } elseif (str_starts_with($field, '#__user_profiles.')) {
                $profileFields[] = str_replace('#__user_profiles.', 'p.', $field);
            } elseif (str_starts_with($field, '#__user_notes.')) {
                $noteFields[] = str_replace('#__user_notes.', 'n.', $field);
            } elseif (str_starts_with($field, '#__usergroups.')) {
                $groupFields[] = str_replace('#__usergroups.', 'g.', $field);
            }
        }

        $userFields = array_map(function ($field) {
            return ($field === 'u.password') ? 'REPEAT("*", 5) AS password' : $field;
        }, $userFields);

        $query->select($userFields)
            ->from($db->quoteName('#__users', 'u'))
            ->order('u.registerDate DESC')
            ->setLimit($itemsPerPage, $offset);

        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('u.name LIKE ' . $search . ' OR u.username LIKE ' . $search . ' OR u.email LIKE ' . $search);
        }

        if (!empty($start)) {
            $query->where('u.registerDate >= ' . $db->quote($start));
        }
        if (!empty($end)) {
            $query->where('u.registerDate <= ' . $db->quote($end));
        }

        $db->setQuery($query);
        $users = $db->loadObjectList();

        foreach ($users as $user) {
            if (!empty($noteFields)) {
                $userNotes = $this->getUserNotes($user->id, $noteFields);
                foreach ($userNotes as $note) {
                    foreach ($note as $key => $value) {
                        $user->{$key} = $value;
                    }
                }
            }

            if (!empty($groupFields)) {
                $userGroups = $this->getUserGroups($user->id, $groupFields);
                foreach ($userGroups as $group) {
                    foreach ($group as $key => $value) {
                        $user->{$key} = $value;
                    }
                }
            }

            if (!empty($profileFields)) {
                $userProfiles = $this->getUserProfiles($user->id, $profileFields);
                foreach ($userProfiles as $profile) {
                    foreach ($profile as $key => $value) {
                        $user->{$key} = $value;
                    }
                }
            }
        }

        $queryCount = $db->getQuery(true)
            ->select('COUNT(' . $db->quoteName('u.id') . ')')
            ->from($db->quoteName('#__users', 'u'));

        if (!empty($search)) {
            $queryCount->where('u.name LIKE ' . $search . ' OR u.username LIKE ' . $search . ' OR u.email LIKE ' . $search);
        }

        if (!empty($start)) {
            $queryCount->where('u.registerDate >= ' . $db->quote($start));
        }
        if (!empty($end)) {
            $queryCount->where('u.registerDate <= ' . $db->quote($end));
        }

        $db->setQuery($queryCount);
        $count = $db->loadResult();
        $maxPage = (int)ceil($count / $itemsPerPage);

        return [
            'docs' => $users,
            'count' => $count,
            'current' => $currentPage,
            'maxPage' => $maxPage
        ];
    }

    private function getUserNotes($userId, $fields)
    {
        $db = $this->getDatabase();
        $query = $db->createQuery();

        $query->select($fields)
            ->from($db->quoteName('#__user_notes', 'n'))
            ->where($db->quoteName('n.user_id') . ' = ' . (int)$userId);

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    private function getUserGroups($userId, $fields)
    {
        $db = $this->getDatabase();
        $query = $db->createQuery();

        $query->select($fields)
            ->from($db->quoteName('#__usergroups', 'g'))
            ->join('INNER', $db->quoteName('#__user_usergroup_map', 'ugm') . ' ON ' . $db->quoteName('g.id') . ' = ' . $db->quoteName('ugm.group_id'))
            ->where($db->quoteName('ugm.user_id') . ' = ' . (int)$userId);

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    private function getUserProfiles($userId, $fields)
    {
        $db = $this->getDatabase();
        $query = $db->createQuery();

        $query->select($fields)
            ->from($db->quoteName('#__user_profiles', 'p'))
            ->where($db->quoteName('p.user_id') . ' = ' . (int)$userId);

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

        $query = $db->createQuery();

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

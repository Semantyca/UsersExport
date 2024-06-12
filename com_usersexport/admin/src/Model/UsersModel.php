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
        $query = $db->getQuery(true);
        $offset = ($currentPage - 1) * $itemsPerPage;

        $userFields = [];
        $profileFields = [];
        $noteFields = [];
        $groupFields = [];
        $customFields = [];

        foreach ($fields as $field) {
            if (str_starts_with($field, '#__users.')) {
                $userFields[] = $field;
            } elseif (str_starts_with($field, '#__user_profiles.')) {
                $profileFields[] = $field;
            } elseif (str_starts_with($field, '#__user_notes.')) {
                $noteFields[] = $field;
            } elseif (str_starts_with($field, '#__usergroups.')) {
                $groupFields[] = $field;
            } elseif (str_starts_with($field, 'custom.')) {
                $customFields[] = $field;
            }
        }

        if (!in_array('#__users.id', $userFields)) {
            $userFields[] = '#__users.id';
            $idRequested = false;
        } else {
            $idRequested = true;
        }

        $userFields = array_map(function ($field) {
            return ($field === '#__users.password') ? 'REPEAT("*", 5) AS password' : $field;
        }, $userFields);

        $query->select($userFields)
            ->from($db->quoteName('#__users'))
            ->order('registerDate DESC');

        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('name LIKE ' . $search . ' OR username LIKE ' . $search . ' OR email LIKE ' . $search);
        }

        if (!empty($start)) {
            $query->where('registerDate >= ' . $db->quote($start));
        }
        if (!empty($end)) {
            $query->where('registerDate <= ' . $db->quote($end));
        }

        if ($itemsPerPage > 0) {
            $query->setLimit($itemsPerPage, $offset);
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

            if (!empty($customFields)) {
                $userCustomFields = $this->getUserCustomFields($user->id, $customFields);
                foreach ($userCustomFields as $key => $value) {
                    $user->{$key} = $value;
                }
            }

            if (!$idRequested) {
                unset($user->id);
            }
        }

        $queryCount = $db->getQuery(true)
            ->select('COUNT(' . $db->quoteName('#__users.id') . ')')
            ->from($db->quoteName('#__users'));

        if (!empty($search)) {
            $queryCount->where('#__users.name LIKE ' . $search . ' OR #__users.username LIKE ' . $search . ' OR #__users.email LIKE ' . $search);
        }

        if (!empty($start)) {
            $queryCount->where('#__users.registerDate >= ' . $db->quote($start));
        }
        if (!empty($end)) {
            $queryCount->where('#__users.registerDate <= ' . $db->quote($end));
        }

        $db->setQuery($queryCount);
        $count = $db->loadResult();
        $maxPage = ($itemsPerPage > 0) ? (int)ceil($count / $itemsPerPage) : 1;

        return [
            'docs' => $users,
            'count' => $count,
            'current' => $currentPage,
            'maxPage' => $maxPage
        ];
    }



    private function getUserCustomFields($userId, $fields)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select(['cf.name AS field_name', 'cfv.value AS field_value'])
            ->from($db->quoteName('#__fields_values', 'cfv'))
            ->join('INNER', $db->quoteName('#__fields', 'cf') . ' ON ' . $db->quoteName('cf.id') . ' = ' . $db->quoteName('cfv.field_id'))
            ->where($db->quoteName('cfv.item_id') . ' = ' . (int)$userId);

        $db->setQuery($query);
        $customFields = $db->loadObjectList();

        $result = [];
        foreach ($customFields as $customField) {
            //if (in_array($customField->field_name, $fields)) {
                $result[$customField->field_name] = $customField->field_value;
            //}
        }

        return $result;
    }


    private function getUserNotes($userId, $fields)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        $query->select($fields)
            ->from($db->quoteName('#__user_notes', 'n'))
            ->where($db->quoteName('n.user_id') . ' = ' . (int)$userId);

        $db->setQuery($query);
        return $db->loadObjectList();
    }

    private function getUserGroups($userId, $fields)
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

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
        $query = $db->getQuery(true);

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
        $dbName = $app->get('db');

        $prefix = $app->get('dbprefix');

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
            $tableNameWithPrefix = str_replace($prefix, '#__', $column->TABLE_NAME);
            $tableNameWithoutPrefix = str_replace($prefix, '', $column->TABLE_NAME);
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

        $customQuery = $db->getQuery(true);
        $customQuery->select('DISTINCT ' . $db->quoteName('#__fields.name', 'field_name'))
            ->from($db->quoteName('#__users', '#__users'))
            ->leftJoin($db->quoteName('#__fields_values', '#__fields_values') . ' ON ' . $db->quoteName('#__users.id') . ' = ' . $db->quoteName('#__fields_values.item_id'))
            ->leftJoin($db->quoteName('#__fields', '#__fields') . ' ON ' . $db->quoteName('#__fields.id') . ' = ' . $db->quoteName('#__fields_values.field_id'))
            ->where($db->quoteName('#__fields.context') . ' = ' . $db->quote('com_users.user'))
            ->where($db->quoteName('#__fields.state') . ' = 1'); // Only include published custom fields

        $db->setQuery($customQuery);
        $customFields = $db->loadObjectList();

        $customFieldOptions = [
            'label' => 'Custom Fields',
            'key' => 'custom',
            'children' => []
        ];

        foreach ($customFields as $field) {
            $customFieldOptions['children'][] = [
                'label' => $field->field_name,
                'key' => 'custom.' . $field->field_name
            ];
        }

        $options[] = $customFieldOptions;

        return $options;
    }
}

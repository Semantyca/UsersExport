import { defineStore } from 'pinia';
import axios from 'axios';
import { ref, computed } from 'vue';
import { useMessage, useLoadingBar } from 'naive-ui';

axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

export const useUserStore = defineStore('userStore', () => {
    const message = useMessage();
    const loadingBar = useLoadingBar();
    const userMap = ref(new Map());
    const pagination = ref({
        page: 1,
        pageSize: 10,
        count: 0,
        pageCount: 1,
        showQuickJumper: true
    });
    const allUsers = ref([]);
    const availableFields = ref([]);
    const defaultFields = ref({
        label: 'users',
        key: '#__users',
        children: [
            { label: 'id', key: '#__users.id' },
            { label: 'name', key: '#__users.name' },
            { label: 'username', key: '#__users.username' },
            { label: 'email', key: '#__users.email' },
            { label: 'registerDate', key: '#__users.registerDate' }
        ]
    });
    const selectedFields = ref([]);
    const search = ref('');
    const start = ref('');
    const end = ref('');

    const getPagination = computed(() => ({
        page: pagination.value.page,
        pageSize: pagination.value.pageSize,
        itemCount: pagination.value.count,
        pageCount: pagination.value.pageCount,
        size: 'large',
        pageSizes: [10, 20, 30],
        showSizePicker: true
    }));

    const getCurrentPage = computed(() => {
        const pageData = userMap.value.get(pagination.value.page);
        return pageData ? pageData.docs : [];
    });

    const getAllUsers = computed(() => allUsers.value);

    const getAvailableFields = computed(() => availableFields.value);

    const getSelectedFields = computed(() => selectedFields.value);

    const getCsvData = computed(() => {
        const data = getCurrentPage.value;
        return data.length ? convertToCSV(data) : '';
    });

    const fetchUsers = async (page = 1, fields = [], searchValue = '', startDate = '', endDate = '') => {
        try {
            loadingBar.start();
            let fieldsParam;
            if (fields.length > 0) {
                fieldsParam = fields.join(',');
            } else {
                fieldsParam = defaultFields.value.children.map(field => field.key).join(',');
                message.info('No fields selected, using default fields.');
            }

            const response = await axios.get('index.php?option=com_usersexport&task=users.findAll', {
                params: {
                    page,
                    size: pagination.value.pageSize,
                    fields: fieldsParam,
                    search: searchValue,
                    start: startDate,
                    end: endDate
                }
            });

            const pageObj = response.data;
            if (pageObj && pageObj.data) {
                const { docs, count, maxPage, current } = pageObj.data;
                pagination.value.page = current;
                pagination.value.count = count;
                pagination.value.pageCount = maxPage;
                userMap.value.set(page, { docs });
            }
            loadingBar.finish();
        } catch (error) {
            loadingBar.error();
            message.error('Error fetching users: ' + error.message);
            console.error('Error fetching users:', error);
        }
    };

    const fetchAllUsers = async (fields = [], searchValue = '', startDate = '', endDate = '') => {
        try {
            loadingBar.start();
            let fieldsParam;
            if (fields.length > 0) {
                fieldsParam = fields.join(',');
            } else {
                fieldsParam = defaultFields.value.children.map(field => field.key).join(',');
                message.info('No fields selected, using default fields.');
            }

            const response = await axios.get('index.php?option=com_usersexport&task=users.findAll', {
                params: {
                    page: 1,
                    size: 0,
                    fields: fieldsParam,
                    search: searchValue,
                    start: startDate,
                    end: endDate
                }
            });

            const pageObj = response.data;
            if (pageObj && pageObj.data) {
                const { docs } = pageObj.data;
                allUsers.value = docs;
            }
            loadingBar.finish();
        } catch (error) {
            loadingBar.error();
            message.error('Error fetching all users: ' + error.message);
            console.error('Error fetching all users:', error);
        }
    };

    const fetchAvailableFields = async () => {
        try {
            const response = await axios.get('index.php?option=com_usersexport&task=users.getAvailableFields');
            const pageObj = response.data;
            availableFields.value = pageObj.data;
        } catch (error) {
            message.error('Error fetching available fields: ' + error.message);
            console.error('Error fetching available fields:', error);
            throw error;
        }
    };

    const convertToCSV = (data) => {
        const array = [Object.keys(data[0])].concat(data);
        return array.map(row => Object.values(row).map(value => `"${value}"`).join(',')).join('\n');
    };

    return {
        userMap,
        pagination,
        allUsers,
        availableFields,
        defaultFields,
        selectedFields,
        search,
        start,
        end,
        getPagination,
        getCurrentPage,
        getAllUsers,
        getAvailableFields,
        getSelectedFields,
        getCsvData,
        fetchUsers,
        fetchAllUsers,
        fetchAvailableFields,
        convertToCSV
    };
});

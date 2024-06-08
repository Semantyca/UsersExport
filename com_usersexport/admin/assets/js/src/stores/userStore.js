import { defineStore } from "pinia";
import axios from "axios";
import { useMessage, useLoadingBar } from 'naive-ui';

export const useUserStore = defineStore('userStore', {
    state: () => ({
        userMap: new Map(),
        pagination: {
            page: 1,
            pageSize: 5,
            count: 0,
            pageCount: 1
        },
        availableFields: [],
        defaultFields: {
            label: "users",
            key: "#__users",
            children: [
                { label: 'id', key: '#__users.id' },
                { label: 'name', key: '#__users.name' },
                { label: 'username', key: '#__users.username' },
                { label: 'email', key: '#__users.email' },
                { label: 'registerDate', key: '#__users.registerDate' }
            ]
        },
        selectedFields: [] // Add selectedFields to the state
    }),
    getters: {
        getPagination() {
            return {
                page: this.pagination.page,
                pageSize: this.pagination.pageSize,
                itemCount: this.pagination.count,
                pageCount: this.pagination.pageCount,
                size: 'large'
            };
        },
        getCurrentPage() {
            const pageData = this.userMap.get(this.pagination.page);
            return pageData ? pageData.docs : [];
        },
        getAvailableFields() {
            //TODO redundant
            return this.availableFields.data;
        },
        getSelectedFields() {
            return this.selectedFields;
        },
        getCsvData() {
            const data = this.getCurrentPage;
            return data.length ? this.convertToCSV(data) : '';
        }
    },
    actions: {
        async fetchUsers(page = 1, fields = []) {
            const message = useMessage();
            const loadingBar = useLoadingBar();
            try {
                const fieldsParam = fields.join(','); // Concatenate fields with commas
                const response = await axios.get('index.php?option=com_usersexport&task=users.findAll', {
                    params: {
                        page: page,
                        size: this.pagination.pageSize,
                        fields: fieldsParam
                    }
                });

                const pageObj = response.data;
                if (pageObj && pageObj.data) {
                    const { docs, count, maxPage, current } = pageObj.data;
                    this.pagination.page = current;
                    this.pagination.count = count;
                    this.pagination.pageCount = maxPage;
                    this.userMap.set(page, { docs });
                }
            } catch (error) {
                message.error("Error fetching users: " + error.message);
                console.error("Error fetching users:", error);
            }
        },

        async fetchAvailableFields() {
            try {
                const response = await axios.get('index.php?option=com_usersexport&task=users.getAvailableFields');
                this.availableFields = response.data; // Store fetched fields in state
            } catch (error) {
                console.error("Error fetching available fields:", error);
                throw error;
            }
        },

        setSelectedFields(fields) {
            this.selectedFields = fields;
        },

        convertToCSV(data) {
            const array = [Object.keys(data[0])].concat(data);
            return array.map(row => Object.values(row).map(value => `"${value}"`).join(',')).join('\n');
        }
    }
});

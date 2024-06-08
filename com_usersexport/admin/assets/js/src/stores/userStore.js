import { defineStore } from "pinia";
import axios from "axios";
import { useMessage, useLoadingBar } from 'naive-ui';

export const useUserStore = defineStore('userStore', {
    state: () => ({
        userMap: new Map(),
        pagination: {
            currentPage: 1,
            itemsPerPage: 5,
            totalItems: 0,
            totalPages: 0
        },
        availableFields: []
    }),
    getters: {
        getPagination() {
            return {
                page: this.pagination.currentPage,
                pageSize: this.pagination.itemsPerPage,
                itemCount: this.pagination.totalItems,
                pageCount: this.pagination.totalPages,
                size: 'large'
            };
        },
        getCurrentPage() {
            const pageData = this.userMap.get(this.pagination.currentPage);
            return pageData ? pageData.docs : [];
        },
        getAvailableFields() {
            return this.availableFields.data;
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
                const response = await axios.get('index.php?option=com_usersexport&task=users.findAll', {
                    params: {
                        page: page,
                        limit: this.pagination.itemsPerPage,
                        fields: fields
                    }
                });

                const pageObj = response.data;
                if (pageObj && pageObj.data) {
                    const { docs, count, maxPage, current } = pageObj.data;
                    this.pagination.page = current;
                    this.pagination.pageSize = this.pagination.itemsPerPage;
                    this.pagination.itemCount = count;
                    this.pagination.pageCount = maxPage;
                    this.userMap.set(page, { docs });
                }
            } catch (error) {
                message.error("Error fetching users: " + error.message);
                console.error("Error fetching users:", error);
            }
        },

        async fetchAvailableFields(usersOnly = false) {
            try {
                const response = await axios.get('index.php?option=com_usersexport&task=users.getAvailableFields', {
                    params: {
                        usersOnly: usersOnly
                    }
                });
                this.availableFields = response.data; // Store fetched fields in state
            } catch (error) {
                console.error("Error fetching available fields:", error);
                throw error;
            }
        },

        convertToCSV(data) {
            const array = [Object.keys(data[0])].concat(data);
            return array.map(row => Object.values(row).map(value => `"${value}"`).join(',')).join('\n');
        }
    }
});

import { defineStore } from "pinia";
import axios from "axios";
import { useMessage, useLoadingBar } from 'naive-ui';

export const useUserStore = defineStore('userStore', {
    state: () => ({
        userMap: new Map(),
        pagination: {
            currentPage: 1,
            itemsPerPage: 10,
            totalItems: 0,
            totalPages: 0
        }
    }),
    getters: {
        getPagination() {
            return {
                page: this.pagination.currentPage,
                pageSize: this.pagination.itemsPerPage,
                itemCount: this.pagination.totalItems,
                pageCount: this.pagination.totalPages,
                size: 'large',
                showSizePicker: true,
                pageSizes: [10, 20, 50]
            };
        },
        getCurrentPage() {
            const pageData = this.userMap.get(this.pagination.currentPage);
            return pageData ? pageData.docs : [];
        }
    },
    actions: {
        async fetchUsers(page = 1) {
            const message = useMessage();
            const loadingBar = useLoadingBar();

            try {
                loadingBar.start();

                const response = await axios.get('index.php?option=com_usersexport&task=users.findAll', {
                    params: {
                        page: page,
                        limit: this.pagination.itemsPerPage
                    }
                });

                const pageObj = response.data;
                if (pageObj && pageObj.data) {
                    const { docs, count, maxPage, current } = pageObj.data;
                    this.pagination.page = current;
                    this.pagination.pageSize = current;
                    this.pagination.itemCount = count;
                    this.pagination.pageCount = maxPage;
                    this.userMap.set(page, { docs });

                }

                loadingBar.finish();
            } catch (error) {
                loadingBar.error();
                message.error("Error fetching users: " + error.message);
                console.error("Error fetching users:", error);
            }
        }
    }
});

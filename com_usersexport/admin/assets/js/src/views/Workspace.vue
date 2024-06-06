<template>
  <n-card>
    <n-data-table
        remote
        :columns="columns"
        :data="userStore.getCurrentPage"
        :pagination="userStore.getPagination"
      />
  </n-card>
</template>

<script>
import {defineComponent, onMounted} from 'vue';
import {useUserStore} from '../stores/userStore';
import {NCard, NDataTable, NPagination} from 'naive-ui';

export default defineComponent({
  components: {
    NCard,
    NDataTable,
    NPagination
  },
  setup() {
    const userStore = useUserStore();
    const columns = [
      {
        title: 'ID',
        key: 'key'
      },
      {
        title: 'Name',
        key: 'name'
      },
      {
        title: 'Username',
        key: 'username'
      },
      {
        title: 'Email',
        key: 'email'
      },
      {
        title: 'Register Date',
        key: 'registerDate'
      }
    ];

    const fetchUsers = async (page) => {
      await userStore.fetchUsers(page);
    };

    const handlePageChange = (page) => {
      fetchUsers(page);
    };

    onMounted(() => {
      fetchUsers(1);
    });

    return {
      columns,
      userStore,
      handlePageChange
    };
  }
});
</script>

<style scoped>
/* Add any custom styles here if needed */
</style>

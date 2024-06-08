<template>
  <n-card>
    <h1 class="title">Users export</h1>
    <div class="toolbar">
      <n-button type="info" size="large" @click="toggleFilter">Filter</n-button>
      <n-button type="primary" size="large" @click="exportCSV">Export CSV</n-button>
    </div>
    <transition name="slide-down">
      <div v-if="showFilter" class="filter-toolbar">
        <n-input size="large" v-model="searchQuery" placeholder="Search..." style="width: 400px;" />
        <n-date-picker size="large" v-model="dateRange" type="daterange" placeholder="Select date range" />
        <n-tree-select
            filterable
            clearable
            size="large"
            v-model="selectedColumns"
            multiple
            check-strategy="child"
            checkable
            cascade
            :options="userStore.getAvailableFields"
            placeholder="Select columns"
        />
      </div>
    </transition>
    <div class="data-table-container">
      <n-skeleton text v-if="loading" class="data-table-skeleton" title height="30px"></n-skeleton>
      <n-data-table
          v-else
          remote
          :columns="columns"
          :data="userStore.getCurrentPage"
          :pagination="userStore.getPagination"
          class="data-table"
      />
    </div>
  </n-card>
</template>

<script>
import { defineComponent, ref, onMounted, watch } from 'vue';
import { useUserStore } from '../stores/userStore';
import { NCard, NDataTable, NButton, NInput, NDatePicker, NTreeSelect, NSkeleton } from 'naive-ui';

export default defineComponent({
  components: {
    NCard,
    NDataTable,
    NButton,
    NInput,
    NDatePicker,
    NTreeSelect,
    NSkeleton
  },
  setup() {
    const userStore = useUserStore();
    const columns = ref([]);
    const showFilter = ref(false);
    const searchQuery = ref('');
    const dateRange = ref(null);
    const selectedColumns = ref([]);
    const loading = ref(true);

    const defaultFields = [
      'u.id',
      'u.name',
      'u.username',
      'u.email',
      'u.registerDate'
    ];

    const updateColumns = () => {
      columns.value = selectedColumns.value.length > 0
          ? selectedColumns.value.map(field => {
            const [table, column] = field.split('.');
            return { title: column, key: column };
          })
          : defaultFields.map(field => {
            const column = field.split('.')[1];
            return { title: column, key: column };
          });
    };

    const fetchUsers = async (page) => {
      loading.value = true;
      await userStore.fetchUsers(page, selectedColumns.value.length > 0 ? selectedColumns.value : defaultFields);
      loading.value = false;
    };

    const fetchAvailableFields = async () => {
      try {
        await userStore.fetchAvailableFields(true);
      } catch (error) {
        console.error("Error fetching available fields:", error);
      }
    };

    const handlePageChange = (page) => {
      fetchUsers(page);
    };

    const toggleFilter = () => {
      showFilter.value = !showFilter.value;
    };

    const exportCSV = () => {
      // Logic for exporting CSV
    };

    onMounted(() => {
      fetchAvailableFields().then(() => {
        updateColumns();
        fetchUsers(1);
      });
    });

    watch(selectedColumns, () => {
      updateColumns();
      fetchUsers(1);
    });

    return {
      columns,
      userStore,
      handlePageChange,
      showFilter,
      searchQuery,
      dateRange,
      selectedColumns,
      toggleFilter,
      exportCSV,
      loading
    };
  }
});
</script>

<style scoped>
.toolbar {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}

.filter-toolbar {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}

.data-table-container {
  margin-top: 20px;
}

.data-table {
  margin-top: 20px;
}

.title {
  margin-bottom: 20px;
}

.data-table-skeleton {
  margin-top: 20px;
}

.slide-down-enter-active, .slide-down-leave-active {
  transition: max-height 0.5s ease;
}
.slide-down-enter, .slide-down-leave-to /* .slide-down-leave-active in <2.1.8 */ {
  max-height: 0;
  overflow: hidden;
}
</style>

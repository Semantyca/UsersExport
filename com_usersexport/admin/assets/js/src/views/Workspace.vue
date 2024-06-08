<template>
  <n-card>
    <n-grid :cols="1" x-gap="12" y-gap="12" class="mt-1">
      <n-gi>
        <n-h1>Users export</n-h1>
      </n-gi>
      <n-gi>
        <n-space>
          <n-button type="info" size="large" @click="toggleFilter">Filter</n-button>
          <n-button type="primary" size="large" @click="exportCSV">Export CSV</n-button>
        </n-space>
      </n-gi>
      <n-gi>
        <n-collapse v-model:show="showFilter">
          <n-collapse-item title="Filter">
            <n-space size="large">
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
            </n-space>
          </n-collapse-item>
        </n-collapse>
      </n-gi>
      <n-gi>
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
      </n-gi>
      <n-gi>
        <code-mirror
            v-model="userStore.getCsvData"
            :read-only="true"
            basic
            :lang="lang"
            :dark="dark"
            :style="{ width: '100%', marginTop: '20px' }"
        />
      </n-gi>
    </n-grid>
  </n-card>
</template>

<script>
import { defineComponent, ref, onMounted, watch } from 'vue';
import { useUserStore } from '../stores/userStore';
import {
  NCard, NDataTable, NButton, NInput, NDatePicker, NTreeSelect,
  NSkeleton, NGrid, NGi, NH1, NSpace, NCollapse, NCollapseItem
} from 'naive-ui';
import CodeMirror from 'vue-codemirror6';
import { markdown } from '@codemirror/lang-markdown';

export default defineComponent({
  components: {
    NCard,
    NDataTable,
    NButton,
    NInput,
    NDatePicker,
    NTreeSelect,
    NSkeleton,
    NGrid,
    NGi,
    NH1,
    NSpace,
    NCollapse,
    NCollapseItem,
    CodeMirror
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
      const csvData = userStore.getCsvData;
      const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', 'users_export.csv');
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
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
      loading,
      lang: ref(markdown()),
      dark: ref(false)
    };
  }
});
</script>

<style scoped>
.data-table-container {
  margin-top: 20px;
}

.data-table {
  margin-top: 20px;
}

.data-table-skeleton {
  margin-top: 20px;
}

.title {
  margin-bottom: 20px;
}
</style>

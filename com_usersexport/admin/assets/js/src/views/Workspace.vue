<template>
  <n-card>
    <n-grid :cols="1" x-gap="12" y-gap="12" class="mt-1">
      <n-gi>
        <n-h2>Users export</n-h2>
      </n-gi>
      <n-gi>
        <n-space>
          <n-button type="info" size="large" @click="toggleFilter">Filter</n-button>
          <n-button type="success" size="large" @click="togglePreview">Preview</n-button>
          <n-button type="primary" size="large" @click="exportCSV">Export CSV</n-button>
        </n-space>
      </n-gi>
      <n-gi>
        <n-collapse-transition :show="showFilter">
          <n-grid :cols="10" x-gap="12">
            <n-gi span="3">
              <n-input size="large" v-model="searchQuery" placeholder="Search..." class="w-[25rem]"/>
            </n-gi>
            <n-gi span="2">
              <n-date-picker size="large" v-model="dateRange" type="daterange" placeholder="Select date range"
                             class="w-[25rem]"/>
            </n-gi>
            <n-gi span="5">
              <n-tree-select
                  :default-value="userStore.selectedFields"
                  filterable
                  clearable
                  size="large"
                  v-model="userStore.selectedFields"
                  multiple
                  check-strategy="child"
                  checkable
                  cascade
                  :options="userStore.availableFields"
                  placeholder="Select columns"
                  class="w-[25rem]"
                  @update:value="handleTreeSelectChange"
              />
            </n-gi>
          </n-grid>
        </n-collapse-transition>
      </n-gi>
      <n-gi>
        <n-collapse-transition :show="showPreview">
          <n-code :code="csvData" language="csv" :hljs="hljs" />
        </n-collapse-transition>
      </n-gi>
      <n-gi>
<!--        <n-skeleton v-if="loading" text :repeat="5" class="data-table-skeleton" title height="30px"></n-skeleton>-->
        <n-data-table
            remote
            :columns="columns"
            :data="userStore.getCurrentPage"
            :pagination="userStore.getPagination"
            @update:page="handlePageChange"
        />
      </n-gi>
    </n-grid>
  </n-card>
</template>

<script>
import { defineComponent, ref, onMounted, watch } from 'vue';
import { useUserStore } from '../stores/userStore';
import hljs from 'highlight.js';
import {
  NCard, NDataTable, NButton, NInput, NDatePicker, NTreeSelect,
  NSkeleton, NGrid, NGi, NH2, NH4, NSpace, NCollapseTransition, NCode
} from 'naive-ui';

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
    NH2,
    NH4,
    NSpace,
    NCollapseTransition,
    NCode
  },
  setup() {
    const userStore = useUserStore();
    const columns = ref([]);
    const searchQuery = ref('');
    const dateRange = ref(null);
    const loading = ref(true);
    const csvData = ref('');
    const showFilter = ref(false);
    const showPreview = ref(false);

    const updateColumns = () => {
      const newColumns = userStore.selectedFields.map(field => {
        const column = field.split('.').pop();
        return { title: column, key: column };
      });

      // Only update columns that have changed
      if (JSON.stringify(columns.value) !== JSON.stringify(newColumns)) {
        columns.value = newColumns;
      }
    };

    const fetchUsers = async (page, fields) => {
      loading.value = true;
      await userStore.fetchUsers(page, fields);
      loading.value = false;
      updateCsvData();
    };

    const fetchAvailableFields = async () => {
      try {
        await userStore.fetchAvailableFields();
      } catch (error) {
        console.error("Error fetching available fields:", error);
      }
    };

    const toggleFilter = () => {
      showFilter.value = !showFilter.value;
    };

    const togglePreview = () => {
      updateCsvData();
      showPreview.value = !showPreview.value;
    };

    const exportCSV = () => {
      const data = userStore.getCurrentPage;
      const csv = convertToCSV(data);
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', 'users_export.csv');
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    };

    const updateCsvData = () => {
      const data = userStore.getCurrentPage;
      csvData.value = convertToCSV(data);
    };

    const convertToCSV = (data) => {
      const array = [Object.keys(data[0])].concat(data);
      return array.map(row => Object.values(row).map(value => `"${value}"`).join(',')).join('\n');
    };

    onMounted(() => {
      fetchAvailableFields().then(() => {
        userStore.selectedFields = userStore.defaultFields.children.map(field => field.key);
        updateColumns();
        fetchUsers(1, userStore.selectedFields);
      });
    });

    watch(() => userStore.selectedFields, (newVal, oldVal) => {
      if (newVal !== oldVal) {
        updateColumns();
        fetchUsers(1, userStore.selectedFields);
      }
    });

    const handleTreeSelectChange = (value) => {
      userStore.selectedFields = value;
    };

    function handlePageChange(page) {
      fetchUsers(page, userStore.selectedFields);
    }

    return {
      columns,
      userStore,
      handlePageChange,
      showFilter,
      searchQuery,
      dateRange,
      toggleFilter,
      togglePreview,
      exportCSV,
      loading,
      csvData,
      showPreview,
      hljs,
      handleTreeSelectChange
    };
  }
});
</script>

<style scoped>
.data-table-skeleton {
  margin-top: 20px;
}
</style>

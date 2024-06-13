<template>
    <n-card>
      <n-grid :cols="1" x-gap="12" y-gap="12" class="mt-1">
        <n-gi>
          <n-h2>UsersExport</n-h2>
        </n-gi>
        <n-gi>
          <n-space>
            <n-button type="info" size="large" @click="toggleFilter">Filter</n-button>
            <n-button type="success" size="large" @click="togglePreview">Preview</n-button>
            <n-button type="primary" size="large" @click="exportAllDataAsCSV">Export CSV</n-button>
          </n-space>
        </n-gi>
        <n-gi>
          <n-collapse-transition :show="showFilter">
            <n-grid cols="5 xl:10" y-gap="12" x-gap="12" responsive="screen">
              <n-gi :span="3">
                <n-input size="large"
                         clearable
                         v-model="searchQuery"
                         placeholder="Search Username, Name or Email address..."
                         class="w-[25rem]"
                         @input="handleSearchInput" />
              </n-gi>
              <n-gi span="2">
                <n-date-picker size="large"
                               clearable
                               v-model="dateRange"
                               type="daterange"
                               class="w-[25rem]"
                               @update:value="handleDateRangeChange" />
              </n-gi>
              <n-gi span="5">
                <n-tree-select
                    :default-value="userStore.defaultFields.children.map(field => field.key)"
                    filterable
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
import { debounce } from 'lodash';
import { convertToCSV, exportCSV } from '../utils/csvUtils';


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
    const loading = ref(false);
    const csvData = ref('');
    const showFilter = ref(false);
    const showPreview = ref(false);


    const updateColumns = () => {
      const newColumns = userStore.selectedFields.map(field => {
        const column = field.split('.').pop();
        return { title: column, key: column };
      });

      if (JSON.stringify(columns.value) !== JSON.stringify(newColumns)) {
        columns.value = newColumns;
      }
    };

    const fetchUsers = async (page = 1) => {
      await userStore.fetchUsers(
          page,
          userStore.selectedFields,
          searchQuery.value,
          dateRange.value ? dateRange.value[0] : '',
          dateRange.value ? dateRange.value[1] : ''
      );
      updatePreviewCsvData();
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
      updatePreviewCsvData();
      showPreview.value = !showPreview.value;
    };

    const exportAllDataAsCSV = async () => {
      await userStore.fetchAllUsers(
          userStore.selectedFields,
          searchQuery.value,
          dateRange.value ? dateRange.value[0] : '',
          dateRange.value ? dateRange.value[1] : ''
      );
      const data = userStore.getAllUsers;
      const timestamp = new Date().toISOString().replace(/[:.-]/g, '');
      const filename = `users_export_${timestamp}.csv`;
      exportCSV(data, filename);
    };

    const updatePreviewCsvData = () => {
      const data = userStore.getCurrentPage;
      csvData.value = data.length ? convertToCSV(data) : '';
    };

    onMounted(async () => {
      await fetchAvailableFields();
      userStore.selectedFields = userStore.defaultFields.children.map(field => field.key);
      updateColumns();
    });

    watch(() => userStore.selectedFields, (newVal, oldVal) => {
      if (newVal !== oldVal) {
        updateColumns();
        fetchUsers(1);
      }
    });

    watch(dateRange, () => {
      fetchUsers(1);
    });

    const handleSearchInput = debounce((val) => {
      searchQuery.value = val;
      if (val.length >= 2 || val.length === 0) {
        fetchUsers(1);
      }
    }, 500);

    const handleDateRangeChange = (value) => {
      dateRange.value = value;
    };

    const handleTreeSelectChange = (value) => {
      userStore.selectedFields = value;
    };

    function handlePageChange(page) {
      fetchUsers(page);
    }

    const resetFields = () => {
      searchQuery.value = '';
      dateRange.value = null;
      userStore.selectedFields = userStore.defaultFields.children.map(field => field.key);
      updateColumns();
      fetchUsers(1);
    };

    return {
      columns,
      userStore,
      handlePageChange,
      showFilter,
      searchQuery,
      dateRange,
      toggleFilter,
      togglePreview,
      exportAllDataAsCSV,
      loading,
      csvData,
      showPreview,
      hljs,
      handleTreeSelectChange,
      handleSearchInput,
      handleDateRangeChange,
      resetFields
    };
  }
});
</script>


<style scoped>

</style>

<template>
  <n-card>
    <n-grid :cols="1" x-gap="12" y-gap="12" class="mt-1">
      <n-gi>
        <n-h2>Users export</n-h2>
      </n-gi>
      <n-gi>
        <n-space>
          <n-button type="info" size="large" @click="toggleFilter">Filter</n-button>
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
                  class="w-[25rem]"
              />
            </n-gi>
          </n-grid>
        </n-collapse-transition>
      </n-gi>
      <n-gi>
        <n-skeleton text v-if="loading" class="data-table-skeleton" title height="30px"></n-skeleton>
        <n-data-table
            v-else
            remote
            :columns="columns"
            :data="userStore.getCurrentPage"
            :pagination="userStore.getPagination"
            @update:page="handlePageChange"
        />
      </n-gi>
      <n-gi>
        <n-h4 class="mb-0">Preview</n-h4>
        <div class="mb-0">(For the preview the list was truncated)</div>
      </n-gi>
      <n-gi>
        <code-mirror
            v-model="csvData"
            :read-only="true"
            basic
            :lang="lang"
            :dark="dark"
            class="w-full"
        />
      </n-gi>
    </n-grid>
  </n-card>
</template>

<script>
import {defineComponent, ref, onMounted, watch} from 'vue';
import {useUserStore} from '../stores/userStore';
import {
  NCard, NDataTable, NButton, NInput, NDatePicker, NTreeSelect,
  NSkeleton, NGrid, NGi, NH2, NH4, NSpace, NCollapseTransition
} from 'naive-ui';
import CodeMirror from 'vue-codemirror6';
import {markdown} from '@codemirror/lang-markdown';

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
    CodeMirror
  },
  setup() {
    const userStore = useUserStore();
    const columns = ref([]);
    const searchQuery = ref('');
    const dateRange = ref(null);
    const selectedColumns = ref([]);
    const loading = ref(true);
    const csvData = ref('');
    const showFilter = ref(false);

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
            return {title: column, key: column};
          })
          : defaultFields.map(field => {
            const column = field.split('.')[1];
            return {title: column, key: column};
          });
    };

    const fetchUsers = async (page) => {
      loading.value = true;
      await userStore.fetchUsers(page, selectedColumns.value.length > 0 ? selectedColumns.value : defaultFields);
      loading.value = false;
      updateCsvData();
    };

    const fetchAvailableFields = async () => {
      try {
        await userStore.fetchAvailableFields(true);
      } catch (error) {
        console.error("Error fetching available fields:", error);
      }
    };

    const toggleFilter = () => {
      showFilter.value = !showFilter.value;
    };

    const exportCSV = () => {
      const data = userStore.getCurrentPage;
      const csv = convertToCSV(data);
      const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
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
        updateColumns();
        fetchUsers(1, []);
      });
    });

    watch(selectedColumns, () => {
      //updateColumns();
      updateCsvData();
      fetchUsers(1);
    });

    function handlePageChange(page) {
      userStore.fetchUsers(page, []);
    }

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
      dark: ref(false),
      csvData
    };
  }
});
</script>

<style scoped>

.data-table-skeleton {
  margin-top: 20px;
}
</style>

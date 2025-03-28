<template>
    <n-config-provider>
        <div class="container">
            <div>
                <date-picker
                    v-model="range"
                    :minDate="minDate"
                    :maxDate="maxDate"
                />
            </div>

            <div class="chart">
                <chart
                    :data="filteredData"
                    :dateRange="range"
                    label="₽"
                />
            </div>

        </div>
        <n-data-table
            :columns="columns"
            :data="filteredData"
            :pagination="pagination"
            :bordered="false"
            :loading="false"
            @update:sorter="handleSorterChange"
            @update:filters="handleFilterChange"
            min-height="300"
        />
    </n-config-provider>
</template>

<script setup>
import { onMounted, computed, ref } from "vue";
import chart from './chart.vue';
import datePicker from './date-picker.vue';
import { toISODateString } from '/assets/utils/date.js';

const minDate = ref(Date.now());
const maxDate = ref(Date.now());
const range = ref([minDate.value, maxDate.value])

const pagination = ref(false);
const categoryFilter = ref(null);
const data = ref([]);
const filteredData = computed(() => {
    if (!data.value) {
        return [[]];
    }
    if (!range.value) {
        return data.value;
    }
    return data.value.filter((row) => {
        if (row.date === undefined) {
            return false
        }
        return range.value[0] <= row.date && row.date <= range.value[1]
                && (!categoryFilter.value || !categoryFilter.value.length || categoryFilter.value.includes(row.category))
    });
});
const columns = ref([]);

async function fetchData() {
    const res = await fetch(`/api/sales`);
    const now = new Date();
    let rawData = await res.json();
    rawData.forEach((row) => {
        row.date = toLocalTimestamp(row.date);
    });
    data.value = rawData;
    minDate.value = data.value.reduce((a, b) => b.date < a ? b.date : a, data.value[0].date);
    maxDate.value = data.value.reduce((a, b) => b.date > a ? b.date : a, data.value[0].date);
    range.value = [minDate.value, maxDate.value];
    columns.value = createColumns(data.value[0]);
};

function dateRender(row) {
    return toISODateString(row.date);
};

function toLocalTimestamp(datestring) {
    return new Date(datestring).getTime() + (new Date()).getTimezoneOffset() * 60000;
}

function createColumns(headerRow) {
    return Object.keys(headerRow).map((key) => {
        return {
            title: ucFirst(key),
            key: key,
            sorter: 'default',
            sortOrder: key === 'date' ? 'ascend' : false,
            render: key === 'date' ? dateRender : undefined,
            filter: key === 'category' ? ((value, row) => row.category === value) : undefined,
            filterOptions: key === 'category' ? getCategories() : undefined,
        }
    })
}

function ucFirst(s) {
    return s.charAt(0).toUpperCase() + s.slice(1)
}

function getCategories() {
    let categories = Array.from(new Set(data.value.map((row) => row.category)));
    return categories.map((category) => (
        {
            label: category,
            value: category,
        }
    )).sort((a,b) => a.label.localeCompare(b.label));
}

function handleSorterChange(sorter) {
    columns.value.forEach((column) => {
        if (column.sortOrder === void 0)
            return;
        if (!sorter) {
            column.sortOrder = false;
            return;
        }
        if (column.key === sorter.columnKey) {
            column.sortOrder = sorter.order;
        } else {
            column.sortOrder = false;
        }
    });
}
function handleFilterChange(filters, sourceColumn) {
    categoryFilter.value = filters['category'];
}

onMounted(() => {
    fetchData()
})
</script>

<style scoped>
.n-date-picker {
    width: 300px;
}
.container {
    display: flex;
}
.container .chart {
    flex-grow: 1;
}
</style>

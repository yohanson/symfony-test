<template>
    <n-config-provider>
        <div class="container">
            <div>
                <n-date-picker
                    v-model:value="range"
                    type="daterange"
                    clearable
                    :close-on-select="true"
                    :first-day-of-week="0"
                    :is-date-disabled="isDateDisabled"
                    @clear="onClearDateRange"
                />
            </div>

            <div class="chart">
                <chart
                    :data="filteredData"
                    column="price"
                />
            </div>

        </div>
        <n-data-table
            :columns="columns"
            :data="filteredData"
            :pagination="pagination"
            :bordered="false"
            :loading="loading"
            @update:sorter="handleSorterChange"
            allow-checking-not-loaded
            min-height="300"
        />
    </n-config-provider>
</template>

<script setup>
import { defineComponent, h, onMounted, computed, ref, nextTick } from "vue";
import chart from './chart.vue';
import { toISODateString } from '/assets/utils/date.js';

const minDate = ref(Date.now());
const maxDate = ref(Date.now());
const range = ref([minDate.value, maxDate.value])

const loading = ref(true);
const pagination = ref(false);
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
    loading.value = false;
};

function dateRender(row) {
    return toISODateString(row.date);
};

function toLocalTimestamp(datestring) {
    return new Date(datestring).getTime() + (new Date()).getTimezoneOffset() * 60000;
}

function isDateDisabled(date) {
    return date < minDate.value || date > maxDate.value;
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
        if (column.key === sorter.columnKey)
          column.sortOrder = sorter.order;
        else column.sortOrder = false;
    });
}

async function onClearDateRange() {
    await nextTick();
    range.value = [minDate.value, maxDate.value];
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

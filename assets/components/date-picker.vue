<script setup>
import { ref, nextTick } from "vue";

const range = defineModel();
const props = defineProps({
    minDate: {
        type: Number,
        required: true,
    },
    maxDate: {
        type: Number,
        required: true,
    },
});

function isDateDisabled(date) {
    return date < props.minDate || date > props.maxDate;
}

async function onClearDateRange() {
    await nextTick();
    range.value = [props.minDate, props.maxDate];
}

</script>

<template>
    <n-date-picker
        v-model:value="range"
        type="daterange"
        clearable
        :close-on-select="true"
        :first-day-of-week="0"
        :is-date-disabled="isDateDisabled"
        @clear="onClearDateRange"
    />
</template>

<style scoped>
.n-date-picker {
    width: 300px;
}
</style>

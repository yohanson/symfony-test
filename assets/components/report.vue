<script setup>
import { ref } from 'vue'
import Grid from './Grid.vue'

const sales = ref(null)
const headers = ref([])
const searchQuery = ref('')

async function fetchData() {
  sales.value = null
  const res = await fetch(`/api/sales`)
  sales.value = await res.json()
  headers.value = Object.keys(sales.value[0])
}
fetchData();
</script>

<template>
    <Grid :data="sales"
        :filter-key="searchQuery" />
</template>

<style lang="css">
    
</style>

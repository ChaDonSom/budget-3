<script lang="ts" setup>
import DataTableRow from "@/core/tables/DataTableRow.vue"
</script>

<template>
    <DataTableRow
        style="height: unset"
        class="text-xl italic border-t-2 text-slate-600 border-slate-400"
    >
        <slot />
    </DataTableRow>
</template>

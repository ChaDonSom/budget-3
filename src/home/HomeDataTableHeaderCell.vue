<script lang="ts" setup>
import DataTableHeaderCell from "@/core/tables/DataTableHeaderCell.vue"
import { columnsToShow, sort } from "."

withDefaults(
    defineProps<{
        columnId: keyof (typeof columnsToShow)["value"]
        sortable?: boolean
        numeric?: boolean
    }>(),
    {
        sortable: true,
        numeric: true,
    }
)
</script>

<template>
    <DataTableHeaderCell
        :sortable="sortable"
        :numeric="numeric"
        :column-id="columnId"
        :sort="sort[columnId]"
        :class="{ hidden: !columnsToShow[columnId] }"
    >
        <slot />
    </DataTableHeaderCell>
</template>

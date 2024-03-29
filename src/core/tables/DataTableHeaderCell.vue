<template>
    <th
        ref="mainRef"
        class="mdc-data-table__header-cell"
        :class="thClasses"
        @click="thClick"
        :key="thUpdateKey"
        v-bind="thBindableAttributes"
    >
        <template v-if="sortable">
            <div class="mdc-data-table__header-cell-wrapper">
                <button
                    v-if="numeric"
                    class="mdc-icon-button material-icons mdc-data-table__sort-icon-button"
                    aria-label="Sort by dessert"
                    :aria-describedby="`sort-status-label-${uid}`"
                >
                    arrow_drop_up
                </button>
                <div class="mdc-data-table__header-cell-label">
                    <slot></slot>
                </div>
                <button
                    v-if="!numeric"
                    class="mdc-icon-button material-icons mdc-data-table__sort-icon-button"
                    aria-label="Sort by dessert"
                    :aria-describedby="`sort-status-label-${uid}`"
                >
                    arrow_drop_up
                </button>
                <div
                    class="mdc-data-table__sort-status-label"
                    aria-hidden="true"
                    :id="`sort-status-label-${uid}`"
                ></div>
            </div>
        </template>
        <template v-else>
            <slot></slot>
        </template>
    </th>
</template>

<script lang="ts" setup>
import { computed, ref } from "vue";
import { thUpdateKey } from "@/core/tables/store";

const props = defineProps({
    numeric: {
        type: Boolean,
        required: false,
        default: () => false,
    },
    sortable: {
        type: Boolean,
        required: false,
        default: () => false,
    },
    sort: {
        type: Object,
        required: false,
    },
    columnId: {
        type: String,
        default: () => "dessert",
    },
});

const mainRef = ref<HTMLTableCellElement | null>(null);
const uid = ref(Math.round(Math.random() * 10000000));

function thClick() {
    setTimeout(() => thUpdateKey.value++);
}
const thClasses = computed(() => {
    const x = thUpdateKey.value;
    return {
        "mdc-data-table__header-cell--numeric": props.numeric,
        "mdc-data-table__header-cell--with-sort": props.sortable,
        "mdc-data-table__header-cell--sorted":
            props.sortable && props.sort?.value != "none",
        "mdc-data-table__header-cell--sorted-descending":
            props.sortable && props.sort?.value == "descending",
    };
});
const thBindableAttributes = computed(() => {
    const x = thUpdateKey.value;
    const result: { [key: string]: string } = {};

    if (props.sortable) {
        result["aria-sort"] = props.sort?.value ?? "none"; // 'ascending' or 'descending' based on sort order
        result["data-column-id"] = props.columnId;
    }

    return result;
});
</script>

<style scoped lang="scss">
/**
  We have to help out the DataTable component here by applying its styles. For some reason,
  DataTable's styles will only apply when not scoped (not even using :deep)
 */
@use "./index" as tables;

.mdc-data-table__header-cell .mdc-data-table__sort-icon-button {
    position: absolute;
    left: calc(50% - 14px);
    bottom: -16px;
}

.mdc-data-table__header-cell::after {
    content: "";
    display: block;
    position: absolute;
    width: calc(100% + 32px);
    height: 2px;
    bottom: 0;
    left: -16px;
    background-color: rgb(197, 197, 197);
}
</style>

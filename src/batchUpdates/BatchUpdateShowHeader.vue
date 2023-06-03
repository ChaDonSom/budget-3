<template>
    <h1 class="text-xl mt-5">
        {{ $route.params.id != "new" ? "Edit" : "New" }} changes
        {{
            toDateTime(batchForm.date) <= DateTime.now() ? "made" : "to be made"
        }}
        {{
            toDateTime(batchForm.date).toLocaleString(
                DateTime.DATE_MED_WITH_WEEKDAY
            )
        }}
    </h1>
</template>

<script lang="ts" setup>
import { toDateTime } from "@/core/utilities/datetime";
import { DateTime } from "luxon";

defineProps({
    batchForm: {
        type: Object,
        required: true,
    },
});
</script>

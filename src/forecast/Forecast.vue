<template>
    <main class="mx-auto max-w-2xl px-4 pb-20">
        <h1 class="pt-4 pb-2 text-2xl">Forecast</h1>
        <p class="mb-5 text-gray-600">
            Scheduled payments, grouped by pay week. Select next year to keep
            looking ahead.
        </p>

        <p v-if="!auth.authenticated" class="py-8 text-center">
            Log in to view your forecast.
        </p>
        <p v-else-if="loading" class="py-8 text-center">Loading forecast…</p>
        <p v-else-if="loadError" class="py-8 text-center text-red-600">
            Could not load your forecast.
        </p>
        <template v-else>
            <p v-if="!weeks.length" class="py-8 text-center">
                No scheduled payments in this period.
            </p>

            <section
                v-for="week in weeks"
                :key="week.start"
                class="forecast-card mb-5 rounded-lg border"
            >
                <h2 class="forecast-divider border-b px-4 py-3 font-medium">
                    Week of {{ week.label }}
                </h2>
                <ul>
                    <li
                        v-for="occurrence in week.occurrences"
                        :key="occurrence.id"
                        class="flex items-center gap-3 px-4 py-3"
                    >
                        <time class="forecast-muted w-20 shrink-0">
                            {{ formatDate(occurrence.date) }}
                        </time>
                        <span class="min-w-0 flex-1">{{ occurrence.name }}</span>
                        <span class="shrink-0 text-right">
                            {{ dollars(occurrence.amount) }}
                        </span>
                    </li>
                </ul>
                <p
                    class="forecast-divider flex justify-between border-t px-4 py-3 font-medium"
                >
                    <span>Week total</span>
                    <span>{{ dollars(week.total) }}</span>
                </p>
            </section>

            <button
                v-if="!loading && !loadError"
                class="forecast-button w-full rounded-lg border px-4 py-3 font-medium"
                type="button"
                @click="extendForecast"
            >
                Show next year
            </button>
        </template>
    </main>
</template>

<script setup lang="ts">
import { dollars } from "@/core/utilities/currency"
import { useAuth } from "@/core/users/auth"
import { useAccountsStore } from "@/store/accounts"
import { DateTime } from "luxon"
import { computed, onMounted, ref } from "vue"
import { forecastOccurrences, groupForecastByWeek } from "."

const auth = useAuth()
const accounts = useAccountsStore()
const loading = ref(true)
const loadError = ref(false)
const start = DateTime.now().startOf("day")
const end = ref(start.plus({ years: 1 }))

const weeks = computed(() =>
    groupForecastByWeek(
        forecastOccurrences(accounts.values, start, end.value)
    )
)

function formatDate(date: string) {
    return DateTime.fromISO(date).toLocaleString(DateTime.DATE_MED)
}

function extendForecast() {
    end.value = end.value.plus({ years: 1 })
}

onMounted(async () => {
    if (!auth.authenticated) {
        loading.value = false
        return
    }

    try {
        await accounts.fetchData()
    } catch (error) {
        loadError.value = true
        throw error
    } finally {
        loading.value = false
    }
})
</script>

<style scoped>
.forecast-card {
    background-color: var(--color-background-soft);
    border-color: var(--color-border);
    color: var(--color-heading);
}

.forecast-divider {
    border-color: var(--color-border);
}

.forecast-muted {
    color: var(--color-text);
}

.forecast-button {
    border-color: var(--color-border);
    color: var(--color-heading);
}

.forecast-button:hover {
    background-color: var(--color-background-mute);
}
</style>

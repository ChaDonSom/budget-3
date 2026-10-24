<template>
    <main class="mx-auto max-w-2xl px-4 pb-20">
        <h1 class="pt-4 pb-2 text-2xl">Forecast</h1>
        <p class="mb-5 text-gray-600">
            Scheduled payments, grouped by pay week. Use the calendar for a
            color-coded view of upcoming totals.
        </p>

        <p v-if="!auth.authenticated" class="py-8 text-center">
            Log in to view your forecast.
        </p>
        <p v-else-if="loading" class="py-8 text-center">Loading forecast…</p>
        <p v-else-if="loadError" class="py-8 text-center text-red-600">
            Could not load your forecast.
        </p>
        <template v-else>
            <section class="forecast-income mb-5 rounded-lg border p-4">
                <h2 class="mb-1 text-lg font-medium">Expected weekly income</h2>
                <p class="mb-3 text-sm text-gray-600">
                    Forecast-only estimate. It does not change your accounts or
                    home page. Saved in this browser for your account.
                </p>
                <div class="flex flex-wrap gap-3">
                    <label class="forecast-income-field">
                        <span>Amount each week</span>
                        <span class="flex items-center gap-2">
                            <span aria-hidden="true">$</span>
                            <input
                                v-model="weeklyIncomeInput"
                                aria-label="Expected income amount each week"
                                class="forecast-income-input"
                                type="number"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                placeholder="0.00"
                            />
                        </span>
                    </label>
                    <label class="forecast-income-field">
                        <span>Pay day</span>
                        <select
                            v-model.number="incomeWeekday"
                            aria-label="Expected weekly income pay day"
                            class="forecast-income-input"
                        >
                            <option
                                v-for="day in incomeWeekdays"
                                :key="day.value"
                                :value="day.value"
                            >
                                {{ day.label }}
                            </option>
                        </select>
                    </label>
                </div>
                <p
                    v-if="hasIncomeInput && !validIncomeInput"
                    class="mt-2 text-sm text-red-600"
                    role="alert"
                >
                    Enter a valid income amount of zero or more.
                </p>
                <p
                    v-if="incomeSettingsError"
                    class="mt-2 text-sm text-red-600"
                    role="alert"
                >
                    {{ incomeSettingsError }}
                </p>
            </section>

            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-2" role="group" aria-label="Forecast view">
                    <button
                        class="forecast-button rounded-lg border px-3 py-2"
                        :aria-pressed="view === 'list'"
                        type="button"
                        @click="view = 'list'"
                    >
                        List
                    </button>
                    <button
                        class="forecast-button rounded-lg border px-3 py-2"
                        :aria-pressed="view === 'calendar'"
                        type="button"
                        @click="view = 'calendar'"
                    >
                        Calendar
                    </button>
                </div>
                <div
                    v-if="view === 'calendar'"
                    class="flex gap-2"
                    role="group"
                    aria-label="Calendar range"
                >
                    <button
                        v-for="months in [1, 2, 3]"
                        :key="months"
                        class="forecast-button rounded-lg border px-3 py-2"
                        :aria-pressed="monthCount === months"
                        type="button"
                        @click="setMonthCount(months)"
                    >
                        {{ months }} mo
                    </button>
                </div>
            </div>

            <p
                v-if="view === 'calendar' && !calendarWeeks.length"
                class="py-4 text-center"
            >
                No forecast activity in this period.
            </p>

            <section
                v-if="view === 'calendar'"
                class="mb-6"
                aria-label="Forecast calendar"
            >
                <p class="mb-3 text-sm text-gray-600">
                    Green weeks add money. Red weeks spend money. Stronger color
                    means a larger weekly total. Tap a day for details.
                </p>
                <div
                    v-for="month in calendarMonths"
                    :key="month.start"
                    class="forecast-month mb-6 rounded-lg border p-3"
                >
                    <h2 class="mb-3 text-center text-lg font-medium">
                        {{ month.label }}
                    </h2>
                    <div class="forecast-calendar-grid forecast-weekdays">
                        <span v-for="day in weekdays" :key="day">{{
                            day
                        }}</span>
                    </div>
                    <div class="forecast-calendar-grid">
                        <template
                            v-for="(day, index) in month.days"
                            :key="day?.date ?? `${month.start}-empty-${index}`"
                        >
                            <div
                                v-if="!day"
                                class="forecast-empty-day"
                                aria-hidden="true"
                            ></div>
                            <div
                                v-else
                                class="forecast-day rounded-md border"
                                :class="{
                                    'forecast-day-active':
                                        hoveredDate === day.date,
                                }"
                                :style="dayStyle(day)"
                                @mouseenter="hoveredDate = day.date"
                                @mouseleave="hoveredDate = null"
                            >
                                <button
                                    class="forecast-day-button"
                                    type="button"
                                    :disabled="!weekForDay(day)"
                                    :aria-label="dayLabel(day)"
                                    @focus="hoveredDate = day.date"
                                    @blur="hoveredDate = null"
                                    @click="selectDay(day)"
                                >
                                    <span>{{ day.number }}</span>
                                    <span
                                        v-if="weekForDay(day)"
                                        class="forecast-day-total"
                                    >
                                        {{
                                            compactDollars(
                                                weekForDay(day)!.total
                                            )
                                        }}
                                    </span>
                                </button>
                                <div
                                    v-if="
                                        hoveredDate === day.date &&
                                        weekForDay(day)
                                    "
                                    class="forecast-tooltip"
                                    :class="
                                        day.column > 3 ? 'right-0' : 'left-0'
                                    "
                                >
                                    <p class="font-medium">
                                        Week of
                                        {{
                                            formatDate(weekForDay(day)!.start)
                                        }}:
                                        {{ dollars(weekForDay(day)!.total) }}
                                    </p>
                                    <ul class="mt-1">
                                        <li
                                            v-for="occurrence in weekForDay(day)!
                                                .occurrences"
                                            :key="occurrence.id"
                                            class="flex justify-between gap-3"
                                        >
                                            <span>
                                                {{
                                                    formatDate(occurrence.date)
                                                }}
                                                {{ occurrence.name }}
                                            </span>
                                            <span class="shrink-0">
                                                {{ dollars(occurrence.amount) }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <section
                    v-if="selectedWeek"
                    class="forecast-card rounded-lg border p-4"
                    aria-live="polite"
                >
                    <h2 class="mb-2 text-lg font-medium">
                        Week of {{ formatDate(selectedWeek.start) }}:
                        {{ dollars(selectedWeek.total) }}
                    </h2>
                    <ul>
                        <li
                            v-for="occurrence in selectedWeek.occurrences"
                            :key="occurrence.id"
                            class="flex justify-between gap-3 py-1"
                        >
                            <span>
                                {{ formatDate(occurrence.date) }}
                                {{ occurrence.name }}
                            </span>
                            <span class="shrink-0">
                                {{ dollars(occurrence.amount) }}
                            </span>
                        </li>
                    </ul>
                </section>
            </section>

            <template v-if="view === 'list'">
                <p v-if="!weeks.length" class="py-8 text-center">
                    No forecast activity in this period.
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
                            <span class="min-w-0 flex-1">{{
                                occurrence.name
                            }}</span>
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
                    class="forecast-button w-full rounded-lg border px-4 py-3 font-medium"
                    type="button"
                    @click="extendForecast"
                >
                    Show next year
                </button>
            </template>
        </template>
    </main>
</template>

<script setup lang="ts">
import { dollars } from "@/core/utilities/currency"
import { useAuth } from "@/core/users/auth"
import { useAccountsStore } from "@/store/accounts"
import { DateTime } from "luxon"
import { computed, onMounted, ref, watch } from "vue"
import {
    forecastOccurrences,
    forecastWeekStart,
    groupForecastByWeek,
    weeklyIncomeOccurrences,
} from "."
import type { ForecastWeekday } from "."

const auth = useAuth()
const accounts = useAccountsStore()
const loading = ref(true)
const loadError = ref(false)
const view = ref<"list" | "calendar">("list")
const monthCount = ref(3)
const hoveredDate = ref<string | null>(null)
const selectedWeekStart = ref<string | null>(null)
const weeklyIncomeInput = ref<number | string>("")
const incomeWeekday = ref<ForecastWeekday>(3)
const incomeSettingsReady = ref(false)
const incomeSettingsError = ref("")
const start = DateTime.now().startOf("day")
const end = ref(start.plus({ years: 1 }))
const weekdays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]
const incomeWeekdays: { label: string; value: ForecastWeekday }[] = [
    { label: "Sunday", value: 7 },
    { label: "Monday", value: 1 },
    { label: "Tuesday", value: 2 },
    { label: "Wednesday", value: 3 },
    { label: "Thursday", value: 4 },
    { label: "Friday", value: 5 },
    { label: "Saturday", value: 6 },
]
const incomeSettingsKey = computed(
    () => `forecast-weekly-income-${auth.user?.id ?? "guest"}`
)

const hasIncomeInput = computed(
    () => String(weeklyIncomeInput.value).trim() !== ""
)

const validIncomeInput = computed(() => {
    if (!hasIncomeInput.value) return true
    const amount = Number(weeklyIncomeInput.value)
    return Number.isFinite(amount) && amount >= 0
})

const weeklyIncomeAmount = computed(() => {
    if (!validIncomeInput.value || !hasIncomeInput.value) return 0
    return Number(weeklyIncomeInput.value)
})

const occurrences = computed(() => [
    ...forecastOccurrences(accounts.values, start, end.value),
    ...weeklyIncomeOccurrences(
        weeklyIncomeAmount.value,
        incomeWeekday.value,
        start,
        end.value
    ),
])

const weeks = computed(() => groupForecastByWeek(occurrences.value))

const weeksByStart = computed(
    () => new Map(weeks.value.map((week) => [week.start, week]))
)

const calendarMonths = computed(() => {
    const firstMonth = start.startOf("month")

    return Array.from({ length: monthCount.value }, (_, index) => {
        const monthStart = firstMonth.plus({ months: index })
        const firstDayOffset = monthStart.weekday % 7
        const dayCount = monthStart.daysInMonth
        const cellCount = Math.ceil((firstDayOffset + dayCount) / 7) * 7
        const days = Array.from({ length: cellCount }, (_, cellIndex) => {
            const dayNumber = cellIndex - firstDayOffset + 1
            if (dayNumber < 1 || dayNumber > dayCount) return null

            const date = monthStart.plus({ days: dayNumber - 1 })
            return {
                date: toISODate(date),
                number: date.day,
                weekStart: forecastWeekStart(toISODate(date)),
                column: cellIndex % 7,
            }
        })

        return {
            start: toISODate(monthStart),
            label: monthStart.toLocaleString({
                month: "long",
                year: "numeric",
            }),
            days,
        }
    })
})

const calendarWeeks = computed(() => {
    const starts = new Set(
        calendarMonths.value.flatMap((month) =>
            month.days.flatMap((day) => (day ? [day.weekStart] : []))
        )
    )
    return [...starts].flatMap((weekStart) => {
        const week = weeksByStart.value.get(weekStart)
        return week ? [week] : []
    })
})

const maxWeekMagnitude = computed(() =>
    Math.max(0, ...calendarWeeks.value.map((week) => Math.abs(week.total)))
)

const selectedWeek = computed(() =>
    calendarWeeks.value.find((week) => week.start === selectedWeekStart.value)
)

function weekForDay(day: { weekStart: string }) {
    return weeksByStart.value.get(day.weekStart)
}

function dayStyle(day: { weekStart: string }) {
    const week = weekForDay(day)
    if (!week) return {}

    const magnitude = maxWeekMagnitude.value
        ? Math.abs(week.total) / maxWeekMagnitude.value
        : 0
    const color =
        week.total > 0
            ? "22, 163, 74"
            : week.total < 0
            ? "220, 38, 38"
            : "148, 163, 184"

    return {
        backgroundColor: `rgba(${color}, ${
            week.total ? 0.12 + magnitude * 0.32 : 0.16
        })`,
    }
}

function dayLabel(day: { date: string; weekStart: string }) {
    const week = weekForDay(day)
    return week
        ? `${formatDate(day.date)}. Week of ${formatDate(
              week.start
          )}: ${dollars(week.total)}. Select for payment details.`
        : formatDate(day.date)
}

function compactDollars(amount: number) {
    return new Intl.NumberFormat(undefined, {
        style: "currency",
        currency: "USD",
        notation: "compact",
        maximumFractionDigits: 1,
    }).format(amount)
}

function selectDay(day: { weekStart: string }) {
    selectedWeekStart.value = day.weekStart
}

function setMonthCount(months: number) {
    monthCount.value = months
    selectedWeekStart.value = null
}

function formatDate(date: string) {
    return DateTime.fromISO(date).toLocaleString(DateTime.DATE_MED)
}

function toISODate(date: DateTime) {
    const value = date.toISODate()
    if (!value) throw new Error("Could not format forecast calendar date.")
    return value
}

function extendForecast() {
    end.value = end.value.plus({ years: 1 })
}

function isForecastWeekday(value: unknown): value is ForecastWeekday {
    return (
        typeof value === "number" &&
        Number.isInteger(value) &&
        value >= 1 &&
        value <= 7
    )
}

function isSavedIncomeSettings(
    value: unknown
): value is { amount: string; weekday: ForecastWeekday } {
    if (
        typeof value !== "object" ||
        value === null ||
        !("amount" in value) ||
        !("weekday" in value)
    ) {
        return false
    }

    const amount: unknown = Reflect.get(value, "amount")
    const weekday: unknown = Reflect.get(value, "weekday")
    return (
        typeof amount === "string" &&
        (amount.trim() === "" ||
            (Number.isFinite(Number(amount)) && Number(amount) >= 0)) &&
        isForecastWeekday(weekday)
    )
}

function loadIncomeSettings() {
    try {
        const saved = localStorage.getItem(incomeSettingsKey.value)
        if (saved) {
            const settings: unknown = JSON.parse(saved)
            if (!isSavedIncomeSettings(settings)) {
                throw new Error("Saved weekly income settings are invalid.")
            }

            weeklyIncomeInput.value = settings.amount.trim()
                ? Number(settings.amount)
                : ""
            incomeWeekday.value = settings.weekday
        }
    } catch (error) {
        incomeSettingsError.value =
            "Could not load saved income settings from this browser."
        console.error("Could not load forecast income settings.", error)
    } finally {
        incomeSettingsReady.value = true
    }
}

watch([weeklyIncomeInput, incomeWeekday], () => {
    if (!incomeSettingsReady.value) return
    if (!validIncomeInput.value) return

    try {
        localStorage.setItem(
            incomeSettingsKey.value,
            JSON.stringify({
                amount: String(weeklyIncomeInput.value),
                weekday: incomeWeekday.value,
            })
        )
        incomeSettingsError.value = ""
    } catch (error) {
        incomeSettingsError.value =
            "Could not save income settings in this browser."
        console.error("Could not save forecast income settings.", error)
    }
})

onMounted(async () => {
    if (!auth.authenticated) {
        loading.value = false
        return
    }

    loadIncomeSettings()

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

.forecast-button[aria-pressed="true"] {
    background-color: var(--color-background-mute);
    border-color: var(--color-border-hover);
}

.forecast-income {
    background-color: var(--color-background-soft);
    border-color: var(--color-border);
    color: var(--color-heading);
}

.forecast-income-field {
    display: flex;
    min-width: 9rem;
    flex: 1;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.875rem;
}

.forecast-income-input {
    min-height: 2.5rem;
    width: 100%;
    border: 1px solid var(--color-border);
    border-radius: 0.375rem;
    background-color: var(--color-background);
    color: var(--color-heading);
    padding: 0.375rem 0.5rem;
}

.forecast-month {
    background-color: var(--color-background-soft);
    border-color: var(--color-border);
    color: var(--color-heading);
}

.forecast-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.25rem;
}

.forecast-weekdays {
    margin-bottom: 0.25rem;
    color: var(--color-text);
    font-size: 0.75rem;
    text-align: center;
}

.forecast-day {
    min-width: 0;
    border-color: var(--color-border);
    color: var(--color-heading);
}

.forecast-day-active {
    z-index: 10;
    border-color: var(--color-border-hover);
}

.forecast-day-button {
    display: flex;
    width: 100%;
    min-height: 3.25rem;
    flex-direction: column;
    align-items: flex-start;
    justify-content: space-between;
    overflow: hidden;
    padding: 0.25rem;
    text-align: left;
}

.forecast-day-button:disabled {
    cursor: default;
}

.forecast-day-total {
    max-width: 100%;
    overflow: hidden;
    font-size: 0.625rem;
    line-height: 1rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.forecast-tooltip {
    position: absolute;
    top: calc(100% + 0.25rem);
    z-index: 20;
    width: 16rem;
    max-width: calc(100vw - 2rem);
    border: 1px solid var(--color-border);
    border-radius: 0.5rem;
    background-color: var(--color-background);
    color: var(--color-heading);
    padding: 0.75rem;
    box-shadow: 0 4px 12px rgb(0 0 0 / 15%);
    font-size: 0.75rem;
}

@media (max-width: 640px) {
    .forecast-tooltip {
        display: none;
    }
}
</style>

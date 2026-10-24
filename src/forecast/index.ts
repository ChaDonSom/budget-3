import { toDateTime } from "@/core/utilities/datetime"
import type { Account } from "@/store/accounts"
import { DateTime } from "luxon"

export type ForecastOccurrence = {
    id: string
    date: string
    name: string
    amount: number
}

export type ForecastWeek = {
    start: string
    label: string
    occurrences: ForecastOccurrence[]
    total: number
}

export type ForecastWeekday = 1 | 2 | 3 | 4 | 5 | 6 | 7

export function forecastOccurrences(
    accounts: Account[],
    from: DateTime,
    through: DateTime
): ForecastOccurrence[] {
    const occurrences: ForecastOccurrence[] = []

    for (const account of accounts) {
        for (const update of account.batch_updates ?? []) {
            let date = toDateTime(update.date)
            let occurrence = 0

            while (date <= through) {
                if (date >= from) {
                    occurrences.push({
                        id: `${update.id}-${account.id}-${occurrence}`,
                        date: date.toISODate()!,
                        name: account.name,
                        amount: update.pivot.amount / 100,
                    })
                }

                const weeks = update.weeks
                if (!weeks) break

                date = date.plus(weeks >= 4 ? { months: weeks / 4 } : { weeks })
                occurrence++
            }
        }
    }

    return occurrences.sort(
        (left, right) =>
            left.date.localeCompare(right.date) ||
            left.name.localeCompare(right.name)
    )
}

export function weeklyIncomeOccurrences(
    amount: number,
    weekday: ForecastWeekday,
    from: DateTime,
    through: DateTime
): ForecastOccurrence[] {
    if (!Number.isFinite(amount) || amount < 0) {
        throw new RangeError("Weekly forecast income must be a valid amount.")
    }
    if (amount === 0) return []

    const daysUntilPayday = (weekday - from.weekday + 7) % 7
    let date = from.startOf("day").plus({ days: daysUntilPayday })
    const occurrences: ForecastOccurrence[] = []

    while (date <= through) {
        const isoDate = date.toISODate()
        if (!isoDate) throw new Error("Could not format forecast income date.")

        occurrences.push({
            id: `expected-income-${isoDate}`,
            date: isoDate,
            name: "Expected weekly income",
            amount,
        })
        date = date.plus({ weeks: 1 })
    }

    return occurrences
}

export function groupForecastByWeek(
    occurrences: ForecastOccurrence[]
): ForecastWeek[] {
    const weeks = new Map<string, ForecastWeek>()

    for (const occurrence of occurrences) {
        const start = forecastWeekStart(occurrence.date)
        let week = weeks.get(start)

        if (!week) {
            const date = toDateTime(start)
            week = {
                start,
                label: date.toLocaleString(DateTime.DATE_MED),
                occurrences: [],
                total: 0,
            }
            weeks.set(start, week)
        }

        week.occurrences.push(occurrence)
        week.total += occurrence.amount
    }

    return [...weeks.values()].sort((left, right) =>
        left.start.localeCompare(right.start)
    )
}

export function forecastWeekStart(date: string): string {
    const parsedDate = toDateTime(date)
    const daysSinceThursday = (parsedDate.weekday - 4 + 7) % 7
    const start = parsedDate.minus({ days: daysSinceThursday }).toISODate()
    if (!start) throw new Error("Could not format forecast week start.")
    return start
}

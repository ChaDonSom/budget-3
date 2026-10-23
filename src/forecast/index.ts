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

                date = date.plus(
                    weeks >= 4 ? { months: weeks / 4 } : { weeks }
                )
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

export function groupForecastByWeek(
    occurrences: ForecastOccurrence[]
): ForecastWeek[] {
    const weeks = new Map<string, ForecastWeek>()

    for (const occurrence of occurrences) {
        const date = toDateTime(occurrence.date)
        const daysSinceThursday = (date.weekday - 4 + 7) % 7
        const start = date.minus({ days: daysSinceThursday }).toISODate()!
        let week = weeks.get(start)

        if (!week) {
            week = {
                start,
                label: date
                    .minus({ days: daysSinceThursday })
                    .toLocaleString(DateTime.DATE_MED),
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

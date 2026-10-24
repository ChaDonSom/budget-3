import { DateTime } from "luxon"
import type { Account } from "@/store/accounts"
import { describe, expect, it } from "vitest"
import {
    forecastOccurrences,
    forecastWeekStart,
    groupForecastByWeek,
    weeklyIncomeOccurrences,
} from "./index"

function scheduledAccount(
    date: string,
    weeks: number,
    amount: number
): Account {
    return {
        id: 1,
        name: "Rent",
        amount: 0,
        user_id: 1,
        note: "",
        batch_updates: [
            {
                id: 7,
                user_id: 1,
                batch: 1,
                date,
                done_at: null,
                notify_me: false,
                weeks,
                note: "",
                pivot: { amount },
            },
        ],
    }
}

describe("forecastOccurrences", () => {
    it("repeats scheduled payments through the requested date", () => {
        const forecast = forecastOccurrences(
            [scheduledAccount("2026-01-01", 1, -132200)],
            DateTime.fromISO("2026-01-01"),
            DateTime.fromISO("2026-01-15")
        )

        expect(forecast.map(({ date }) => date)).toEqual([
            "2026-01-01",
            "2026-01-08",
            "2026-01-15",
        ])
        expect(forecast.map(({ amount }) => amount)).toEqual([
            -1322, -1322, -1322,
        ])
    })

    it("repeats four-week schedules monthly", () => {
        const forecast = forecastOccurrences(
            [scheduledAccount("2026-01-31", 4, -132200)],
            DateTime.fromISO("2026-01-01"),
            DateTime.fromISO("2026-04-01")
        )

        expect(forecast.map(({ date }) => date)).toEqual([
            "2026-01-31",
            "2026-02-28",
            "2026-03-28",
        ])
    })

    it("groups transactions by Thursday pay weeks and sums their amounts", () => {
        const weeks = groupForecastByWeek([
            { id: "a", date: "2026-01-01", name: "Water", amount: -20 },
            { id: "b", date: "2026-01-06", name: "Rent", amount: -100 },
            { id: "c", date: "2026-01-08", name: "Power", amount: -30 },
        ])

        expect(weeks).toHaveLength(2)
        expect(weeks[0].start).toBe("2026-01-01")
        expect(weeks[0].total).toBe(-120)
        expect(weeks[0].occurrences.map(({ name }) => name)).toEqual([
            "Water",
            "Rent",
        ])
        expect(weeks[1].start).toBe("2026-01-08")
        expect(weeks[1].total).toBe(-30)
    })
})

describe("forecastWeekStart", () => {
    it("maps each day to its Thursday pay-week start", () => {
        expect(forecastWeekStart("2026-01-01")).toBe("2026-01-01")
        expect(forecastWeekStart("2026-01-04")).toBe("2026-01-01")
        expect(forecastWeekStart("2026-01-07")).toBe("2026-01-01")
        expect(forecastWeekStart("2026-01-08")).toBe("2026-01-08")
    })
})

describe("weeklyIncomeOccurrences", () => {
    it("adds the first selected weekday on or after the start, then repeats weekly", () => {
        const occurrences = weeklyIncomeOccurrences(
            1322,
            3,
            DateTime.fromISO("2026-01-01"),
            DateTime.fromISO("2026-01-21")
        )

        expect(occurrences.map(({ date }) => date)).toEqual([
            "2026-01-07",
            "2026-01-14",
            "2026-01-21",
        ])
        expect(occurrences.every(({ amount }) => amount === 1322)).toBe(true)
        expect(
            occurrences.every(({ name }) => name === "Expected weekly income")
        ).toBe(true)
    })

    it("includes income in the same weekly total as scheduled payments", () => {
        const income = weeklyIncomeOccurrences(
            1322,
            3,
            DateTime.fromISO("2026-01-01"),
            DateTime.fromISO("2026-01-07")
        )
        const weeks = groupForecastByWeek([
            ...income,
            { id: "rent", date: "2026-01-05", name: "Rent", amount: -1200 },
        ])

        expect(weeks).toHaveLength(1)
        expect(weeks[0].total).toBe(122)
    })

    it("does not add occurrences when income amount is zero", () => {
        expect(
            weeklyIncomeOccurrences(
                0,
                3,
                DateTime.fromISO("2026-01-01"),
                DateTime.fromISO("2026-01-21")
            )
        ).toEqual([])
    })
})

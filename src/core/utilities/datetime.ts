import { DateTime } from "luxon";

export function toDateTime(date: string) {
    return DateTime.fromFormat(date, "yyyy-MM-dd");
}
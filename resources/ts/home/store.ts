import { useLocalStorage } from "@vueuse/core";

export const columnsToShow = useLocalStorage("budget-home-table-columns-to-show", {
    name: true,
    nextDate: false,
    nextAmount: false,
    minimum: false,
    overMinimum: false,
    percentCovered: false,
    amount: true,
});

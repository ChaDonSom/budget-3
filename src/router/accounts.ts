import AccountSavingPlanningVue from "@/accounts/AccountSavingPlanning.vue";
import type { RouteRecordRaw } from "vue-router";

const routes: RouteRecordRaw[] = [
    {
        name: "saving-planning",
        path: "/accounts/:accountId/saving-planning",
        component: AccountSavingPlanningVue,
    },
];

export default routes;

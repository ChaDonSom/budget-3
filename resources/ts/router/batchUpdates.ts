import BatchUpdateVue from "@/ts/batchUpdates/BatchUpdate.vue";
import HistoryVue from "@/ts/batchUpdates/History.vue";
import { RouteRecordRaw } from "vue-router";

const routes: RouteRecordRaw[] = [
    { name: "history", path: "/history", component: HistoryVue, props: true },
    { name: "batch-updates-show", path: "/batch-updates/:id", component: BatchUpdateVue, props: true },
];

export default routes;

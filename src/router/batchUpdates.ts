import BatchUpdateVue from "@/batchUpdates/BatchUpdate.vue";
import HistoryVue from "@/batchUpdates/History.vue";
import { RouteRecordRaw } from "vue-router";

const routes: RouteRecordRaw[] = [
    { name: "history", path: "/history", component: HistoryVue, props: true },
    { name: "batch-updates-show", path: "/batch-updates/:id", component: BatchUpdateVue, props: true },
];

export default routes;

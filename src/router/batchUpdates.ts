import BatchUpdateVue from "@/batchUpdates/BatchUpdate.vue";
import BatchUpdateDetailVue from "@/batchUpdates/BatchUpdateDetail.vue";
import HistoryVue from "@/batchUpdates/History.vue";
import type { RouteRecordRaw } from "vue-router";

const routes: RouteRecordRaw[] = [
    { name: "history", path: "/history", component: HistoryVue, props: true },
    { name: "batch-updates-show", path: "/batch-updates/:id", component: BatchUpdateVue, props: true },
    { name: "batch-updates-detail", path: "/batch-updates/:id/detail", component: BatchUpdateDetailVue, props: true }
];

export default routes;

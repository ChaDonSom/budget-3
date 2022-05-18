import { RouteRecordRaw } from "vue-router";
import Templates from "@/ts/templates/Templates.vue";
import Template from "@/ts/templates/Template.vue";

const routes: RouteRecordRaw[] = [
    { name: "templates", path: "/templates", component: Templates, props: true },
    { name: "templates-show", path: "/templates/:id", component: Template, props: true },
];

export default routes;

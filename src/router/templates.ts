import { RouteRecordRaw } from "vue-router";
import Templates from "@/templates/Templates.vue";
import Template from "@/templates/Template.vue";

const routes: RouteRecordRaw[] = [
    { name: "templates", path: "/templates", component: Templates, props: true },
    { name: "templates-show", path: "/templates/:id", component: Template, props: true },
];

export default routes;

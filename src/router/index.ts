import { createRouter, createWebHashHistory } from "vue-router";
import type { RouteRecordRaw } from "vue-router";
import Home from '@/home/Home.vue'
import authRoutes from '@/router/auth'
import templatesRoutes from '@/router/templates'
import batchUpdatesRoutes from '@/router/batchUpdates'

let routes: RouteRecordRaw[] = [
    {
        name: "index",
        path: "/",
        component: Home,
        props: true,
    },
];
routes = routes.concat(authRoutes).concat(templatesRoutes).concat(batchUpdatesRoutes)

export const router = createRouter({
    history: createWebHashHistory(),
    routes,
});

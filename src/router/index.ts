import { createRouter, createWebHashHistory } from "vue-router"
import type { RouteRecordRaw } from "vue-router"
import Home from "@/home/Home.vue"
import authRoutes from "@/router/auth"
import templatesRoutes from "@/router/templates"
import batchUpdatesRoutes from "@/router/batchUpdates"
import accountsRoutes from "@/router/accounts"
import { useModals } from "@/store/modals"

let routes: RouteRecordRaw[] = [
    {
        name: "index",
        path: "/",
        component: Home,
        props: true,
    },
]
routes = routes
    .concat(authRoutes)
    .concat(templatesRoutes)
    .concat(batchUpdatesRoutes)
    .concat(accountsRoutes)

export const router = createRouter({
    history: createWebHashHistory(),
    routes,
})

router.beforeEach((to: any, from: any) => {
    const modals = useModals()
    if (modals.keys.length > 0) {
        modals.close(modals.keys.at(-1) ?? 0)
        return false
    }
})

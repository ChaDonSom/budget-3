import { createRouter, createWebHashHistory, RouteRecordRaw } from "vue-router"
import Home from '@/ts/home/Home.vue'
import authRoutes from '@/ts/router/auth'
import templatesRoutes from '@/ts/router/templates'
import batchUpdatesRoutes from '@/ts/router/batchUpdates'

let routes: RouteRecordRaw[] = [
    {
        name: 'index', path: '/', component: Home, props: true
    }
]
routes = routes.concat(authRoutes).concat(templatesRoutes).concat(batchUpdatesRoutes)



export const router = createRouter({
    history: createWebHashHistory(),
    routes,
})

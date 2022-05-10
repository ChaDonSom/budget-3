import { createRouter, createWebHashHistory, RouteRecordRaw } from "vue-router"
import Home from '@/ts/home/Home.vue'
import authRoutes from '@/ts/router/auth'

let routes: RouteRecordRaw[] = [
    {
        name: 'index', path: '/', component: Home
    }
]
routes = routes.concat(authRoutes)



export const router = createRouter({
    history: createWebHashHistory(),
    routes,
})

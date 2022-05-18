import { createRouter, createWebHashHistory, RouteRecordRaw } from "vue-router"
import Home from '@/ts/home/Home.vue'
import authRoutes from '@/ts/router/auth'
import templatesRoutes from '@/ts/router/templates'

let routes: RouteRecordRaw[] = [
    {
        name: 'index', path: '/', component: Home, props: true
    }
]
routes = routes.concat(authRoutes).concat(templatesRoutes)



export const router = createRouter({
    history: createWebHashHistory(),
    routes,
})

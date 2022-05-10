import { createRouter, createWebHashHistory, RouteRecordRaw } from "vue-router"
import HelloWorld from '@/ts/dev-intro/HelloWorld.vue'
import authRoutes from '@/ts/router/auth'

let routes: RouteRecordRaw[] = [
    {
        name: 'index', path: '/', component: HelloWorld, props: {
            msg: 'Welcome to Somero Budget'
        }
    }
]
routes = routes.concat(authRoutes)



export const router = createRouter({
    history: createWebHashHistory(),
    routes,
})

<template>
    <Head>
        <title>{{ title }}</title>
        <link rel="manifest" href="/manifest.json" v-if="prod">
        <link ref="favicon" href="/favicon.ico">
    </Head>
    <div class="fixed top-1 left-0 right-0 flex justify-center z-10">
        <transition name="offline-toast" mode="out-in">
            <div class="rounded-2xl bg-gray-200 px-3 py-2 flex items-center gap-2" v-if="!online && !down">
                <i class="material-icons">offline_bolt</i>
                Offline
            </div>
        </transition>
    </div>
    <div class="fixed top-1 right-3 flex z-10">
        <VDropdown :delay="0">
            <template #default="{ show, hide, shown }">
                <transition name="auth-buttons" mode="out-in">
                    <IconButton v-if="hasInitiallyLoaded && auth.authenticated" @click="shown ? hide() : show()">
                        more_vert
                    </IconButton>
                </transition>
            </template>
            <template #popper="{ hide }">
                <div class="p-5" @click="hide">
                    <RouterLink :to="{ name: 'index' }">
                        <Button :disabled="$route.name == 'index'" :raised="$route.name == 'index'">
                            <template #leading-icon>home</template>
                            Home
                        </Button>
                    </RouterLink>
                    <RouterLink :to="{ name: 'profile' }">
                        <Button :disabled="!auth.authenticated || $route.name == 'profile'"
                            :raised="$route.name == 'profile'">
                            <template #leading-icon>person</template>
                            Profile
                        </Button>
                    </RouterLink>
                    <RouterLink :to="{ name: 'templates' }">
                        <Button :disabled="!auth.authenticated || $route.name == 'templates'"
                            :raised="$route.name == 'templates'">
                            <template #leading-icon>content_copy</template>
                            Templates
                        </Button>
                    </RouterLink>
                    <RouterLink :to="{ name: 'history' }">
                        <Button :disabled="!auth.authenticated || $route.name == 'history'"
                            :raised="$route.name == 'history'">
                            <template #leading-icon>history</template>
                            History
                        </Button>
                    </RouterLink>
                    <Button @click="auth.logout">Log out</Button>
                </div>
            </template>
        </VDropdown>
    </div>
    <div class="fixed top-1 right-1 flex z-10">
        <transition name="auth-buttons" mode="out-in">
            <div v-if="auth.authenticated">
                <!-- <Button @click="auth.logout">Log out</Button> -->
            </div>
            <div v-else class="flex">
                <transition name="auth-buttons" mode="out-in">
                    <div v-if="$route.name == 'login'">
                        <RouterLink to="/register">
                            <Button>Register</Button>
                        </RouterLink>
                    </div>
                    <div v-else-if="$route.name == 'register'">
                        <RouterLink to="/login">
                            <Button>Log in</Button>
                        </RouterLink>
                    </div>
                    <div v-else class="flex">
                        <RouterLink to="/login">
                            <Button>Log in</Button>
                        </RouterLink>
                        <RouterLink to="/register">
                            <Button>Register</Button>
                        </RouterLink>
                    </div>
                </transition>
            </div>
        </transition>
    </div>
    <RouterView #default="{ Component }">
        <transition name="page-navigation" mode="out-in">
            <component :is="Component" v-if="hasInitiallyLoaded" v-cloak :key="$route.path" />
        </transition>
    </RouterView>
    <transition-group name="modal">
        <Component v-for="modal of modals.values" :key="modal.id" :is="modal.modal" v-bind="modal.props"
            :id="modal.id" />
    </transition-group>
    <Snackbar v-for="notification of auth.user?.notifications" :timeout="-1">
        {{ notification.data.title }}<br>
        {{ notification.data.message }}
        <template #actions>
            <SnackbarActionButton>
                <a :href="notification.data.action">Open</a>
            </SnackbarActionButton>
            <SnackbarDismissButton @click="dismissNotification(notification.data.uuid)">close</SnackbarDismissButton>
        </template>
    </Snackbar>
</template>

<script setup lang="ts">
import { useAuth } from "@/core/users/auth";
import Button from "@/core/buttons/Button.vue";
import { onMounted, ref, watch } from "vue";
import { useOnline, useWindowScroll } from "@vueuse/core";
import { useModals } from "@/store/modals";
import { Head } from "@vueuse/head"
import IconButton from '@/core/buttons/IconButton.vue';
import Snackbar from '@/core/snackbars/Snackbar.vue';
import SnackbarActionButton from '@/core/snackbars/SnackbarActionButton.vue';
import SnackbarDismissButton from '@/core/snackbars/SnackbarDismissButton.vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useEcho } from '@/store/echo';

const title = import.meta.env.VITE_APP_NAME
const prod = import.meta.env.PROD

const auth = useAuth();

const modals = useModals()
const route = useRoute()
const echo = useEcho()
const online = useOnline();

const { y } = useWindowScroll();
let prevY = 0;
const down = ref(false);
watch(y, () => {
    if (y.value > prevY) {
        down.value = true;
        prevY = y.value;
    } else {
        down.value = false;
        prevY = y.value;
    }
});

const hasInitiallyLoaded = ref(false);
type Notification = {
    id: string,
    read_at: string | null,
    data: { title: string, message: string, action: string, uuid: string }
}
onMounted(async () => {
    if (!auth.authenticated || !auth.user) {
        await auth.getUser();
        echo.echo.private(`App.Models.User.${auth.user?.id}`)
            .listen(
                'PushNotificationCreated',
                (payload: { notification: Notification }) => {
                    if (auth.user?.notifications) auth.user.notifications.unshift(payload.notification)
                }
            )
            .listen(
                'PushNotificationUpdated',
                (payload: { notification: Notification }) => {
                    console.log('PushNotificationUpdated')
                    if (auth.user?.notifications) {
                        // Update the notification or remove it if the update is to mark it read
                        let index = auth.user.notifications.findIndex(n => n.data.uuid == payload.notification.data.uuid)
                        if (index != -1) {
                            auth.user.notifications[index] = payload.notification
                            if (payload.notification.read_at) {
                                let temp = auth.user.notifications
                                temp.splice(index)
                                auth.user.notifications = temp
                            }
                        }
                    }
                }
            )
    }

    hasInitiallyLoaded.value = true;
});

// Dismiss notification:
// when we go to the notification's action
watch(
    () => route.query,
    to => {
        if (to.notification_uuid) dismissNotification(String(to.notification_uuid))
    },
    { deep: true, immediate: true }
)
// This is also called when we just hit the x button to dismiss notifications directly
async function dismissNotification(id: string) {
    let response = await axios.get(`/api/dismiss-notification/${id}`)
    let index = auth.user?.notifications.findIndex(i => i.id == response.data.id)
    if (auth.user && index && index != -1) auth.user.notifications[index] = response.data
}
</script>

<style scoped lang="scss">
@use "@/css/transitions";
</style>

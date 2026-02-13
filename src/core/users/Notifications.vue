<template>
    <div class="flex flex-col items-center relative">
        <h1 class="my-6 text-2xl">Notifications</h1>

        <div class="mb-4">
            <MdcSwitch v-model="showDismissed"
                >Show dismissed notifications</MdcSwitch
            >
        </div>

        <div v-if="loading" class="my-8">
            <p>Loading notifications...</p>
        </div>

        <div v-else-if="!filteredNotifications.length" class="my-8">
            <p>
                {{
                    showDismissed
                        ? "No dismissed notifications"
                        : "No new notifications"
                }}
            </p>
        </div>

        <div v-else class="w-full max-w-2xl">
            <div
                v-for="notification in filteredNotifications"
                :key="notification.id"
                class="bg-white rounded-lg shadow-md p-4 mb-4 border-l-4"
                :class="
                    notification.read_at ? 'border-gray-400' : 'border-blue-500'
                "
            >
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="font-semibold text-lg">
                            {{ notification.data.title }}
                        </h3>
                        <p class="text-gray-700 mt-1">
                            {{ notification.data.message }}
                        </p>
                        <p class="text-gray-500 text-sm mt-2">
                            {{ formatDate(notification.created_at) }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 ml-4">
                        <IconButton
                            v-if="notification.data.action"
                            v-tooltip="'Open'"
                            @click="openAction(notification)"
                        >
                            open_in_new
                        </IconButton>
                        <IconButton
                            v-if="!notification.read_at"
                            v-tooltip="'Dismiss'"
                            @click="dismissNotification(notification.data.uuid)"
                        >
                            close
                        </IconButton>
                        <IconButton
                            v-else
                            v-tooltip="'Mark as unread'"
                            @click="
                                undismissNotification(notification.data.uuid)
                            "
                        >
                            refresh
                        </IconButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue"
import axios from "axios"
import MdcSwitch from "@/core/switches/MdcSwitch.vue"
import IconButton from "@/core/buttons/IconButton.vue"
import { useAuth } from "@/core/users/auth"
import { DateTime } from "luxon"

type Notification = {
    id: string
    read_at: string | null
    created_at: string
    data: {
        uuid: string
        title: string
        message: string
        action: string
    }
}

const auth = useAuth()
const showDismissed = ref(false)
const loading = ref(true)
const notifications = ref<Notification[]>([])

const filteredNotifications = computed(() => {
    return notifications.value.filter((n) =>
        showDismissed.value ? n.read_at !== null : n.read_at === null
    )
})

async function fetchNotifications() {
    loading.value = true
    try {
        const response = await axios.get("/api/notifications")
        notifications.value = response.data
    } catch (error) {
        console.error("Error fetching notifications:", error)
    } finally {
        loading.value = false
    }
}

async function dismissNotification(uuid: string) {
    try {
        const response = await axios.get(`/api/dismiss-notification/${uuid}`)
        const index = notifications.value.findIndex((n) => n.data.uuid === uuid)
        if (index !== -1) {
            notifications.value[index] = response.data
        }
        // Also update the auth store if present
        if (auth.user) {
            const authIndex = auth.user.notifications.findIndex(
                (n) => n.data.uuid === uuid
            )
            if (authIndex !== -1) {
                auth.user.notifications[authIndex] = response.data
            }
        }
    } catch (error) {
        console.error("Error dismissing notification:", error)
    }
}

async function undismissNotification(uuid: string) {
    try {
        const response = await axios.get(`/api/undismiss-notification/${uuid}`)
        const index = notifications.value.findIndex((n) => n.data.uuid === uuid)
        if (index !== -1) {
            notifications.value[index] = response.data
        }
        // Also update the auth store if present
        if (auth.user) {
            const authIndex = auth.user.notifications.findIndex(
                (n) => n.data.uuid === uuid
            )
            if (authIndex !== -1) {
                auth.user.notifications[authIndex] = response.data
            } else {
                // Add it back to the notifications list
                auth.user.notifications.unshift(response.data)
            }
        }
    } catch (error) {
        console.error("Error undismissing notification:", error)
    }
}

function openAction(notification: Notification) {
    if (notification.data.action) {
        window.location.href = notification.data.action
    }
}

function formatDate(dateString: string) {
    const date = DateTime.fromISO(dateString)
    const now = DateTime.now()

    const diff = now.diff(date, ["days", "hours", "minutes"])

    if (diff.days >= 1) {
        return date.toFormat("MMM dd, yyyy")
    } else if (diff.hours >= 1) {
        return `${Math.floor(diff.hours)} hour${
            Math.floor(diff.hours) !== 1 ? "s" : ""
        } ago`
    } else if (diff.minutes >= 1) {
        return `${Math.floor(diff.minutes)} minute${
            Math.floor(diff.minutes) !== 1 ? "s" : ""
        } ago`
    } else {
        return "Just now"
    }
}

onMounted(() => {
    fetchNotifications()
})
</script>

<style scoped>
/* Add any additional styles if needed */
</style>

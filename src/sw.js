/* eslint-env serviceworker */
importScripts(
    "https://storage.googleapis.com/workbox-cdn/releases/6.2.0/workbox-sw.js"
);

/* global workbox */

const { precacheAndRoute, cleanupOutdatedCaches } = workbox.precaching;
const { clientsClaim } = workbox.core;
const { registerRoute } = workbox.routing;
const { CacheFirst, NetworkFirst } = workbox.strategies;
const { ExpirationPlugin } = workbox.expiration;
const { CacheableResponsePlugin } = workbox.cacheableResponse;
// import Pusher from "pusher-js/worker";
// importScripts("https://js.pusher.com/7.0/pusher.worker.min.js");
importScripts("https://js.pusher.com/beams/service-worker.js");

self.skipWaiting();
clientsClaim();

cleanupOutdatedCaches();

precacheAndRoute(self.__WB_MANIFEST);

PusherPushNotifications.onNotificationReceived = ({
    payload,
    pushEvent,
    handleNotification,
}) => {
    // payload.notification.title = "A new title!";

    // Copied from the source code: https://github.com/pusher/push-notifications-web/blob/2ee625ebd7d9a179ba83c74f0eeac4e571861fa9/src/service-worker.js#L116
    // Modified to include badge
    const handleNotificationModified = async (payloadFromCallback) => {
        console.log('handleNotificationModified')
        const hideNotificationIfSiteHasFocus = payloadFromCallback.notification.hide_notification_if_site_has_focus === true;
        if (
            hideNotificationIfSiteHasFocus &&
            (await self.PusherPushNotifications._hasFocusedClient())
        ) {
            return;
        }

        const title = payloadFromCallback.notification.title || "";
        const body = payloadFromCallback.notification.body || "";
        const icon = payloadFromCallback.notification.icon;
        const badge = payloadFromCallback.notification.badge || "";

        const options = {
            body,
            icon,
            badge,
            data: {
                pusher: {
                    customerPayload: payloadFromCallback,
                    pusherMetadata: payload.data.pusher,
                },
            },
        };

        return self.registration.showNotification(title, options);
    };

    pushEvent.waitUntil(handleNotificationModified(payload));
};

registerRoute(
    /^https:\/\/fonts\.googleapis\.com\/.*/i,
    new CacheFirst({
        cacheName: "google-fonts-cache",
        plugins: [
            new ExpirationPlugin({
                maxEntries: 10,
                maxAgeSeconds: 31536e3, // a year
            }),
            new CacheableResponsePlugin({ statuses: [0, 200] }),
        ],
    }),
    "GET"
);

registerRoute(
    /\/assets\/.*/i,
    new CacheFirst({
        cacheName: "assets-cache",
        plugins: [
            new ExpirationPlugin({
                maxEntries: 5,
                maxAgeSeconds: 31536e3, // a year
            }),
            new CacheableResponsePlugin({ statuses: [0, 200] }),
        ],
    })
);

// The external icons
const iconUrls = [
    "https://laravel.com/img/logomark.min.svg",
    "https://laravel-vite.innocenzi.dev/logo.svg",
    "https://vitejs.dev/logo.svg",
    "https://pinia.vuejs.org/logo.svg",
    "https://upload.wikimedia.org/wikipedia/commons/4/4c/Typescript_logo_2020.svg",
    "https://d33wubrfki0l68.cloudfront.net/2f6479d73bc25170dc532dd42e059166573bf478/61057/favicon.svg",
];
registerRoute(
    ({ url: e }) => iconUrls.includes(e.href),
    new CacheFirst({
        cacheName: "external-cache",
        plugins: [
            new ExpirationPlugin({
                maxEntries: 5,
                maxAgeSeconds: 31536e3, // a year
            }),
            new CacheableResponsePlugin({ statuses: [0, 200] }),
        ],
    })
);

registerRoute(
    ({ url: e }) => "/" == e.pathname || "" == e.pathname,
    new NetworkFirst({
        cacheName: "index-page-cache",
        plugins: [
            new ExpirationPlugin({
                maxEntries: 5,
                maxAgeSeconds: 31536e3, // a year
            }),
            new CacheableResponsePlugin({ statuses: [0, 200] }),
        ],
    }),
    "GET"
);

registerRoute(
    ({ url: e }) => "/api/user" == e.pathname || "api/user" == e.pathname,
    new NetworkFirst({
        cacheName: "user-cache",
        plugins: [
            new ExpirationPlugin({
                maxEntries: 1,
                maxAgeSeconds: 60 * 60 * 24, // 24 hours
            }),
            new CacheableResponsePlugin({ statuses: [0, 200] }),
        ],
    })
);

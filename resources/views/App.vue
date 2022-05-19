<template>
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
						<Button><template #leading-icon>home</template>Home</Button>
					</RouterLink>
					<RouterLink :to="{ name: 'templates' }" v-if="auth.authenticated">
						<Button><template #leading-icon>content_copy</template>Templates</Button>
					</RouterLink>
					<Button @click="auth.logout">Log out</Button>
				</div>
			</template>
		</VDropdown>
	</div>
	<div class="fixed top-1 right-1 flex z-10">
		<transition name="auth-buttons" mode="out-in">
			<div v-if="!auth.authenticated" class="flex">
				<transition name="auth-buttons" mode="out-in">
					<div v-if="$route.name == 'login'">
						<RouterLink to="register">
							<Button>Register</Button>
						</RouterLink>
					</div>
					<div v-else-if="$route.name == 'register'">
						<RouterLink to="login">
							<Button>Log in</Button>
						</RouterLink>					
					</div>
					<div v-else class="flex">
						<RouterLink to="login">
							<Button>Log in</Button>
						</RouterLink>	
						<RouterLink to="register">
							<Button>Register</Button>
						</RouterLink>				
					</div>
				</transition>
			</div>
		</transition>
	</div>
	<RouterView #default="{ Component }">
		<transition name="page-navigation" mode="out-in">
			<component :is="Component" v-if="hasInitiallyLoaded" v-cloak />
		</transition>
	</RouterView>
	<transition-group name="modal">
		<Component
			v-for="modal of modals.values"
			:key="modal.id"
			:is="modal.modal"
			v-bind="modal.props"
			:id="modal.id"
		/>
	</transition-group>
</template>

<script setup lang="ts">
import { useAuth } from '@/ts/core/users/auth';
import Button from '@/ts/core/buttons/Button.vue';
import { onMounted, ref, watch } from 'vue';
import { useOnline, useScroll, useWindowScroll } from '@vueuse/core';
import { useModals } from '@/ts/store/modals';
import IconButton from '@/ts/core/buttons/IconButton.vue';

const auth = useAuth()

const online = useOnline()

const { x, y } = useWindowScroll()
let prevY = 0
const down = ref(false)
watch(y, () => {
	if (y.value > prevY) {
		down.value = true
		prevY = y.value
	} else {
		down.value = false
		prevY = y.value
	}
})

const hasInitiallyLoaded = ref(false)
onMounted(async () => {
	if (!auth.authenticated || !auth.user) {
		await auth.getUser()
	}

	hasInitiallyLoaded.value = true
})

const modals = useModals()
</script>

<style scoped lang="scss">
@use "@/css/transitions";
</style>
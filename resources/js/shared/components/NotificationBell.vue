<template>
    <div v-if="authStore.token" class="bell">
        <button
            type="button"
            class="bell__button"
            :aria-expanded="open"
            @click="toggle"
        >
            <span class="bell__icon" aria-hidden="true">🔔</span>
            <span v-if="store.unreadCount > 0" class="bell__badge">
                {{ store.unreadCount > 9 ? '9+' : store.unreadCount }}
            </span>
        </button>

        <div v-if="open" class="bell__dropdown">
            <div class="bell__dropdown-header">
                <span>Notificaciones</span>
                <button
                    type="button"
                    class="bell__mark-all"
                    :disabled="store.unreadCount === 0"
                    @click="markAll"
                >
                    Marcar todo
                </button>
            </div>

            <p v-if="recent.length === 0" class="bell__empty">
                No tienes notificaciones recientes.
            </p>

            <ul v-else class="bell__list">
                <li
                    v-for="notification in recent"
                    :key="notification.id"
                    class="bell__item"
                    :class="{ 'bell__item--unread': !notification.read_at }"
                >
                    <p class="bell__item-title">
                        {{ notification.title }}
                    </p>
                    <p class="bell__item-body">
                        {{ notification.body }}
                    </p>
                </li>
            </ul>

            <router-link class="bell__link" to="/notificaciones" @click="open = false">
                Ver todas
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useNotificationsStore } from '@/stores/notifications';

const authStore = useAuthStore();
const store = useNotificationsStore();
const open = ref(false);

const recent = computed(() => store.items.slice(0, 5));

function toggle() {
    open.value = !open.value;

    if (open.value) {
        store.fetchNotifications('all');
    }
}

function markAll() {
    store.markAllAsRead();
}

onMounted(() => {
    if (authStore.token) {
        store.fetchUnreadCount();
    }
});
</script>

<style scoped>
.bell {
    position: relative;
}

.bell__button {
    position: relative;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1.25rem;
    line-height: 1;
    padding: 0.25rem;
}

.bell__badge {
    position: absolute;
    top: -0.25rem;
    right: -0.25rem;
    background: #dc2626;
    color: #ffffff;
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 999px;
    padding: 0.05rem 0.35rem;
    min-width: 1.1rem;
    text-align: center;
}

.bell__dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 0.5rem);
    width: 280px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 10px 25px -10px rgba(15, 23, 42, 0.25);
    padding: 0.75rem;
    z-index: 20;
}

.bell__dropdown-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.bell__mark-all {
    border: none;
    background: transparent;
    color: #2563eb;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
}

.bell__mark-all:disabled {
    color: #94a3b8;
    cursor: default;
}

.bell__empty {
    color: #94a3b8;
    font-size: 0.85rem;
    margin: 0.5rem 0;
}

.bell__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
    max-height: 240px;
    overflow-y: auto;
}

.bell__item {
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 0.4rem 0.55rem;
}

.bell__item--unread {
    background: #eff6ff;
    border-color: #bfdbfe;
}

.bell__item-title {
    font-size: 0.85rem;
    font-weight: 600;
    margin: 0;
}

.bell__item-body {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0.15rem 0 0;
}

.bell__link {
    display: block;
    text-align: center;
    margin-top: 0.6rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #2563eb;
    text-decoration: none;
}
</style>

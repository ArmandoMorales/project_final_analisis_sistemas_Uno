<template>
    <section class="notifications">
        <h1 class="notifications__title">
            Notificaciones internas
        </h1>
        <p class="notifications__subtitle">
            Avisos del sistema y mensajes dirigidos a tu cuenta.
        </p>

        <div class="notifications__toolbar">
            <div class="notifications__tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    class="notifications__tab"
                    :class="{ 'notifications__tab--active': store.status === tab.value }"
                    @click="store.fetchNotifications(tab.value)"
                >
                    {{ tab.label }}
                </button>
            </div>

            <button
                type="button"
                class="notifications__mark-all"
                :disabled="!hasUnread"
                @click="store.markAllAsRead()"
            >
                Marcar todas como leídas
            </button>
        </div>

        <p v-if="store.loading" class="notifications__state">
            Cargando notificaciones…
        </p>
        <p v-else-if="store.error" class="notifications__state notifications__state--error">
            {{ store.error }}
        </p>
        <p v-else-if="store.items.length === 0" class="notifications__state">
            No tienes notificaciones por el momento.
        </p>

        <ul v-else class="notifications__list">
            <li
                v-for="notification in store.items"
                :key="notification.id"
                class="notifications__item"
                :class="`notifications__item--${notification.type}`"
            >
                <div class="notifications__item-header">
                    <h2 class="notifications__item-title">
                        {{ notification.title }}
                    </h2>
                    <span v-if="!notification.read_at" class="notifications__badge">
                        Nueva
                    </span>
                </div>
                <p class="notifications__item-body">
                    {{ notification.body }}
                </p>
                <div class="notifications__item-footer">
                    <time class="notifications__item-date">
                        {{ formatDate(notification.created_at) }}
                    </time>
                    <button
                        v-if="!notification.read_at"
                        type="button"
                        class="notifications__mark-read"
                        @click="store.markAsRead(notification.id)"
                    >
                        Marcar como leída
                    </button>
                </div>
            </li>
        </ul>
    </section>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useNotificationsStore } from '@/stores/notifications';

const store = useNotificationsStore();

const tabs = [
    { value: 'all', label: 'Todas' },
    { value: 'unread', label: 'No leídas' },
    { value: 'read', label: 'Leídas' },
];

const hasUnread = computed(() => store.items.some((item) => !item.read_at));

function formatDate(value) {
    return new Date(value).toLocaleString();
}

onMounted(() => {
    store.fetchNotifications();
});
</script>

<style scoped>
.notifications {
    max-width: 720px;
}

.notifications__title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.notifications__subtitle {
    color: #475569;
    margin-bottom: 1.25rem;
}

.notifications__toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.notifications__tabs {
    display: flex;
    gap: 0.5rem;
}

.notifications__tab {
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #475569;
    border-radius: 999px;
    padding: 0.35rem 0.9rem;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

.notifications__tab--active {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
}

.notifications__mark-all {
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #2563eb;
    border-radius: 6px;
    padding: 0.35rem 0.9rem;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

.notifications__mark-all:disabled {
    color: #94a3b8;
    cursor: default;
}

.notifications__state {
    color: #475569;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
}

.notifications__state--error {
    color: #b91c1c;
    border-color: #fecaca;
    background: #fef2f2;
}

.notifications__list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    list-style: none;
    padding: 0;
    margin: 0;
}

.notifications__item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #94a3b8;
    border-radius: 8px;
    padding: 0.85rem 1rem;
}

.notifications__item--info {
    border-left-color: #2563eb;
}

.notifications__item--success {
    border-left-color: #16a34a;
}

.notifications__item--warning {
    border-left-color: #d97706;
}

.notifications__item--danger {
    border-left-color: #dc2626;
}

.notifications__item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.notifications__item-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.notifications__badge {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #2563eb;
    background: #dbeafe;
    border-radius: 999px;
    padding: 0.15rem 0.6rem;
}

.notifications__item-body {
    color: #334155;
    margin: 0.35rem 0;
}

.notifications__item-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}

.notifications__item-date {
    color: #94a3b8;
    font-size: 0.8rem;
}

.notifications__mark-read {
    border: none;
    background: transparent;
    color: #2563eb;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
}
</style>

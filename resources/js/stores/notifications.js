import { defineStore } from 'pinia';
import api from '@/plugins/axios';

export const useNotificationsStore = defineStore('notifications', {
    state: () => ({
        items: [],
        meta: null,
        status: 'all',
        loading: false,
        error: '',
        unreadCount: 0,
    }),
    actions: {
        async fetchNotifications(status = this.status) {
            this.status = status;
            this.loading = true;
            this.error = '';

            try {
                const { data } = await api.get('/notifications', {
                    params: { status },
                });

                this.items = data.data;
                this.meta = data.meta;
            } catch (error) {
                this.error = error?.response?.data?.message
                    ?? 'No fue posible cargar las notificaciones.';
            } finally {
                this.loading = false;
            }
        },

        async fetchUnreadCount() {
            try {
                const { data } = await api.get('/notifications/unread-count');
                this.unreadCount = data.unread_count;
            } catch {
                // Silently ignore: the bell simply keeps its last known count.
            }
        },

        async markAsRead(id) {
            try {
                const { data } = await api.patch(`/notifications/${id}/read`);

                const target = this.items.find((item) => item.id === id);
                if (target) {
                    target.read_at = data.data.read_at;
                }

                if (this.unreadCount > 0) {
                    this.unreadCount -= 1;
                }
            } catch (error) {
                this.error = error?.response?.data?.message
                    ?? 'No fue posible marcar la notificación como leída.';
            }
        },

        async markAllAsRead() {
            try {
                await api.patch('/notifications/read-all');

                const now = new Date().toISOString();
                this.items.forEach((item) => {
                    if (!item.read_at) {
                        item.read_at = now;
                    }
                });

                this.unreadCount = 0;
            } catch (error) {
                this.error = error?.response?.data?.message
                    ?? 'No fue posible marcar las notificaciones como leídas.';
            }
        },
    },
});

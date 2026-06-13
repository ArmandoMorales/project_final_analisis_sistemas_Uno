import { defineStore } from 'pinia';
import api from '@/plugins/axios';

export const useNotificationsStore = defineStore('notifications', {
    state: () => ({
        items: [],
        meta: null,
        status: 'all',
        loading: false,
        error: '',
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
    },
});

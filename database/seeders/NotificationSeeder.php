<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->where('slug', 'san-marcos-demo')->first();

        if ($tenant === null) {
            return;
        }

        $broadcasts = [
            [
                'title' => 'Mantenimiento programado del sistema',
                'body' => 'El sistema HIS estará en mantenimiento el sábado de 22:00 a 23:00. Guarde su trabajo antes de esa hora.',
                'type' => Notification::TYPE_WARNING,
            ],
            [
                'title' => 'Actualización de políticas de bioseguridad',
                'body' => 'Se publicó el nuevo protocolo de bioseguridad para el área de hospitalización. Revíselo en el tablón de anuncios.',
                'type' => Notification::TYPE_INFO,
            ],
            [
                'title' => 'Respaldo de base de datos completado',
                'body' => 'El respaldo automático nocturno de la base de datos del hospital finalizó correctamente.',
                'type' => Notification::TYPE_SUCCESS,
            ],
        ];

        foreach ($broadcasts as $broadcast) {
            Notification::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => null, 'title' => $broadcast['title']],
                ['body' => $broadcast['body'], 'type' => $broadcast['type']]
            );
        }

        $user = User::query()->where('tenant_id', $tenant->id)->first();

        if ($user === null) {
            return;
        }

        $personal = [
            [
                'title' => 'Tu turno fue reasignado',
                'body' => 'El coordinador de enfermería reasignó tu turno del jueves al área de emergencias.',
                'type' => Notification::TYPE_WARNING,
                'read_at' => null,
            ],
            [
                'title' => 'Resultado de laboratorio disponible',
                'body' => 'El resultado de laboratorio de un paciente asignado a ti ya está disponible para revisión.',
                'type' => Notification::TYPE_INFO,
                'read_at' => null,
            ],
            [
                'title' => 'Bienvenido al sistema HIS',
                'body' => 'Tu cuenta fue creada correctamente. Explora el módulo de notificaciones para mantenerte al día.',
                'type' => Notification::TYPE_SUCCESS,
                'read_at' => now(),
            ],
        ];

        foreach ($personal as $notification) {
            Notification::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $user->id, 'title' => $notification['title']],
                ['body' => $notification['body'], 'type' => $notification['type'], 'read_at' => $notification['read_at']]
            );
        }
    }
}

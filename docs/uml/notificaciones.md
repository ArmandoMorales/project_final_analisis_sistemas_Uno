# Módulo 25 — Notificaciones internas: Diagramas UML

Este documento contiene los diagramas UML (Base Global) del módulo
**Notificaciones internas**, correspondientes al Sprint 3. Los diagramas
están escritos en formato [Mermaid](https://mermaid.js.org/) y se renderizan
automáticamente al visualizar este archivo en GitHub.

## 1. Diagrama de casos de uso

Actores: **Usuario autenticado** (cualquier rol con sesión activa) y el
**Sistema** (origen de las notificaciones de difusión y personales).

```mermaid
flowchart LR
    Usuario([Usuario autenticado])

    subgraph Modulo de Notificaciones
        UC1((Listar notificaciones))
        UC2((Filtrar por estado: todas / no leídas / leídas))
        UC3((Ver contador de no leídas))
        UC4((Marcar una notificación como leída))
        UC5((Marcar todas como leídas))
    end

    Sistema([Sistema / Seeder])

    Usuario --> UC1
    Usuario --> UC2
    Usuario --> UC3
    Usuario --> UC4
    Usuario --> UC5

    UC2 -.extiende.-> UC1

    Sistema -.genera notificaciones.-> UC1
```

**Descripción de los casos de uso**

| Caso de uso | Descripción | Endpoint asociado |
|---|---|---|
| Listar notificaciones | El usuario consulta las notificaciones visibles para él (personales + difusión de su tenant), paginadas. | `GET /api/v1/notifications` |
| Filtrar por estado | Extiende el listado permitiendo filtrar por `all`, `unread` o `read`. | `GET /api/v1/notifications?status=...` |
| Ver contador de no leídas | El usuario obtiene cuántas notificaciones no leídas tiene, usado por la campanita del header. | `GET /api/v1/notifications/unread-count` |
| Marcar una notificación como leída | El usuario marca una notificación individual como leída, validando que le pertenezca o sea de difusión de su tenant. | `PATCH /api/v1/notifications/{notification}/read` |
| Marcar todas como leídas | El usuario marca como leídas todas las notificaciones visibles que aún estén pendientes. | `PATCH /api/v1/notifications/read-all` |

## 2. Diagrama de clases

Representa las clases involucradas en el módulo: el modelo `Notification`,
sus relaciones con `Tenant` y `User`, su fábrica de pruebas y el controlador
de la API.

```mermaid
classDiagram
    class Notification {
        +int id
        +string tenant_id
        +int|null user_id
        +string title
        +string body
        +string type
        +datetime|null read_at
        +datetime created_at
        +datetime updated_at
        +isRead() bool
        +scopeVisibleTo(query, User) Builder
        +scopeUnread(query) Builder
    }

    class Tenant {
        +string id
        +string name
        +string slug
    }

    class User {
        +int id
        +string tenant_id
        +string name
        +string email
    }

    class NotificationFactory {
        +definition() array
        +forUser(User) static
        +read() static
    }

    class NotificationController {
        +index(Request) JsonResponse
        +unreadCount(Request) JsonResponse
        +markAsRead(Request, Notification) JsonResponse
        +markAllAsRead(Request) JsonResponse
    }

    Notification "many" --> "1" Tenant : belongsTo
    Notification "many" --> "0..1" User : belongsTo
    NotificationFactory ..> Notification : crea instancias
    NotificationController ..> Notification : consulta / actualiza
```

**Notas sobre el modelo**

- `user_id` nulo indica una **notificación de difusión** (visible para todo
  el tenant). Si tiene valor, es una **notificación personal**.
- `scopeVisibleTo` centraliza la regla de visibilidad: mismo `tenant_id` y
  (`user_id` nulo O `user_id` igual al del usuario autenticado).
- `scopeUnread` filtra por `read_at IS NULL`.
- `type` admite los valores `info`, `success`, `warning`, `danger`, usados
  para dar estilo visual en la interfaz.

## 3. Diagrama de secuencia

Flujo principal: el usuario abre la página de notificaciones, el frontend
consulta el listado y el contador de no leídas, y luego marca una
notificación como leída.

```mermaid
sequenceDiagram
    actor Usuario
    participant UI as NotificationsPage / NotificationBell (Vue)
    participant Store as Pinia notifications store
    participant API as NotificationController
    participant DB as Base de datos (notifications)

    Usuario->>UI: Abre /notificaciones o la campanita
    UI->>Store: fetchNotifications(status)
    Store->>API: GET /api/v1/notifications?status=...
    API->>DB: Notification::visibleTo(user)->when(status)->paginate()
    DB-->>API: Colección paginada
    API-->>Store: 200 OK { data, meta }
    Store-->>UI: Actualiza items y meta

    UI->>Store: fetchUnreadCount()
    Store->>API: GET /api/v1/notifications/unread-count
    API->>DB: Notification::visibleTo(user)->unread()->count()
    DB-->>API: total
    API-->>Store: 200 OK { unread_count }
    Store-->>UI: Actualiza badge de la campanita

    Usuario->>UI: Click "Marcar como leída"
    UI->>Store: markAsRead(id)
    Store->>API: PATCH /api/v1/notifications/{id}/read
    API->>API: Verifica tenant_id y user_id del usuario autenticado
    alt Notificación accesible
        API->>DB: update read_at = now()
        DB-->>API: notificación actualizada
        API-->>Store: 200 OK { data }
        Store-->>UI: Marca el ítem como leído y decrementa el contador
    else No accesible (otro usuario/tenant)
        API-->>Store: 403 Forbidden
        Store-->>UI: Muestra mensaje de error
    end
```

## 4. Relación con el resto del sistema (Base Global)

- El módulo se apoya en la **autenticación JWT** (`guard api`,
  middleware `jwt.auth`) y en el **middleware de tenant** (`tenant`,
  cabecera `X-Tenant-ID`), ambos ya existentes en la base del proyecto.
- La tabla `notifications` referencia `tenants.id` y `users.id` mediante
  llaves foráneas con `cascadeOnDelete`, integrándose al esquema
  multi-tenant general.
- El `NotificationSeeder` se agregó al `DatabaseSeeder` general, junto a
  `RoleSeeder` y `TenantSeeder`, para poblar datos de demostración.

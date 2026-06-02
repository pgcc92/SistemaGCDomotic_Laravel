-- GC Domotic Dashboard - Agenda (Instalaciones / Postventa)
-- Ejecutar en tu Postgres remoto (DB: gcdomoticbot) en schema public.

CREATE TABLE IF NOT EXISTS public.agenda_instalaciones (
  id            SERIAL PRIMARY KEY,
  tipo          VARCHAR(20)  NOT NULL, -- VENTA | POSTVENTA
  estado        VARCHAR(20)  NOT NULL, -- PENDIENTE | PROGRAMADA | REALIZADA | CANCELADA
  venta_id      INTEGER NULL,
  ticket_id     VARCHAR(50) NULL,
  cliente_id    INTEGER NULL,
  cliente_wa    VARCHAR(30) NULL,
  tecnico_id    INTEGER NULL,
  sucursal_id   INTEGER NULL,
  titulo        VARCHAR(150) NULL,
  descripcion   TEXT NULL,
  fecha_programada TIMESTAMP NOT NULL,
  duracion_min  INTEGER NOT NULL DEFAULT 60,
  prioridad     VARCHAR(20) NULL, -- BAJA | MEDIA | ALTA | URGENTE
  notas         TEXT NULL,
  -- opcional: fecha/hora real de término (para confirmación)
  -- terminado_at  TIMESTAMP NULL,
  -- opcional: evidencia ligada a un registro de dispositivos_cliente
  -- evidencia_dispositivo_id INTEGER NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT now(),
  updated_at    TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_agenda_fecha
  ON public.agenda_instalaciones (fecha_programada ASC);

CREATE INDEX IF NOT EXISTS idx_agenda_estado
  ON public.agenda_instalaciones (estado ASC);

CREATE INDEX IF NOT EXISTS idx_agenda_tecnico
  ON public.agenda_instalaciones (tecnico_id ASC);

CREATE INDEX IF NOT EXISTS idx_agenda_cliente_wa
  ON public.agenda_instalaciones (cliente_wa ASC);

-- FKs opcionales (solo si existen las tablas)
-- ALTER TABLE public.agenda_instalaciones
--   ADD CONSTRAINT fk_agenda_venta FOREIGN KEY (venta_id) REFERENCES public.ventas(id) ON DELETE SET NULL;
-- ALTER TABLE public.agenda_instalaciones
--   ADD CONSTRAINT fk_agenda_ticket FOREIGN KEY (ticket_id) REFERENCES public.tickets(ticket_id) ON DELETE SET NULL;
-- ALTER TABLE public.agenda_instalaciones
--   ADD CONSTRAINT fk_agenda_cliente FOREIGN KEY (cliente_id) REFERENCES public.clientes(id) ON DELETE SET NULL;
-- ALTER TABLE public.agenda_instalaciones
--   ADD CONSTRAINT fk_agenda_tecnico FOREIGN KEY (tecnico_id) REFERENCES public.tecnicos(id) ON DELETE SET NULL;
-- ALTER TABLE public.agenda_instalaciones
--   ADD CONSTRAINT fk_agenda_sucursal FOREIGN KEY (sucursal_id) REFERENCES public.sucursales(id) ON DELETE SET NULL;

-- Opcional (recomendado) para confirmación con hora de término y evidencia
-- ALTER TABLE public.agenda_instalaciones
--   ADD COLUMN IF NOT EXISTS terminado_at TIMESTAMP NULL;
-- ALTER TABLE public.agenda_instalaciones
--   ADD COLUMN IF NOT EXISTS evidencia_dispositivo_id INTEGER NULL;

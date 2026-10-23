# 💊 Droguería Vida Sana — ERP Farmacéutico
**Segundo Parcial de Software de Gestión Empresarial**  
**Institución:** Corporación de Estudios Tecnológicos del Norte del Valle (COTECNOVA) — 2026  
**Integrantes:**
* **Jhon Esteban Molina Echavarría** — Análisis de procesos, modelo de datos (diagrama ER y diccionario), migración y modelo `Lot`, documentación y validación de pruebas.
* **Heiber Lozano Mercado** — Matriz de seguridad y roles con Spatie (`DrogueriaRolesSeeder`) e implementación del servicio transaccional FEFO (`DispensingService`).

**Repositorio Oficial:** [https://github.com/moliech/parcial2](https://github.com/moliech/parcial2)  
**Ramas sincronizadas:** Tanto `main` como `parcial2` contienen exactamente la misma versión final aprobada y lista para calificación.

---

## 🧭 Índice Rápido de Entregables (Criterios de Evaluación)

| Criterio (% Rúbrica) | Descripción del Entregable | Enlace Directo |
| :--- | :--- | :--- |
| **1. Análisis de Procesos (20%)** | Flujo operativo Procure-to-FEFO, 7 reglas de negocio farmacéuticas, ciclos de estados (compras y ventas con efecto en stock) y 3 KPIs con fórmulas KaTeX. | [📄 `docs/parcial2/analisis_drogueria.md`](docs/parcial2/analisis_drogueria.md) |
| **2. Modelo de Datos (20%)** | Diagrama Entidad-Relación y Diccionario de datos de 3 tablas clave (`lots`, `purchase_details`, `sale_details`) con justificación de trazabilidad por `lot_id`. | [🖼️ `docs/parcial2/er_drogueria.png`](docs/parcial2/er_drogueria.png)<br>[📖 Diccionario de Datos](docs/parcial2/analisis_drogueria.md#5-diseño-del-modelo-de-datos) |
| **3. Seguridad y RBAC (20%)** | Matriz canónica Rol × Módulo × Acción (4 roles), justificación de segregación de funciones, resolución del caso práctico y Seeder Spatie ejecutable. | [🛡️ Matriz de Seguridad](docs/parcial2/analisis_drogueria.md#6-matriz-de-seguridad)<br>[⚙️ `database/seeders/DrogueriaRolesSeeder.php`](database/seeders/DrogueriaRolesSeeder.php) |
| **4. Implementación en Laravel (40%)** | Migración `lots`, modelo `Lot`, servicio transaccional `DispensingService` con FEFO estricto y capturas de prueba en MySQL y Tinker. | [📦 Migración `lots`](database/migrations/2026_10_23_020729_create_lots_table.php)<br>[🧩 Modelo `Lot.php`](app/Models/Lot.php)<br>[⚡ `DispensingService.php`](app/Services/DispensingService.php)<br>[📸 Galería de Capturas](#-evidencias-de-la-implementación) |

---

## 📌 Contexto de la Empresa y Problemática
La **Droguería Vida Sana**, ubicada en Cartago (Valle del Cauca), comercializa medicamentos de venta libre, fórmula médica, medicamentos de control especial y productos de cuidado personal.

El sistema resuelve tres problemáticas críticas de gestión farmacéutica:
1. **Control estricto de vencimientos y dispensación FEFO (*First Expired, First Out*):** Garantizar que nunca se despache un medicamento vencido y priorizar automáticamente la salida de aquellos lotes con fecha de caducidad más cercana.
2. **Medicamentos de control especial y trazabilidad:** Exigir supervisión exclusiva del Regente de Farmacia y trazabilidad por lote unitario en los renglones de venta.
3. **Segregación de funciones:** El usuario que autoriza y emite la orden de compra no puede ser el mismo que ejecuta la recepción física de la mercancía en bodega.

---

## 📸 Evidencias de la Implementación

### 1. Estructura de la Tabla `lots` en MySQL (`DESCRIBE lots;`)
Verificación de los tipos de datos, claves primarias, foráneas e índices de rendimiento en MySQL 8.4:
![DESCRIBE lots](docs/parcial2/capturas/Captura1.png)

---

### 2. Código Fuente de Componentes Clave

| Componente | Archivo Fuente | Captura de Código |
| :--- | :--- | :--- |
| **Modelo `Lot`** | [`app/Models/Lot.php`](app/Models/Lot.php) | ![Modelo Lot](docs/parcial2/capturas/Captura2.png) |
| **Seeder Spatie** | [`database/seeders/DrogueriaRolesSeeder.php`](database/seeders/DrogueriaRolesSeeder.php) | ![Seeder Roles](docs/parcial2/capturas/Captura3.png) |
| **Servicio FEFO** | [`app/Services/DispensingService.php`](app/Services/DispensingService.php) | ![Servicio Dispensing](docs/parcial2/capturas/Captura4.png) |

---

### 3. Pruebas de Despacho y Validación en Laravel Tinker

#### A. Estado Inicial de los Lotes (Antes del Despacho)
Creación de 3 lotes de prueba para *Amoxicilina 500mg*:
* `LOT-EXP-01`: Vencido hace 15 días (10 unidades).
* `LOT-PROX-02`: Próximo a vencer en 30 días (5 unidades).
* `LOT-LEJ-03`: Vence en 1 año (15 unidades).

![Lotes Antes del Despacho](docs/parcial2/capturas/Captura5.png)

#### B. Despacho Aplicando FEFO (Después del Despacho)
Se despachan **8 unidades** mediante `$service->dispense($prod, 8)`. El servicio:
1. Omite `LOT-EXP-01` por estar vencido (conserva sus 10 unidades intactas).
2. Consume completamente `LOT-PROX-02` (5 unidades descontadas, queda en 0).
3. Descuenta 3 unidades de `LOT-LEJ-03` (quedan 12 unidades disponibles).

![Lotes Después del Despacho](docs/parcial2/capturas/Captura6.png)

#### C. Error por Stock Insuficiente en Lotes Vigentes
Al solicitar despachar **20 unidades** cuando solo restan 12 unidades vigentes, el sistema cancela la transacción y dispara de manera segura una excepción de validación:

![Excepción Stock Insuficiente](docs/parcial2/capturas/Captura7.png)

---

## 🚀 Puesta en Marcha Rápida (Entorno Local)

Para reproducir el entorno y ejecutar las pruebas:

```bash
# 1. Levantar contenedores con Laravel Sail
./vendor/bin/sail up -d

# 2. Ejecutar migraciones
./vendor/bin/sail php artisan migrate

# 3. Poblar roles y permisos con Spatie
./vendor/bin/sail php artisan db:seed --class=DrogueriaRolesSeeder

# 4. Probar dispensación en consola interactiva Tinker
./vendor/bin/sail php artisan tinker
```

# Análisis de la Droguería Vida Sana
**Asignatura:** Software de Gestión Empresarial — Segundo Parcial  
**Institución:** COTECNOVA 2026  
**Integrantes:** Esteban Molina & Heiber Lozano  
**Repositorio:** [https://github.com/moliech/parcial2](https://github.com/moliech/parcial2)  
**Rama Oficial de Entrega:** `parcial2`

---

## 1. Flujo integrado

### Descripción paso a paso (Procure-to-FEFO)
1. **Detección de Necesidad de Abastecimiento:**
   El sistema identifica que el stock disponible vigente de un medicamento ha llegado a su punto mínimo de reorden o ha agotado sus lotes no vencidos.
2. **Generación de la Orden de Compra:**
   El usuario con rol `admin` (dueño/compras) genera una Orden de Compra dirigida al proveedor (por ejemplo, *«Distribuidora Farmacéutica del Valle»*), registrando productos, cantidades y precios acordados. La orden queda en estado **Pendiente**.
3. **Recepción Física por Lote en Bodega:**
   La mercancía llega a la droguería (en una o varias entregas). El usuario con rol `auxiliar-bodega` descarga físicamente las cajas y registra en el sistema los números de lote y sus respectivas fechas de vencimiento de cada caja recibida.
4. **Inspección Técnica y Aprobación Sanitaria:**
   El `regente` de farmacia (responsable técnico) inspecciona físicamente el estado de los empaques, verifica las fechas de caducidad contra la documentación del laboratorio y autoriza el ingreso formal al inventario sanitario.
5. **Actualización de Inventario:**
   El sistema inserta los registros en la tabla `lots` (asociados al producto) con su cantidad y fecha de vencimiento, incrementando de manera atómica el stock general del producto.
6. **Venta y Dispensación FEFO (POS):**
   El cliente solicita el medicamento en mostrador. El `cajero` ingresa la venta en el sistema. Automáticamente el servicio `DispensingService` valida la disponibilidad, ignora lotes vencidos, y descuenta unidades priorizando el lote con fecha de caducidad más cercana (**FEFO**: *First Expired, First Out*). Si se trata de un medicamento de control especial, el sistema exige la autorización y firma digital/token del `regente`.
7. **Emisión de Documento Contable y Trazabilidad:**
   Se genera el ticket o factura de venta registrando en `sale_details` la relación con `lot_id` para garantizar la trazabilidad sanitaria individual.
8. **Alimentación de Indicadores (KPIs):**
   La transacción actualiza inmediatamente los tableros de control gerencial del dueño: lotes próximos a vencer, rotación de existencias y mermas por vencimiento.

---

## 2. Reglas de negocio

| # | Regla de Negocio | Módulo | DÓNDE se implementa | Mecanismo Técnico en Laravel |
|---|---|---|---|---|
| **R1** | **Nunca vender un lote vencido:** Un lote cuya fecha de caducidad sea anterior a la fecha actual queda inhabilitado para despacho. | Ventas / POS | **Servicio** (`DispensingService`) | Filtro de consulta: `where('expiration_date', '>=', now()->toDateString())`. |
| **R2** | **Despacho estricto por FEFO (*First Expired, First Out*):** Siempre se deben consumir primero las existencias del lote no vencido con expiración más próxima. | Inventario / Ventas | **Servicio** (`DispensingService`) | Ordenamiento: `orderBy('expiration_date', 'asc')`. |
| **R3** | **Medicamentos de control especial requieren autorización del regente:** No se puede completar el despacho si no existe aprobación del responsable técnico. | Ventas / Dispensación | **Middleware & Form Request** | Middleware `EnsureRegenteApproval` y validación en `SaleRequest`. |
| **R4** | **Segregación de funciones en abastecimiento:** La persona que crea una orden de compra no puede ser la misma que la recibe y valida en bodega. | Compras / Bodega | **Policy & Middleware** | `PurchasePolicy@receive`: valida que `$user->id !== $purchase->user_id`. |
| **R5** | **Validación previa de stock no vencido:** Si la cantidad solicitada supera la sumatoria de lotes vigentes, la transacción debe cancelarse completamente sin alterar el stock. | Ventas POS | **Servicio & Form Request** | Validación atómica con `DB::transaction` y lanzamiento de `ValidationException`. |
| **R6** | **Restricción de vencimiento en compras:** No se permite recibir en bodega ningún lote cuya fecha de caducidad sea inferior o igual a la fecha de recepción. | Compras / Recepción | **Form Request** (`ReceiveLotRequest`) | Regla de validación: `'expiration_date' => 'required|date|after:today'`. |
| **R7** | **Semáforo de vencimientos en inventario:** Marcar visualmente en amarillo lotes a vencer en &le; 60 días y en rojo a &le; 30 días. | Inventario / Catálogo | **Vista** (Blade) | Directivas Blade condicionales con badges de color en la tabla de lotes. |

---

## 3. Estados

### Estados de una Orden de Compra y su impacto en inventario:
* **Pendiente:** La orden ha sido emitida y enviada al proveedor. **Impacto en inventario:** Ninguno (el stock real no cambia; puede registrarse como stock proyectado).
* **Recepción Parcial:** El distribuidor entregó una parte de la mercancía. **Impacto en inventario:** Se crean los lotes de las unidades efectivamente recibidas y se incrementa el stock únicamente en esa cantidad verificada.
* **Recibida / Completada:** Se recibió el 100% de la mercancía o se cerró la entrega tras inspección técnica del regente. **Impacto en inventario:** Se consolida el aumento total del stock y los lotes quedan activos para venta.
* **Cancelada:** La orden fue rechazada o anulada antes de recibir mercancía. **Impacto en inventario:** Ninguno.

### Estados de una Venta y su impacto en inventario:
* **En Proceso / Pendiente:** Los productos están agregados al carrito o en caja pendientes de pago. **Impacto en inventario:** Bloqueo transaccional temporal (`lockForUpdate`) para evitar condiciones de carrera, sin descontar definitivamente.
* **Completada / Pagada:** Se confirma el pago y se expide el documento de venta. **Impacto en inventario:** Se descuenta de forma atómica e irreversible la cantidad de los lotes vigentes mediante FEFO y se reduce el stock general del producto.
* **Anulada / Devuelta:** Se autoriza una devolución por parte del regente. **Impacto en inventario:** Se reintegran las unidades al lote original de donde salieron, siempre y cuando la fecha de expiración del lote aún sea válida al momento del reingreso.

---

## 4. Indicadores (KPIs)

1. **Unidades y Valor de Lotes Próximos a Vencer (&le; 30 días):**
   * *Objetivo:* Alertar al dueño y regente para activar promociones, devoluciones a laboratorios o rotación prioritaria antes de que el medicamento se convierta en pérdida total.
   * *Fórmula:* `SUM(quantity * cost_price) WHERE expiration_date BETWEEN now() AND now() + INTERVAL 30 DAY`.
2. **Índice de Merma Sanitaria por Medicamentos Vencidos (%):**
   * *Objetivo:* Medir la ineficiencia de inventario y pérdidas económicas causadas por lotes caducados que deben destruirse legalmente.
   * *Fórmula:* `(Costo total de medicamentos vencidos dados de baja en el mes / Costo total del inventario promedio) * 100`.
3. **Porcentaje de Efectividad FEFO (% Cumplimiento):**
   * *Objetivo:* Auditar que el 100% de los despachos automáticos del sistema respetaron la fecha de expiración más antigua sin saltarse lotes.
   * *Fórmula:* `(Total de dispensaciones conformes a FEFO / Total de dispensaciones realizadas) * 100`.

---

## 5. Diseño del Modelo de Datos

### Diagrama Entidad-Relación
El diagrama oficial con cardinalidades se encuentra en: `docs/parcial2/er_drogueria.png`.

### Diccionario de Datos

#### Tabla: `lots`
| Campo | Tipo | Nulo | Clave / Índice | Descripción | Restricciones |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | PK, AUTO_INCREMENT | Identificador único del lote | Valor positivo autoincremental |
| `product_id` | BIGINT UNSIGNED | NO | FK | Medicamento al que pertenece el lote | Llave foránea hacia `products(id)` con `ON DELETE CASCADE` |
| `lot_number` | VARCHAR(100) | NO | UNIQUE COMPUESTO | Número de lote provisto por el laboratorio | Obligatorio, no vacío |
| `expiration_date` | DATE | NO | INDEX | Fecha de vencimiento impresa por laboratorio | Formato YYYY-MM-DD, indexada |
| `quantity` | INT UNSIGNED | NO | - | Cantidad de unidades físicas vigentes en el lote | Default `0`, `CHECK (quantity >= 0)` |
| `created_at` / `updated_at` | TIMESTAMP | SÍ | - | Marcas de tiempo de auditoría | Estándar de Laravel |
* **Índice Único Compuesto:** `UNIQUE KEY unique_product_lot (product_id, lot_number)` — Evita duplicidad del mismo lote en el mismo medicamento.

#### Tabla: `purchase_details`
| Campo | Tipo | Nulo | Clave / Índice | Descripción | Restricciones |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | PK, AUTO_INCREMENT | Identificador del renglón de compra | Autoincremental |
| `purchase_id` | BIGINT UNSIGNED | NO | FK | Orden de compra cabecera | FK hacia `purchases(id)` con `ON DELETE CASCADE` |
| `product_id` | BIGINT UNSIGNED | NO | FK | Medicamento o insumo comprado | FK hacia `products(id)` con `ON DELETE RESTRICT` |
| `quantity` | INT UNSIGNED | NO | - | Cantidad de unidades adquiridas | `CHECK (quantity > 0)` |
| `unit_price` | DECIMAL(12,2) | NO | - | Costo unitario de compra acordado | `CHECK (unit_price >= 0)` |
| `created_at` / `updated_at` | TIMESTAMP | SÍ | - | Marcas de tiempo | Estándar Laravel |

#### Tabla: `sale_details`
| Campo | Tipo | Nulo | Clave / Índice | Descripción | Restricciones |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | NO | PK, AUTO_INCREMENT | Identificador del ítem facturado | Autoincremental |
| `sale_id` | BIGINT UNSIGNED | NO | FK | Factura o venta cabecera | FK hacia `sales(id)` con `ON DELETE CASCADE` |
| `product_id` | BIGINT UNSIGNED | NO | FK | Medicamento dispensado | FK hacia `products(id)` con `ON DELETE RESTRICT` |
| `lot_id` | BIGINT UNSIGNED | NO | FK | Lote específico del cual se debitaron las unidades | FK hacia `lots(id)` con `ON DELETE RESTRICT` |
| `quantity` | INT UNSIGNED | NO | - | Cantidad de unidades vendidas de ese lote | `CHECK (quantity > 0)` |
| `unit_price` | DECIMAL(12,2) | NO | - | Precio de venta cobrado por unidad | `CHECK (unit_price >= 0)` |
| `created_at` / `updated_at` | TIMESTAMP | SÍ | - | Marcas de tiempo | Estándar Laravel |

### Pregunta Técnica: ¿En qué tabla se guarda de qué lote salió cada unidad vendida? Justificar.
* **Respuesta:** Se guarda en la tabla **`sale_details`** mediante la columna foránea **`lot_id`** vinculada a `lots(id)`.
* **Justificación:**  
  En la industria farmacéutica y bajo los estándares regulatorios del INVIMA, es un mandato legal garantizar la **trazabilidad bidireccional**. Como un mismo producto puede despacharse utilizando unidades de múltiples lotes debido a la rotación FEFO (por ejemplo, una venta de 8 tabletas de *Amoxicilina* que toma 5 tabletas del lote `L2` que vence el próximo mes y 3 tabletas del lote `L3` que vence el próximo año), la asignación del lote debe ocurrir a nivel de detalle de venta. De este modo, si las autoridades sanitarias ordenan el retiro preventivo (*recall*) de un lote contaminado o defectuoso, el sistema permite consultar en cuestión de segundos exactamente en qué facturas, a qué clientes y en qué fechas fue dispensado dicho lote.

---

## 6. Matriz de seguridad

### Matriz Rol × Módulo × Acción
| Módulo | Acción | admin (Dueño) | regente | cajero | auxiliar-bodega |
|---|---|:---:|:---:|:---:|:---:|
| **Productos** | Ver catálogo | ✅ | ✅ | ✅ | ✅ |
| | Crear / Editar producto | ✅ | ✅ (Ficha técnica) | ❌ | ❌ |
| | Eliminar / Dar de baja | ✅ | ❌ | ❌ | ❌ |
| **Lotes** | Ver lotes y vencimientos | ✅ | ✅ | ✅ (Consulta) | ✅ |
| | Crear lote (recepción) | ❌ (Segregación) | ❌ | ❌ | ✅ |
| | Ajustar / Cuarentena lote | ✅ | ✅ (Inspección) | ❌ | ❌ |
| **Compras** | Crear / Aprobar orden | ✅ | ❌ | ❌ | ❌ |
| | Ver órdenes de compra | ✅ | ✅ | ❌ | ✅ (Para recibir) |
| **Bodega** | Recibir lotes de compra | ❌ (Segregación) | ✅ (Aprobación técnica) | ❌ | ✅ (Descarga física) |
| **Ventas POS** | Crear venta y cobrar | ❌ | ❌ | ✅ | ❌ |
| | Anular factura | ✅ | ❌ | ❌ | ❌ |
| **Control Especial** | Dispensar control especial | ❌ | ✅ (Autorización técnica) | Solo con regente | ❌ |
| **Indicadores** | Ver Dashboard y KPIs | ✅ | ✅ (Sanitarios) | ❌ | ❌ |

### Justificación de la Segregación de Funciones
La segregación de funciones exige que la persona que crea y autoriza una orden de compra **no tenga el permiso para recibir la mercancía físicamente en bodega**.  
En la matriz:
* El rol `admin` tiene `crear-compras` y `aprobar-compras`, pero **no posee** el permiso `recibir-lotes`.
* El rol `auxiliar-bodega` posee `recibir-lotes`, pero **carece** del permiso de `crear-compras`.  
Esta separación impide la creación de compras ficticias a distribuidores inexistentes, colusión con proveedores o alteración fraudulenta de existencias sin verificación física.

### Caso Práctico: El regente cubre la caja los sábados
1. **¿Se le asignaría un segundo rol o un permiso directo? ¿Por qué?**  
   Se le debe asignar un **segundo rol (`cajero`)** mediante el paquete Spatie:
   ```php
   $user->assignRole(['regente', 'cajero']);
   ```
   **Justificación:** Otorgar permisos directos individuales a un usuario fragmenta la arquitectura RBAC (*Role-Based Access Control*), dificulta las auditorías sanitarias y genera acumulación descontrolada de privilegios (*permission creep*). Al asignarle el rol completo de `cajero`, sus funciones de venta quedan tipificadas, estandarizadas y pueden removerse fácilmente al culminar el turno sin tocar su perfil principal de regente.
2. **¿Qué rol elegiría al iniciar sesión?**  
   El usuario inicia sesión con el rol activo de **`cajero`** para operar la caja registradora y emitir comprobantes de venta. Si durante el turno se requiere dispensar un medicamento de control especial, el sistema reconoce que el usuario autenticado también posee el rol `regente`, permitiéndole auto-autorizar la transacción previa verificación de su contraseña o código de seguridad, dejando el rastro en el log de auditoría.

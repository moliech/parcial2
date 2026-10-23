# 💊 Droguería Vida Sana — ERP Farmacéutico
**Segundo Parcial de Software de Gestión Empresarial**  
**Institución:** COTECNOVA 2026  
**Integrantes:** Jhon Esteban Molina Echavarria & Heiber Lozano Mercado  
**Repositorio Oficial:** [https://github.com/moliech/parcial2](https://github.com/moliech/parcial2)  

---

## 📌 Contexto de la Empresa y Problemática
La **Droguería Vida Sana**, ubicada en Cartago (Valle del Cauca), comercializa medicamentos de venta libre, fórmula médica, medicamentos de control especial y productos de cuidado personal.

El sistema reemplaza el control previo en hojas de cálculo para resolver dos problemas críticos:
1. **Control estricto de vencimientos y dispensación FEFO:** Garantizar que nunca se venda un producto vencido y despachar siempre primero el lote que vence antes (*First Expired, First Out*).
2. **Control especial y trazabilidad:** Exigir autorización y supervisión del Regente de Farmacia en medicamentos de control especial y trazabilidad por lote unitario.
3. **Segregación de funciones:** El usuario que emite la orden de compra no puede ser el mismo que realiza la recepción física de mercancía en bodega.

---

## 🛠️ Tecnologías y Arquitectura Base
* **Framework:** Laravel 11 / 12 con PHP 8.3 / 8.5
* **Entorno:** Laravel Sail (Docker + MySQL 8.4)
* **Seguridad y Control de Acceso (RBAC):** `spatie/laravel-permission`
* **Transacciones ACID:** `DB::transaction` en capa de servicios
* **Gestión Documental:** Documentación técnica oficial en `docs/parcial2/`

---

## 🗄️ Entidades Base del Sistema
* `users`: Usuarios y autenticación del personal.
* `categories`: Categorías farmacéuticas (Venta libre, Control especial, etc.).
* `products`: Medicamentos y productos de mostrador con control de stock general.
* `providers`: Distribuidores farmacéuticos mayoristas.
* `clients`: Clientes / pacientes registrados.
* `purchases` y `purchase_details`: Órdenes de abastecimiento y renglones de compra.
* `sales` y `sale_details`: Ventas en punto de venta (POS) y trazabilidad por lote.
* `permissions` y `roles`: Matriz de seguridad RBAC de Spatie.

---

## 🌿 Ramas de Trabajo
* `main`: Rama de trabajo de Esteban Molina (Documentación, Gráfica ER, Modelo Lot y pruebas).
* `heiber`: Rama de trabajo de Heiber Lozano (Seeder Spatie y Servicio transaccional FEFO).
* `parcial2`: Rama de entrega final oficial requerida para calificación.

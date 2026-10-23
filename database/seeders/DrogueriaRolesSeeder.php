<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DrogueriaRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar la cache de permisos de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Permisos del Sistema
        $permissions = [
            'ver-productos', 'crear-productos', 'editar-productos', 'eliminar-productos',
            'ver-lotes', 'crear-lotes', 'ajustar-lotes',
            'ver-compras', 'crear-compras', 'aprobar-compras', 'cancelar-compras',
            'recibir-lotes', // Acción propia de bodega
            'dispensar-control-especial', // Acción propia de regente
            'ver-ventas', 'crear-ventas', 'anular-ventas',
            'ver-kpis',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // 2. Roles y Sincronización
        // ROL 1: Administrador (Dueño) -> Sin permiso de 'recibir-lotes' (Segregación de funciones)
        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions([
            'ver-productos', 'crear-productos', 'editar-productos', 'eliminar-productos',
            'ver-lotes', 'ver-compras', 'crear-compras', 'aprobar-compras', 'cancelar-compras',
            'ver-ventas', 'anular-ventas', 'ver-kpis'
        ]);

        // ROL 2: Regente de Farmacia (Responsable Técnico)
        $regente = Role::findOrCreate('regente', 'web');
        $regente->syncPermissions([
            'ver-productos', 'editar-productos',
            'ver-lotes', 'ajustar-lotes',
            'ver-compras',
            'recibir-lotes',
            'dispensar-control-especial',
            'ver-ventas',
            'ver-kpis',
        ]);

        // ROL 3: Cajero
        $cajero = Role::findOrCreate('cajero', 'web');
        $cajero->syncPermissions([
            'ver-productos',
            'ver-lotes',
            'ver-ventas',
            'crear-ventas',
        ]);

        // ROL 4: Auxiliar de Bodega
        $auxiliar = Role::findOrCreate('auxiliar-bodega', 'web');
        $auxiliar->syncPermissions([
            'ver-productos',
            'ver-lotes',
            'crear-lotes',
            'ver-compras',
            'recibir-lotes',
        ]);
    }
}

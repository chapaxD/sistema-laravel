<?php

namespace App\Helpers;

class PermissionHelper
{
    /**
     * Definición de permisos por rol
     */
    private static $permissions = [
        'Administrador' => [
            'modules' => ['dashboard', 'users', 'inventory', 'services', 'quotations', 'work-orders', 'sales', 'receipts', 'payment-plans'],
            'actions' => ['create', 'read', 'update', 'delete'],
            'scope' => 'all', // Ver todos los registros
        ],
        'Secretaria' => [
            'modules' => ['dashboard', 'inventory', 'services', 'quotations', 'work-orders', 'sales', 'receipts', 'payment-plans'],
            'actions' => ['create', 'read', 'update', 'delete'],
            'scope' => 'all',
            'readonly' => ['users'], // Solo lectura en usuarios
        ],
        'Vendedor' => [
            'modules' => ['dashboard', 'quotations', 'sales', 'receipts', 'payment-plans'],
            'actions' => ['create', 'read', 'update', 'delete'],
            'scope' => 'own', // Solo sus propios registros
            'readonly' => ['inventory', 'services'],
        ],
        'Técnico' => [
            'modules' => ['dashboard', 'work-orders'],
            'actions' => ['read', 'update'],
            'scope' => 'assigned', // Solo órdenes asignadas
            'readonly' => ['inventory', 'services'],
        ],
        'Contador' => [
            'modules' => ['dashboard', 'sales', 'receipts', 'payment-plans'],
            'actions' => ['create', 'read', 'update', 'delete'],
            'scope' => 'all',
            'readonly' => ['quotations'],
        ],
        'Cliente' => [
            'modules' => ['dashboard', 'quotations', 'work-orders', 'receipts', 'payment-plans'],
            'actions' => ['read'],
            'scope' => 'own',
        ],
    ];

    /**
     * Verificar si un rol puede acceder a un módulo
     */
    public static function canAccessModule($role, $module)
    {
        if (!isset(self::$permissions[$role])) {
            return false;
        }

        $rolePerms = self::$permissions[$role];
        
        // Verificar si está en módulos principales
        if (in_array($module, $rolePerms['modules'])) {
            return true;
        }

        // Verificar si está en readonly
        if (isset($rolePerms['readonly']) && in_array($module, $rolePerms['readonly'])) {
            return true;
        }

        return false;
    }

    /**
     * Verificar si un rol puede realizar una acción
     */
    public static function canPerformAction($role, $action, $module)
    {
        if (!isset(self::$permissions[$role])) {
            return false;
        }

        $rolePerms = self::$permissions[$role];

        // Si el módulo está en readonly, solo puede leer
        if (isset($rolePerms['readonly']) && in_array($module, $rolePerms['readonly'])) {
            return $action === 'read';
        }

        // Verificar si puede acceder al módulo
        if (!self::canAccessModule($role, $module)) {
            return false;
        }

        // Verificar si puede realizar la acción
        return in_array($action, $rolePerms['actions']);
    }

    /**
     * Obtener el scope de datos para un rol
     */
    public static function getDataScope($role)
    {
        if (!isset(self::$permissions[$role])) {
            return 'none';
        }

        return self::$permissions[$role]['scope'] ?? 'none';
    }

    /**
     * Obtener todos los módulos accesibles para un rol
     */
    public static function getAccessibleModules($role)
    {
        if (!isset(self::$permissions[$role])) {
            return [];
        }

        $rolePerms = self::$permissions[$role];
        $modules = $rolePerms['modules'];

        if (isset($rolePerms['readonly'])) {
            $modules = array_merge($modules, $rolePerms['readonly']);
        }

        return array_unique($modules);
    }

    /**
     * Verificar si un módulo es de solo lectura para un rol
     */
    public static function isReadOnly($role, $module)
    {
        if (!isset(self::$permissions[$role])) {
            return true;
        }

        $rolePerms = self::$permissions[$role];
        
        return isset($rolePerms['readonly']) && in_array($module, $rolePerms['readonly']);
    }
}

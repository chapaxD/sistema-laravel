import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();
    const user = computed(() => page.props.auth.user);
    const userRole = computed(() => user.value?.rol);

    // Definición de permisos (debe coincidir con PermissionHelper.php)
    const permissions = {
        'Administrador': {
            modules: ['dashboard', 'users', 'inventory', 'services', 'quotations', 'work-orders', 'sales', 'receipts', 'payment-plans'],
            actions: ['create', 'read', 'update', 'delete'],
            readonly: []
        },
        'Secretaria': {
            modules: ['dashboard', 'inventory', 'services', 'quotations', 'work-orders', 'sales', 'receipts', 'payment-plans'],
            actions: ['create', 'read', 'update', 'delete'],
            readonly: ['users']
        },
        'Vendedor': {
            modules: ['dashboard', 'quotations', 'sales', 'receipts', 'payment-plans'],
            actions: ['create', 'read', 'update', 'delete'],
            readonly: ['inventory', 'services']
        },
        'Técnico': {
            modules: ['dashboard', 'work-orders'],
            actions: ['read', 'update'],
            readonly: ['inventory', 'services']
        },
        'Contador': {
            modules: ['dashboard', 'sales', 'receipts', 'payment-plans'],
            actions: ['create', 'read', 'update', 'delete'],
            readonly: ['quotations']
        },
        'Cliente': {
            modules: ['dashboard', 'quotations', 'work-orders', 'receipts', 'payment-plans'],
            actions: ['read'],
            readonly: []
        }
    };

    /**
     * Verificar si el usuario tiene un rol específico
     */
    const hasRole = (role) => {
        return userRole.value === role;
    };

    /**
     * Verificar si el usuario tiene alguno de los roles especificados
     */
    const hasAnyRole = (roles) => {
        return roles.includes(userRole.value);
    };

    /**
     * Verificar si el usuario puede acceder a un módulo
     */
    const canAccessModule = (module) => {
        if (!userRole.value || !permissions[userRole.value]) {
            return false;
        }

        const rolePerms = permissions[userRole.value];
        return rolePerms.modules.includes(module) || rolePerms.readonly.includes(module);
    };

    /**
     * Verificar si el usuario puede realizar una acción en un módulo
     */
    const canPerformAction = (action, module) => {
        if (!userRole.value || !permissions[userRole.value]) {
            return false;
        }

        const rolePerms = permissions[userRole.value];

        // Si el módulo es de solo lectura, solo puede leer
        if (rolePerms.readonly.includes(module)) {
            return action === 'read';
        }

        // Verificar si puede acceder al módulo
        if (!canAccessModule(module)) {
            return false;
        }

        // Verificar si puede realizar la acción
        return rolePerms.actions.includes(action);
    };

    /**
     * Verificar si un módulo es de solo lectura
     */
    const isReadOnly = (module) => {
        if (!userRole.value || !permissions[userRole.value]) {
            return true;
        }

        const rolePerms = permissions[userRole.value];
        return rolePerms.readonly.includes(module);
    };

    /**
     * Obtener todos los módulos accesibles
     */
    const getAccessibleModules = () => {
        if (!userRole.value || !permissions[userRole.value]) {
            return [];
        }

        const rolePerms = permissions[userRole.value];
        return [...rolePerms.modules, ...rolePerms.readonly];
    };

    return {
        user,
        userRole,
        hasRole,
        hasAnyRole,
        canAccessModule,
        canPerformAction,
        isReadOnly,
        getAccessibleModules
    };
}

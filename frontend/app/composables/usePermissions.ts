import type { User, Permission } from '~/types/models';

export const usePermissions = () => {
    const { user } = useAuth();

    const hasPermission = (permissionName: string): boolean => {
        if (!user.value) return false;

        const currentUser = user.value as User;

        // Admin override (optional but recommended safety net)
        if (currentUser.role?.name === 'admin') return true;

        const permissions = currentUser.role?.permissions || [];
        return permissions.some((p: Permission) => p.name === permissionName);
    };

    const hasRole = (roleName: string): boolean => {
        if (!user.value) return false;
        const currentUser = user.value as User;
        return currentUser.role?.name === roleName;
    }

    return {
        hasPermission,
        hasRole
    };
};

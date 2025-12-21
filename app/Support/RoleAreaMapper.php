<?php

namespace App\Support;

final class RoleAreaMapper
{
    // role_id => area_id
    private const MAP = [
        9  => 1,  // Cloud Services Support -> Área 1
        11 => 1,  // Service Support Cloud Coordinator -> Área 1

        16 => 4,  // Comunicaciones 1 -> Área 4
        17 => 4,  // Comunicaciones 2 -> Área 4

        14 => 2,  // Infraestructura 1 -> Área 2
        15 => 2,  // Infraestructura 2 -> Área 2
    ];

    public static function areaIdFromRoleId(?int $roleId): ?int
    {
        return $roleId ? (self::MAP[$roleId] ?? null) : null;
    }

    // ✅ para filtrar roles por área (create)
    public static function roleIdsForArea(?int $areaId): array
    {
        if (!$areaId) return [];

        $roleIds = [];
        foreach (self::MAP as $roleId => $mappedAreaId) {
            if ((int)$mappedAreaId === (int)$areaId) {
                $roleIds[] = (int)$roleId;
            }
        }
        return $roleIds;
    }

    // ✅ para validar rápido (store/update)
    public static function roleBelongsToArea(int $roleId, int $areaId): bool
    {
        return (int)(self::MAP[$roleId] ?? 0) === (int)$areaId;
    }
}

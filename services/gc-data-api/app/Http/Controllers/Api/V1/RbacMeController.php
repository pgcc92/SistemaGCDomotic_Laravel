<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Rbac\RbacService;
use Illuminate\Http\JsonResponse;

final class RbacMeController
{
    public function __construct(
        private readonly RbacService $rbac,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        $uid = (int) request()?->attributes->get('remote_uid', 0);
        if ($uid <= 0) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        $perms = $this->rbac->permissionsForUser($uid);
        $canViewAllAgenda = $this->rbac->canViewAll($uid, 'agenda');

        return response()->json([
            'ok' => true,
            'data' => [
                'uid' => $uid,
                'can_view_all' => [
                    'agenda' => $canViewAllAgenda,
                ],
                'permissions' => $perms,
            ],
        ]);
    }
}

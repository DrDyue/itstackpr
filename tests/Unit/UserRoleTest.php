<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_legacy_it_worker_role_is_treated_as_admin(): void
    {
        $user = new User([
            'role' => User::ROLE_IT_WORKER,
        ]);

        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->canManageRequests());
    }
}

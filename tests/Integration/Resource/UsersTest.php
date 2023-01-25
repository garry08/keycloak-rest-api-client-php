<?php

declare(strict_types=1);

namespace Fschmtt\Keycloak\Test\Integration\Resource;

use Exception;
use Fschmtt\Keycloak\Http\Criteria;
use Fschmtt\Keycloak\Representation\Group;
use Fschmtt\Keycloak\Representation\Role;
use Fschmtt\Keycloak\Representation\User;
use Fschmtt\Keycloak\Test\Integration\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class UsersTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testImportSearchUpdateDeleteUser(): void
    {
        $resource = $this->getKeycloak()->users();
        $importedUsername = Uuid::uuid4()->toString();
        $importedFirstName = Uuid::uuid4()->toString();
        $updatedFirstName = Uuid::uuid4()->toString();

        // Get all users
        $allUsers = $resource->all('master');
        static::assertGreaterThanOrEqual(1, $allUsers->count());
        $user = $allUsers->first();
        static::assertInstanceOf(User::class, $user);

        // Create user
        $resource->create(
            'master',
            new User(firstName: $importedFirstName, username: $importedUsername)
        );

        // Search (imported) user
        $importedUser = $resource->search('master', new Criteria([
            'username' => $importedUsername,
            'exact' => true,
        ]))->first();
        static::assertInstanceOf(User::class, $importedUser);
        static::assertEquals($importedFirstName, $importedUser->getFirstName());

        // Get single (imported) user
        $importedUser = $resource->get('master', $importedUser->getId());
        static::assertSame($importedUsername, $importedUser->getUsername());

        // Update (imported) user
        $resource->update('master', $importedUser->getId(), $importedUser->withFirstName($updatedFirstName));

        $updatedUser = $resource->search('master', new Criteria([
            'username' => $importedUsername,
            'exact' => true,
        ]))->first();
        static::assertInstanceOf(User::class, $updatedUser);
        static::assertSame($updatedFirstName, $updatedUser->getFirstName());

        // Delete (imported) user
        $resource->delete('master', $updatedUser->getId());

        try {
            $resource->get('master', $updatedUser->getId());
            static::fail('User should not exist anymore');
        } catch (Exception $e) {
            static::assertSame(404, $e->getCode());
        }
    }

    public function testJoinRetrieveLeaveGroupUser(): void
    {
        $users = $this->getKeycloak()->users();
        $user = $users->all('master')->first();

        // create a temp group required for our test
        $groups = $this->getKeycloak()->groups();
        $groupName = Uuid::uuid4()->toString();
        $groups->create(
            'master',
            new Group(name: $groupName),
        );
        $group = $groups->all('master')->first();

        // join group
        $users->joinGroup('master', $user->getId(), $group->getId());

        $userGroups = $users->retrieveGroups('master', $user->getId());
        static::assertGreaterThanOrEqual(1, $userGroups->count());
        $userFirstGroup = $userGroups->first();
        static::assertInstanceOf(Group::class, $userFirstGroup);
        static::assertSame($group->getId(), $userFirstGroup->getId());

        // leave group
        $users->leaveGroup('master', $user->getId(), $group->getId());

        $userGroups = $users->retrieveGroups('master', $user->getId());
        static::assertGreaterThanOrEqual(0, $userGroups->count());

        // remove the temp group
        $groups->delete('master', $group->getId());
    }

    public function testAddRemoveRealmRoleUser(): void
    {
        $users = $this->getKeycloak()->users();
        $user = $users->all('master')->first();

        // retrieve user's roles and count them
        $roles = $users->retrieveRealmRoles('master', $user->getId());
        $cntRoles = $roles->count();

        // retrieve user's available roles and count them
        $foo = $users->retrieveRealmRoles('master', $user->getId());
        static::assertGreaterThanOrEqual(1, $foo->count());

        $availableRoles = $users->retrieveAvailableRealmRoles('master', $user->getId());
        $cntAvailableRoles = $availableRoles->count();
        static::assertGreaterThanOrEqual(1, $cntAvailableRoles);
        $role = $availableRoles->first();
        static::assertInstanceOf(Role::class, $role);

        // add the first available role to the user
        $users->addRealmRoles('master', $user->getId(), [$role]);

        $roles = $users->retrieveRealmRoles('master', $user->getId());
        static::assertEquals($cntRoles + 1, $roles->count());
        static::assertContainsEquals($role, $roles);

        $availableRoles = $users->retrieveAvailableRealmRoles('master', $user->getId());
        static::assertEquals($cntAvailableRoles - 1, $availableRoles->count());

        // remove the role from the user (back to the initial state)
        $users->removeRealmRoles('master', $user->getId(), [$role]);

        $roles = $users->retrieveRealmRoles('master', $user->getId());
        static::assertEquals($cntRoles, $roles->count());
        static::assertNotContainsEquals($role, $roles);

        $availableRoles = $users->retrieveAvailableRealmRoles('master', $user->getId());
        static::assertEquals($cntAvailableRoles, $availableRoles->count());
    }
}

<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Contracts\UserRepository;
use App\Application\Exceptions\UsernameTaken;
use App\Domain\Users\Role;
use App\Domain\Users\User as DomainUser;
use App\Models\User as UserModel;
use Illuminate\Database\QueryException;

final class EloquentUserRepository implements UserRepository
{
    public function findById(string $id): ?DomainUser
    {
        $user = UserModel::query()->find($id);

        return $user === null ? null : $this->toDomain($user);
    }

    public function findByUsername(string $username): ?DomainUser
    {
        $user = UserModel::query()->where('username', mb_strtolower(trim($username)))->first();

        return $user === null ? null : $this->toDomain($user);
    }

    public function create(DomainUser $user): DomainUser
    {
        try {
            $model = new UserModel;
            $model->fill([
                'id' => $user->id,
                'username' => $user->username,
                'password_hash' => $user->passwordHash,
                'role' => $user->role->value,
            ]);
            $model->save();
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? null) === '23505') {
                throw new UsernameTaken('Ese nombre de usuario ya está registrado.', previous: $exception);
            }

            throw $exception;
        }

        return $this->toDomain($model);
    }

    private function toDomain(UserModel $model): DomainUser
    {
        return new DomainUser(
            (string) $model->getKey(),
            (string) $model->username,
            (string) $model->password_hash,
            Role::from((string) $model->role),
        );
    }
}

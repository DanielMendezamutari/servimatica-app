<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\User\{Email, Password, PinCode, Role, User, Username, UserRepositoryInterface, UserStatus};
use App\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        $model = UserModel::find($id);
        return $model ? $this->map($model) : null;
    }
    public function findByLogin(string $login): ?User
    {
        $login = strtolower(trim($login));
        $model = UserModel::where('username', $login)->orWhere('email', $login)->first();
        return $model ? $this->map($model) : null;
    }
    public function all(): array
    {
        return UserModel::orderBy('name')->get()->map(fn (UserModel $model) => $this->map($model))->all();
    }
    public function identityExists(string $field, string $value, ?int $except = null): bool
    {
        if (!in_array($field, ['username', 'email'], true)) {
            throw new \InvalidArgumentException('Campo no válido.');
        }
        return UserModel::where($field, $value)->when($except, fn ($query) => $query->where('id', '!=', $except))->exists();
    }
    public function save(User $user): User
    {
        $model = $user->id ? UserModel::findOrFail($user->id) : new UserModel();
        $model->fill([
            'name' => $user->name,
            'ci' => $user->ci,
            'username' => $user->username->value,
            'email' => $user->email->value,
            'phone' => $user->phone,
            'address' => $user->address,
            'gender' => $user->gender,
            'sales_commission' => $user->salesCommission,
            'branch' => $user->branch,
            'avatar' => $user->avatar,
            'password' => $user->password->hash,
            'pin_code' => $user->pinCode->hash,
            'role' => $user->role->value,
        ]);
        if (!$model->exists) {
            $model->status = $user->status->value;
        }
        $model->save();
        return $this->map($model->fresh());
    }
    public function toggleStatus(int $id, int $actorId): User
    {
        return DB::transaction(function () use ($id, $actorId) {
            $model = UserModel::lockForUpdate()->findOrFail($id);
            if ($id === $actorId) {
                throw ValidationException::withMessages(['status' => 'No puede desactivar su propia cuenta de Dueño/Administrador.']);
            }
            $model->status = $model->status === 'active' ? 'inactive' : 'active';
            $model->save();
            return $this->map($model);
        });
    }
    private function map(UserModel $model): User
    {
        return new User(
            $model->id,
            $model->name,
            new Username($model->username),
            new Email($model->email),
            new Password($model->password),
            new PinCode($model->pin_code),
            Role::from($model->role),
            UserStatus::from($model->status),
            $model->ci,
            $model->phone,
            $model->address,
            $model->gender,
            (float) ($model->sales_commission ?? 0.0),
            $model->branch ?? 'Casa Matriz',
            $model->avatar,
            $model->avatar_url,
            $model->created_at?->toISOString()
        );
    }
}

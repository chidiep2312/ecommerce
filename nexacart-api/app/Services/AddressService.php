<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddressService
{
    public function getUserAddresses(User $user): Collection
    {
        return Address::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();
    }

    public function create(
        User $user,
        array $data,
    ): Address {
        return DB::transaction(function () use ($user, $data) {
            $hasAddress = $user->addresses()->exists();

            if (!$hasAddress) {
                $data['is_default'] = true;
            }

            if ($data['is_default'] ?? false) {
                $user->addresses()->update([
                    'is_default' => false,
                ]);
            }

            $data['user_id'] = $user->id;

            return Address::query()->create($data);
        });
    }

    public function update(
        User $user,
        Address $address,
        array $data,
    ): Address {
        return DB::transaction(
            function () use ($user, $address, $data) {
                $address = Address::query()
                    ->whereKey($address->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureOwnership($user, $address);

                if ($data['is_default'] ?? false) {
                    $user->addresses()
                        ->whereKeyNot($address->id)
                        ->update([
                            'is_default' => false,
                        ]);
                }

          
                if (
                    $address->is_default
                    && array_key_exists('is_default', $data)
                    && $data['is_default'] === false
                ) {
                    unset($data['is_default']);
                }

                $address->update($data);

                return $address->refresh();
            },
        );
    }

    public function setDefault(
        User $user,
        Address $address,
    ): Address {
        return DB::transaction(function () use ($user, $address) {
            $address = Address::query()
                ->whereKey($address->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureOwnership($user, $address);

            $user->addresses()
                ->where('id', '!=', $address->id)
                ->update([
                    'is_default' => false,
                ]);

            $address->update([
                'is_default' => true,
            ]);

            return $address->refresh();
        });
    }

    public function delete(
        User $user,
        Address $address,
    ): void {
        DB::transaction(function () use ($user, $address) {
            $address = Address::query()
                ->whereKey($address->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureOwnership($user, $address);

            $wasDefault = $address->is_default;

            $address->delete();

            /*
             * Nếu xóa địa chỉ mặc định,
             * lấy địa chỉ còn lại mới nhất làm mặc định.
             */
            if ($wasDefault) {
                $newDefault = $user->addresses()
                    ->latest('id')
                    ->first();

                $newDefault?->update([
                    'is_default' => true,
                ]);
            }
        });
    }

    private function ensureOwnership(
        User $user,
        Address $address,
    ): void {
        if ($address->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'address' => [
                    'Bạn không có quyền quản lý địa chỉ này.',
                ],
            ]);
        }
    }
}
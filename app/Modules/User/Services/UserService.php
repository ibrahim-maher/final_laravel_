<?php

namespace App\Modules\User\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function updateProfile($userId, $data)
    {
        $user = User::find($userId);
        if ($user) {
            $user->update([
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
            ]);
            return $user;
        }
        return false;
    }

    public function changePassword($userId, $newPassword)
    {
        $user = User::find($userId);
        if ($user) {
            $user->password = Hash::make($newPassword);
            return $user->save();
        }
        return false;
    }

    public function getUserProfile($userId)
    {
        return User::find($userId);
    }
}
<?php

namespace App\Modules\Admin\Services;

use App\Models\User;

class AdminService
{
    public function getAllUsers()
    {
        return User::all();
    }

    public function getUserById($id)
    {
        return User::find($id);
    }

    public function deleteUser($id)
    {
        return User::destroy($id);
    }

    public function updateUserStatus($id, $status)
    {
        $user = User::find($id);
        if ($user) {
            $user->status = $status;
            return $user->save();
        }
        return false;
    }
}
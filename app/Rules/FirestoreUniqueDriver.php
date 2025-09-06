<?php

namespace App\Rules;

use App\Modules\Driver\Services\DriverService;
use Illuminate\Contracts\Validation\Rule;

class FirestoreUniqueDriver implements Rule
{
    protected $driverService;

    public function __construct(DriverService $driverService)
    {
        $this->driverService = $driverService;
    }

    public function passes($attribute, $value)
    {
        // Check if a driver with the given firebase_uid exists in Firestore
        $driver = $this->driverService->getDriverById($value);
        return is_null($driver); // Return true if no driver is found (unique)
    }

    public function message()
    {
        return 'The :attribute has already been taken.';
    }
}
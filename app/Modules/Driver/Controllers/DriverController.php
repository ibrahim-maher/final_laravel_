<?php

namespace App\Modules\Driver\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Driver\Services\DriverService;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    protected $driverService;

    public function __construct(DriverService $driverService)
    {
        $this->driverService = $driverService;
    }

    public function index()
    {
        $drivers = $this->driverService->getAllDrivers();
        return view('driver.index', compact('drivers'));
    }

    public function create()
    {
        return view('driver.create');
    }

    public function store(Request $request)
    {
        $driver = $this->driverService->createDriver($request->all());
        return redirect()->route('driver.index')->with('success', 'Driver created successfully');
    }

    public function show($id)
    {
        $driver = $this->driverService->getDriverById($id);
        return view('driver.show', compact('driver'));
    }

    public function edit($id)
    {
        $driver = $this->driverService->getDriverById($id);
        return view('driver.edit', compact('driver'));
    }
}
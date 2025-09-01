@extends('layouts.app')

@section('title', 'Drivers')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Drivers Management</h1>
                <a href="{{ route('driver.create') }}" class="btn btn-primary">Add New Driver</a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>License Number</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($drivers as $driver)
                                <tr>
                                    <td>{{ $driver->id }}</td>
                                    <td>{{ $driver->name }}</td>
                                    <td>{{ $driver->license_number }}</td>
                                    <td>{{ $driver->phone }}</td>
                                    <td>{{ $driver->email }}</td>
                                    <td>
                                        <span class="badge badge-{{ $driver->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($driver->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $driver->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('driver.show', $driver->id) }}" class="btn btn-sm btn-info">View</a>
                                        <a href="{{ route('driver.edit', $driver->id) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <button class="btn btn-sm btn-danger">Delete</button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No drivers found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
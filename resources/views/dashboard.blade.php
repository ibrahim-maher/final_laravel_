<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Dashboard</h4>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm">Logout</button>
                        </form>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <h5>Welcome to your Dashboard!</h5>
                        
                        @if(session('firebase_user'))
                            <div class="mt-4">
                                <h6>User Information:</h6>
                                <p><strong>UID:</strong> {{ session('firebase_user.uid') }}</p>
                                <p><strong>Email:</strong> {{ session('firebase_user.email') }}</p>
                                @if(session('firebase_user.name'))
                                    <p><strong>Name:</strong> {{ session('firebase_user.name') }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
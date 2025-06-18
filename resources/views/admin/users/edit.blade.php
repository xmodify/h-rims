@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <h3>{{ isset($user) ? 'Edit' : 'Create' }} User</h3>

    <form method="POST" action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if(isset($user)) @method('PUT') @endif

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label>Active</label>
            <input type="active" name="active" class="form-control" value="{{ old('email', $user->active ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label>Status</label>            
            <select name="status" class="form-select" aria-label="Default select example">              
                <option value="user">User</option>
                <option value="udmin">Admin</option>
            </select>
        </div>

        @if (!isset($user))
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        @endif

        <button type="submit" class="btn btn-primary">
            {{ isset($user) ? 'Update' : 'Create' }}
        </button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back</a>
    </form>

    @if($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: `{!! implode('<br>', $errors->all()) !!}`,
                confirmButtonColor: '#dc3545'
            });
        </script>
    @endif

</div>
@endsection

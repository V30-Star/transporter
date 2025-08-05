@extends('layouts.app')

@section('title', 'Master Role Access')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-black">
                        <h3 class="card-title">Kelola Akses Role</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('roleaccess.store') }}" method="POST">
                            @csrf <!-- CSRF Token for security -->

                            <!-- Display fsysuserid (readonly) -->
                            <div class="form-group mb-4">
                                <label for="fuserid" class="form-label">User</label>
                                <input type="text" name="fuserid" id="fuserid" class="form-control" readonly
                                    value="{{ $user->fsysuserid }}">
                            </div>

                            <!-- Hidden field for fuid -->
                            <input type="hidden" name="fuid" value="{{ $user->fuid }}">

                            <!-- Permissions Table -->
                            <div class="form-group mb-4">
                                <label class="form-label">Akses</label>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Akses</th>
                                            <th>View</th>
                                            <th>Create</th>
                                            <th>Update</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Wilayah</td>
                                            <td><input type="checkbox" name="permission[]" value="viewWilayah"
                                                    {{ isset($roleAccess) && in_array('viewWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                            </td>
                                            <td><input type="checkbox" name="permission[]" value="createWilayah"
                                                    {{ isset($roleAccess) && in_array('createWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                            </td>
                                            <td><input type="checkbox" name="permission[]" value="updateWilayah"
                                                    {{ isset($roleAccess) && in_array('updateWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                            </td>
                                            <td><input type="checkbox" name="permission[]" value="deleteWilayah"
                                                    {{ isset($roleAccess) && in_array('deleteWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Save Button -->
                            <div class="form-group mb-4">
                                <button class="btn btn-success btn-block" type="submit">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

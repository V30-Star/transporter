@extends('layouts.app')

@section('title', 'Master Role Access')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">Kelola Akses Role</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Left Column: Jabatan & Akses Role Form -->
                            <div class="col-md-6">
                                <div class="form-group mb-4">
                                    <label for="jabatan" class="font-weight-bold">Jabatan</label>
                                    <select id="jabatan" class="form-control">
                                        <option value="">Pilih Jabatan</option>
                                        <!-- Populate Jabatan options dynamically from the database -->
                                    </select>
                                </div>

                                <div class="form-group mb-4">
                                    <label class="font-weight-bold">Akses</label>
                                    <table class="table table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Akses</th>
                                                <th>Create</th>
                                                <th>View</th>
                                                <th>Update</th>
                                                <th>Delete</th>
                                                <th>Download</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Arsip Dokumen</td>
                                                <td><input type="checkbox" name="akses[]" value="create"
                                                        class="form-check-input"></td>
                                                <td><input type="checkbox" name="akses[]" value="view"
                                                        class="form-check-input"></td>
                                                <td><input type="checkbox" name="akses[]" value="update"
                                                        class="form-check-input"></td>
                                                <td><input type="checkbox" name="akses[]" value="delete"
                                                        class="form-check-input"></td>
                                                <td><input type="checkbox" name="akses[]" value="download"
                                                        class="form-check-input"></td>
                                            </tr>
                                            <tr>
                                                <td>Unit Kerja</td>
                                                <td><input type="checkbox" name="akses[]" value="create"
                                                        class="form-check-input"></td>
                                                <td><input type="checkbox" name="akses[]" value="view"
                                                        class="form-check-input"></td>
                                                <td><input type="checkbox" name="akses[]" value="update"
                                                        class="form-check-input"></td>
                                                <td><input type="checkbox" name="akses[]" value="delete"
                                                        class="form-check-input"></td>
                                                <td><input type="checkbox" name="akses[]" value="download"
                                                        class="form-check-input"></td>
                                            </tr>
                                            <!-- More rows can be added here -->
                                        </tbody>
                                    </table>
                                </div>

                                <div class="form-group mb-4">
                                    <button class="btn btn-success btn-block">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection

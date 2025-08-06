@extends('layouts.app')

@section('title', 'Kelola Set Menu')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold text-center mb-6">Kelola Set Menu</h1>

            <form action="{{ route('roleaccess.store') }}" method="POST">
                @csrf <!-- CSRF Token for security -->

                <!-- Display fsysuserid (readonly) -->
                <div class="form-group mb-4 flex justify-between items-center">
                    <!-- User Input -->
                    <div>
                        <label for="fuserid" class="form-label">User</label>
                        <input type="text" name="fuserid" id="fuserid" class="form-control" readonly
                            value="{{ $user->fsysuserid }}">
                    </div>

                    <!-- Select All and Deselect All Buttons (Aligned to the Right) -->
                    <div class="flex space-x-2">
                        <!-- Select All Button -->
                        <button type="button" onclick="checkAllCheckboxes()"
                            class="inline-flex items-center bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Pilih Semua
                        </button>

                        <!-- Deselect All Button -->
                        <button type="button" onclick="uncheckAllCheckboxes()"
                            class="inline-flex items-center bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Hapus Pilihan
                        </button>
                    </div>
                </div>

                <!-- Hidden field for fuid -->
                <input type="hidden" name="fuid" value="{{ $user->fuid }}">

                <!-- Permissions Table -->
                <div class="form-group mb-4">
                    <table class="table table-bordered w-full">
                        <thead style="border-bottom: 2px solid #000; padding-bottom: 10px;">
                            <tr>
                                <th class="text-left">Akses</th>
                                <th>View</th>
                                <th>Create</th>
                                <th>Update</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" style="height: 10px;"></td> <!-- Empty row for spacing -->
                            </tr>
                            <tr>
                                <td>Wilayah</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewWilayah" id="viewWilayah"
                                        {{ isset($roleAccess) && in_array('viewWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createWilayah" id="createWilayah"
                                        {{ isset($roleAccess) && in_array('createWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateWilayah" id="updateWilayah"
                                        {{ isset($roleAccess) && in_array('updateWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteWilayah" id="deleteWilayah"
                                        {{ isset($roleAccess) && in_array('deleteWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <br>

                <!-- Button Row (Save and Back buttons side by side) -->
                <div class="form-group mb-4 flex space-x-2">
                    <!-- Save Button -->
                    <button class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                        type="submit">
                        Simpan
                    </button>

                    <!-- Back Button -->
                    <a href="{{ route('sysuser.index') }}"
                        class="inline-flex items-center bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                        Kembali
                    </a>
                </div>

            </form>
        </div>
    </div>
    <script>
        function checkAllCheckboxes() {
            // Get all checkboxes in the form
            var checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = true; // Set all checkboxes to checked
            });
        }

        function uncheckAllCheckboxes() {
            var checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false; // Set all checkboxes to unchecked
            });
        }
    </script>
@endsection

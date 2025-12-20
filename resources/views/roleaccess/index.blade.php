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
                        <label for="fusercreate" class="form-label">User</label>
                        <input type="text" name="fusercreate" id="fusercreate" class="form-control" readonly
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

                {{-- ...above your Permissions Table --}}
                <div class="form-group mb-4 flex items-end gap-2">
                    <div class="flex-1">
                        <label for="copy_from_user" class="form-label">Copy permissions from user</label>
                        <select id="copy_from_user" class="form-control">
                            <option value="">-- Choose user --</option>
                            @foreach ($allUsers as $u)
                                <option value="{{ $u->fuid }}">{{ $u->fsysuserid }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" onclick="copyFromUser()"
                        class="inline-flex items-center bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                        Copy
                    </button>
                </div>

                <!-- Permissions Table -->
                <div class="form-group mb-4">
                    <table class="table table-bordered w-full">
                        <thead>
                            <tr class="bg-gray-200 text-gray-800 text-center">
                                <th class="px-3 py-2 text-left">Menu</th>
                                <th class="px-3 py-2">Access</th>
                                <th class="px-3 py-2">Add</th>
                                <th class="px-3 py-2">Edit</th>
                                <th class="px-3 py-2">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" style="height: 10px;"></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Wilayah</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewWilayah"
                                        {{ isset($roleAccess) && in_array('viewWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createWilayah"
                                        {{ isset($roleAccess) && in_array('createWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateWilayah"
                                        {{ isset($roleAccess) && in_array('updateWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteWilayah"
                                        {{ isset($roleAccess) && in_array('deleteWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-3 py-2 font-semibold">Customer</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewCustomer"
                                        {{ isset($roleAccess) && in_array('viewCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createCustomer"
                                        {{ isset($roleAccess) && in_array('createCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateCustomer"
                                        {{ isset($roleAccess) && in_array('updateCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteCustomer"
                                        {{ isset($roleAccess) && in_array('deleteCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Group Customer</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewGroupCustomer"
                                        {{ isset($roleAccess) && in_array('viewGroupCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createGroupCustomer"
                                        {{ isset($roleAccess) && in_array('createGroupCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateGroupCustomer"
                                        {{ isset($roleAccess) && in_array('updateGroupCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteGroupCustomer"
                                        {{ isset($roleAccess) && in_array('deleteGroupCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-3 py-2 font-semibold">Wewenang User</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewSysuser"
                                        {{ isset($roleAccess) && in_array('viewSysuser', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createSysuser"
                                        {{ isset($roleAccess) && in_array('createSysuser', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateSysuser"
                                        {{ isset($roleAccess) && in_array('updateSysuser', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteSysuser"
                                        {{ isset($roleAccess) && in_array('deleteSysuser', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Salesman</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewSalesman"
                                        {{ isset($roleAccess) && in_array('viewSalesman', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createSalesman"
                                        {{ isset($roleAccess) && in_array('createSalesman', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateSalesman"
                                        {{ isset($roleAccess) && in_array('updateSalesman', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteSalesman"
                                        {{ isset($roleAccess) && in_array('deleteSalesman', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Satuan</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewSatuan"
                                        {{ isset($roleAccess) && in_array('viewSatuan', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createSatuan"
                                        {{ isset($roleAccess) && in_array('createSatuan', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateSatuan"
                                        {{ isset($roleAccess) && in_array('updateSatuan', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteSatuan"
                                        {{ isset($roleAccess) && in_array('deleteSatuan', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-3 py-2 font-semibold">Merek</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewMerek"
                                        {{ isset($roleAccess) && in_array('viewMerek', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createMerek"
                                        {{ isset($roleAccess) && in_array('createMerek', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateMerek"
                                        {{ isset($roleAccess) && in_array('updateMerek', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteMerek"
                                        {{ isset($roleAccess) && in_array('deleteMerek', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Gudang</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewGudang"
                                        {{ isset($roleAccess) && in_array('viewGudang', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createGudang"
                                        {{ isset($roleAccess) && in_array('createGudang', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateGudang"
                                        {{ isset($roleAccess) && in_array('updateGudang', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteGudang"
                                        {{ isset($roleAccess) && in_array('deleteGudang', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-3 py-2 font-semibold">Group Product</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewGroupProduct"
                                        {{ isset($roleAccess) && in_array('viewGroupProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createGroupProduct"
                                        {{ isset($roleAccess) && in_array('createGroupProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateGroupProduct"
                                        {{ isset($roleAccess) && in_array('updateGroupProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteGroupProduct"
                                        {{ isset($roleAccess) && in_array('deleteGroupProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>


                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Product</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewProduct"
                                        {{ isset($roleAccess) && in_array('viewProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createProduct"
                                        {{ isset($roleAccess) && in_array('createProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateProduct"
                                        {{ isset($roleAccess) && in_array('updateProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteProduct"
                                        {{ isset($roleAccess) && in_array('deleteProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-3 py-2 font-semibold">Supplier</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewSupplier"
                                        {{ isset($roleAccess) && in_array('viewSupplier', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createSupplier"
                                        {{ isset($roleAccess) && in_array('createSupplier', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateSupplier"
                                        {{ isset($roleAccess) && in_array('updateSupplier', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteSupplier"
                                        {{ isset($roleAccess) && in_array('deleteSupplier', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Rekening</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewRekening"
                                        {{ isset($roleAccess) && in_array('viewRekening', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createRekening"
                                        {{ isset($roleAccess) && in_array('createRekening', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateRekening"
                                        {{ isset($roleAccess) && in_array('updateRekening', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteRekening"
                                        {{ isset($roleAccess) && in_array('deleteRekening', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-3 py-2 font-semibold">Sub Account</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewSubAccount"
                                        {{ isset($roleAccess) && in_array('viewSubAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createSubAccount"
                                        {{ isset($roleAccess) && in_array('createSubAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateSubAccount"
                                        {{ isset($roleAccess) && in_array('updateSubAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteSubAccount"
                                        {{ isset($roleAccess) && in_array('deleteSubAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Account</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewAccount"
                                        {{ isset($roleAccess) && in_array('viewAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createAccount"
                                        {{ isset($roleAccess) && in_array('createAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateAccount"
                                        {{ isset($roleAccess) && in_array('updateAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteAccount"
                                        {{ isset($roleAccess) && in_array('deleteAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Permintaan Pembelian</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewTr_prh"
                                        {{ isset($roleAccess) && in_array('viewTr_prh', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createTr_prh"
                                        {{ isset($roleAccess) && in_array('createTr_prh', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateTr_prh"
                                        {{ isset($roleAccess) && in_array('updateTr_prh', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteTr_prh"
                                        {{ isset($roleAccess) && in_array('deleteTr_prh', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-3 py-2 font-semibold">Wewenang User</td>
                                <td class="text-center" colspan="4">
                                    <input type="checkbox" name="permission[]" value="roleaccess"
                                        {{ isset($roleAccess) && in_array('roleaccess', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Approval Permintaan Pembelian</td>
                                <td class="text-center" colspan="4">
                                    <input type="checkbox" name="permission[]" value="approvalpr"
                                        {{ isset($roleAccess) && in_array('approvalpr', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <br>

                <div class="form-group mb-4 flex justify-end space-x-2">
                    <button class="inline-flex items-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
                        type="submit">
                        Simpan
                    </button>

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
            var checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = true;
            });
        }

        function uncheckAllCheckboxes() {
            var checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
        }
    </script>

    <script>
        async function copyFromUser() {
            const select = document.getElementById('copy_from_user');
            const sourceFuid = select.value;
            if (!sourceFuid) return;

            const url = "{{ route('roleaccess.permissions', ['fuid' => '__FUID__']) }}".replace('__FUID__',
                sourceFuid);

            try {
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) throw new Error('Failed to load permissions');
                const data = await res.json();

                const selected = new Set(data.permissions || []);
                document.querySelectorAll('input[type="checkbox"][name="permission[]"]').forEach(cb => {
                    cb.checked = selected.has(cb.value);
                });
            } catch (e) {
                alert(e.message || 'Could not copy permissions.');
            }
        }
    </script>

@endsection

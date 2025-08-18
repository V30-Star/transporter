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
                    <table class="table table-bordered w-40">
                        <thead style="border-bottom: 2px solid #000; padding-bottom: 10px;">
                            <tr>
                                <th class="text-left">Akses</th>
                                <th>Wewenang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" style="height: 10px;"></td>
                            </tr>
                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Wilayah Menu
                                </th>
                            </tr>
                            <tr>
                                <td>WilayahView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewWilayah" id="viewWilayah"
                                        {{ isset($roleAccess) && in_array('viewWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>WilayahCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createWilayah" id="createWilayah"
                                        {{ isset($roleAccess) && in_array('createWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>WilayahUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateWilayah" id="updateWilayah"
                                        {{ isset($roleAccess) && in_array('updateWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>WilayahDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteWilayah" id="deleteWilayah"
                                        {{ isset($roleAccess) && in_array('deleteWilayah', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Customer Menu
                                </th>
                            </tr>

                            <tr>
                                <td>CustomerView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewCustomer" id="viewCustomer"
                                        {{ isset($roleAccess) && in_array('viewCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>CustomerCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createCustomer" id="createCustomer"
                                        {{ isset($roleAccess) && in_array('createCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>CustomerUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateCustomer" id="updateCustomer"
                                        {{ isset($roleAccess) && in_array('updateCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>CustomerDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteCustomer" id="deleteCustomer"
                                        {{ isset($roleAccess) && in_array('deleteCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Group Customer Menu
                                </th>
                            </tr>

                            <tr>
                                <td>GroupCustomerView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewGroupCustomer"
                                        id="viewGroupCustomer"
                                        {{ isset($roleAccess) && in_array('viewGroupCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>GroupCustomerCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createGroupCustomer"
                                        id="createGroupCustomer"
                                        {{ isset($roleAccess) && in_array('createGroupCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>GroupCustomerUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateGroupCustomer"
                                        id="updateGroupCustomer"
                                        {{ isset($roleAccess) && in_array('updateGroupCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>GroupCustomerDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteGroupCustomer"
                                        id="deleteGroupCustomer"
                                        {{ isset($roleAccess) && in_array('deleteGroupCustomer', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Sys User Menu
                                </th>
                            </tr>

                            <tr>
                                <td>SysuserView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewSysuser" id="viewSysuser"
                                        {{ isset($roleAccess) && in_array('viewSysuser', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SysuserCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createSysuser" id="createSysuser"
                                        {{ isset($roleAccess) && in_array('createSysuser', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SysuserUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateSysuser" id="updateSysuser"
                                        {{ isset($roleAccess) && in_array('updateSysuser', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SysuserDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteSysuser" id="deleteSysuser"
                                        {{ isset($roleAccess) && in_array('deleteSysuser', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Salesman Menu
                                </th>
                            </tr>

                            <tr>
                                <td>SalesmanView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewSalesman" id="viewSalesman"
                                        {{ isset($roleAccess) && in_array('viewSalesman', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SalesmanCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createSalesman"
                                        id="createSalesman"
                                        {{ isset($roleAccess) && in_array('createSalesman', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SalesmanUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateSalesman"
                                        id="updateSalesman"
                                        {{ isset($roleAccess) && in_array('updateSalesman', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SalesmanDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteSalesman"
                                        id="deleteSalesman"
                                        {{ isset($roleAccess) && in_array('deleteSalesman', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Satuan Menu
                                </th>
                            </tr>

                            <tr>
                                <td>SatuanView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewSatuan" id="viewSatuan"
                                        {{ isset($roleAccess) && in_array('viewSatuan', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SatuanCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createSatuan" id="createSatuan"
                                        {{ isset($roleAccess) && in_array('createSatuan', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SatuanUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateSatuan" id="updateSatuan"
                                        {{ isset($roleAccess) && in_array('updateSatuan', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SatuanDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteSatuan" id="deleteSatuan"
                                        {{ isset($roleAccess) && in_array('deleteSatuan', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Merek Menu
                                </th>
                            </tr>

                            <tr>
                                <td>MerekView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewMerek" id="viewMerek"
                                        {{ isset($roleAccess) && in_array('viewMerek', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>MerekCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createMerek" id="createMerek"
                                        {{ isset($roleAccess) && in_array('createMerek', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>MerekUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateMerek" id="updateMerek"
                                        {{ isset($roleAccess) && in_array('updateMerek', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>MerekDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteMerek" id="deleteMerek"
                                        {{ isset($roleAccess) && in_array('deleteMerek', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Gudang Menu
                                </th>
                            </tr>

                            <tr>
                                <td>GudangView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewGudang" id="viewGudang"
                                        {{ isset($roleAccess) && in_array('viewGudang', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>GudangCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createGudang" id="createGudang"
                                        {{ isset($roleAccess) && in_array('createGudang', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>GudangUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateGudang" id="updateGudang"
                                        {{ isset($roleAccess) && in_array('updateGudang', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>GudangDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteGudang" id="deleteGudang"
                                        {{ isset($roleAccess) && in_array('deleteGudang', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Group Product Menu
                                </th>
                            </tr>

                            <tr>
                                <td>GroupProductView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewGroupProduct"
                                        id="viewGroupProduct"
                                        {{ isset($roleAccess) && in_array('viewGroupProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>GroupProductCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createGroupProduct"
                                        id="createGroupProduct"
                                        {{ isset($roleAccess) && in_array('createGroupProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>GroupProductUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateGroupProduct"
                                        id="updateGroupProduct"
                                        {{ isset($roleAccess) && in_array('updateGroupProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>GroupProductDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteGroupProduct"
                                        id="deleteGroupProduct"
                                        {{ isset($roleAccess) && in_array('deleteGroupProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Product Menu
                                </th>
                            </tr>

                            <tr>
                                <td>ProductView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewProduct" id="viewProduct"
                                        {{ isset($roleAccess) && in_array('viewProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>ProductCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createProduct" id="createProduct"
                                        {{ isset($roleAccess) && in_array('createProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>ProductUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateProduct" id="updateProduct"
                                        {{ isset($roleAccess) && in_array('updateProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>ProductDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteProduct" id="deleteProduct"
                                        {{ isset($roleAccess) && in_array('deleteProduct', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Supplier Menu
                                </th>
                            </tr>

                            <tr>
                                <td>SupplierView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewSupplier" id="viewSupplier"
                                        {{ isset($roleAccess) && in_array('viewSupplier', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SupplierCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createSupplier"
                                        id="createSupplier"
                                        {{ isset($roleAccess) && in_array('createSupplier', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SupplierUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateSupplier"
                                        id="updateSupplier"
                                        {{ isset($roleAccess) && in_array('updateSupplier', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SupplierDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteSupplier"
                                        id="deleteSupplier"
                                        {{ isset($roleAccess) && in_array('deleteSupplier', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Rekening Menu
                                </th>
                            </tr>

                            <tr>
                                <td>RekeningView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewRekening" id="viewRekening"
                                        {{ isset($roleAccess) && in_array('viewRekening', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>RekeningCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createRekening"
                                        id="createRekening"
                                        {{ isset($roleAccess) && in_array('createRekening', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>RekeningUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateRekening"
                                        id="updateRekening"
                                        {{ isset($roleAccess) && in_array('updateRekening', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>RekeningDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteRekening"
                                        id="deleteRekening"
                                        {{ isset($roleAccess) && in_array('deleteRekening', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    SubAccount Menu
                                </th>
                            </tr>

                            <tr>
                                <td>SubAccountView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewSubAccount"
                                        id="viewSubAccount"
                                        {{ isset($roleAccess) && in_array('viewSubAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SubAccountCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createSubAccount"
                                        id="createSubAccount"
                                        {{ isset($roleAccess) && in_array('createSubAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SubAccountUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateSubAccount"
                                        id="updateSubAccount"
                                        {{ isset($roleAccess) && in_array('updateSubAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>SubAccountDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteSubAccount"
                                        id="deleteSubAccount"
                                        {{ isset($roleAccess) && in_array('deleteSubAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Account Menu
                                </th>
                            </tr>

                            <tr>
                                <td>AccountView</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="viewAccount" id="viewAccount"
                                        {{ isset($roleAccess) && in_array('viewAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>AccountCreate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="createAccount" id="createAccount"
                                        {{ isset($roleAccess) && in_array('createAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>AccountUpdate</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="updateAccount" id="updateAccount"
                                        {{ isset($roleAccess) && in_array('updateAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                            <tr>
                                <td>AccountDelete</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="deleteAccount" id="deleteAccount"
                                        {{ isset($roleAccess) && in_array('deleteAccount', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>

                            <tr>
                                <th colspan="2" style="background-color: #f0f0f0; font-weight: bold;">
                                    Wewenang User Menu
                                </th>
                            </tr>

                            <tr>
                                <td>Wewenang User</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permission[]" value="roleaccess" id="roleaccess"
                                        {{ isset($roleAccess) && in_array('roleaccess', explode(',', $roleAccess->fpermission)) ? 'checked' : '' }}>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <br>

                <div class="form-group mb-4 flex space-x-2">
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

@extends('layouts.app')

@section('title', 'Kelola Set Menu')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold text-center mb-6">Kelola Set Menu</h1>

            <form action="{{ route('roleaccess.store') }}" method="POST">
                @csrf <!-- CSRF Token for security -->
                @php
                    $selectedPermissions =
                        isset($roleAccess) && filled($roleAccess->fpermission)
                            ? array_filter(array_map('trim', explode(',', $roleAccess->fpermission)))
                            : [];
                @endphp

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
                            <!-- 1. MASTER DATA -->
                            <tr class="bg-gray-700 text-white font-bold">
                                <td colspan="5" class="px-3 py-2 text-xs uppercase tracking-wider">Master Data</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Wilayah</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewWilayah" {{ in_array('viewWilayah', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createWilayah" {{ in_array('createWilayah', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateWilayah" {{ in_array('updateWilayah', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteWilayah" {{ in_array('deleteWilayah', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Customer</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewCustomer" {{ in_array('viewCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createCustomer" {{ in_array('createCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateCustomer" {{ in_array('updateCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteCustomer" {{ in_array('deleteCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Group Customer</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewGroupCustomer" {{ in_array('viewGroupCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createGroupCustomer" {{ in_array('createGroupCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateGroupCustomer" {{ in_array('updateGroupCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteGroupCustomer" {{ in_array('deleteGroupCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Salesman</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewSalesman" {{ in_array('viewSalesman', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createSalesman" {{ in_array('createSalesman', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateSalesman" {{ in_array('updateSalesman', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteSalesman" {{ in_array('deleteSalesman', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Satuan</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewSatuan" {{ in_array('viewSatuan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createSatuan" {{ in_array('createSatuan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateSatuan" {{ in_array('updateSatuan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteSatuan" {{ in_array('deleteSatuan', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Merek</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewMerek" {{ in_array('viewMerek', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createMerek" {{ in_array('createMerek', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateMerek" {{ in_array('updateMerek', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteMerek" {{ in_array('deleteMerek', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Gudang</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewGudang" {{ in_array('viewGudang', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createGudang" {{ in_array('createGudang', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateGudang" {{ in_array('updateGudang', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteGudang" {{ in_array('deleteGudang', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Group Product</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewGroupProduct" {{ in_array('viewGroupProduct', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createGroupProduct" {{ in_array('createGroupProduct', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateGroupProduct" {{ in_array('updateGroupProduct', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteGroupProduct" {{ in_array('deleteGroupProduct', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Product</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewProduct" {{ in_array('viewProduct', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createProduct" {{ in_array('createProduct', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateProduct" {{ in_array('updateProduct', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteProduct" {{ in_array('deleteProduct', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Supplier</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewSupplier" {{ in_array('viewSupplier', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createSupplier" {{ in_array('createSupplier', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateSupplier" {{ in_array('updateSupplier', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteSupplier" {{ in_array('deleteSupplier', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Rekening</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewRekening" {{ in_array('viewRekening', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createRekening" {{ in_array('createRekening', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateRekening" {{ in_array('updateRekening', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteRekening" {{ in_array('deleteRekening', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Sub Account</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewSubAccount" {{ in_array('viewSubAccount', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createSubAccount" {{ in_array('createSubAccount', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateSubAccount" {{ in_array('updateSubAccount', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteSubAccount" {{ in_array('deleteSubAccount', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Account</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewAccount" {{ in_array('viewAccount', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createAccount" {{ in_array('createAccount', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateAccount" {{ in_array('updateAccount', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteAccount" {{ in_array('deleteAccount', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Currency</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewCurrency" {{ in_array('viewCurrency', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createCurrency" {{ in_array('createCurrency', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateCurrency" {{ in_array('updateCurrency', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteCurrency" {{ in_array('deleteCurrency', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">User (Wewenang User Data)</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewSysuser" {{ in_array('viewSysuser', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createSysuser" {{ in_array('createSysuser', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateSysuser" {{ in_array('updateSysuser', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteSysuser" {{ in_array('deleteSysuser', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>

                            <!-- 2. TRANSAKSI PENJUALAN -->
                            <tr class="bg-gray-700 text-white font-bold">
                                <td colspan="5" class="px-3 py-2 text-xs uppercase tracking-wider">Transaksi Penjualan</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Sales Order (SO)</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewSalesOrder" {{ in_array('viewSalesOrder', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createSalesOrder" {{ in_array('createSalesOrder', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateSalesOrder" {{ in_array('updateSalesOrder', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteSalesOrder" {{ in_array('deleteSalesOrder', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Surat Jalan</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewSuratJalan" {{ in_array('viewSuratJalan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createSuratJalan" {{ in_array('createSuratJalan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateSuratJalan" {{ in_array('updateSuratJalan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteSuratJalan" {{ in_array('deleteSuratJalan', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Faktur Penjualan</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewInvoice" {{ in_array('viewInvoice', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createInvoice" {{ in_array('createInvoice', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateInvoice" {{ in_array('updateInvoice', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteInvoice" {{ in_array('deleteInvoice', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Retur Penjualan</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewReturPenjualan" {{ in_array('viewReturPenjualan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createReturPenjualan" {{ in_array('createReturPenjualan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateReturPenjualan" {{ in_array('updateReturPenjualan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteReturPenjualan" {{ in_array('deleteReturPenjualan', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Lembar Penagihan</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewLembarPenagihan" {{ in_array('viewLembarPenagihan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createLembarPenagihan" {{ in_array('createLembarPenagihan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateLembarPenagihan" {{ in_array('updateLembarPenagihan', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteLembarPenagihan" {{ in_array('deleteLembarPenagihan', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Pelunasan Customer</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewPelunasanCustomer" {{ in_array('viewPelunasanCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createPelunasanCustomer" {{ in_array('createPelunasanCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updatePelunasanCustomer" {{ in_array('updatePelunasanCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deletePelunasanCustomer" {{ in_array('deletePelunasanCustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>

                            <!-- 3. TRANSAKSI PEMBELIAN -->
                            <tr class="bg-gray-700 text-white font-bold">
                                <td colspan="5" class="px-3 py-2 text-xs uppercase tracking-wider">Transaksi Pembelian</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Permintaan Pembelian (PR)</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewTr_prh" {{ in_array('viewTr_prh', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createTr_prh" {{ in_array('createTr_prh', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateTr_prh" {{ in_array('updateTr_prh', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteTr_prh" {{ in_array('deleteTr_prh', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Order Pembelian (PO)</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewTr_poh" {{ in_array('viewTr_poh', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createTr_poh" {{ in_array('createTr_poh', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateTr_poh" {{ in_array('updateTr_poh', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteTr_poh" {{ in_array('deleteTr_poh', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Penerimaan Barang</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewPenerimaanBarang" {{ in_array('viewPenerimaanBarang', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createPenerimaanBarang" {{ in_array('createPenerimaanBarang', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updatePenerimaanBarang" {{ in_array('updatePenerimaanBarang', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deletePenerimaanBarang" {{ in_array('deletePenerimaanBarang', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Faktur Pembelian</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewFakturPembelian" {{ in_array('viewFakturPembelian', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createFakturPembelian" {{ in_array('createFakturPembelian', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateFakturPembelian" {{ in_array('updateFakturPembelian', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteFakturPembelian" {{ in_array('deleteFakturPembelian', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Retur Pembelian</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewReturPembelian" {{ in_array('viewReturPembelian', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createReturPembelian" {{ in_array('createReturPembelian', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateReturPembelian" {{ in_array('updateReturPembelian', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteReturPembelian" {{ in_array('deleteReturPembelian', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Pelunasan Supplier (Bayar Supplier)</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewBayarSupplier" {{ in_array('viewBayarSupplier', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createBayarSupplier" {{ in_array('createBayarSupplier', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateBayarSupplier" {{ in_array('updateBayarSupplier', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteBayarSupplier" {{ in_array('deleteBayarSupplier', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>

                            <!-- 4. TRANSAKSI PERSEDIAAN & GUDANG -->
                            <tr class="bg-gray-700 text-white font-bold">
                                <td colspan="5" class="px-3 py-2 text-xs uppercase tracking-wider">Transaksi Persediaan & Gudang</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Adjustment Stock</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewAdjstock" {{ in_array('viewAdjstock', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createAdjstock" {{ in_array('createAdjstock', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateAdjstock" {{ in_array('updateAdjstock', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteAdjstock" {{ in_array('deleteAdjstock', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Mutasi Stock</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewMutasi" {{ in_array('viewMutasi', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createMutasi" {{ in_array('createMutasi', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateMutasi" {{ in_array('updateMutasi', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteMutasi" {{ in_array('deleteMutasi', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Pemakaian Barang</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewPemakaianbarang" {{ in_array('viewPemakaianbarang', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createPemakaianbarang" {{ in_array('createPemakaianbarang', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updatePemakaianBarang" {{ in_array('updatePemakaianBarang', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deletePemakaianBarang" {{ in_array('deletePemakaianBarang', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Assembling</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewAssembling" {{ in_array('viewAssembling', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createAssembling" {{ in_array('createAssembling', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updateAssembling" {{ in_array('updateAssembling', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deleteAssembling" {{ in_array('deleteAssembling', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>

                            <!-- 5. KAS, BANK & AKUNTANSI -->
                            <tr class="bg-gray-700 text-white font-bold">
                                <td colspan="5" class="px-3 py-2 text-xs uppercase tracking-wider">Kas, Bank & Akuntansi</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Penerimaan Kas/Bank</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewPenerimaanKas" {{ in_array('viewPenerimaanKas', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createPenerimaanKas" {{ in_array('createPenerimaanKas', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updatePenerimaanKas" {{ in_array('updatePenerimaanKas', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deletePenerimaanKas" {{ in_array('deletePenerimaanKas', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Pengeluaran Kas/Bank</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewPengeluaranKas" {{ in_array('viewPengeluaranKas', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createPengeluaranKas" {{ in_array('createPengeluaranKas', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updatePengeluaranKas" {{ in_array('updatePengeluaranKas', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deletePengeluaranKas" {{ in_array('deletePengeluaranKas', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Jurnal Transaksi</td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="viewjurnaltransaksi" {{ in_array('viewjurnaltransaksi', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="createjurnaltransaksi" {{ in_array('createjurnaltransaksi', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="updatejurnaltransaksi" {{ in_array('updatejurnaltransaksi', $selectedPermissions) ? 'checked' : '' }}></td>
                                <td class="text-center"><input type="checkbox" name="permission[]" value="deletejurnaltransaksi" {{ in_array('deletejurnaltransaksi', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>

                            <!-- 6. WEWENANG & KONFIGURASI KHUSUS -->
                            <tr class="bg-gray-700 text-white font-bold">
                                <td colspan="5" class="px-3 py-2 text-xs uppercase tracking-wider">Wewenang & Konfigurasi Khusus</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Hak Kelola Wewenang User</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="roleaccess" {{ in_array('roleaccess', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Boleh Edit TOP, Max JT Tempo & Limit Customer (Customereditadmin)</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="Customereditadmin" {{ (in_array('Customereditadmin', $selectedPermissions) || in_array('customereditadmin', $selectedPermissions)) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Lihat HPP Produk</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="viewProductHpp" {{ in_array('viewProductHpp', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Print Faktur Pembelian</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="printFakturPembelian" {{ in_array('printFakturPembelian', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Print Retur Pembelian</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="printReturPembelian" {{ in_array('printReturPembelian', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Boleh Access Semua Cabang</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="semuacabang" {{ in_array('semuacabang', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Boleh Ganti Tanggal Transaksi</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="BolehGantiTanggal" {{ in_array('BolehGantiTanggal', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Boleh Lanjut Ke Surat Jalan</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="BolehLanjutKeSuratJalan" {{ in_array('BolehLanjutKeSuratJalan', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Edit Periode Accounting</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="editPeriode" {{ in_array('editPeriode', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>

                            <!-- 7. PERSETUJUAN (APPROVAL) -->
                            <tr class="bg-gray-700 text-white font-bold">
                                <td colspan="5" class="px-3 py-2 text-xs uppercase tracking-wider">Persetujuan (Approval)</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Boleh Approve Sales Order</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="approveSalesOrder" {{ in_array('approveSalesOrder', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Boleh Approve Faktur Penjualan</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="approveFakturPenjualan" {{ in_array('approveFakturPenjualan', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Boleh Approve PR (Permintaan Pembelian)</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="approvePR" {{ in_array('approvePR', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Boleh Approve PO (Order Pembelian)</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="approvePO" {{ in_array('approvePO', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Boleh Approve Produk</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="approveProduct" {{ in_array('approveProduct', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>

                            <!-- 8. LAPORAN & LISTING -->
                            <tr class="bg-gray-700 text-white font-bold">
                                <td colspan="5" class="px-3 py-2 text-xs uppercase tracking-wider">Laporan & Listing</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Reporting Pelunasan Customer</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="viewreportingpelunasancustomer" {{ in_array('viewreportingpelunasancustomer', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Reporting Pelunasan Supplier</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="viewreportingpelunasansupplier" {{ in_array('viewreportingpelunasansupplier', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Listing Retur Pembelian</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="viewlistingreturpembelian" {{ in_array('viewlistingreturpembelian', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Listing Retur Penjualan</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="viewlistingreturpenjualan" {{ in_array('viewlistingreturpenjualan', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Listing Penerimaan Kas Bank</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="viewlistingpenerimaankasbank" {{ in_array('viewlistingpenerimaankasbank', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Listing Pengeluaran Kas Bank</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="viewlistingpengeluarankasbank" {{ in_array('viewlistingpengeluarankasbank', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>

                            <!-- 9. WIDGET DASHBOARD -->
                            <tr class="bg-gray-700 text-white font-bold">
                                <td colspan="5" class="px-3 py-2 text-xs uppercase tracking-wider">Widget Dashboard</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Total Piutang Usaha</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="dashboardTotalPiutangUsaha" {{ in_array('dashboardTotalPiutangUsaha', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Belum Jatuh Tempo</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="dashboardBelumJatuhTempo" {{ in_array('dashboardBelumJatuhTempo', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Lewat Jatuh Tempo</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="dashboardLewatJatuhTempo" {{ in_array('dashboardLewatJatuhTempo', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Omset (YTD)</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="dashboardOmsetYtd" {{ in_array('dashboardOmsetYtd', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 font-semibold">Omset Penjualan per Bulan</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="dashboardOmsetPenjualanBulan" {{ in_array('dashboardOmsetPenjualanBulan', $selectedPermissions) ? 'checked' : '' }}></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 font-semibold">Top Piutang Lewat Jatuh Tempo</td>
                                <td class="text-center" colspan="4"><input type="checkbox" name="permission[]" value="dashboardTopPiutangLewatJatuhTempo" {{ in_array('dashboardTopPiutangLewatJatuhTempo', $selectedPermissions) ? 'checked' : '' }}></td>
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
                window.showAppErrorAlert('TERJADI KESALAHAN', e.message || 'GAGAL COPY PERMISSIONS.');
            }
        }
    </script>

@endsection

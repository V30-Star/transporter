<?php

namespace App\Http\Controllers;

use App\Mail\GenericApprovalNotification;
use App\Models\Groupproduct;
use App\Models\Merek;
use App\Models\Product;
use App\Models\Satuan;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Support\ApprovalState;

class ProductController extends Controller
{
    private function ensureProductPermission(string $permission)
    {
        if ($this->hasRestrictedPermission($permission)) {
            return null;
        }

        return redirect()
            ->route('dashboard')
            ->with('error', 'Anda tidak memiliki akses ke menu produk.');
    }

    protected function canApproveProduct(): bool
    {
        return in_array('approveProduct', explode(',', session('user_restricted_permissions', '')));
    }

    protected function canViewProductHpp(): bool
    {
        return in_array('viewProductHpp', explode(',', session('user_restricted_permissions', '')));
    }

    protected function resolveProductDefaultHpp(Product $product): float
    {
        return match ((string) ($product->fsatuandefault ?? '1')) {
            '2' => (float) ($product->fhpp2 ?? 0),
            '3' => (float) ($product->fhpp3 ?? 0),
            default => (float) ($product->fhpp ?? 0),
        };
    }

    protected function resolveProductDefaultUnit(Product $product): string
    {
        return match ((string) ($product->fsatuandefault ?? '1')) {
            '2' => trim((string) ($product->fsatuanbesar ?? '')),
            '3' => trim((string) ($product->fsatuanbesar2 ?? '')),
            default => trim((string) ($product->fsatuankecil ?? '')),
        } ?: trim((string) ($product->fsatuankecil ?? ''));
    }

    protected function getApprovalRecipients(): array
    {
        return array_values(array_filter([
            trim((string) config('approval.product.stage1', '')),
            trim((string) config('approval.product.stage2', '')),
        ]));
    }

    protected function sendApprovalNotification(Product $product, string $approver): void
    {
        $fields = [
            ['label' => 'Nama Produk', 'value' => $product->fprdname ?? '-'],
            ['label' => 'Kode Produk', 'value' => $product->fprdcode ?? '-'],
            ['label' => 'Group', 'value' => $product->fgroupcode ?? '-'],
            ['label' => 'Merek', 'value' => $product->fmerek ?? '-'],
            ['label' => 'Satuan 1', 'value' => $product->fsatuankecil ?? '-'],
            ['label' => 'HPP', 'value' => format_number($product->fhpp ?? 0)],
            ['label' => 'Min. Stok', 'value' => number_format((float) ($product->fminstock ?? 0), 2, ',', '.')],
        ];
        $recipients = array_slice($this->getApprovalRecipients(), 0, 2);

        if (! empty($recipients[0]) && ! empty($product->fapproval_token)) {
            Mail::to($recipients[0])->send(new GenericApprovalNotification(
                'Approval Produk',
                'Produk Approval',
                (string) ($product->fprdcode ?? '-'),
                $approver,
                route('approval.product.page', ['fprdid' => $product->fprdid, 'token' => $product->fapproval_token]),
                $fields,
                []
            ));
        }

        if (! empty($recipients[1]) && ! empty($product->fapproval_token2)) {
            Mail::to($recipients[1])->send(new GenericApprovalNotification(
                'Approval Produk',
                'Produk Approval',
                (string) ($product->fprdcode ?? '-'),
                $approver,
                route('approval.product.page', ['fprdid' => $product->fprdid, 'token' => $product->fapproval_token2]),
                $fields,
                []
            ));
        }
    }

    private function shouldRequestProductApproval(Request $request): bool
    {
        return $request->boolean('fapproval');
    }

    protected function initializeApprovalState(): array
    {
        return ApprovalState::initializeApprovalColumns(
            array_slice($this->getApprovalRecipients(), 0, 2),
            fn() => \Illuminate\Support\Str::random(64)
        );
    }

    protected function getApprovalLockMessage(Product $product): ?string
    {
        return ApprovalState::isEditBlockedRecord($product)
            ? 'Produk belum dapat diubah karena status approval saat ini belum mengizinkan edit.'
            : null;
    }

    protected function getEnabledProductImageNumbers(): array
    {
        return collect([1, 2, 3])
            ->filter(fn($number) => (string) env('UPLOADFOTO' . $number, '1') === '1')
            ->values()
            ->all();
    }

    protected function getEnabledProductImageFields(): array
    {
        return array_map(
            fn($number) => 'fimage' . $number,
            $this->getEnabledProductImageNumbers()
        );
    }

    protected function getProductUsageInfo(Product $product): array
    {
        $usageMap = [
            'po' => [
                'used' => $product->trPods()->exists(),
                'label' => 'PO',
            ],
            'pr' => [
                'used' => $product->trPrds()->exists(),
                'label' => 'PR',
            ],
            'stok' => [
                'used' => $product->trstockdts()->exists(),
                'label' => 'Transaksi Stok',
            ],
        ];

        $usedBy = collect($usageMap)
            ->filter(fn($item) => $item['used'])
            ->pluck('label')
            ->values()
            ->all();

        return [
            'is_used' => ! empty($usedBy),
            'used_by' => $usedBy,
        ];
    }

    public function index(Request $request)
    {
        if ($guard = $this->ensureProductPermission('viewProduct')) {
            return $guard;
        }

        $canViewHpp = $this->canViewProductHpp();

        if ($request->ajax()) {
            $query = Product::query()
                ->from('msprd')
                ->leftJoin('msmerek', 'msmerek.fmerekcode', '=', 'msprd.fmerek');

            $status = $request->input('status', 'active');
            if ($status === 'active') {
                $query->where('msprd.fnonactive', '0');
            } elseif ($status === 'nonactive') {
                $query->where('msprd.fnonactive', '1');
            }
            $totalRecords = Product::count();
            $searchableColumns = ['msprd.fprdcode', 'msprd.fprdname', 'msprd.fsatuankecil', 'msprd.fminstock', 'msmerek.fmerekname'];
            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search, $searchableColumns) {
                    foreach ($searchableColumns as $column) {
                        $q->orWhere($column, 'ilike', "%{$search}%");
                    }
                });
            }

            $columnFields = [
                'msprd.fprdcode',
                'msprd.fprdname',
                'msmerek.fmerekname',
                'msprd.fsatuankecil',
                'msprd.fminstock',
            ];
            foreach ($columnFields as $index => $field) {
                $colSearch = $request->input("columns.{$index}.search.value");
                if ($colSearch !== null && $colSearch !== '') {
                    $query->where($field, 'ilike', "%{$colSearch}%");
                }
            }

            $filteredRecords = (clone $query)->count();

            $orderColumnIndex = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');
            $columns = [
                'msprd.fprdcode',
                'msprd.fprdname',
                'msmerek.fmerekname',
                'msprd.fsatuankecil',
                'msprd.fminstock',
                'msprd.fnonactive',
            ];
            if (isset($columns[$orderColumnIndex])) {
                $query->orderBy($columns[$orderColumnIndex], $orderDir);
            }

            $start = $request->input('start', 0);
            $length = $request->input('length', 10);

            $products = $query->skip($start)->take($length)->get([
                'msprd.fprdcode',
                'msprd.fprdname',
                'msprd.fsatuankecil',
                'msprd.fsatuanbesar',
                'msprd.fsatuanbesar2',
                'msprd.fsatuandefault',
                'msprd.fsatuandefaultlaporan',
                'msprd.fminstock',
                'msprd.fimage1',
                'msprd.fprdid',
                'msprd.fnonactive',
                'msprd.fapproval',
                'msprd.fapproval2',
                'msprd.fmerek',
                'msprd.fhpp',
                'msprd.fhpp2',
                'msprd.fhpp3',
                'msmerek.fmerekname AS merek_name',
            ]);

            $data = $products->map(function ($item) use ($canViewHpp) {
                $isActive = (string) $item->fnonactive === '0';
                $statusBadge = $isActive
                    ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Active</span>'
                    : '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-red-200 text-red-700">Non Active</span>';

                return [
                    'fprdcode' => $item->fprdcode,
                    'fprdname' => $item->fprdname,
                    'fmerek' => $item->merek_name,
                    'fsatuankecil' => $this->resolveProductDefaultUnit($item),
                    'fminstock' => $item->fminstock,
                    'fhpp_display' => $canViewHpp ? $this->resolveProductDefaultHpp($item) : null,
                    'fimage1' => $item->fimage1,
                    'status' => $statusBadge,
                    'fprdid' => $item->fprdid,
                    'fapproval' => $item->fapproval,
                    'fapproval2' => $item->fapproval2,
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        $canCreate = in_array('createProduct', explode(',', session('user_restricted_permissions', '')));
        $canEdit = in_array('updateProduct', explode(',', session('user_restricted_permissions', '')));
        $canDelete = in_array('deleteProduct', explode(',', session('user_restricted_permissions', '')));

        return view('product.index', compact('canCreate', 'canEdit', 'canDelete', 'canViewHpp'));
    }

    public function suggestNames(Request $request)
    {
        if ($guard = $this->ensureProductPermission('viewProduct')) {
            return $guard;
        }

        $term = (string) $request->get('term', '');

        $q = DB::table('msprd')->whereNotNull('fprdname');

        if ($term !== '') {
            $q->where('fprdname', 'ILIKE', "%{$term}%");
        }

        $names = $q->distinct()
            ->orderBy('fprdname')
            ->limit(15)
            ->pluck('fprdname');

        return response()->json($names);
    }

    public function suggestCodes(Request $request)
    {
        if ($guard = $this->ensureProductPermission('viewProduct')) {
            return $guard;
        }

        $term = (string) $request->get('term', '');

        $q = DB::table('msprd')->whereNotNull('fprdcode');

        if ($term !== '') {
            $q->where('fprdcode', 'ILIKE', "%{$term}%");
        }

        $codes = $q->distinct()
            ->orderBy('fprdcode')
            ->limit(15)
            ->pluck('fprdcode');

        return response()->json($codes);
    }

    private function generateProductCode($groupId, $merekId): string
    {
        $paddedGroupId = str_pad($groupId, 3, '0', STR_PAD_LEFT);
        $paddedMerekId = str_pad($merekId, 3, '0', STR_PAD_LEFT);

        $prefix = $paddedGroupId . '.' . $paddedMerekId . '.';
        $prefixLength = strlen($prefix);

        $lastCode = Product::where('fprdcode', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(fprdcode FROM ' . ($prefixLength + 1) . ') AS INTEGER) DESC')
            ->value('fprdcode');

        if (! $lastCode) {
            $newNumber = 1;
        } else {
            $number = (int) substr($lastCode, $prefixLength);
            $newNumber = $number + 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        if ($guard = $this->ensureProductPermission('createProduct')) {
            return $guard;
        }

        $groups = Groupproduct::where('fnonactive', 0)->get();
        $merks = Merek::where('fnonactive', 0)->get();
        $satuan = Satuan::where('fnonactive', 0)->get();
        $newProductCode = $this->generateProductCode($groups->first()->fgroupcode ?? 1, $merks->first()->fmerekcode ?? 1);
        $enabledImageNumbers = $this->getEnabledProductImageNumbers();

        return view('product.create', compact('groups', 'merks', 'satuan', 'newProductCode', 'enabledImageNumbers'));
    }

    public function store(Request $request)
    {
        if ($guard = $this->ensureProductPermission('createProduct')) {
            return $guard;
        }

        try {
            $shouldSendApprovalNotification = false;
            $needsApprovalNotification = $this->shouldRequestProductApproval($request);
            $enabledImageFields = $this->getEnabledProductImageFields();
            $validationRules = [
                'fprdcode' => 'nullable|string|unique:msprd,fprdcode',
                'fprdname' => 'required|string',
                'ftype' => 'string',
                'fbarcode' => 'nullable',
                'fgroupcode' => 'required',
                'fmerek' => 'required',
                'fsatuankecil' => 'required',
                'fsatuanbesar' => ['nullable', 'string', 'different:fsatuankecil'],
                'fsatuanbesar2' => ['nullable', 'string', 'different:fsatuankecil', 'different:fsatuanbesar'],
                'fsatuandefault' => 'in:1,2,3',
                'fsatuandefaultlaporan' => 'in:1,2,3',
                'fqtykecil' => [
                    'nullable',
                    'numeric',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($request->filled('fsatuanbesar') && (float) $value <= 1) {
                            $fail('Isi Satuan 2 harus lebih besar dari 1.');
                        }
                    },
                ],
                'fqtykecil2' => [
                    'nullable',
                    'numeric',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($request->filled('fsatuanbesar2') && (float) $value <= 1) {
                            $fail('Isi Satuan 3 harus lebih besar dari 1.');
                        }
                        if (
                            $request->filled('fsatuanbesar') && $request->filled('fsatuanbesar2')
                            && trim((string) $request->input('fsatuanbesar')) !== trim((string) $request->input('fsatuanbesar2'))
                            && (float) $request->input('fqtykecil') === (float) $value
                        ) {
                            $fail('Isi Satuan 3 tidak boleh sama dengan Isi Satuan 2 jika satuannya berbeda.');
                        }
                    },
                ],
                'fminstock' => 'numeric',
                'fhpp' => 'nullable',
                'fhpp2' => 'nullable',
                'fhpp3' => 'nullable',
            ];

            foreach ($enabledImageFields as $imageField) {
                $validationRules[$imageField] = 'nullable|image|max:2048';
            }

            $validated = $request->validate($validationRules, [
                'fgroupcode.required' => 'Group produk wajib diisi.',
                'fmerek.required' => 'Merek wajib diisi.',
                'fprdname.required' => 'Nama produk wajib diisi.',
                'fsatuankecil.required' => 'Satuan 1 wajib diisi.',
                'fsatuanbesar.string' => 'Satuan 2 tidak valid.',
                'fsatuanbesar.different' => 'Satuan 2 tidak boleh sama dengan Satuan 1.',
                'fsatuanbesar2.string' => 'Satuan 3 tidak valid.',
                'fsatuanbesar2.different' => 'Satuan 3 tidak boleh sama dengan Satuan 1 atau 2.',
                'fqtykecil.numeric' => 'Satuan 2 harus angka.',
                'fqtykecil2.numeric' => 'Satuan 3 harus angka.',
            ]);

            $validated['fprdname'] = strtoupper($request->fprdname);

            if (empty($request->fprdcode)) {
                $validated['fprdcode'] = $this->generateProductCode($request->fgroupcode, $request->fmerek);
            } else {
                $validated['fprdcode'] = $request->fprdcode;
            }

            $sanitizeNumeric = function ($value) {
                if ($value === null || $value === '') {
                    return 0;
                }
                $clean = preg_replace('/[^0-9.]/', '', $value);

                return (is_numeric($clean)) ? (float) $clean : 0;
            };

            $numericFields = [
                'fhpp',
                'fhpp2',
                'fhpp3',
                'fhargajuallevel1',
                'fhargajuallevel2',
                'fhargajuallevel3',
                'fhargajual2level1',
                'fhargajual2level2',
                'fhargajual2level3',
                'fhargajual3level1',
                'fhargajual3level2',
                'fhargajual3level3',
            ];

            foreach ($numericFields as $field) {
                $validated[$field] = $sanitizeNumeric($request->input($field));
            }

            $user = auth('sysuser')->user();
            $approvalState = $this->initializeApprovalState();
            $validated = array_merge($validated, $approvalState);
            $shouldSendApprovalNotification = $needsApprovalNotification
                && ApprovalState::hasApprovalProgress((object) $approvalState);
            $validated['fcreatedby'] = $user->fname ?? 'System';
            $validated['fcreatedat'] = now();
            $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';

            $googleDriveService = new \App\Services\GoogleDriveService;
            foreach ($enabledImageFields as $imageField) {
                if ($request->hasFile($imageField) && $request->file($imageField)->isValid()) {
                    try {
                        $fileId = $googleDriveService->uploadImage($request, $imageField);
                        if ($fileId) {
                            $validated[$imageField] = $fileId;
                        }
                    } catch (\Exception $e) {
                        \Log::error("Upload $imageField Failed: " . $e->getMessage());
                    }
                }
            }

            $product = Product::create($validated);

            if ($shouldSendApprovalNotification) {
                $this->sendApprovalNotification($product, $user->fname ?? 'System');
            }

            return redirect()
                ->route('product.create')
                ->with('success', 'Produk berhasil disimpan.');
        } catch (\Illuminate\Validation\ValidationException $v) {
            throw $v;
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Produk belum bisa disimpan. Cek data.');
        }
    }

    public function edit($id)
    {
        if ($guard = $this->ensureProductPermission('updateProduct')) {
            return $guard;
        }

        $product = Product::findOrFail($id);
        if ($message = $this->getApprovalLockMessage($product)) {
            return redirect()->route('product.view', $product->fprdid)->with('error', $message);
        }
        $groups = Groupproduct::where('fnonactive', 0)->get();
        $merks = Merek::where('fnonactive', 0)->get();
        $satuan = Satuan::where('fnonactive', 0)->get();
        $usageInfo = $this->getProductUsageInfo($product);
        $enabledImageNumbers = $this->getEnabledProductImageNumbers();

        return view('product.edit', [
            'product' => $product,
            'groups' => $groups,
            'merks' => $merks,
            'satuan' => $satuan,
            'action' => 'edit',
            'usageInfo' => $usageInfo,
            'enabledImageNumbers' => $enabledImageNumbers,
        ]);
    }

    public function view($id)
    {
        if ($guard = $this->ensureProductPermission('viewProduct')) {
            return $guard;
        }

        $product = Product::findOrFail($id);
        $groups = Groupproduct::where('fnonactive', 0)->get();
        $merks = Merek::where('fnonactive', 0)->get();
        $satuan = Satuan::where('fnonactive', 0)->get();
        $enabledImageNumbers = $this->getEnabledProductImageNumbers();

        return view('product.view', [
            'product' => $product,
            'groups' => $groups,
            'merks' => $merks,
            'satuan' => $satuan,
            'enabledImageNumbers' => $enabledImageNumbers,
            'approvalLockMessage' => $this->getApprovalLockMessage($product),
        ]);
    }

    public function update(Request $request, $fprdid)
    {
        if ($guard = $this->ensureProductPermission('updateProduct')) {
            return $guard;
        }

        $product = Product::findOrFail($fprdid);
        if ($message = $this->getApprovalLockMessage($product)) {
            return redirect()->route('product.view', $product->fprdid)->with('error', $message);
        }
        try {
            $shouldSendApprovalNotification = false;
            $usageInfo = $this->getProductUsageInfo($product);
            $enabledImageFields = $this->getEnabledProductImageFields();

            $validationRules = [
                'fprdcode' => "required|string|unique:msprd,fprdcode,{$fprdid},fprdid",
                'fprdname' => 'required|string',
                'ftype' => 'string',
                'fbarcode' => 'nullable',
                'fgroupcode' => 'required',
                'fmerek' => 'required',
                'fsatuankecil' => 'required',
                'fsatuanbesar' => ['nullable', 'string', 'different:fsatuankecil'],
                'fsatuanbesar2' => [
                    'nullable',
                    'string',
                    'different:fsatuankecil',
                    'different:fsatuanbesar',
                ],
                'fsatuandefault' => 'in:1,2,3',
                'fsatuandefaultlaporan' => 'in:1,2,3',
                'fqtykecil' => [
                    'nullable',
                    'numeric',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($request->filled('fsatuanbesar') && (float) $value <= 1) {
                            $fail('Isi Satuan 2 harus lebih besar dari 1.');
                        }
                    },
                ],
                'fqtykecil2' => [
                    'nullable',
                    'numeric',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($request->filled('fsatuanbesar2') && (float) $value <= 1) {
                            $fail('Isi Satuan 3 harus lebih besar dari 1.');
                        }
                        if (
                            $request->filled('fsatuanbesar') && $request->filled('fsatuanbesar2')
                            && trim((string) $request->input('fsatuanbesar')) !== trim((string) $request->input('fsatuanbesar2'))
                            && (float) $request->input('fqtykecil') === (float) $value
                        ) {
                            $fail('Isi Satuan 3 tidak boleh sama dengan Isi Satuan 2 jika satuannya berbeda.');
                        }
                    },
                ],
                'fminstock' => 'numeric',
                'fhpp' => 'nullable',
                'fhpp2' => 'nullable',
                'fhpp3' => 'nullable',
            ];

            foreach ($enabledImageFields as $imageField) {
                $validationRules[$imageField] = 'nullable|image|max:2048';
            }

            $validated = $request->validate(
                $validationRules,
                [
                    'fprdcode.unique' => 'Kode produk sudah ada.',
                    'fprdname.required' => 'Nama produk wajib diisi.',
                    'fgroupcode.required' => 'Group produk wajib diisi.',
                    'fmerek.required' => 'Merek wajib diisi.',
                    'fsatuankecil.required' => 'Satuan 1 wajib diisi.',
                    'fsatuanbesar.string' => 'Satuan 2 tidak valid.',
                    'fsatuanbesar.different' => 'Satuan 2 tidak boleh sama dengan Satuan 1.',
                    'fsatuanbesar2.string' => 'Satuan 3 tidak valid.',
                    'fsatuanbesar2.different' => 'Satuan 3 tidak boleh sama dengan Satuan 1 atau 2.',
                    'fqtykecil.numeric' => 'Satuan 2 harus angka.',
                    'fqtykecil2.numeric' => 'Satuan 3 harus angka.',
                ]
            );

            $validated['fprdcode'] = strtoupper($validated['fprdcode']);
            $validated['fprdname'] = strtoupper($validated['fprdname']);

            $shouldSendApprovalNotification = false;

            $sanitizeNumeric = function ($value) {
                if ($value === null || $value === '') {
                    return 0;
                }
                $clean = preg_replace('/[^0-9.]/', '', $value);

                return (is_numeric($clean)) ? (float) $clean : 0;
            };

            $numericFields = [
                'fhpp',
                'fhpp2',
                'fhpp3',
                'fhargajuallevel1',
                'fhargajuallevel2',
                'fhargajuallevel3',
                'fhargajual2level1',
                'fhargajual2level2',
                'fhargajual2level3',
                'fhargajual3level1',
                'fhargajual3level2',
                'fhargajual3level3',
                'fqtykecil',
                'fqtykecil2',
                'fminstock',
            ];

            foreach ($numericFields as $field) {
                $validated[$field] = $sanitizeNumeric($request->input($field));
            }

            if ($usageInfo['is_used']) {
                $normalizeText = fn($value) => strtoupper(trim((string) ($value ?? '')));
                $normalizeNumber = fn($value) => (float) ($value ?? 0);

                $unitFields = ['fsatuankecil', 'fsatuanbesar', 'fsatuanbesar2'];
                $qtyFields = [
                    'fsatuanbesar' => 'fqtykecil',
                    'fsatuanbesar2' => 'fqtykecil2',
                ];
                $unitLabels = [
                    'fsatuankecil' => 'Satuan 1',
                    'fsatuanbesar' => 'Satuan 2',
                    'fsatuanbesar2' => 'Satuan 3',
                ];

                $errors = [];

                if ($normalizeText($product->fprdcode) !== $normalizeText($validated['fprdcode'] ?? null)) {
                    $errors['fprdcode'] = 'Kode produk tidak bisa diubah. Sudah dipakai transaksi.';
                }

                foreach ($unitFields as $field) {
                    if ($normalizeText($product->{$field}) !== $normalizeText($validated[$field] ?? null)) {
                        $errors[$field] = ($unitLabels[$field] ?? 'Satuan') . ' tidak bisa diubah. Sudah dipakai transaksi.';
                    }
                }

                foreach ($qtyFields as $unitField => $qtyField) {
                    $oldUnit = $normalizeText($product->{$unitField});
                    $oldQty = $normalizeNumber($product->{$qtyField});
                    $newQty = $normalizeNumber($validated[$qtyField] ?? null);

                    if (abs($oldQty - $newQty) > 0.000001) {
                        $errors[$qtyField] = 'Qty konversi untuk ' . $oldUnit . ' tidak bisa diubah. Sudah dipakai transaksi.';
                    }
                }

                if (! empty($errors)) {
                    throw ValidationException::withMessages($errors);
                }
            }

            $userLogin = auth('sysuser')->user();
            $validated['fupdatedby'] = auth('sysuser')->user()->fname ?? null;
            $validated['fupdatedat'] = now();
            $validated['fnonactive'] = $request->has('fnonactive') ? '1' : '0';
            $needsApprovalNotification = $this->shouldRequestProductApproval($request);

            $googleDriveService = new GoogleDriveService;
            foreach ($enabledImageFields as $imageField) {
                if ($request->hasFile($imageField) && $request->file($imageField)->isValid()) {
                    try {
                        if (! empty($product->{$imageField})) {
                            $oldFileId = $this->normalizeGoogleDriveFileId($product->{$imageField});
                            if ($oldFileId) {
                                $googleDriveService->deleteImage($oldFileId);
                            }
                        }

                        $fileId = $googleDriveService->uploadImage($request, $imageField);
                        if ($fileId) {
                            $validated[$imageField] = $fileId;
                        }
                    } catch (\Exception $e) {
                        return redirect()->back()->with('error', 'Produk belum bisa diupdate. Cek data.');
                    }
                }
            }

            $product->update($validated);

            DB::table('logprd')->insert([
                'fprdid'                  => $product->fprdid,
                'fprdcode'                => $product->fprdcode,
                'fprdname'                => $product->fprdname,
                'fgroupcode'              => $product->fgroupcode,
                'ftype'                   => $product->ftype,
                'fhpp'                    => $product->fhpp,
                'fstok'                   => $product->fstok,
                'fhargajuallevel1'        => $product->fhargajuallevel1,
                'fhargajuallevel2'        => $product->fhargajuallevel2,
                'fhargajuallevel3'        => $product->fhargajuallevel3,
                'fhargajual2level1'       => $product->fhargajual2level1,
                'fhargajual2level2'       => $product->fhargajual2level2,
                'fhargajual2level3'       => $product->fhargajual2level3,
                'fnonactive'              => $product->fnonactive,
                'fcreatedby'              => $product->fcreatedby,
                'fupdatedby'              => $product->fupdatedby,
                'fcreatedat'              => $product->fcreatedat,
                'fupdatedat'              => $product->fupdatedat,
                'fmerek'                  => $product->fmerek,
                'fminmargin'              => $product->fminmargin,
                'fformula'                => $product->fformula,
                'fminstock'               => $product->fminstock,
                'fsatuanbesar'            => $product->fsatuanbesar,
                'fsatuanbesar2'           => $product->fsatuanbesar2,
                'fhargasatuankecillevel1' => $product->fhargasatuankecillevel1,
                'fhargasatuankecillevel2' => $product->fhargasatuankecillevel2,
                'fhargasatuankecillevel3' => $product->fhargasatuankecillevel3,
                'ftypeproduct'            => $product->ftypeproduct,
                'fsatuankecil'            => $product->fsatuankecil,
                'fqtykecil'               => $product->fqtykecil,
                'fsatuandefault'          => $product->fsatuandefault,
                'fimagename'              => $product->fimagename,
                'fbarcode'                => $product->fbarcode,
                'fapproval'               => $product->fapproval,
                'fhpp2'                   => $product->fhpp2,
                'fhpp3'                   => $product->fhpp3,
                'fhargajual3level1'       => $product->fhargajual3level1,
                'fhargajual3level2'       => $product->fhargajual3level2,
                'fhargajual3level3'       => $product->fhargajual3level3,
                'fimage1'                 => $product->fimage1,
                'fimage2'                 => $product->fimage2,
                'fimage3'                 => $product->fimage3,
                'fqtykecil2'              => $product->fqtykecil2,
                'fuserapproved'           => $product->fuserapproved,
                'fdateapproved'           => $product->fdateapproved,
                'fapproval_reason'        => $product->fapproval_reason,
                'fapproval_token'         => $product->fapproval_token,
                'fapproval2'              => $product->fapproval2,
                'fuserapproved2'          => $product->fuserapproved2,
                'fdateapproved2'          => $product->fdateapproved2,
                'fapproval_reason2'       => $product->fapproval_reason2,
                'fapproval_token2'        => $product->fapproval_token2,
                'fsatuandefaultlaporan'   => $product->fsatuandefaultlaporan,
                'feditmode'               => 'U', // Update
                'fuseridlog'              => $userLogin->fname ?? null,
                'fdatetimelog'            => now(),
            ]);

            if ($needsApprovalNotification && $shouldSendApprovalNotification) {
                $product->refresh();
                $this->sendApprovalNotification($product, auth('sysuser')->user()->fname ?? 'System');
            }

            return redirect()
                ->route('product.index')
                ->with('success', 'Produk berhasil diupdate.');
        } catch (ValidationException $e) {
            return redirect()
                ->route('product.edit', $product->fprdid)
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Produk belum bisa diupdate. Cek data.');
        }
    }

    public function deletePhoto($fprdid, $field = 'fimage1')
    {
        if (! $this->hasRestrictedPermission('updateProduct')) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke menu produk.',
            ], 403);
        }

        $product = Product::findOrFail($fprdid);
        $field = request()->query('field', $field);
        $allowedFields = ['fimage1', 'fimage2', 'fimage3'];
        if (! in_array($field, $allowedFields, true)) {
            return response()->json([
                'message' => 'Field foto tidak valid.',
            ], 422);
        }

        if (empty($product->{$field})) {
            return response()->json([
                'message' => 'Foto produk tidak ada.',
            ], 422);
        }

        try {
            $googleDriveService = new GoogleDriveService;
            $fileId = $this->normalizeGoogleDriveFileId($product->{$field});

            if ($fileId) {
                $googleDriveService->deleteImage($fileId);
            }

            $product->update([$field => null]);

            return response()->json([
                'message' => 'Foto product berhasil dihapus.',
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Gagal menghapus foto product.',
            ], 500);
        }
    }

    public function photo($fprdid, $field = 'fimage1')
    {
        if (! $this->hasRestrictedPermission('viewProduct')) {
            abort(403);
        }

        $product = Product::findOrFail($fprdid);
        $field = request()->query('field', $field);
        $allowedFields = ['fimage1', 'fimage2', 'fimage3'];
        if (! in_array($field, $allowedFields, true)) {
            abort(404);
        }

        if (empty($product->{$field})) {
            abort(404);
        }

        $fileId = $this->normalizeGoogleDriveFileId($product->{$field});
        if (! $fileId) {
            abort(404);
        }

        try {
            $googleDriveService = new GoogleDriveService;
            $imageData = $googleDriveService->getImageContent($fileId);

            if (! $imageData || empty($imageData['content'])) {
                abort(404);
            }

            return response($imageData['content'], 200, [
                'Content-Type' => $imageData['mimeType'] ?? 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="' . ($imageData['name'] ?? 'product-image') . '"',
                'Cache-Control' => 'private, max-age=300',
            ]);
        } catch (\Exception $e) {
            abort(500);
        }
    }

    public function delete($fprdid)
    {
        if ($guard = $this->ensureProductPermission('deleteProduct')) {
            return $guard;
        }

        $product = Product::with('merek')->findOrFail($fprdid);
        $usageInfo = $this->getProductUsageInfo($product);

        return view('product.delete', [
            'product' => $product,
            'usageInfo' => $usageInfo,
        ]);
    }

    public function destroy($fprdid)
    {
        if (! $this->hasRestrictedPermission('deleteProduct')) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke menu produk.',
            ], 403);
        }

        try {
            $product = Product::findOrFail($fprdid);
            $usageInfo = $this->getProductUsageInfo($product);

            if ($usageInfo['is_used']) {
                return response()->json([
                    'message' => 'Produk ' . $product->fprdcode . ' tidak bisa dihapus. Sudah direferensi di ' . implode(', ', $usageInfo['used_by']) . '.',
                ], 422);
            }

            $userLogin = auth('sysuser')->user();

            // 1. Selalu INSERT log baru sebelum data utama di-delete (feditmode = 'D')
            DB::table('logprd')->insert([
                'fprdid'                  => $product->fprdid,
                'fprdcode'                => $product->fprdcode,
                'fprdname'                => $product->fprdname,
                'fgroupcode'              => $product->fgroupcode,
                'ftype'                   => $product->ftype,
                'fhpp'                    => $product->fhpp,
                'fstok'                   => $product->fstok,
                'fhargajuallevel1'        => $product->fhargajuallevel1,
                'fhargajuallevel2'        => $product->fhargajuallevel2,
                'fhargajuallevel3'        => $product->fhargajuallevel3,
                'fhargajual2level1'       => $product->fhargajual2level1,
                'fhargajual2level2'       => $product->fhargajual2level2,
                'fhargajual2level3'       => $product->fhargajual2level3,
                'fnonactive'              => $product->fnonactive,
                'fcreatedby'              => $product->fcreatedby,
                'fupdatedby'              => $product->fupdatedby,
                'fcreatedat'              => $product->fcreatedat,
                'fupdatedat'              => $product->fupdatedat,
                'fmerek'                  => $product->fmerek,
                'fminmargin'              => $product->fminmargin,
                'fformula'                => $product->fformula,
                'fminstock'               => $product->fminstock,
                'fsatuanbesar'            => $product->fsatuanbesar,
                'fsatuanbesar2'           => $product->fsatuanbesar2,
                'fhargasatuankecillevel1' => $product->fhargasatuankecillevel1,
                'fhargasatuankecillevel2' => $product->fhargasatuankecillevel2,
                'fhargasatuankecillevel3' => $product->fhargasatuankecillevel3,
                'ftypeproduct'            => $product->ftypeproduct,
                'fsatuankecil'            => $product->fsatuankecil,
                'fqtykecil'               => $product->fqtykecil,
                'fsatuandefault'          => $product->fsatuandefault,
                'fimagename'              => $product->fimagename,
                'fbarcode'                => $product->fbarcode,
                'fapproval'               => $product->fapproval,
                'fhpp2'                   => $product->fhpp2,
                'fhpp3'                   => $product->fhpp3,
                'fhargajual3level1'       => $product->fhargajual3level1,
                'fhargajual3level2'       => $product->fhargajual3level2,
                'fhargajual3level3'       => $product->fhargajual3level3,
                'fimage1'                 => $product->fimage1,
                'fimage2'                 => $product->fimage2,
                'fimage3'                 => $product->fimage3,
                'fqtykecil2'              => $product->fqtykecil2,
                'fuserapproved'           => $product->fuserapproved,
                'fdateapproved'           => $product->fdateapproved,
                'fapproval_reason'        => $product->fapproval_reason,
                'fapproval_token'         => $product->fapproval_token,
                'fapproval2'              => $product->fapproval2,
                'fuserapproved2'          => $product->fuserapproved2,
                'fdateapproved2'          => $product->fdateapproved2,
                'fapproval_reason2'       => $product->fapproval_reason2,
                'fapproval_token2'        => $product->fapproval_token2,
                'fsatuandefaultlaporan'   => $product->fsatuandefaultlaporan,
                'feditmode'               => 'D', // Delete
                'fuseridlog'              => $userLogin->fname ?? null,
                'fdatetimelog'            => now(),
            ]);

            $product->delete();

            return response()->json(['message' => 'Produk ' . $product->fprdname . ' berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Produk belum bisa dihapus. Coba lagi.'], 500);
        }
    }

    public function laporan($fprdid)
    {
        if (! $this->hasRestrictedPermission('viewProduct')) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke menu produk.',
            ], 403);
        }

        $product = Product::findOrFail($fprdid);

        $stokData = DB::select('
            SELECT 
                v.fwhcode AS fwhcode, 
                w.fwhname,
                (v.fsaldo / p.fqtykecil) AS fsaldo, 
                p.fsatuanbesar
            FROM prdwh v  
            LEFT OUTER JOIN mswh w ON v.fwhcode = w.fwhcode
            LEFT OUTER JOIN msprd p ON p.fprdcode = v.fprdcode 
            WHERE v.fprdcode = :fprdcode
        ', ['fprdcode' => $product->fprdcode]);

        $customerData = DB::select('
            SELECT 
                m.fsono,
                m.frefno,
                c.fcustomername,
                d.fprdcode,
                m.fsodate,
                m.fcurrency,
                d.fpricenet AS fprice,
                d.fqty,
                CAST(d.fdesc AS CHAR(100)) AS fdesc,
                d.fsatuan
            FROM tranmt m
            JOIN trandt d ON m.fsono = d.fsono
            JOIN mscustomer c ON m.fcustno = c.fcustomercode
            WHERE d.fprdcode = :fprdcode
            ORDER BY m.fsodate DESC 
            LIMIT 30
        ', ['fprdcode' => $product->fprdcode]);

        $supplierData = DB::select("
            SELECT 
                d.fstockmtno,
                CASE 
                    WHEN m.fstockmtcode = 'BUY' THEN s.fsuppliername 
                    ELSE CAST('ADJ' AS CHAR(3)) 
                END AS fsuppliername,
                m.fstockmtdate,
                m.fcurrency,
                COALESCE(d.fprice, 0) AS fprice,
                d.fqty,
                d.fsatuan
            FROM trstockmt m 
            LEFT OUTER JOIN trstockdt d ON m.fstockmtno = d.fstockmtno 
            LEFT OUTER JOIN mssupplier s ON m.fsupplier = s.fsuppliercode
            WHERE 
                d.fqty > 0 
                AND (
                    (m.fstockmtcode = 'BUY') 
                    OR 
                    (m.fstockmtcode = 'ADJ')
                ) 
                AND d.fprdcode = :fprdcode
            ORDER BY m.fstockmtdate DESC 
        ", ['fprdcode' => $product->fprdcode]);

        $outstandingPo = DB::select("
            SELECT 
                h.fpono, 
                h.fpodate, 
                s.fsuppliername, 
                (d.fqty - COALESCE(t.fqtyterima, 0)) AS fqty 
            FROM tr_poh h  
            LEFT JOIN tr_pod d ON h.fpono = d.fpono  
            LEFT JOIN mssupplier s ON h.fsupplier = s.fsuppliercode  
            LEFT JOIN ( 
                SELECT 
                    CASE 
                        WHEN fstockmtcode = 'TER' THEN frefso 
                        ELSE frefdtno 
                    END AS frefdtno, 
                    trstockdt.fprdcode, 
                    SUM(trstockdt.fqty) AS fqtyterima  
                FROM trstockdt 
                WHERE trstockdt.fstockmtcode = 'TER' 
                   OR (trstockdt.fcode = 'P' AND trstockdt.fstockmtcode = 'BUY')
                GROUP BY 
                    CASE 
                        WHEN fstockmtcode = 'TER' THEN frefso 
                        ELSE frefdtno 
                    END, 
                    trstockdt.fprdcode
            ) t ON h.fpono = t.frefdtno AND t.fprdcode = d.fprdcode 
            WHERE (d.fqty - COALESCE(t.fqtyterima, 0)) > 0 
              AND h.fclose = '0' 
              AND d.fprdcode = :fprdcode
              AND h.fbranchcode = :fbranchcode
            ORDER BY 
                h.fpono ASC
        ", [
            'fprdcode' => $product->fprdcode,
            'fbranchcode' => $this->getCurrentBranchCode() ?: 'BG'
        ]);

        $outstandingSo = DB::select("
            SELECT 
                m.fsono,
                m.fcustno,
                c.fcustomername AS fcustname,
                d.fprdcode,
                m.fsodate,
                m.fcurrency,
                d.fpricenet AS fpricefaktur,
                d.fqty,
                s.fsalesmanname AS fsalesman
            FROM tranmt m
            INNER JOIN trandt d     ON m.fsono = d.fsono
            INNER JOIN mscustomer c ON m.fcustno = c.fcustomercode
            LEFT JOIN mssalesman s  ON m.fsalesman = s.fsalesmancode
            WHERE m.ftrcode = 'INV'
              AND d.fprdcode = :fprdcode
              AND m.fbranchcode = :fbranchcode
            ORDER BY 
                m.fsodate DESC 
            LIMIT 100
        ", [
            'fprdcode' => $product->fprdcode,
            'fbranchcode' => $this->getCurrentBranchCode() ?: 'BG'
        ]);

        return response()->json([
            'product' => [
                'fprdcode' => $product->fprdcode,
                'fprdname' => $product->fprdname,
            ],
            'stok' => $stokData,
            'customer' => $customerData,
            'supplier' => $supplierData,
            'outstanding_po' => $outstandingPo,
            'outstanding_so' => $outstandingSo,
        ]);
    }

    private function normalizeGoogleDriveFileId(?string $rawValue): ?string
    {
        if (! $rawValue) {
            return null;
        }

        $value = trim($rawValue);

        if (! str_contains($value, 'http')) {
            return $value;
        }

        if (preg_match('~/d/([a-zA-Z0-9_-]+)~', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $value, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

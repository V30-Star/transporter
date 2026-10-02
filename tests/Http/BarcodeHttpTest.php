<?php

namespace Tests\Http;

use App\Models\Barcode;
use Illuminate\Support\Facades\DB;
use Tests\Http\Concerns\MakesTestMaster;

class BarcodeHttpTest extends LiveDbTestCase
{
    use MakesTestMaster;

    private const NAME = 'ZZ_DUSK_LABEL';

    protected function setUp(): void
    {
        parent::setUp();

        $this->grantPermissions('viewProduct', 'createProduct');
    }

    private function item(array $override = []): array
    {
        return array_merge(['fprdcode' => 'ZZDUSKP1', 'fprdname' => self::NAME, 'fbarcode' => '', 'price' => 15000, 'qty' => 3], $override);
    }

    private function print(array $items, array $settings = [])
    {
        $payload = array_merge(['items' => $items, 'columns' => 2, 'show_name' => 1, 'show_price' => 1, 'show_code' => 1], $settings);

        return $this->atomic(fn () => $this->post(route('barcode.print'), $payload));
    }

    public function test_the_page_opens(): void
    {
        $this->get(route('barcode.index'))->assertOk();
    }

    public function test_product_search_returns_the_barcode_falling_back_to_the_code(): void
    {
        $this->makeProduct();

        $rows = $this->get(route('barcode.search-products', ['q' => 'ZZDUSKP1']))->assertOk()->json();
        $row = collect($rows)->firstWhere('fprdcode', 'ZZDUSKP1');
        $this->assertNotNull($row, 'Produk uji tidak ditemukan lewat pencarian.');
        $this->assertSame(trim($row['fprdcode']), trim($row['fbarcode']), 'Barcode kosong harus memakai kode produk.');
        $this->assertArrayHasKey('price', $row);
        $this->assertArrayHasKey('satuan', $row);
    }

    public function test_labels_repeat_by_qty_and_show_name_and_edited_price(): void
    {
        $html = $this->print([$this->item()])->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, self::NAME), 'Qty 3 harus mencetak 3 label.');
        $this->assertSame(3, substr_count($html, 'Rp 15.000'), 'Harga hasil edit tampil di tiap label.');
    }

    public function test_the_items_can_be_sent_as_a_json_string_like_the_form_does(): void
    {
        $html = $this->atomic(fn () => $this->post(route('barcode.print'), [
            'items' => json_encode([$this->item(['qty' => 2, 'price' => 2500])]),
            'show_name' => 1, 'show_price' => 1, 'show_code' => 1,
        ]))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, self::NAME));
        $this->assertSame(2, substr_count($html, 'Rp 2.500'));
    }

    public function test_price_is_hidden_when_zero_or_switched_off(): void
    {
        $zero = $this->print([$this->item(['price' => 0])])->getContent();
        $this->assertStringNotContainsString('Rp 0', $zero, 'Harga 0 tidak ditampilkan.');

        $off = $this->print([$this->item()], ['show_price' => 0])->getContent();
        $this->assertStringNotContainsString('Rp 15.000', $off, 'Tampilkan harga mati: harga tidak muncul.');
        $this->assertSame(3, substr_count($off, self::NAME));
    }

    public function test_qty_per_item_is_capped_on_the_server(): void
    {
        $html = $this->print([$this->item(['qty' => 10050])], ['columns' => 6])->assertOk()->getContent();

        $this->assertSame(9999, substr_count($html, self::NAME), 'Qty di atas 9999 harus dipotong di server.');
    }

    public function test_a_product_name_is_escaped_on_the_printed_label(): void
    {
        $html = $this->print([$this->item(['fprdname' => '<b>ZZ_TAG</b>', 'qty' => 1])])->getContent();

        $this->assertStringNotContainsString('<b>ZZ_TAG</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;ZZ_TAG', $html);
    }

    public function test_the_page_script_escapes_text_before_putting_it_into_html(): void
    {
        $html = $this->get(route('barcode.index'))->getContent();

        $this->assertStringContainsString('function escapeHtml', $html);
        $this->assertStringContainsString('${escapeHtml(item.fprdname)}', $html, 'Nama produk di tabel antrean harus di-escape.');
        $this->assertStringContainsString('editPriceInput', $html, 'Dialog Edit Label harus punya kolom harga.');
        $this->assertStringNotContainsString('editQtyInput', $html, 'Dialog Edit Label tidak punya kolom Jumlah Label.');
    }

    public function test_direct_print_by_product_code(): void
    {
        $this->makeProduct();

        $html = $this->get(route('barcode.print.direct', ['fprdcode' => 'ZZDUSKP1', 'qty' => 4]))->assertOk()->getContent();
        $this->assertSame(4, substr_count($html, 'ZZ_DUSK_PRODUK'), 'Cetak langsung qty 4 mencetak 4 label.');
    }

    public function test_print_remembers_the_settings_and_keeps_only_the_five_latest(): void
    {
        Barcode::query()->delete();

        // Waktu maju 1 detik per cetak: "terbaru" ditentukan oleh updated_at (presisi detik).
        $base = now();
        foreach ([30, 31, 32, 33, 34, 35, 36] as $i => $width) {
            \Carbon\Carbon::setTestNow($base->copy()->addSeconds($i));
            $this->print([$this->item(['qty' => 1])], ['label_width' => $width]);
        }

        $this->assertSame(5, Barcode::count(), 'Hanya 5 pengaturan terbaru yang disimpan.');
        $this->assertFalse(Barcode::where('label_width', 30)->exists(), 'Pengaturan terlama dihapus.');

        \Carbon\Carbon::setTestNow($base->copy()->addSeconds(10));
        $this->print([$this->item(['qty' => 1])], ['label_width' => 36]);
        \Carbon\Carbon::setTestNow();
        $this->assertSame(5, Barcode::count(), 'Pengaturan identik tidak digandakan.');
    }

    public function test_recent_settings_can_be_saved_and_cleared(): void
    {
        Barcode::query()->delete();

        $response = $this->atomic(fn () => $this->postJson(route('barcode.recent-settings.save'), ['label_width' => 40, 'label_height' => 20, 'columns' => 2]));
        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSame(1, Barcode::count());
        $this->assertEquals(40, $response->json('recents.0.labelWidth'));

        $this->atomic(fn () => $this->postJson(route('barcode.recent-settings.clear')))->assertOk();
        $this->assertSame(0, Barcode::count());
    }

    public function test_setting_limits_are_enforced(): void
    {
        Barcode::query()->delete();

        $this->atomic(fn () => $this->postJson(route('barcode.recent-settings.save'), [
            'label_width' => 1, 'label_height' => 1, 'columns' => 99, 'barcode_height' => 500, 'font_size' => 99, 'gap_x' => -5,
        ]));

        $s = Barcode::first();
        $this->assertNotNull($s);
        $this->assertEquals(10, $s->label_width);
        $this->assertEquals(8, $s->label_height);
        $this->assertEquals(6, $s->columns, 'Kolom maksimal 6.');
        $this->assertEquals(80, $s->barcode_height, 'Tinggi barcode maksimal 80.');
        $this->assertEquals(14, $s->font_size, 'Font maksimal 14.');
        $this->assertEquals(0, $s->gap_x, 'Jarak tidak boleh negatif.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Tgl;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrtuPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Semua tab orang tua harus merender tanpa error
     * (regresi: Tgl dengan input string pernah memanggil helper carbon()
     * yang tidak ada, sehingga /ortu/kehadiran 500).
     */
    public function test_ortu_tabs_render(): void
    {
        $this->seed(DatabaseSeeder::class);

        $mama = User::where('email', 'mama.aruna@sekolah.test')->firstOrFail();

        $this->actingAs($mama)->get(route('ortu.beranda'))->assertOk();
        $this->actingAs($mama)->get(route('ortu.info'))->assertOk();
        $this->actingAs($mama)->get(route('ortu.jadwal'))->assertOk();

        $response = $this->actingAs($mama)->get(route('ortu.hadir'));
        $response->assertOk();
        $response->assertSee('Riwayat kehadiran');
        $response->assertSee('Lapor tidak masuk');
    }

    /**
     * Tanggal string Y-m-d diformat panjang dengan benar.
     */
    public function test_tgl_formats_string_dates(): void
    {
        $this->assertSame('Kamis, 19 November 2026', Tgl::panjang('2026-11-19'));
        $this->assertSame('Kam 19/11', Tgl::pendek('2026-11-19'));
        $this->assertSame('08.15', Tgl::jam('2026-11-19 08:15:00'));
    }
}

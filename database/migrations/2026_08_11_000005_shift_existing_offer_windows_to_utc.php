<?php

use App\Support\LocalTime;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Offer windows created before store-time handling existed were saved with the
 * admin's local wall-clock time written straight into a UTC column, which made
 * them start and end hours off. Reinterpret those values as store time.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->shift(fn (Carbon $value) => Carbon::parse($value->format('Y-m-d H:i:s'), LocalTime::zone())->utc());
    }

    public function down(): void
    {
        $this->shift(fn (Carbon $value) => Carbon::parse($value->format('Y-m-d H:i:s'), 'UTC')->setTimezone(LocalTime::zone()));
    }

    private function shift(callable $convert): void
    {
        $rows = DB::table('product_offers')
            ->where(fn ($q) => $q->whereNotNull('starts_at')->orWhereNotNull('ends_at'))
            ->get(['id', 'starts_at', 'ends_at']);

        foreach ($rows as $row) {
            DB::table('product_offers')->where('id', $row->id)->update([
                'starts_at' => $row->starts_at ? $convert(Carbon::parse($row->starts_at, 'UTC')) : null,
                'ends_at' => $row->ends_at ? $convert(Carbon::parse($row->ends_at, 'UTC')) : null,
            ]);
        }
    }
};

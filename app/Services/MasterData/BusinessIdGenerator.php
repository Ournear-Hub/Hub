<?php

namespace App\Services\MasterData;

use App\Enums\BusinessIdPrefix;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BusinessIdGenerator
{
    public function next(BusinessIdPrefix|string $prefix): string
    {
        $prefix = $prefix instanceof BusinessIdPrefix ? $prefix->value : $prefix;

        return DB::transaction(function () use ($prefix) {
            $sequence = DB::table('business_id_sequences')
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                DB::table('business_id_sequences')->insert([
                    'prefix' => $prefix,
                    'next_number' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $this->format($prefix, 1);
            }

            $number = (int) $sequence->next_number;

            if ($number < 1) {
                throw new RuntimeException("Invalid business ID sequence for {$prefix}.");
            }

            DB::table('business_id_sequences')
                ->where('prefix', $prefix)
                ->update([
                    'next_number' => $number + 1,
                    'updated_at' => now(),
                ]);

            return $this->format($prefix, $number);
        });
    }

    public function format(string $prefix, int $number): string
    {
        return sprintf('%s-%06d', $prefix, $number);
    }
}

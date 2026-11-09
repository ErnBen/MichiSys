<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TENANT_TABLES = [
        'users',
        'categories',
        'products',
        'combos',
        'clients',
        'providers',
        'inventory_movements',
        'sales',
        'sale_product',
        'sale_combo',
        'combo_product',
        'product_provider',
    ];

    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('owner_name');
            $table->string('email')->nullable()->unique();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $owner = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->first() ?? DB::table('users')->orderBy('id')->first();

        $now = now();
        $businessId = DB::table('businesses')->insertGetId([
            'name' => 'MichiSys - Negocio inicial',
            'owner_name' => $owner->name ?? 'Administrador inicial',
            'email' => $owner->email ?? null,
            'active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($owner && $owner->role !== 'admin') {
            DB::table('users')->where('id', $owner->id)->update(['role' => 'admin']);
        }

        foreach (self::TENANT_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('business_id')
                    ->nullable()
                    ->constrained('businesses')
                    ->restrictOnDelete();
                $table->index('business_id');
            });

            DB::table($tableName)
                ->whereNull('business_id')
                ->update(['business_id' => $businessId]);
        }
    }

    public function down(): void
    {
        throw new LogicException(
            'This migration assigns existing records to businesses and cannot be rolled back without a separate data-preservation procedure.'
        );
    }
};

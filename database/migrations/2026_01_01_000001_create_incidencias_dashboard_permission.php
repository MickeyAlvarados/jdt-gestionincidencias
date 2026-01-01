<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $guardName = config('auth.defaults.guard') ?? 'web';

        $moduleId = DB::table('modulos')
            ->where('url', 'incidencias.index')
            ->value('modulo_id');

        Permission::updateOrCreate(
            ['name' => 'incidencias.dashboard', 'guard_name' => $guardName],
            ['module_id' => $moduleId, 'description' => 'DASHBOARD']
        );

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $guardName = config('auth.defaults.guard') ?? 'web';

        Permission::where(['name' => 'incidencias.dashboard', 'guard_name' => $guardName])->delete();

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};

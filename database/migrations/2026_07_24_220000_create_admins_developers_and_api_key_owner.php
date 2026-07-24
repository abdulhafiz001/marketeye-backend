<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 20)->default('admin'); // admin|moderator
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('developers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('organization')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->foreignId('developer_id')->nullable()->after('user_id')->constrained('developers')->nullOnDelete();
            $table->unsignedInteger('monthly_limit')->default(10000)->after('daily_limit');
        });

        // Activity log: point at admins table instead of users
        if (Schema::hasTable('admin_activity_log')) {
            Schema::table('admin_activity_log', function (Blueprint $table) {
                $table->dropForeign(['admin_id']);
            });
        }

        // Migrate existing role=admin/moderator users into admins (same credentials)
        if (Schema::hasTable('users')) {
            $rows = DB::table('users')->whereIn('role', ['admin', 'moderator'])->get();
            foreach ($rows as $row) {
                DB::table('admins')->insertOrIgnore([
                    'name' => $row->name,
                    'email' => $row->email,
                    'password' => $row->password,
                    'role' => $row->role === 'moderator' ? 'moderator' : 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropConstrainedForeignId('developer_id');
            $table->dropColumn('monthly_limit');
        });

        Schema::dropIfExists('developers');
        Schema::dropIfExists('admins');
    }
};

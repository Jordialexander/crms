<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('guard_name');
            $table->string('description')->nullable()->after('parent_id');
            $table->integer('level')->default(1)->after('description');
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('roles')
                ->nullOnDelete();
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('description')->nullable()->after('guard_name');
            $table->string('category')->nullable()->after('description');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'description', 'level', 'deleted_at']);
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['description', 'category', 'deleted_at']);
        });
    }
};

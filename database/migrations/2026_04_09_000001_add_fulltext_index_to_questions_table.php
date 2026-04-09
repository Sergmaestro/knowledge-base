<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\MariaDbGrammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Schema\Grammars\PostgresGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function shouldRun(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql']);
    }

    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->fullText(['title', 'content', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropFullText(['title', 'content', 'tag']);
        });
    }
};

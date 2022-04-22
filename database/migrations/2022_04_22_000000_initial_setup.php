<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InitialSetup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->rememberToken();
            $table->timestamps();
            $table->timestamp('last_login')->nullable();
        });

        Schema::create('database_access', function (Blueprint $table) {
            $table->string('name');

            $table->foreignId('user_id')->constrained('users');
        });

        Schema::create('table_access', function (Blueprint $table) {
            $table->string('database_name');
            $table->string('table_name');

            $table->foreignId('user_id')->constrained('users');
        });

        Schema::create('database_aliases', function (Blueprint $table) {
            $table->string('name');
            $table->string('alias');
        });

        Schema::create('table_aliases', function (Blueprint $table) {
            $table->string('database_name');
            $table->string('table_name');
            $table->string('alias');
        });

        Schema::create('column_aliases', function (Blueprint $table) {
            $table->string('database_name');
            $table->string('table_name');
            $table->string('column_name');
            $table->string('alias');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('database_access');
        Schema::dropIfExists('table_access');
        Schema::dropIfExists('database_aliases');
        Schema::dropIfExists('table_aliases');
        Schema::dropIfExists('column_aliases');
    }
}

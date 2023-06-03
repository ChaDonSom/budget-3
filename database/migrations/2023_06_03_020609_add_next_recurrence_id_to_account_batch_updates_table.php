<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_batch_updates', function (Blueprint $table) {
            $table->unsignedBigInteger('next_recurrence_id')->nullable()->after('note');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_batch_updates', function (Blueprint $table) {
            $table->dropColumn('next_recurrence_id');
        });
    }
};

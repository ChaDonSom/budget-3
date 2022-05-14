<?php

use App\Models\Account;
use App\Models\AccountBatchUpdate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountAccountBatchUpdateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_account_batch_update', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignIdFor(Account::class)->index();
            $table->foreignIdFor(AccountBatchUpdate::class)->index();

            $table->integer('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_account_batch_update');
    }
}

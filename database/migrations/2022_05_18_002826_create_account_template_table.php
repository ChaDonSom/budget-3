<?php

use App\Models\Account;
use App\Models\Template;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountTemplateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_template', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignIdFor(Account::class)->index();
            $table->foreignIdFor(Template::class)->index();

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
        Schema::dropIfExists('account_template');
    }
}

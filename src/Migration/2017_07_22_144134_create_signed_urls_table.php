<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSignedUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('signed_urls', function (Blueprint $table)
        {
            $table->uuid('url_signature');
            $table->uuid('user_id');
            $table->string('request_type');
            $table->integer('requested_at', false, true);
            $table->integer('expire_at', false, true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('signed_urls');
    }
}

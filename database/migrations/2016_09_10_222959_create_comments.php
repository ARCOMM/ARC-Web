<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateComments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //--- Create Comments Table
        Schema::create('comments', function($table) {
            $table->increments('id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->integer('user_id')->unsigned();
            $table->integer('join_request_id')->unsigned();
            $table->longtext('text');
        });

        //--- Create Foreign Keys
        Schema::table('comments', function($table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('join_request_id')->references('id')->on('join_requests');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}

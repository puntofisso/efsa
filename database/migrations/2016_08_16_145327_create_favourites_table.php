<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFavouritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favourites', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->enum('type', ['question', 'company']);    
            // fav_identifer = QUESTION ID if question, COMPANY NAME if company
            $table->string('fav_identifier');

            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
            $table->primary(['user_id','fav_identifier']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('favourites');
    }
}

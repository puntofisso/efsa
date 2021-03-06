<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestionsMetasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('questions_metas', function (Blueprint $table) {

            $table->string('question_id',17);
            $table->string('tag');
            $table->integer('score')->unsigned();
            $table->primary(['question_id','tag']);
            
            $table->timestamps();



        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('questions_metas');
    }
}

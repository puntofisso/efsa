<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class QuestionsLastUpdates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('Questions_LastUpdates', function (Blueprint $table) {
            $table->string('QUESTIONNUMBER', 17);
            $table->datetime('LASTUPDATED');
            $table->timestamps();
            $table->primary('QUESTIONNUMBER');
        });

        DB::insert("insert into Questions_LastUpdates (QUESTIONNUMBER, LASTUPDATED) SELECT QUESTIONNUMBER, STR_TO_DATE(LASTUPDATED, '%d/%m/%Y') FROM Questions");

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('Questions_LastUpdates');
    }
    
}

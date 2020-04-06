<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSentencesVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sentences_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sentence_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('note');
            $table->timestamps();

            $table->unique(['sentence_id', 'user_id']);

            $table->foreign('sentence_id')->references('id')->on('sentences');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sentences_votes');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePelunasansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pelunasans', function (Blueprint $table) {
            $table->id();
            $table->string('pelunasanId')->unique();
            $table->string('bayarKepada');
            $table->string('jumlah');
            $table->date('tanggal');
            $table->string('keterangan');
            $table->string('detail');
            $table->string('diterimaOleh')->nullable();
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
        Schema::dropIfExists('pelunasans');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyFundsBackupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create( 'daily_funds_backup', function ( Blueprint $table )
        {
            $table->bigIncrements( 'id' );
            $table->bigInteger( 'fund_id' );
            $table->float( 'price', 8, 4 );
            $table->timestamp( 'as_at' )->nullable();
            $table->integer( 'status' )->default( 0 );
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists( 'daily_funds_backup' );
    }
}

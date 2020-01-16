<?php

use Illuminate\Database\Seeder;

class FundsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $funds = array(
            'AmMetLife Equity Fund',
            'AmMetLife Bond Fund',
            'AmMetLife Balanced Fund',
            'AmMetLife Dana Teguh',
            'AmMetLife Oasis Islamic Equity Fund',
            'AmMetLife Global Emerging Market Fund',
            'AmMetLife Precious Metals Fund',
            'AmMetLife Global Agribusiness Fund',
            'AmMetLife Dividend Fund',
            'AmMetLife Asia Pacific REITS Fund',
            'AmMetLife Tactical Bond Fund',
        );

        foreach ( $funds as $fund )
        {
            DB::table( 'funds' )->insert( [
                'name'        => trim( strip_tags( $fund ) ),
                'description' => '',
                'status'      => 1,
                'created_at'  => date( 'Y-m-d h:i:s' ),
            ] );
        }
    }
}

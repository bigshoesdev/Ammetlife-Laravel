<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class DailyFundsBackup extends Model
{
    protected $table = 'daily_funds_backup';
    /**
    * @var array
    */
    protected $fillable = [
        'fund_id',
        'price',
        'as_at',
    ];

    /**
    * @param $funds
    */
    private static function formatFunds( $funds )
    {
        $data = [];
        if ( empty( $funds ) ) {
            return $data;
        }

        if ( !empty( $funds ) ) {
            foreach ( $funds as $fund ) {
                $data[] = [
                    'id'      => $fund->id,
                    'fund_id' => $fund->fund_id,
                    'name'    => Funds::getFundName( $fund->fund_id ),
                    'price'   => $fund->price,
                    'as_at'   => $fund->as_at != '' ? date( 'Y-m-d', strtotime( $fund->as_at ) ) : '',
                    'status'  => $fund->status == 1 ? 1 : 0,
                ];
            }
        }

        return $data;
    }

    /**
    * Returns all daily funds
    *
    * @return array
    */
    public static function getAllDailyFunds( $offset = 0, $per_page = 15 )
    {
        $data       = [];
        $query      = static::query()->skip( $offset * $per_page )->orderBy( 'id', 'DESC' )->take( $per_page )->get();

        $data['total'] = static::count();
        $data['items'] = static::formatFunds( $query );

        return $data;
    }
}
<?php

namespace App;

use App\DailyFundsBackup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class DailyFunds extends Model
{
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
    * Returns latest daily funds
    *
    * @return array
    */
    public static function getDailyFunds()
    {
        $last_date = static::query()->join( 'funds', function ( $join )
            {
                $join->on( 'daily_funds.fund_id', '=', 'funds.id' )->where( 'funds.status', '=', 1 );
            } )->where( 'daily_funds.status', 1 )->whereDate('daily_funds.as_at', '<=', date('Y-m-d'))->orderBy( 'daily_funds.as_at', 'DESC' )->first()->as_at;

        $funds = static::query()->join( 'funds', function ( $join )
            {
                $join->on( 'daily_funds.fund_id', '=', 'funds.id' )->where( 'funds.status', '=', 1 );
            } )->where( 'daily_funds.status', 1 )->whereDate( 'daily_funds.as_at', '=', date( 'Y-m-d', strtotime( $last_date ) ) )->get();

        $data = [
            'date'  => $last_date,
            'funds' => [],
        ];

        $data['funds'] = self::formatFunds( $funds );

        return $data;
    }

    /**
    * Returns all daily funds
    *
    * @return array
    */
    public static function getAllDailyFunds( $offset = 0, $per_page = 15, $status = '', $date = '' )
    {
        $data       = [];
        $countQuery = static::query();
        $query      = static::query();
        if ( $status != '' && $status != null ) {
            $countQuery = $countQuery->where( 'status', '=', $status );
            $query      = $query->where( 'status', '=', $status );
        }
        if ( $date != '' && $date != null ) {
            $countQuery = $countQuery->whereDate( 'as_at', '=', $date );
            $query      = $query->whereDate( 'as_at', '=', $date );
        }
        $query = $query->skip( $offset * $per_page )->orderBy( 'id', 'DESC' )->take( $per_page )->get();

        $data['total'] = $countQuery->count();
        $data['items'] = static::formatFunds( $query );

        return $data;
    }

    /**
    * Returns Current As At Value
    *
    * @param int $id
    */
    public static function getCurrentAsAt( $id )
    {
        return static::query()->where( 'fund_id', $id )->where( 'status', 1 )->whereDate('as_at', '<=', date('Y-m-d'))->orderBy( 'as_at', 'DESC' )->first()->as_at;
    }

    /**
    * Returns min date for datepicker
    *
    * @param int $id
    */
    public static function getMinDate( $id )
    {
        return static::query()->where( 'fund_id', $id )->where( 'status', 1 )->whereDate('as_at', '<=', date('Y-m-d'))->orderBy( 'as_at', 'ASC' )->first()->as_at;
    }

    /**
    * Returns max date for datepicker
    *
    * @param int $id
    */
    public static function getMaxDate( $id )
    {
        return static::query()->where( 'fund_id', $id )->where( 'status', 1 )->whereDate('as_at', '<=', date('Y-m-d'))->orderBy( 'as_at', 'DESC' )->first()->as_at;
    }

    /**
    * @param $id
    */
    public static function getStartPrice( $id )
    {
        return static::query()->where( 'fund_id', $id )->where( 'status', 1 )->orderBy( 'id', 'ASC' )->first()->price;
    }

    /**
    * @param $id
    */
    public static function getCurrentPrice( $id )
    {
        return static::query()->where( 'fund_id', $id )->where( 'status', 1 )->orderBy( 'id', 'DESC' )->first()->price;
    }

    /**
    * @param $data
    * @param $date
    */
    public static function validateBeforeInsert( $data )
    {
        $date = $data[0]['as_at'];
        $ids  = Arr::pluck( $data, 'fund_id' );

        $query = static::query()->whereDate( 'as_at', $date )->whereIn( 'fund_id', $ids );

        //$sql = str_replace_array( '?', $query->getBindings(), $query->toSql() );
        //return $sql;

        return $query->get()->isEmpty();
    }

    /**
    * @param $data
    * @param $date
    */
    public static function validateDate( $date )
    {
        $max_date   = static::query()->orderBy( 'id', 'DESC' )->first()->as_at;
        $check_date = date( 'Y-m-d', strtotime( $max_date . ' -1 months' ) );

        return strtotime( $date ) > strtotime( $check_date ) ? true : false;
    }

    /**
    * @param $data
    */
    public static function importData( $data )
    {
        $insert = static::insert( $data );
        self::backupRecords();
        return $insert;
    }

    /**
    * @param $fund_id
    */
    public static function getChartData( $id, $from = '', $to = '' )
    {
        $query = static::query()->where( 'fund_id', $id )->where( 'status', 1 );
        if ( $from != '' ) {
            $query = $query->whereDate( 'as_at', '>=', date( 'Y-m-d', strtotime( $from ) ) );
        }
        if ( $to != '' ) {
            $query = $query->whereDate( 'as_at', '<=', date( 'Y-m-d', strtotime( $to ) ) );
        } else {
            $query = $query->whereDate('as_at', '<=', date('Y-m-d'));
        }
        $query = $query->orderBy( 'as_at', 'ASC' )->get();

        return $query;
    }

    public static function backupRecords()
    {
        $max_date   = static::query()->orderBy( 'id', 'DESC' )->first()->as_at;
        $check_date = date( 'Y-m-d', strtotime( $max_date . ' -2 years' ) );
        $rows = static::query()->whereDate( 'as_at', '<', $check_date )->get();
        if ( !$rows->isEmpty() ) {
            $rows = $rows->toArray();
            if ( DailyFundsBackup::insert( $rows ) ) {
                static::query()->whereDate( 'as_at', '<', $check_date )->delete();
            }
        }
    }

    /**
    * @param $fund_id
    * @param $price
    * @return mixed
    */
    public static function updateDailyFund( $fund_id, $price )
    {
        $fund = static::findOrFail( $fund_id );
        $fund->price = $price;
        $fund->save();

        return $fund;
    }

    /**
    * @param $date
    * @return mixed
    */
    public static function deleteDailyFund( $date )
    {
        $date = date( 'Y-m-d', strtotime( $date ) );
        static::query()->whereDate( 'as_at', '=', $date )->delete();
    }
}
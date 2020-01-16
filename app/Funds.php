<?php
namespace App;
use Illuminate\Database\Eloquent\Model;

class Funds extends Model
{
    /**
    * @var array
    */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
    * @param $key
    * @param $value
    */
    private static function getBy( $value = null, $key = 'id' )
    {
        return static::query()->where( $key, $value )->first();
    }

    /**
    * @param $id
    */
    public static function getFunds()
    {
        return static::query()->where( 'status', 1 )->get();
    }

    /**
    * @param $id
    */
    public static function getFund( $id = 0 )
    {
        return static::findOrFail( $id );
    }

    /**
    * @param $id
    */
    public static function getFundName( $value = null )
    {
        return self::findOrFail( $value )->name;
    }

    /**
    * @param $id
    */
    public static function getFundDesc( $value = null )
    {
        return self::findOrFail( $value )->description;
    }

    /**
    * @param $id
    */
    public static function getFundDate( $value = null )
    {
        return self::findOrFail( $value )->created_at;
    }

    /**
    * @param $id
    */
    public static function getFundDetails( $id )
    {
        $data = [];
        $fund = static::getFund( $id );
        if ( $fund && $fund->status == 1 ) {
            $current_price = DailyFunds::getCurrentPrice( $fund->id );
            $start_price   = DailyFunds::getStartPrice( $fund->id );
            $data          = [
                'id'           => $fund->id,
                'name'         => $fund->name,
                'description'  => $fund->description,
                'as_at'        => DailyFunds::getCurrentAsAt( $fund->id ),
                'price'        => $current_price,
                'price_change' => $current_price - $start_price,
                'map'          => DailyFunds::getChartData( $fund->id ),
                'min_date'     => DailyFunds::getMinDate( $fund->id ),
                'max_date'     => DailyFunds::getMaxDate( $fund->id ),
            ];
        }

        return $data;
    }

    /**
    * @param $name
    * @return mixed
    */
    public static function isNameExistOrCreate( $name = '' )
    {
        if ( empty( $name ) ) {
            return true;
        }

        $fund = self::getBy( trim( $name ), 'name' );
        if ( $fund ) {
            return $fund->id;
        }
        else {
            return static::insertGetId(
                [
                    'name'        => trim( $name ),
                    'description' => '',
                    'status'      => 1,
                    'created_at'  => date( 'Y-m-d h:i:s' ),
                ]
            );
        }
    }

    /**
    * Returns all daily funds
    *
    * @return array
    */
    public static function getAllFunds( $offset = 0, $per_page = 15 )
    {
        $data          = [];
        $data['total'] = static::count();
        $data['items'] = static::skip( $offset * $per_page )->orderBy( 'id', 'ASC' )->take( $per_page )->get();

        return $data;
    }

    /**
    * @param $fund_id
    * @param $description
    * @return mixed
    */
    public static function updateFund( $fund_id, $description, $status )
    {
        $fund              = static::findOrFail( $fund_id );
        $fund->description = $description;
        $fund->status      = (int) $status;
        $fund->save();

        return $fund;
    }
}

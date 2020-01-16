<?php

namespace App\Http\Controllers;

use App\DailyFundsBackup;
use App\DailyFunds;
use App\Funds;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FundController extends Controller
{
    /**
    * @return mixed
    */
    public function getDailyFunds()
    {
        try {
            $data = DailyFunds::getDailyFunds();

            return new JsonResponse( ['status_code' => 200, 'data' => $data] );
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }

    /**
    * @return mixed
    */
    public function getFundDetails( Request $request, $fund_id )
    {
        try {
            $data = Funds::getFundDetails( $fund_id );

            return new JsonResponse( ['status_code' => 200, 'data' => $data] );
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }

    /**
    * @return mixed
    */
    public function getFundsList()
    {
        try {
            $data = Funds::getFunds();

            return new JsonResponse( ['status_code' => 200, 'data' => $data] );
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }

    /**
    * @param Request $request
    * @return mixed
    */
    public function getChartData( Request $request )
    {
        try {
            $fund_id = $request->input( 'fund_id' );
            if ( $fund_id == '' ) {
                return self::sendInvalidResponse( 'Requested fund informaton not available.' );
            }
            $from = $request->input( 'from' );
            $to   = $request->input( 'to' );
            $data = DailyFunds::getChartData( $fund_id, $from, $to );

            return new JsonResponse( ['status_code' => 200, 'data' => $data] );
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }

    /**
    * @param Request $request
    * @return mixed
    */
    public function allDailyFunds( Request $request )
    {
        try {
            $offset   = $request->input( 'offset' );
            $per_page = $request->input( 'per_page' );
            $status   = $request->input( 'status' );
            $date     = $request->input( 'date' );
            $data     = DailyFunds::getAllDailyFunds( $offset, $per_page, $status, $date );

            return new JsonResponse( ['status_code' => 200, 'data' => $data ] );
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }

    /**
    * @param Request $request
    * @return mixed
    */
    public function allDailyFundsBackup( Request $request )
    {
        try {
            $offset   = $request->input( 'offset' );
            $per_page = $request->input( 'per_page' );
            $data     = DailyFundsBackup::getAllDailyFunds( $offset, $per_page );

            return new JsonResponse( ['status_code' => 200, 'data' => $data ] );
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }

    /**
    * @param Request $request
    * @return mixed
    */
    public function allFunds( Request $request )
    {
        try {
            $offset   = $request->input( 'offset' );
            $per_page = $request->input( 'per_page' );
            $data     = Funds::getAllFunds( $offset, $per_page );

            return new JsonResponse( ['status_code' => 200, 'data' => $data] );
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }

    /**
    * @return mixed
    */
    public function uploadFunds( Request $request )
    {
        try {
            $step = $request->input( 'step' );
            $step = (int) $step;
            if ( $step == 1 ) {
                $file = $request->file( 'file' );

                return self::processUploadFile( $file );
            }
            else if ( $step == 2 ) {
                $data = $request->input( 'data' );

                return self::processFundsImport( $data );
            }

            return self::sendInvalidResponse();
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }

    /**
    * @param $message
    */
    private static function sendInvalidResponse( $message = '' )
    {
        $message = !empty( $message ) ? $message : 'Something went wrong, Please try again.';

        return new JsonResponse( [
                'status_code' => 404,
                'error'       => $message,
            ], Response::HTTP_NOT_FOUND );
    }

    /**
    * @param $row
    * @param $col
    */
    private static function pluckByKey( $data = [], $row = null, $col = null )
    {
        if ( empty( $data ) || empty( $row ) || empty( $col ) ) {
            return false;
        }

        if ( isset( $data[$row][$col] ) && !empty( $data[$row][$col] ) ) {
            return trim( $data[$row][$col] );
        }

        return false;
    }

    /**
    * @param array $data
    */
    private static function pluckFundDate( $data = [] )
    {
        $string  = self::pluckByKey( $data, 1, 1 );
        $matches = array();
        preg_match( '/[0-9]{1,2} [a-zA-Z]{1,3} [0-9]{4}/', $string, $matches );
        if ( isset( $matches[0] ) ) {
            return date( 'Y-m-d', strtotime( $matches[0] ) );
        }
        else {
            return false;
        }
    }

    /**
    * @param array $data
    */
    private static function pluckFundRows( $data = [] )
    {
        if ( empty( $data ) ) {
            return false;
        }

        $start = 4;
        if ( isset( $data[$start] ) ) {
            $pluckData = array_slice( $data, $start, count( $data ) - $start );
            $pluckData = array_filter( $pluckData );

            if ( empty( $pluckData ) ) {
                return false;
            }

            $rows = [];
            foreach ( $pluckData as $row ) {
                if ( !empty( $row[1] ) && !empty( $row[2] ) ) {
                    $rows[] = [$row[1], $row[2]];
                }
            }

            if ( empty( $rows ) ) {
                return false;
            }

            if ( count( $rows ) != count( array_filter( $rows ) ) ) {
                return false;
            }

            return array_filter( $rows );
        }

        return false;
    }

    /**
    * @param array $data
    */
    private static function prepareDataForProcess( $data = [] )
    {
        if ( empty( $data ) ) {
            return false;
        }

        $formattedData = [];

        foreach ( $data['rows'] as $row ) {
            $formattedData[] = [
                'name'  => $row[0],
                'price' => round( $row[1], 4 ),
                'date'  => $data['date'],
            ];
        }

        return $formattedData;
    }

    /**
    * @param $file
    */
    private static function processUploadFile( $file )
    {

        $data = [];

        $reader = ReaderEntityFactory::createReaderFromFile( $file->getClientOriginalName() );
        $reader->setShouldPreserveEmptyRows( true );
        $reader->open( $file->getPathName() );
        foreach ( $reader->getSheetIterator() as $sheet ) {
            foreach ( $sheet->getRowIterator() as $row ) {
                $data[] = $row->toArray();
            }
        }
        $reader->close();

        if ( empty( $data ) ) {
            return self::sendInvalidResponse( 'File is empty, Please upload the valid file.' );
        }

        $fundDate = self::pluckFundDate( $data );
        $fundRows = self::pluckFundRows( $data );

        if ( $fundDate === false ) {
            return self::sendInvalidResponse( 'Fund Date is not available in cell B2, Please check your file data.' );
        }

        if ( $fundRows === false ) {
            return self::sendInvalidResponse( 'Fund Data format is invalid or have empty data, Please check your file data.' );
        }

        $preparedData = self::prepareDataForProcess(
            [
                'date' => $fundDate,
                'rows' => $fundRows,
            ]
        );

        return new JsonResponse( [
                'status_code' => 200,
                'message'     => 'File processed successfully.',
                'data'        => $preparedData,
            ], Response::HTTP_OK );
    }

    /**
    * @param $data
    */
    private static function processFundsImport( $data )
    {

        if ( empty( $data ) ) {
            return self::sendInvalidResponse();
        }

        $validatedData  = true;
        $dailyFundsData = [];
        foreach ( $data as $row ) {
            $dailyFundsData[] = [
                'fund_id' => Funds::isNameExistOrCreate( $row['name'] ),
                'price'   => $row['price'],
                'as_at'   => date( 'Y-m-d', strtotime( $row['date'] ) ),
            ];
        }

        $validatedDate = DailyFunds::validateDate( $dailyFundsData[0]['as_at'] );

        if ( !$validatedDate ) {
            return self::sendInvalidResponse( 'Funds date is 1 month older than latest funds date.' );
        }

        $validatedData = DailyFunds::validateBeforeInsert( $dailyFundsData );

        if ( !$validatedData ) {
            return self::sendInvalidResponse( 'Funds records already available with matching name and date.' );
        }

        $importData = DailyFunds::importData( $dailyFundsData );

        return new JsonResponse( ['status_code' => 200, 'data' => $importData] );
    }

    /**
    * @param $data
    */
    public static function updateFund( Request $request )
    {
        try {
            $fund_id     = $request->input( 'id' );
            $description = $request->input( 'description' );
            $status = $request->input( 'status' );
            $data        = Funds::updateFund( $fund_id, $description, $status );

            return new JsonResponse( ['status_code' => 200, 'data' => $data] );
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }

    /**
    * @param $data
    */
    public static function updateStatus( Request $request )
    {
        try {
            $date   = $request->input( 'date' );
            $status = $request->input( 'status' );
            $status = (int) $status;
            $data   = DailyFunds::whereDate( 'as_at', '=', $date )->update( ['status' => $status] );

            return new JsonResponse( ['status_code' => 200, 'data' => $data] );
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }

    /**
    * @param $data
    */
    public static function updateDailyFund( Request $request )
    {
        try {
            $fund_id = $request->input( 'id' );
            $price = $request->input( 'price' );

            $priceReg = "/^[+-]?([0-9]{1,4}[.])?[0-9]{0,4}$/";

            /* validation */
            if(empty($fund_id)) {
                return self::sendInvalidResponse( 'Please try again later.' );
            } else if(empty($price)) {
                return self::sendInvalidResponse( 'Price is required.' );
            } elseif (!preg_match($priceReg,$price)) {
                return self::sendInvalidResponse( 'Price is invalid.' );
            } else {
                $data = DailyFunds::updateDailyFund( $fund_id, $price );
                return new JsonResponse( ['status_code' => 200, 'data' => $data] );
            }
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }

    /**
    * @param $data
    */
    public static function deleteDailyFund( Request $request )
    {
        try {
            $date = $request->input( 'date' );

            /* validation */
            if(empty($date)) {
                return self::sendInvalidResponse( 'Date is required.' );
            } else {
                $data = DailyFunds::deleteDailyFund( $date );
                return new JsonResponse( ['status_code' => 200, 'data' => $data] );
            }
        }
        catch ( ValidationException $e ) {
            return $e->getResponse();
        }
    }
}

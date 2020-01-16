<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$api = app( 'Dingo\Api\Routing\Router' );

$api->version( 'v1', ['namespace' => 'App\Http\Controllers'], function ( $api )
{

    $api->group( ['middleware' => 'api.auth'], function ( $api )
    {
        $api->get( '/auth/user', [
            'uses' => 'Auth\AuthController@getUser',
        ] );

        $api->post( '/auth/update', [
            'uses' => 'Auth\AuthController@updateUser',
        ] );

        $api->post( 'upload-funds', [
            'uses' => 'FundController@uploadFunds',
        ] );

        $api->post( 'all-daily-funds', [
            'uses' => 'FundController@allDailyFunds',
        ] );
        
        $api->post( 'all-daily-funds-backup', [
            'uses' => 'FundController@allDailyFundsBackup',
        ] );

        $api->post( 'all-funds', [
            'uses' => 'FundController@allFunds',
        ] );

        $api->post( 'update-fund', [
            'uses' => 'FundController@updateFund',
        ] );

        $api->post( 'update-status', [
            'uses' => 'FundController@updateStatus',
        ] );

        $api->post( 'update-daily-fund', [
            'uses' => 'FundController@updateDailyFund',
        ] );

        $api->post( 'delete-daily-fund', [
            'uses' => 'FundController@deleteDailyFund',
        ] );

    } );
    
    $api->get( 'daily-funds', [
        'uses' => 'FundController@getDailyFunds',
    ] );

    $api->get( 'funds-list', [
        'uses' => 'FundController@getFundsList',
    ] );

    $api->get( 'fund-details/{fund_id}', [
        'uses' => 'FundController@getFundDetails',
    ] );

    $api->post( 'chart-data', [
        'uses' => 'FundController@getChartData',
    ] );

    $api->post( '/auth/login', [
        'uses' => 'Auth\AuthController@postLogin',
    ] );
} );
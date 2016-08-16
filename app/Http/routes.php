<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

use App\Favourites;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/example', function () {

	$x = array();
	$x[] = 'one';
	$x[] = 'two';
	$x[] = 'two';
	$x[] = 'three';
	return json_encode($x);

} );

Route::get('/questions', function() {
	$questions = DB::select('select * from efsa.Questions limit 10');
	return json_encode($questions);
});

Route::get('/questions/{id}', function ($id) {


    $questions = DB::select('select * from efsa.Questions where efsa.Questions.QUESTIONNUMBER = :id', ['id' => $id]);
	return json_encode($questions);
});

Route::get('/questions/{id}/status', function ($id) {


    $question = DB::select('select STATUS from efsa.Questions where efsa.Questions.QUESTIONNUMBER = :id', ['id' => $id]);

    $valid_status = ['In progress','Under Consideration','Registration not yet completed','Additional data request','Finished','Waiting for full dossier','Not accepted','Withdrawn','Deleted'];

    $out['question'] = $question;
    $out['list_of_possible_status'] = $valid_status;

	return json_encode($out);
});

Route::get('/unitpanel/all', function() {
	$query=DB::select('SELECT distinct UNIT,PANEL FROM Questions ORDER BY `Questions`.`UNIT` ASC' );
	return json_encode($query);
});

Route::get('/unitpanel/units', function() {
	$query=DB::select('SELECT distinct UNIT FROM Questions ORDER BY `Questions`.`UNIT` ASC' );
	return json_encode($query);
});

Route::get('/unitpanel/panels', function() {
	$query=DB::select('SELECT distinct PANEL FROM Questions ORDER BY `Questions`.`PANEL` ASC' );
	return json_encode($query);
});

Route::get('/petitioners', function() {
	$query=DB::select('SELECT PETITIONER, COUNT(PETITIONER) AS COUNT FROM `Questions` GROUP BY PETITIONER ORDER BY COUNT DESC');
	return json_encode($query);
});

// Favouriting & Unfavouriting
Route::get('/fav/{type}/{id}', ['middleware' => 'auth', function ($type, $id)  {

    $user = Auth::user();
    $user_id=$user->id;

	$fav = App\Favourites::firstOrNew(array('fav_identifier' => $id, 'user_id' => $user_id));
	$fav->user_id = $user_id;
    $fav->type = $type;
    $fav->fav_identifier = $id;
	$fav->save();
}]);

Route::get('/unfav/{type}/{id}', ['middleware' => 'auth', function ($type, $id)  {
    $user = Auth::user();
    $user_id=$user->id;

    $deletedRows = App\Favourites::where('fav_identifier', $id)
    							->where('user_id', $user_id)
    							->delete();
}]);

Route::auth();
Route::get('/home', 'HomeController@index');

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

// Authentication framework by Laravel
Route::auth();
Route::get('/home', 'HomeController@index');

// Questions
Route::get('/questions/get/{id}', function ($id) {

    $questions = DB::select('select * from efsa.Questions where efsa.Questions.QUESTIONNUMBER = :id', ['id' => $id]);
	return json_encode($questions);
});

Route::get('/questions/get/{id}/status', function ($id) {

    $question = DB::select('select STATUS from efsa.Questions where efsa.Questions.QUESTIONNUMBER = :id', ['id' => $id]);

    $valid_status = ['In progress','Under Consideration','Registration not yet completed','Additional data request','Finished','Waiting for full dossier','Not accepted','Withdrawn','Deleted'];

    $out['question'] = $question;
    $out['list_of_possible_status'] = $valid_status;

	return json_encode($out);
});

Route::get('/questions/get/{id}/tags', function ($id) {

    $question = DB::select('select tag, score from questions_metas where question_id = :id', ['id' => $id]);
   
	return json_encode($question);
});

Route::get('/questions/get/{id}/lastupdate', function ($id) {

    $question = DB::select('select LASTUPDATED from efsa.Questions where efsa.Questions.QUESTIONNUMBER = :id', ['id' => $id]);



	return json_encode($question);
});

Route::get('/questions/searchTag/{tags}', function ($tags) {


	$questions = DB::select('select QUESTIONNUMBER, m.tag, m.Score from efsa.Questions q, efsa.questions_metas m where q.QUESTIONNUMBER = m.question_id AND m.tag IN ( :tags )', ['tags' => $tags]);


	return json_encode($questions);



});

// Unit and Panel
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

// Petitioners (i.e. companies)
Route::get('/petitioners/list', function() {
	$query=DB::select('SELECT PETITIONER, COUNT(PETITIONER) AS COUNT FROM `Questions` GROUP BY PETITIONER ORDER BY COUNT DESC');
	return json_encode($query);
});

Route::get('/petitioners/get/{name}', function($name) {
	$nameDecoded = urldecode($name);
	$query=DB::select('SELECT * from Questions WHERE PETITIONER = :petitioner', ['petitioner' => $nameDecoded]);
	return json_encode($query);
});

Route::get('/petitioners/timeline/{name}', function($name) {
	$nameDecoded = urldecode($name);
	$query=DB::select('SELECT QUESTIONNUMBER, RECEPTIONDATE from Questions WHERE PETITIONER = :petitioner ORDER BY RECEPTIONDATE ASC', ['petitioner' => $nameDecoded]);
	return json_encode($query);
});

// Favouriting & Unfavouriting
Route::get('/favourite/add/{type}/{id}', ['middleware' => 'auth', function ($type, $id)  {

    $user = Auth::user();
    $user_id=$user->id;

    $dt = new DateTime;

	$fav = App\Favourites::firstOrNew(array('fav_identifier' => $id, 'user_id' => $user_id));
	if (!($fav->exists)) {
		$fav->user_id = $user_id;
    	$fav->type = $type;
    	$fav->fav_identifier = $id;
    	$fav->lastupdate = $dt;
		$fav->save();
	}
}]);

Route::get('/favourite/remove/{type}/{id}', ['middleware' => 'auth', function ($type, $id)  {
    $user = Auth::user();
    $user_id=$user->id;

    $deletedRows = App\Favourites::where('fav_identifier', $id)
    							->where('user_id', $user_id)
    							->delete();
}]);

Route::get('/favourite/check/{type}/{id}', ['middleware' => 'auth', function ($type, $id)  {

    $user = Auth::user();
    $user_id=$user->id;

	$fav = App\Favourites::firstOrNew(array('fav_identifier' => $id, 'user_id' => $user_id));
	if ($fav->exists) {
		$out['favourite'] = "yes";
	} else {
		$out['favourite'] = "no";
	}
	return json_encode($out);
}]);

// List favourites for user
Route::get('/favourite/list', ['middleware' => 'auth', function ()  {
    $user = Auth::user();
    $user_id=$user->id;
    $notif = App\Favourites::where('user_id', $user_id)->get();
    return json_encode($notif);
}]);

// Route::get('/favourite/notify', ['middleware' => 'auth', function ()  {
//     $user = Auth::user();
//     $user_id=$user->id;

//     $questions = array();
//     $companies = array();

//     $favourites = DB::select('SELECT f.*, u.email FROM Favourites f, Users u WHERE f.user_id = :userid', ['userid' => $user_id]);
            
          

//             foreach ($favourites as $fav) {
            	
//             //2. if type is QUESTION...
//             if ($fav->type == 'question') {
               


//                 // Get Question date
//                 $qnumber = $fav->fav_identifier;
//                 $lastupdateRow = DB::select('SELECT l.LASTUPDATED FROM Questions q, Questions_LastUpdates l WHERE q.QUESTIONNUMBER = l.QUESTIONNUMBER
//                     AND q.QUESTIONNUMBER = :qnumber', ['qnumber' => $qnumber]);
                
//                 $lastupdate = $lastupdateRow[0]->LASTUPDATED;

                
//                 // Check date
//                 $lastupdatePhp =  strtotime( $lastupdate );
//                 $favdatePhp = strtotime($fav->lastupdate);
               
//                 if ($lastupdatePhp > $favdatePhp) {
                	
//                 	$thisq['QUESTIONNUMBER'] = $qnumber;
//                 	$thisq['LASTUPDATED'] = $lastupdate;
//                     $questions[] = $thisq;
//                 }
//             }

//             // 3. if type is COMPANY...
//             if ($fav->type == 'company') {

                
//                 // Get most recent Question date linked to that company
//                 $company = $fav->fav_identifier;

//                 $lastupdateRow = DB::select('SELECT max(l.LASTUPDATED) as MAXDATE FROM Questions_LastUpdates l, Questions q WHERE l.QUESTIONNUMBER = q.QUESTIONNUMBER  AND q.PETITIONER = :company', ['company' => $company]);
//                 $lastupdate = $lastupdateRow[0]->MAXDATE;

//                 // Get all questions related
//                 $allquestions = DB::select('SELECT q.QUESTIONNUMBER as num, l.LASTUPDATED as upd FROM Questions_LastUpdates l, Questions q WHERE l.QUESTIONNUMBER = q.QUESTIONNUMBER  AND q.PETITIONER = :company ORDER BY l.LASTUPDATED DESC', ['company' => $company]);
//                 $allq = array();

//                 foreach ($allquestions as $onequestion) {
//                 	$thisq['QUESTIONNUMBER'] = $onequestion->num;
//                 	$thisq['LASTUPDATED'] = $onequestion->upd;
//                 	$allq[] = $thisq;
//                 }

//                 // Check date
//                 $lastupdatePhp =  strtotime( $lastupdate );
//                 $favdatePhp = strtotime($fav->lastupdate);
               
//                 if ($lastupdatePhp > $favdatePhp) {
//                      $thisc['COMPANY'] = $company;
//                      $thisc['QUESTIONS'] = $allq;
//                      $companies[] = $thisc;
//                 }
                


//             }

//     }
//                  $notif['questions'] = $questions;
//              $notif['companies'] = $companies;

//     	 return json_encode($notif);
// }]);



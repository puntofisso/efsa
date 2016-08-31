<?php
use App\Favourites;
use App\Logs;

// Base
Route::get('/', function () {
    return view('welcome');
});

// LOOKUP endpoints
Route::get('/LOOKUP/QUESTIONS/date/{from}/{to}', function($from, $to) {
	$nameDecoded = urldecode($name);
	$query=DB::select('SELECT QUESTIONNUMBER, RECEPTIONDATE from Questions WHERE PETITIONER = :petitioner ORDER BY RECEPTIONDATE ASC', ['petitioner' => $nameDecoded]);
	return json_encode($query);
});

Route::get('/LOOKUP/COMPANY/{company}', function($company) {
	
});

Route::get('/LOOKUP/SUBSTANCE/{substance}', function($substance) {
	
});

Route::get('/LOOKUP/HANDLER/{handler}', function($handler) {
	
});

Route::get('/LIST/units', function() {
	$query=DB::select('SELECT distinct UNIT FROM Questions ORDER BY `Questions`.`UNIT` ASC' );
	return json_encode($query);
});

Route::get('/LIST/panels', function() {
	$query=DB::select('SELECT distinct PANEL FROM Questions ORDER BY `Questions`.`PANEL` ASC' );
	return json_encode($query);
});

Route::get('/LIST/companies', function() {
	
});


// Authentication framework by Laravel
Route::auth();
Route::get('/home', 'HomeController@index');

// Authentication via Token
//Route::resource('api/authenticate', 'AuthenticateController', ['only' => ['index']]);
//Route::post('api/authenticate', 'AuthenticateController@authenticate');

Route::group(['prefix' => 'api'], function()
{
	Route::post('authenticate', 'AuthenticateController@authenticate');
	Route::group(['middleware' => 'jwt.auth'], function()
	{
		Route::resource('authenticate', 'AuthenticateController', ['only' => ['index']]);
		Route::get('test', function() {
			$user = Auth::user();
    		$user_id=$user->id;
    		$notif['message'] = "You are user ".$user_id." and your e-mail is".  $user->email;
    		return json_encode($notif);
		});

		// Logs Management
		Route::get('/logs', function ()  {
    		$user = Auth::user();
   			$user_id=$user->id;

    		$logs = App\Logs::where('user_id', $user_id)->get();

    		return json_encode($logs);
		});

		Route::get('/logs/delete', function ()  {
    		$user = Auth::user();
    		$user_id=$user->id;

    		App\Logs::where('user_id', $user_id)->delete();
		});

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

		Route::get('/favourite/remove/{type}/{id}', function ($type, $id)  {
		    $user = Auth::user();
		    $user_id=$user->id;

		    $deletedRows = App\Favourites::where('fav_identifier', $id)
		    							->where('user_id', $user_id)
		    							->delete();
		});

		Route::get('/favourite/check/{type}/{id}', function ($type, $id)  {

		    $user = Auth::user();
		    $user_id=$user->id;

			$fav = App\Favourites::firstOrNew(array('fav_identifier' => $id, 'user_id' => $user_id));
			if ($fav->exists) {
				$out['favourite'] = "yes";
			} else {
				$out['favourite'] = "no";
			}
			return json_encode($out);
		});

		// List favourites for user
		Route::get('/favourite/list/light',  function ()  {
		    $user = Auth::user();
		    $user_id=$user->id;
		    $notif = App\Favourites::where('user_id', $user_id)->get();
		    return json_encode($notif);
		});


		Route::get('/favourite/list/full', function ()  {
		    $user = Auth::user();
		    $user_id=$user->id;

		    $questions = array();
		    $companies = array();

		    $favourites = DB::select('SELECT f.*, u.email FROM favourites f, users u WHERE f.user_id = :userid', ['userid' => $user_id]);
		            
		          

		            foreach ($favourites as $fav) {
		            	
		            //2. if type is QUESTION...
		            if ($fav->type == 'question') {
		               

		                // Get Question date
		                $qnumber = $fav->fav_identifier;
		                $lastupdateRow = DB::select('SELECT l.LASTUPDATED FROM Questions q, Questions_LastUpdates l WHERE q.QUESTIONNUMBER = l.QUESTIONNUMBER
		                    AND q.QUESTIONNUMBER = :qnumber', ['qnumber' => $qnumber]);
		                
		                $lastupdate = $lastupdateRow[0]->LASTUPDATED;

		                
		                // Check date
		                $lastupdatePhp =  strtotime( $lastupdate );
		                $favdatePhp = strtotime($fav->lastupdate);
		               
		                //if ($lastupdatePhp > $favdatePhp) {
		                	
		                	$thisq['QUESTIONNUMBER'] = $qnumber;
		                	$thisq['LASTUPDATED'] = $lastupdate;
		                    $questions[] = $thisq;
		                //}
		            }

		            // 3. if type is COMPANY...
		            if ($fav->type == 'company') {

		                
		                // Get most recent Question date linked to that company
		                $company = $fav->fav_identifier;

		                $lastupdateRow = DB::select('SELECT max(l.LASTUPDATED) as MAXDATE FROM Questions_LastUpdates l, Questions q WHERE l.QUESTIONNUMBER = q.QUESTIONNUMBER  AND q.PETITIONER = :company', ['company' => $company]);
		                $lastupdate = $lastupdateRow[0]->MAXDATE;

		                // Get all questions related
		                $allquestions = DB::select('SELECT q.QUESTIONNUMBER as num, l.LASTUPDATED as upd FROM Questions_LastUpdates l, Questions q WHERE l.QUESTIONNUMBER = q.QUESTIONNUMBER  AND q.PETITIONER = :company ORDER BY l.LASTUPDATED DESC', ['company' => $company]);
		                $allq = array();

		                foreach ($allquestions as $onequestion) {
		                	$thisq['QUESTIONNUMBER'] = $onequestion->num;
		                	$thisq['LASTUPDATED'] = $onequestion->upd;
		                	$allq[] = $thisq;
		                }

		                // Check date
		                $lastupdatePhp =  strtotime( $lastupdate );
		                $favdatePhp = strtotime($fav->lastupdate);
		               
		                //if ($lastupdatePhp > $favdatePhp) {
		                     $thisc['COMPANY'] = $company;
		                     $thisc['QUESTIONS'] = $allq;
		                     $companies[] = $thisc;
		                //}
		                


		            }

		    }
		                 $notif['questions'] = $questions;
		             $notif['companies'] = $companies;

		    	 return json_encode($notif);
		});

	});

});
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

Route::get('/questions/tags/search/{tags}', function ($tags) {


	$questions = DB::select('select QUESTIONNUMBER, PETITIONER, m.tag, m.Score from efsa.Questions q, efsa.questions_metas m where q.QUESTIONNUMBER = m.question_id AND m.tag IN ( :tags )', ['tags' => $tags]);


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
Route::get('/favourite/list/light', ['middleware' => 'auth', function ()  {
    $user = Auth::user();
    $user_id=$user->id;
    $notif = App\Favourites::where('user_id', $user_id)->get();
    return json_encode($notif);
}]);


Route::get('/favourite/list/full', ['middleware' => 'auth', function ()  {
    $user = Auth::user();
    $user_id=$user->id;

    $questions = array();
    $companies = array();

    $favourites = DB::select('SELECT f.*, u.email FROM favourites f, users u WHERE f.user_id = :userid', ['userid' => $user_id]);
            
          

            foreach ($favourites as $fav) {
            	
            //2. if type is QUESTION...
            if ($fav->type == 'question') {
               

                // Get Question date
                $qnumber = $fav->fav_identifier;
                $lastupdateRow = DB::select('SELECT l.LASTUPDATED FROM Questions q, Questions_LastUpdates l WHERE q.QUESTIONNUMBER = l.QUESTIONNUMBER
                    AND q.QUESTIONNUMBER = :qnumber', ['qnumber' => $qnumber]);
                
                $lastupdate = $lastupdateRow[0]->LASTUPDATED;

                
                // Check date
                $lastupdatePhp =  strtotime( $lastupdate );
                $favdatePhp = strtotime($fav->lastupdate);
               
                //if ($lastupdatePhp > $favdatePhp) {
                	
                	$thisq['QUESTIONNUMBER'] = $qnumber;
                	$thisq['LASTUPDATED'] = $lastupdate;
                    $questions[] = $thisq;
                //}
            }

            // 3. if type is COMPANY...
            if ($fav->type == 'company') {

                
                // Get most recent Question date linked to that company
                $company = $fav->fav_identifier;

                $lastupdateRow = DB::select('SELECT max(l.LASTUPDATED) as MAXDATE FROM Questions_LastUpdates l, Questions q WHERE l.QUESTIONNUMBER = q.QUESTIONNUMBER  AND q.PETITIONER = :company', ['company' => $company]);
                $lastupdate = $lastupdateRow[0]->MAXDATE;

                // Get all questions related
                $allquestions = DB::select('SELECT q.QUESTIONNUMBER as num, l.LASTUPDATED as upd FROM Questions_LastUpdates l, Questions q WHERE l.QUESTIONNUMBER = q.QUESTIONNUMBER  AND q.PETITIONER = :company ORDER BY l.LASTUPDATED DESC', ['company' => $company]);
                $allq = array();

                foreach ($allquestions as $onequestion) {
                	$thisq['QUESTIONNUMBER'] = $onequestion->num;
                	$thisq['LASTUPDATED'] = $onequestion->upd;
                	$allq[] = $thisq;
                }

                // Check date
                $lastupdatePhp =  strtotime( $lastupdate );
                $favdatePhp = strtotime($fav->lastupdate);
               
                //if ($lastupdatePhp > $favdatePhp) {
                     $thisc['COMPANY'] = $company;
                     $thisc['QUESTIONS'] = $allq;
                     $companies[] = $thisc;
                //}
                


            }

    }
                 $notif['questions'] = $questions;
             $notif['companies'] = $companies;

    	 return json_encode($notif);
}]);

// Chatbot interaction & Logs management
Route::get('/chat/{convo_id}/{text}', function ($convo_id, $text)  {

	$textD = urldecode($text);
    $user = Auth::user();
    $user_id = '-1';
    $out = array();

    if ($user)
    	$user_id=$user->id;
	
	$url = "https://efsa-chat.ptfs.uk/Program-O-master/chatbot/conversation_start.php?say=$text&convo_id=$convo_id";

	$client = new GuzzleHttp\Client();
    $chatbot = $client->get($url);
    $code = $chatbot->getStatusCode();
    $body = $chatbot->getBody();

    if ($code <> 200) {
    	$out['error'] = "problems communicating with the AI engine";
		return json_encode($out);
    }
    $out['chatbot'] = json_decode($body);
    $out['user'] = $user_id;

    // Store in Logs table    
    $log = new Logs;
    $log->user_id = $user_id;
    $log->usertext = $textD;
    $log->bottext = $out['chatbot']->botsay;
    $log->convo_id = $convo_id;    
    $log->save();

    return json_encode($out);
});

Route::get('/logs', ['middleware' => 'auth', function ()  {
    $user = Auth::user();
    $user_id=$user->id;

    $logs = App\Logs::where('user_id', $user_id)->get();

    return json_encode($logs);
}]);

Route::get('/logs/delete', ['middleware' => 'auth', function ()  {
    $user = Auth::user();
    $user_id=$user->id;

    App\Logs::where('user_id', $user_id)->delete();
}]);

// Admin
Route::get('/admin/question/get/{id}', ['middleware' => ['auth', 'admin'], function($id) {

	$question = DB::select('SELECT * FROM Questions  WHERE QUESTIONNUMBER = :qnum', ['qnum' => $id]);

    return view('admin.question', ['question' => $question[0]]);
}]);

Route::get('/admin/question/update/{id}/{field}/{value}', ['middleware' => ['auth', 'admin'], function($id, $field, $value) {

	DB::update("UPDATE Questions SET $field = :value WHERE QUESTIONNUMBER = :qnum", ['qnum' => $id, 'value' => $value]);
	$question = DB::select('SELECT * FROM Questions  WHERE QUESTIONNUMBER = :qnum', ['qnum' => $id]);
    

    
    $datentime = new DateTime;
    DB::update("UPDATE Questions_LastUpdates SET LASTUPDATED = :datentime WHERE QUESTIONNUMBER = :qnum", ['qnum' => $id, 'datentime' => $datentime]);
    return view('admin.question', ['question' => $question[0]]);
}]);





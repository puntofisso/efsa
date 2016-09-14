<?php
use App\Favourites;
use App\Logs;
use Illuminate\Support\Facades\Input;
// Base
Route::get('/', function () {
    return view('welcome');
});

// LOOKUP endpoints
Route::get('/LOOKUP/HANDLER/{company}/{substance}/{datefrom}/{dateto}', function($company, $substance, $datefrom, $dateto) {

	$sql="SELECT DISTINCT q.QUESTIONNUMBER, q.UNIT, q.PANEL FROM Questions q INNER JOIN questions_metas m
		ON q.QUESTIONNUMBER = m.question_id";
	$dictvar = [];
	if ($company != "NULL") {
		$sql = $sql. " AND q.PETITIONER LIKE :company";
		$dictvar['company'] = "%$company%";
	}
	if ($substance != "NULL") {
		$sql = $sql. " AND  m.tag LIKE :substance";
		$dictvar['substance'] = "%$substance%";
	} 
	if (($datefrom != "NULL") && ($dateto != "NULL")) {
		$sql = $sql. " AND  q.RECEPTIONDATE >= :datefrom AND q.RECEPTIONDATE <= :dateto";
		$dictvar['datefrom'] = $datefrom;	
		$dictvar['dateto'] = $dateto;	
	}

	$query=DB::select($sql, $dictvar);
	return json_encode($query);
});

Route::get('/LOOKUP/COMPANY/{handler}/{substance}/{datefrom}/{dateto}', function($handler, $substance, $datefrom, $dateto) {

	$sql="SELECT DISTINCT q.QUESTIONNUMBER, q.PETITIONER FROM Questions q INNER JOIN questions_metas m
		ON q.QUESTIONNUMBER = m.question_id";
	$dictvar = [];
	if ($handler != "NULL") {
		$sql = $sql. " AND (q.UNIT LIKE :unit OR q.PANEL LIKE :panel)";
		$dictvar['unit'] = "%$handler%";
		$dictvar['panel'] = "%$handler%";
	}
	if ($substance != "NULL") {
		$sql = $sql. " AND  m.tag LIKE :substance";
		$dictvar['substance'] = "%$substance%";
	} 
	if (($datefrom != "NULL") && ($dateto != "NULL")) {
		$sql = $sql. " AND  q.RECEPTIONDATE >= :datefrom AND q.RECEPTIONDATE <= :dateto";
		$dictvar['datefrom'] = $datefrom;	
		$dictvar['dateto'] = $dateto;	
	}

	$query=DB::select($sql, $dictvar);
	return json_encode($query);
});

Route::get('/LOOKUP/SUBSTANCE/{handler}/{company}/{datefrom}/{dateto}', function($handler, $company, $datefrom, $dateto) {

	$sql="SELECT DISTINCT q.QUESTIONNUMBER, m.tag, m.score FROM Questions q INNER JOIN questions_metas m
		ON q.QUESTIONNUMBER = m.question_id";
	$dictvar = [];
	if ($handler != "NULL") {
		$sql = $sql. " AND (q.UNIT LIKE :unit OR q.PANEL LIKE :panel)";
		$dictvar['unit'] = "%$handler%";
		$dictvar['panel'] = "%$handler%";
	}
	if ($company != "NULL") {
		$sql = $sql. " AND q.PETITIONER LIKE :company";
		$dictvar['company'] = "%$company%";
	}
	if (($datefrom != "NULL") && ($dateto != "NULL")) {
		$sql = $sql. " AND  q.RECEPTIONDATE >= :datefrom AND q.RECEPTIONDATE <= :dateto";
		$dictvar['datefrom'] = $datefrom;	
		$dictvar['dateto'] = $dateto;	
	}
	$sql = $sql. " ORDER BY m.score DESC";

	$query=DB::select($sql, $dictvar);
	return json_encode($query);
});


///LOOKUP/QUESTION/$company/$handler/$substance/$datefrom/$dateto
Route::get('/LOOKUP/QUESTION/{company}/{handler}/{substance}/{datefrom}/{dateto}', function($company, $handler, $substance, $datefrom, $dateto) {

	$sql="SELECT DISTINCT q.QUESTIONNUMBER, q.RECEPTIONDATE FROM Questions q INNER JOIN questions_metas m
		ON q.QUESTIONNUMBER = m.question_id";
	$dictvar = [];
	if ($handler != "NULL") {
		$sql = $sql. " AND (q.UNIT LIKE :unit OR q.PANEL LIKE :panel)";
		$dictvar['unit'] = "%$handler%";
		$dictvar['panel'] = "%$handler%";
	}
	if ($company != "NULL") {
		$sql = $sql. " AND q.PETITIONER LIKE :company";
		$dictvar['company'] = "%$company%";
	}
	if ($substance != "NULL") {
		$sql = $sql. " AND  m.tag LIKE :substance";
		$dictvar['substance'] = "%$substance%";
	} 
	if (($datefrom != "NULL") && ($dateto != "NULL")) {
		$sql = $sql. " AND  q.RECEPTIONDATE >= :datefrom AND q.RECEPTIONDATE <= :dateto";
		$dictvar['datefrom'] = $datefrom;	
		$dictvar['dateto'] = $dateto;	
	}
	$sql = $sql." ORDER BY RECEPTIONDATE ASC";

	$query=DB::select($sql, $dictvar);
	return json_encode($query);
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
	$query=DB::select('SELECT distinct PETITIONER FROM Questions ORDER BY `Questions`.`PANEL` ASC' );
	return json_encode($query);
});


// Authentication framework by Laravel
Route::auth();
Route::get('/home', 'HomeController@index');


//Route::resource('api/authenticate', 'AuthenticateController', ['only' => ['index']]);
//Route::post('api/authenticate', 'AuthenticateController@authenticate');

// Authentication via Token
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
// Mandates
Route::get('/mandates/get/{id}', function ($id) {
    $mandates = DB::select('select * from efsa.Questions where efsa.Questions.MANDATE = :id', ['id' => $id]);
	return json_encode($mandates);
});

Route::get('/mandates/list', function () {

    $mandates = DB::select('select DISTINCT MANDATE from efsa.Questions');
	return json_encode($mandates);
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

	$questions = DB::select('select QUESTIONNUMBER, PETITIONER, m.tag, m.Score, SUBJECT, STATUS, RECEPTIONDATE from efsa.Questions q, efsa.questions_metas m where q.QUESTIONNUMBER = m.question_id AND m.tag IN ( :tags )', ['tags' => $tags]);

	$out['questions'] = $questions;


	return json_encode($out);

});

Route::get('/companies/tags/search/{tags}', function ($tags) {

	$companies = DB::select('select distinct PETITIONER, count(distinct PETITIONER) AS count from efsa.Questions q, efsa.questions_metas m where q.QUESTIONNUMBER = m.question_id AND m.tag IN ( :tags ) GROUP BY PETITIONER', ['tags' => $tags]);

	$out['companies'] = $companies;

	return json_encode($out);

});

Route::get('/tags/get/{tag}', function ($tag) {

	$tags = DB::select('select distinct tag,score from questions_metas where tag LIKE :tag', ['tag' => "%$tag%"]);
	return json_encode($tags);

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

Route::get('/unitpanel/get/{name}', function($name ) {
	$query=DB::select('SELECT * FROM Questions WHERE UNIT LIKE :name1 OR PANEL LIKE :name2', ['name1' => "%$name%", 'name2' => "%$name%"] );
	return json_encode($query);
});


// Petitioners (i.e. companies)
Route::get('/petitioners/list', function() {
	$query=DB::select('SELECT PETITIONER, COUNT(PETITIONER) AS COUNT FROM `Questions` GROUP BY PETITIONER ORDER BY COUNT DESC');
	return json_encode($query);
});

Route::get('/petitioners/get/{name}', function($name) {
	$nameDecoded = urldecode($name);
	$query=DB::select('SELECT * from Questions WHERE PETITIONER LIKE :petitioner', ['petitioner' => "%$nameDecoded%"]);
	return json_encode($query);
});

Route::get('/petitioners/timeline/{name}', function($name) {
	$nameDecoded = urldecode($name);
	$query=DB::select('SELECT QUESTIONNUMBER, RECEPTIONDATE from Questions WHERE PETITIONER LIKE :petitioner ORDER BY RECEPTIONDATE ASC', ['petitioner' => "%$nameDecoded%"]);
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
Route::get('/chat/unity/{convoid}/{text}', function($convoid,$text) {

	// 1. call AIML
	$url = "/chat/aiml/$convoid/$text";
	$request = Request::create($url, 'GET');
	$response = Route::dispatch($request);
	$json = json_decode($response->getOriginalContent(),true);
	$aimlreply = $json["chatbot"]["botsay"];
	
	// 2. if AIML replies NO,
	if ($aimlreply == "{NO_MATCH}") {
		
		// 3. call LUIS
		$url = "/chat/luis/preview/$text";
		$request = Request::create($url, 'GET');
		$response = Route::dispatch($request);
		$json = json_decode($response->getOriginalContent(),true);
		$intent = $json["topScoringIntent"]["intent"];
		$entities = $json["entities"];
		
		$handler = "NULL";
		$company = "NULL";
		$substance = "NULL";

		$dates = array();
		foreach ($entities as $entity) {
			$ent_type = $entity["type"];
			$ent_value = $entity["entity"];

			if ($ent_type == "handler") {
				$handler = $ent_value;
			}
			if ($ent_type == "substance") {
				$substance = $ent_value;
			}
			if ($ent_type == "company") {
				$company = $ent_value;
			}
			if ($ent_type == "builtin.datetime.date") {
				$dates[] = $entity["resolution"]["date"];
			}
		}

		if ($intent== "LOOKUP_HANDLER") {
			$url = "/chat/aiml/$convoid/UNITPANEL SET $handler";
		} elseif ($intent == "LOOKUP_COMPANY") {
			//$company = $json["topScoringIntent"]["actions"][0]["parameters"][0]["value"][0]["entity"];
			$url = "/chat/aiml/$convoid/COMPANY SET $company";
		} elseif ($intent == "LOOKUP_QUESTIONS") {
			// TODO define period
			$period = "NULL";
			$url = "/chat/aiml/$convoid/QUESTION SEARCH FROM $from TO $to IN $in HANDLER $handler SUBSTANCE $substance";
		} elseif ($intent == "None") {
			$url = "/chat/aiml/$convoid/LUISNOMATCH";
		} else {
			// Paranoid: no match, should never be here
			$url = "/chat/aiml/$convoid/LUISNOMATCH";
		}
		
		$request = Request::create($url, 'GET');
		$response = Route::dispatch($request);
		$json = json_decode($response->getOriginalContent(),true);
		$aimlreply = $json["chatbot"]["botsay"];
		
		// TODO HOW TO CHECK IF CONNECTION FAILED
		
	} 

	echo $aimlreply;
});


Route::get('/chat/luis/{previeworproduction}/{text}', function($previeworproduction, $text) {
	

	if ($previeworproduction=="preview") {
		$url = "https://api.projectoxford.ai/luis/v1/application/preview?id=22c117cc-11c2-4424-99e9-35284fc26eae&subscription-key=1a42e6ab7d1c4c86ad68118b419da621";
	} else if ($previeworproduction == "production") {
		$url = "https://api.projectoxford.ai/luis/v1/application?id=22c117cc-11c2-4424-99e9-35284fc26eae&subscription-key=1a42e6ab7d1c4c86ad68118b419da621";
	} else die();


	$url = "$url&q=$text";
	
	
	$client = new GuzzleHttp\Client();
    $chatbot = $client->get($url);
    $code = $chatbot->getStatusCode();
    $body = $chatbot->getBody();

    return $body;
});

Route::get('/chat/luis/parse/{previeworproduction}/{text}', function($previeworproduction, $text) {

	
	if ($previeworproduction=="preview") {
		$url = "https://api.projectoxford.ai/luis/v1/application/preview?id=22c117cc-11c2-4424-99e9-35284fc26eae&subscription-key=1a42e6ab7d1c4c86ad68118b419da621";
	} else if ($previeworproduction == "production") {
		$url = "https://api.projectoxford.ai/luis/v1/application?id=22c117cc-11c2-4424-99e9-35284fc26eae&subscription-key=1a42e6ab7d1c4c86ad68118b419da621";
	} else die();
	$mytext=urlencode($text);
	$url = "$url&q=$mytext";

	$client = new GuzzleHttp\Client();
    $chatbot = $client->get($url);
    $code = $chatbot->getStatusCode();
    $body = $chatbot->getBody();



    $json = json_decode($body,true);
	$intent = $json["topScoringIntent"]["intent"];
	$entities = $json["entities"];
		
	$handler = "NULL";
	$company = "NULL";
	$substance = "NULL";
	$dates = array();

	foreach ($entities as $entity) {
		$ent_type = $entity["type"];
		$ent_value = $entity["entity"];

		if ($ent_type == "handler") {
			$handler = $ent_value;
		}
		if ($ent_type == "substance") {
			$substance = $ent_value;
		}
		if ($ent_type == "company") {
			$company = $ent_value;
		}
		if ($ent_type == "builtin.datetime.date") {
			$dates[] = $entity["resolution"]["date"];
		}
	}

	$datefrom = "";
	$dateto = "";

	if (count($dates)==0) {
		// all dates null
		$datefrom = "NULL";
		$dateto = "NULL";
	} elseif (count($dates)==1) {
		$thisdate=$dates[0];
		if (strlen($thisdate)==10) {
			// exact date
			$datefrom = $thisdate;
			$dateto = $thisdate;	
		} elseif (strlen($thisdate)==7) {
			// month, year
			$year = substr($thisdate, 0, 4);
			$month = substr($thisdate, 5, 2);
			$datefrom = "$year-$month-01";
			$monthlength=cal_days_in_month(CAL_GREGORIAN, $month, $year);
			$dateto = "$year-$month-$monthlength";
		} elseif (strlen($thisdate)==4) {
			// year
			$year = substr($thisdate, 0, 4);
			$datefrom = "$year-01-01";
			$dateto = "$year-12-31";
		}
	} elseif (count($dates==2) ) {
		$datesarray = array();

		$firstdate = $dates[0];
		$seconddate = $dates[1];

		if (strlen($firstdate)==10) {
			// exact date
			$datefrom = $firstdate;
			$dateto = $firstdate;	
		} elseif (strlen($firstdate)==7) {
			// month, year
			$year = substr($firstdate, 0, 4);
			$month = substr($firstdate, 5, 2);
			$datefrom = "$year-$month-01";
			$monthlength=cal_days_in_month(CAL_GREGORIAN, $month, $year);
			$dateto = "$year-$month-$monthlength";
		} elseif (strlen($firstdate)==4) {
			// year
			$year = substr($thisdate, 0, 4);
			$datefrom = "$year-01-01";
			$dateto = "$year-12-31";
		}
		$datesarray[] = $datefrom;
		$datesarray[] = $dateto;		

		if (strlen($seconddate)==10) {
			// exact date
			$datefrom = $seconddate;
			$dateto = $seconddate;	
		} elseif (strlen($seconddate)==7) {
			// month, year
			$year = substr($seconddate, 0, 4);
			$month = substr($seconddate, 5, 2);
			$datefrom = "$year-$month-01";
			$monthlength=cal_days_in_month(CAL_GREGORIAN, $month, $year);
			$dateto = "$year-$month-$monthlength";
		} elseif (strlen($seconddate)==4) {
			// year
			$year = substr($thisdate, 0, 4);
			$datefrom = "$year-01-01";
			$dateto = "$year-12-31";
		}
		$datesarray[] = $datefrom;
		$datesarray[] = $dateto;

		$datefrom = min($datesarray);
		$dateto = max($datesarray);
	} else die();
	
	// from all the dates collected, get first and last
	// TODO
	
	if ($intent== "LOOKUP_HANDLER") {
		
		$msg = "This is a list of units/panels that have dealt with that:";

		// TODO - Awful Workaround
		$company = str_replace(" . ", ".",$company);
		$company = str_replace(" .", ".",$company);

		$url = "/LOOKUP/HANDLER/$company/$substance/$datefrom/$dateto";

		$request = Request::create($url, 'GET');
		$response = Route::dispatch($request);
		$out = json_decode($response->getOriginalContent(),true);

		$myout["original"] = $text;
		$myout["query"] = $mytext;
		$myout["message"] = $msg;
		$myout["handlers"] = $out;
		$myout["substances"] = array();
		$myout["companies"] = array();
		$myout["questions"] = array();
		$myout["url"] = $url;

		echo json_encode($myout);

	} elseif ($intent == "LOOKUP_COMPANY") {
	 	$msg = "This is a list of companies that have asked questions within these parametres:";

		$url = "/LOOKUP/COMPANY/$handler/$substance/$datefrom/$dateto";

		$request = Request::create($url, 'GET');
		$response = Route::dispatch($request);
		$out = json_decode($response->getOriginalContent(),true);

		$myout["original"] = $text;
		$myout["query"] = $mytext;
		$myout["message"] = $msg;
		$myout["companies"] = $out;
		$myout["handlers"] = array();
		$myout["substances"] = array();
		$myout["questions"] = array();

		$myout["url"] = $url;

		echo json_encode($myout);
	} elseif ($intent == "LOOKUP_QUESTION") {
		$msg = "This is a list of questions that match those features:";

		$url = "/LOOKUP/QUESTION/$company/$handler/$substance/$datefrom/$dateto";

		$request = Request::create($url, 'GET');
		$response = Route::dispatch($request);
		$out = json_decode($response->getOriginalContent(),true);

		$myout["original"] = $text;
		$myout["query"] = $mytext;
		$myout["message"] = $msg;
		$myout["questions"] = $out;
		$myout["handlers"] = array();
		$myout["substances"] = array();
		$myout["companies"] = array();
		$myout["url"] = $url;

		echo json_encode($myout);
	} elseif ($intent == "LOOKUP_SUBSTANCE") {
		$msg = "This is a list of substances that correspond to these requests:";

		$url = "/LOOKUP/SUBSTANCE/$handler/$company/$datefrom/$dateto";

		$request = Request::create($url, 'GET');
		$response = Route::dispatch($request);
		$out = json_decode($response->getOriginalContent(),true);

		$myout["original"] = $text;
		$myout["query"] = $mytext;
		$myout["message"] = $msg;
		$myout["substances"] = $out;
		$myout["questions"] = array();
		$myout["handlers"] = array();
		$myout["companies"] = array();
		$myout["url"] = $url;

		echo json_encode($myout);

	} elseif ($intent == "None") {
		$msg = "Sorry, I am not able to answer that question.";
	} else {
		$msg = "There has been an error";
	}

});

Route::get('/chat/aiml/{convo_id}/{text}', function ($convo_id, $text)  {

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





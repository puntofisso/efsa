<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use DB;
use Mail;
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        
        


        $schedule->call(function () {
            $toupdate = array();

            // 1. navigate favourites lists
            $favourites = DB::select('SELECT f.*, u.email FROM efsa.favourites f, Users u WHERE f.user_id = u.id');
            
            foreach ($favourites as $fav) {
            $send = false;

            // 2. if type is QUESTION...
            if ($fav->type == 'question') {

                // Get Question date
                $qnumber = $fav->fav_identifier;
                $lastupdateRow = DB::select('SELECT l.LASTUPDATED FROM Questions q, Questions_LastUpdates l WHERE q.QUESTIONNUMBER = l.QUESTIONNUMBER
                    AND
                    q.QUESTIONNUMBER = :qnumber', ['qnumber' => $qnumber]);
                
                $lastupdate = $lastupdateRow[0]->LASTUPDATED;

                
                // Check date
                $lastupdatePhp =  strtotime( $lastupdate );
                $favdatePhp = strtotime($fav->lastupdate);
               
                if ($lastupdatePhp > $favdatePhp) {
                     $send = true;
                     $thisupd['user_id'] = $fav->user_id;
                     $thisupd['fav_identifier'] = $fav->fav_identifier;
                     $toupdate[] = $thisupd;
                }
                $bodymessage = "There is an update available for QUESTION ".$fav->fav_identifier. " dated ".$lastupdatePhp;
                $subject = "EFSA Reminder: Question";
                
            }

            // 3. if type is COMPANY...
            if ($fav->type == 'company') {

                $subject = "EFSA Reminder: Company";
                $bodymessage = "There is an update available for COMPANY ".$fav->fav_identifier;

                // Get most recent Question date linked to that company
                $company = $fav->fav_identifier;


                $lastupdateRow = DB::select('SELECT max(l.LASTUPDATED) as MAXDATE FROM Questions_LastUpdates l, Questions q WHERE l.QUESTIONNUMBER = q.QUESTIONNUMBER  AND q.PETITIONER = :company', ['company' => $company]);

                $lastupdate = $lastupdateRow[0]->MAXDATE;

                // Check date
                $lastupdatePhp =  strtotime( $lastupdate );
                $favdatePhp = strtotime($fav->lastupdate);
               
                if ($lastupdatePhp > $favdatePhp) {
                     $send = true;
                     $thisupd['user_id'] = $fav->user_id;
                     $thisupd['fav_identifier'] = $fav->fav_identifier;
                     $toupdate[] = $thisupd;
                }
                $bodymessage = "There is an update available for COMPANY ".$fav->fav_identifier. " dated ".$lastupdatePhp;
                $subject = "EFSA Reminder: Company";
            }

            
            
            if ($send) {


                $email = $fav->email;
                $data = array(
                    'email' =>$email,               
                    'bodymessage'=> $bodymessage,
                    'subject' => $subject
                );
                
                Mail::send('email.blank', $data, function ($message) use ($data) {
                    $message->to($data['email']);
                    $message->subject($data['subject']);
                });
                
                
            } 
            }
          
            $now = date("Y-m-d H:i:s");

            foreach ($toupdate as $thisupdate) {
                $user_id = $thisupdate['user_id'];
                $fav_identifier = $thisupdate['fav_identifier'];
                
                $aff = DB::update('update favourites set lastupdate = :now where user_id = :user_id AND fav_identifier = :fav_identifier', ['now' => $now, 'user_id' => $user_id, 'fav_identifier' => $fav_identifier]);

                print_r($aff);
            }
            

        })->everyMinute();;
    }
}

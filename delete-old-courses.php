<?php

require_once('functions.php');

// Put your site URL and web service token here

$config['site']     = 'https://moodle.something.ac.uk';
$config['token']    = '74833e911605ee374986da0e41874371';

// If you set this to true, the entire script will fail if Moodle's web
// services reports an error. If it's false, the script will try and continue.
$config['fail']     = false;

// Used for testing. If this is false, the courses will actually be deleted.
// If it's true, the script will tell you which courses it wants to delete, but
// it won't actually delete them.
$config['test']    = true;

$config['check-time-created']  = true;
$config['check-time-modified'] = true;
$config['check-start-date']    = true;
$config['check-end-date']      = true;
$config['check-for-students']  = true;
$config['check-for-changes']   = true;

// Safety date: course start/end/creation date must be before this time
// Modify date: course last changed / recent activity must be before this time

$safetyDate = strtotime("2016-08-01 00:00:00");
$modifyDate = strtotime("2021-08-01 00:00:00");

// These are the fields in the CSV that we use. The first field is 0, not 1

$idField = 0;

out('Moodle Old Course Delete Script by Alex Walker. Version 1.1.', '*');

$file = @fopen('courses.csv', 'r');

if(!$file) {
    out('Cannot find courses.csv', '!', 'red');
    die();
}

$count = 0;

while($csv = fgetcsv($file)) {
    
    $delete = true;
    
    if(@intval($csv[$idField]) == 0) {
        out('Not a valid course ID: '.$csv[$idField], '!', 'red');
        continue;
    }
    
    if($csv[$idField] == 1) {
        out('Can\'t delete course ID 1 - that\'s your site home', '!', 'red');
        continue;
    }
    
    // Get course details
    
    $response = sendToMoodle(
        'core_course_get_courses',
        array('options' => array('ids' => array(
            $csv[$idField],
        ))),
        false
    );
    
    
    if(empty($response)) {
        out('Course doesn\'t exist in Moodle: '.$csv[$idField], '!', 'red'); 
        continue;
    }
    
    $courseDetails = $response[0];
    
    out('Looking at course '.$courseDetails['id'].' - '.$courseDetails['fullname'], '+', 'blue');
    
    if($config['check-time-created'] && $courseDetails['timecreated'] > $safetyDate) {
        out('Course was created too recently: '.date("Y-m-d", $courseDetails['timecreated']), '-', 'yellow'); 
        $delete = false;
    }
    
    if($config['check-time-modified'] && $courseDetails['timemodified'] > $modifyDate) {
        out('Course was modified too recently: '.date("Y-m-d", $courseDetails['timemodified']), '-', 'yellow'); 
        $delete = false;
    }
    
    if($config['check-start-date'] && $courseDetails['startdate'] > $safetyDate) {
        out('Course start date is too recent: '.date("Y-m-d", $courseDetails['startdate']), '-', 'yellow'); 
        $delete = false;
    }
    
    if($config['check-end-date'] && $courseDetails['enddate'] > $safetyDate) {
        out('Course end date is too recent: '.date("Y-m-d", $courseDetails['enddate']), '-', 'yellow'); 
        $delete = false;
    }
    
    if($config['check-for-students']) {
    
        $response = sendToMoodle(
            'core_enrol_get_enrolled_users_with_capability',
            array('coursecapabilities' => array(array(
                'courseid' => $csv[$idField],
                'capabilities' => array(
                    'mod/quiz:attempt'
                ),
            ))),
            false
        );
        
        $users = $response[0]['users'];
        
        if(count($users) > 0) {
            out('There are still '.count($users).' students on this course', '-', 'yellow');
            $delete = false;
        }
    }
    
    if($config['check-for-changes']) {
        $response = sendToMoodle(
            'core_course_get_updates_since',
            array(
                'courseid' => $csv[$idField],
                'since' => $modifyDate,
            ),
            false
        );
        
        $changes = $response['instances'];
        
        if(count($changes) > 0) {
            out('The course was changed '.count($changes).' times since '.date('Y-m-d', $modifyDate), '-', 'yellow');
            $delete = false;
        }
    }
        
    if($delete) {
        if(!$config['test']) {
            $response = sendToMoodle(
                'core_course_delete_courses',
                array('courseids' => array(
                    $csv[$idField],
                )),
                false
            );
            $count++;
            out('Deleted course: '.$csv[$idField], '-', 'green');
        } else {
            out('Test mode - would have deleted course: '.$csv[$idField], '-', 'yellow');
        }
    } else {
        out('Not deleting course: '.$csv[$idField], '-', 'yellow'); 
    }
    
}

out('Done. Deleted '.$count.' courses.', '*', 'green');

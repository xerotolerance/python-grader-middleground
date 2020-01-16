<?php
/**
 * Created by PhpStorm.
 * User: xero
 * Date: 10/4/18
 * Time: 3:43 PM
 */

$GLOBALS['teacher_token']="2020d177e17eb5f1127ea2a3e14838078a73ba32";

include_once ("Request.php5");

function runpycode($func_name, $local_dir, $script_name, $python="/usr/local/bin/python3", $verbose=FALSE, ...$argv){
    //1. Create runner file,
    // 2. include file containing code to be run,
    //  3. create array in which to hold results
    $runner_name = 'runner.py';
    $comment_name='make_comments.py';
    $result_name='result.tmp';

    $runner_path = $local_dir.'/'.$runner_name;
    $comment_path = $local_dir.'/'.$comment_name;
    $result_path = $local_dir.'/'.$result_name;

    file_put_contents($runner_path, 'from '.str_ireplace('.py', '', $script_name). ' import *'.PHP_EOL.PHP_EOL);
    file_put_contents($comment_path, 'from '.str_ireplace('.py', '', $script_name). ' import *'.PHP_EOL.PHP_EOL);

    file_put_contents($comment_path, 'comments = []'.PHP_EOL, FILE_APPEND);
    file_put_contents($runner_path, 'results = []'.PHP_EOL, FILE_APPEND);

    // Make a single array of io strings
    $io_array = [];
    foreach($argv as $arg){
        $io_array = array_merge_recursive($io_array, $arg);
    }

    // Turn io strings into assoc. array of arguments => answers
    $testcases = [];
    foreach ($io_array as $test){
        $arg_arr = explode(')=',$test);
        error_log(print_r($io_array, true).PHP_EOL,3,'./runlog');
        if ($arg_arr && $arg_arr[0])
            $arg_arr[0] .= ')';
        $testcases[$arg_arr[0]] = $arg_arr[1];
    }

    //echo file_get_contents($local_dir.'/'.$script_name);
    //echo "\n=========================";


    // Generate and append callable function signatures to runner file
    //  Make python compare the experimental results of func to the expected results
    //   Store comparision into results array
    foreach ($testcases as $input => $output){
        $pattern = '/\(.*\)/';
        $func_sig_call = preg_replace($pattern, $input, $func_name);


	$comments_code=
//Start PyCode
"actual=".$func_sig_call."
print(actual)
print()
test_result = actual==".$output."


comment='''Tested against inputs: ". $input . "
  Expected result: ". $output . "
  Got result: ''' + str(actual)  + '''
  Test Status -- Passed?: ''' + str(test_result) + '''\n'''

comments.append(comment)
"
//End PyCode
	;


    	$results_code = 
//Start PyCode
"actual=".$func_sig_call."
print('''\n''',actual,sep='')
print()
test_result = actual==".$output."
results.append(test_result)
"
//End PyCode
	;


	//$denom=uniqid('##SEP##');
        file_put_contents($comment_path, $comments_code.PHP_EOL.PHP_EOL, FILE_APPEND);
	file_put_contents($runner_path, $results_code.PHP_EOL, FILE_APPEND);

    }

   

    $runner_print_code = 
//Start PyCode
"with open('".$result_path."','w') as resfl:
	print(results,end='',file=resfl)
"
//End PyCode
    ;


    $comments_print_code =
//Start PyCode
"print()
for comment in comments:
	print('''>''', comment, end='''\n\n''')
"
//End PyCode
    ;

    file_put_contents($runner_path, $runner_print_code.PHP_EOL, FILE_APPEND);
    file_put_contents($comment_path, $comments_print_code.PHP_EOL, FILE_APPEND);

    $output_arr = [];

    // Run the runner & make_comments files, saving output into string variables
    $runner_command =  $python . " " . $runner_path;
    $comment_command =  $python . " " . $comment_path;      
    $output_str=exec(escapeshellcmd($runner_command . " 2>&1"),$output_arr);
    $comments_str=shell_exec(escapeshellcmd($comment_command . " 2>&1"));
    $real_result_str = file_get_contents($result_path);

    
    if ($output_arr[count($output_arr)-1]==''){
	array_pop($output_arr);
    }
    //print_r($output_arr);
    $output_str=$output_arr[count($output_arr)-1];
    $full_output=implode("\n",$output_arr);


    //if ($output_str)

    //var_dump($output_str);
    //var_dump($comments_str);


    
    

    if ($verbose) {

	echo ">RUNNER:\n\n". file_get_contents($runner_path).PHP_EOL,PHP_EOL;

        echo "Requested runner exec: \"" . $runner_command . "\"" . PHP_EOL . "<br>";
        echo "Full exec output: ";
        echo "<pre>\n"; print_r($full_output); echo "\n</pre>";
        echo "Actual result output: \"" . $real_result_str . "\"" . PHP_EOL."<br>";
	echo PHP_EOL . PHP_EOL . '==================' . PHP_EOL . PHP_EOL;

	echo ">COMMENTS:\n\n".file_get_contents($comment_path).PHP_EOL,PHP_EOL;

        echo "Requested comment exec: \"" . $comment_command . "\"" . PHP_EOL . "<br>";
        echo "Full exec output: ";
        echo "<pre>\n"; print_r($comments_str); echo "\n</pre>";

    }

    //echo "\nShort output: {".$output_str."}".PHP_EOL;
    //if ($output_str) 


    // Cleanup the mess
    exec('rm -r '.$local_dir);
    $num_failed=0;
    // Return the output as assoc. array of ['results'=>string, 'comments'=>list]
    return ['results'=>$real_result_str, 'comments'=>$comments_str, 'constraints_penalty'=>$num_failed];
}


function check_constraints($question, $answer, $constraints_weight_percentage){
    $wp=dirname($_SERVER['PATH_TRANSLATED']);
    error_log(var_export($question,true).PHP_EOL,3 ,$wp.'/constraint_log');
    error_log(var_export($answer,true).PHP_EOL,3 ,$wp.'/constraint_log');
    $passed=0;
    $constraints = explode('|',$answer['question_constraint']);
    $num_constraints=count($constraints);
    $while_regex='^[[:blank:]]*while[[:blank:]]*((\([^:[:space:]]+\))|([^: ][^:]+))[[:blank:]]*:[[:blank:]]*(#*)*';


/*
    if (!preg_match('/[[:space:]]+return/',$question['answer_text'])){
	$last_print_pos = strrpos($question['answer_text'],'print');
        if ($last_print_pos !== false){
	    substr_replace($question['answer_text'],'return',$last_print_pos,count('print'));
	}
    }
*/

    $answer_text_lines= explode("\n", $question['answer_text']);

    //var_dump($answer_text_lines);

    //var_dump($constraints);
    $comments=[];
    foreach($constraints as $constraint){
        if ($constraint=='while'){
	    $found=false;
	    foreach($answer_text_lines as $line){
                if ( preg_match('/'.$while_regex.'/', $line) > 0 ){
	            $passed++;
		    $found=true;
		    break;
	        }
	    }
	    if (!$found){ 		
		array_push($comments,">  Failed Constraint:  Answer must contain while loop.  \t-". (string)(int)(1/$num_constraints*$constraints_weight_percentage) . "%" . PHP_EOL);
	    }
	    
        }
        elseif($constraint=='for'){
            if ($question['answer_test']){}
        }
        elseif($constraint=='recurssion'){
            if ($question['answer_test']){}
        }
    }

    $comments = $passed == $num_constraints? ['>  All Constraints were met :)'] : $comments;

//    echo "e teapot\n";
    return ['percent_earned'=>$passed/$num_constraints*$constraints_weight_percentage, 'comments'=>implode("\n", $comments)];
}


function grade($question, $answer, $cweightp=NULL, $qweightp=NULL){
    //var_dump($cweightp,$qweightp);
    if ($cweightp && !$qweightp){
    	$qweightp=100-$cweightp;
    }
    elseif($qweightp && !$cweightp){
	$cweightp=100-$pweightp;
    }
    elseif (!$cweightp && !$qweightp){
	$cweightp = $qweightp = 50;
    }
    elseif($cweightp + $qweightp < 100 ){
	die('Grade Percent weights must sum to 100 higher!');
    }

    exec('whoami', $output);
    $dirname=uniqid('cjm68-via-'.$output[0].'-CS490-tmp_');
    $tmp_dir=sys_get_temp_dir();
    $local_path = $tmp_dir.'/'.$dirname;
    $filename = 'code.py';
    mkdir($local_path, 0775);

    if (!file_put_contents($local_path.'/'.$filename, $question['answer_text']))
        die("Couldn't write to test file.");
    $testcases=explode("||", $answer['question_inputs']);


    $constraint_analysis = check_constraints($question,$answer, $cweightp);

    //var_dump($constraint_analysis);

    $cpercent_int = $constraint_analysis['percent_earned'];
    $output_analysis=runpycode($answer['question_fxn_name'], $local_path, $filename, "python3",0, $testcases);
    $qweight = sscanf($answer['question_max_score'], '%D')[0];
    $qpercent_int = substr_count($output_analysis['results'], 'True') / (substr_count($answer['question_inputs'], '||') + 1) * $qweightp;
    
    //echo "Question worth " . (string)$qweight . " points.\n" . PHP_EOL;

    $percent_earned=($qpercent_int + $cpercent_int)/100;
    $grade=(int)($qweight * $percent_earned  + .5);     // <== +.5 & truncate to answer for basic integer rounding
    
    $comments = "\n===========================\nAnalysis of Question " . $question['question_id'] . PHP_EOL . PHP_EOL . $constraint_analysis['comments'] . PHP_EOL . PHP_EOL . $output_analysis['comments'];

    return  ['grade'=>$grade, 'comments'=>$comments];
}

function update_with_grade($act_args){
    $num_questions = count($act_args['questions']);
    foreach(range(0, $num_questions - 1) as $i){
        $answerkey = request_from_backend($GLOBALS['teacher_token'], Request::GET_QUESTION_GRADING_INFO,NULL, $act_args['questions'][$i], ['test_id'=>$act_args['test_id']]);
        //var_dump($answerkey);

	$constraint_worth_percentage_int = NULL;
	$testcases_worth_percentage_int = NULL;

	if ($answerkey === 0){
 	    die('Couldnt obtain evaluation from Backend.');
	}


	if ($answerkey['question_constraint']=='none'){
	    $constraint_worth_percentage_int = 0;
	    $testcases_worth_percentage_int = 100 - $constraint_worth_percentage_int;
	}
	else{
	    $constraint_worth_percentage_int = 25;
	    $testcases_worth_percentage_int = 100 - $constraint_worth_percentage_int;
	}
	
        $evaluation=grade($act_args['questions'][$i], $answerkey, $constraint_worth_percentage_int, $testcases_worth_percentage_int);
	//var_dump($evaluation);


        $act_args['questions'][$i]['grade'] = $evaluation['grade'];
        $act_args['questions'][$i]['comment'] = $evaluation['comments'];
    }
    //var_dump($act_args);
    return $act_args;
}

function request_from_backend($requester, $action, $be_url=NULL, ...$act_argv){
    // Set default URL to beta request script
    $be_url = $be_url? $be_url: "http://afsaccess1.njit.edu/~btc5/cs490/request.php";


    if ($act_argv && $action != Request::GET_TEST_QUESTIONS && $action != Request::GET_TEST_QUESTIONS_ANSWERED){
        $primed_act_argv=array();
        foreach ($act_argv as $key => $value)
            if (!is_int($key))
                array_push($primed_act_argv, [$key=>$value]);
            else
                array_push($primed_act_argv, $act_argv[$key]);
        $act_argv = $primed_act_argv;
    }

    // Make sure action is allowed.
    if (!array_key_exists($action, Request::$enums))
        die("Invalid action requested: " . var_dump($action) . ".");
    ###########################################################################
#   Forwards credentials to backend server,
    $request = array("sessionid" => $requester, "request" => Request::$enums[$action] );
    if ($action >= Request::GET_TEST_QUESTIONS && $act_argv)
        foreach ($act_argv as $arg_kv_pair)
            $request = array_merge_recursive($request, $arg_kv_pair);
//    $request['testid'] = argv[];


    $request_jstr =("request=" . json_encode($request, JSON_PRETTY_PRINT));
    // urlencode URI-sensitive symbols in  string before sending it off
    //  this accounts for special characters to URIs such as '+' signs within user's answers
    $blacklist = ['+','&'];
    foreach($blacklist as $symbol){
        $request_jstr =str_replace($symbol, urlencode($symbol), $request_jstr);
    }

    //echo $request_jstr;

    //error_log($request_jstr.PHP_EOL,3,'./runlog');

    #Regular cURL sessions
    $c_backend = curl_init($be_url);

    #Config for reg. cURL sessions
    $curlopt_common = array(CURLOPT_POST => 1, CURLOPT_RETURNTRANSFER => 1, CURLOPT_POSTFIELDS => $request_jstr);
    curl_setopt_array($c_backend, $curlopt_common);

    $result_backend = curl_exec($c_backend);

    $auth_res = json_decode($result_backend, 1);
    
    error_log('BE returned: '.PHP_EOL.print_r($result_backend, true).PHP_EOL,3,'./runlog');
    
    curl_close($c_backend);
    return $auth_res;
}

function forward_to_frontend($requester, $source=NULL, ...$act_argv){
    //Associative list of keys all requests MUST have.
    $common_keys = ["sessionid"=>NULL,"request"=>NULL];

    //expecting $source to be a PHP Array with 1 key:value pair of "request":(JSON{inner PHP-array})
    // If no source is provided, default source to $_POST
    $source = $source? $source : $_POST;
    if (!array_key_exists("request", $source))
        die("Couldn't find request from source.");

    //need to convert pull JSON value from pair & convert it
    //back to PHP array to interact with inner-PHP-array

    //  expecting $req_array to be a PHP Array with AT LEAST 2 key:value pairs of ["sessionid":(PHP-string), "request":(PHP-string)]
    $req_array = json_decode($source['request'], true);

    //Check that inner PHP-array contains key:value pair of "request":(php-string) & save value for ease of access
    if (array_key_exists("request", $req_array)){
        $req_str = $req_array["request"];
    }else die("no action specified.");

    //creates reverse-lookup map for abstract Request's fields
    Request::reverse_map_enum(Request::$enums);

    //     Also check that the php-string value can be matched to an existing Request-type
    //     via the reverse-lookup-map, created above.
    if (array_key_exists($req_str, Request::$remap) ){
        $action = Request::$remap[$req_str];
        if ($requester != $req_array['sessionid'])
            die("Mismatching sessonids!");
    }
    else die("action not found");

    //Execute requested action, passing args if necessary, and output result

    if ($action < Request::GET_TEST_QUESTIONS) {
        $results[$action] = request_from_backend($requester, $action, NULL);
    }
    else {
        //copy all relative/additional args of requested action to separate array
        $act_argv = $act_argv ? $act_argv : array_diff_key($req_array, $common_keys);

        if ($action == Request::SUBMIT_GRADED_TEST){
            $act_argv = update_with_grade($act_argv);
        }

        if ($act_argv && ( $action== Request::SUBMIT_GRADED_TEST || $action==Request::GET_TEST_QUESTIONS || $action==Request::GET_TEST_QUESTIONS_ANSWERED)){
            $primed_act_argv=array();
            foreach ($act_argv as $key => $value)
                if (!is_int($key))
                    array_push($primed_act_argv, [$key=>$value]);
                else
                    array_push($primed_act_argv, $act_argv[$key]);
            $act_argv = $primed_act_argv;
        }

        $requester = $action == Request::SUBMIT_GRADED_TEST? $GLOBALS['teacher_token'] : $requester;
        $results[$action] = request_from_backend($requester, $action, NULL, ...$act_argv);
    }
    return json_encode($results[$action]);
}

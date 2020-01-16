<?php
/**
 * Created by PhpStorm.
 * User: xero
 * Date: 10/4/18
 * Time: 3:43 PM
 */

$GLOBALS['teacher_token']="2020d177e17eb5f1127ea2a3e14838078a73ba32";

include_once ("Request.php5");
include_once ("Constraint.php5");

function runpycode($func_name, $local_dir, $script_name, $python="/usr/local/bin/python3", $qweight, $rounding_adjustment=0, $verbose=FALSE, ...$argv){
    //1. Create runner file,
    // 2. include fileu containing code to be run,
    //  3. create array in which to hold results
	//echo (string)time()." >entered runpycode\n";
    $runner_name = 'runner.py';
    $comment_name='make_comments.py';
    $result_name='result.tmp';
    $errlog_name='pyerr.log';

    $runner_path = $local_dir.'/'.$runner_name;
    $comment_path = $local_dir.'/'.$comment_name;
    $result_path = $local_dir.'/'.$result_name;
    $errlog_path = $local_dir.'/'.$errlog_name;
    
    file_put_contents($runner_path, 'from '.str_ireplace('.py', '', $script_name). ' import *'.PHP_EOL.PHP_EOL);

    file_put_contents($comment_path, 'import sys'.PHP_EOL.PHP_EOL);
    file_put_contents($comment_path, 'from '.str_ireplace('.py', '', $script_name). ' import *'.PHP_EOL.PHP_EOL, FILE_APPEND);
   
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

    $case_weight = $qweight / count($testcases);
    $leftover = $case_weight - (int)$case_weight;
    $case_weight = (int)$case_weight;

    // Generate and append callable function signatures to runner file
    //  Make python compare the experimental results of func to the expected results
    //   Store comparision into results array
    foreach ($testcases as $input => $output){
        $pattern = '/\(.*\)/';
        $func_sig_call = preg_replace($pattern, $input, $func_name);


	$comments_code=
//Start PyCode
"actual=".$func_sig_call."
case_weight=".$case_weight."

ogstdout = sys.stdout
tmpfl = open('".$local_dir."/tmpfl','a')
sys.stdout = tmpfl
print(actual)
print()
tmpfl.close()
sys.stdout = ogstdout

test_result = actual==".$output."

if test_result:
	sign = '''+'''
	message = '''(''' + sign + str(".$case_weight.") + ''' points)\n'''
else:
	sign = '''-'''
	message = '''\t[''' + sign + str(".$case_weight.") + ''' points from question total.]\n\n'''

comment='''Tested against inputs: ". $input . "
  Expected result: ". $output . "
  Got result: ''' + str(actual)  + '''
  Test Status -- Passed?: ''' + str(test_result) + '''  ''' + message

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

	//echo (string)time()." > Writing runner Pycode to file\n";
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
	//echo (string)time()." > Writing printing Pycode to file\n";
    file_put_contents($runner_path, $runner_print_code.PHP_EOL, FILE_APPEND);
    file_put_contents($comment_path, $comments_print_code.PHP_EOL, FILE_APPEND);

    $output_arr = [];

    // Run the runner & make_comments files, saving output into string variables
    $runner_command =  $python . " " . $runner_path;
    $comment_command =  $python . " " . $comment_path;      
    	//echo (string)time()." > Executing runner...\n ";
    $output_str=exec($runner_command . " 2>".$errlog_path,$output_arr);

	//echo (string)time()." > runner ruturned.\n\n ";
    	//echo (string)time()." > Executing commentor...\n ";
    $comments_str=shell_exec($comment_command . " 2>".$errlog_path);
	//echo (string)time()." > commentor ruturned.\n\n ";

	//echo (string)time()." > Retrieving result from file\n";	
    $real_result_str = file_exists($result_path) ? file_get_contents($result_path) :'[]';
	//echo (string)time()." > Result retreived.\n\n ";
    //echo $real_result_str .PHP_EOL;
    //echo $rounding_adjustment.PHP_EOL; 
    $rounding_adjustment += $leftover * substr_count($real_result_str, 'True');

    //echo $rounding_adjustment . " extra points earned".PHP_EOL;

    if (count($output_arr) && $output_arr[count($output_arr)-1]==''){
	array_pop($output_arr);
    }
    //print_r($output_arr);
    $output_str=count($output_arr)? $output_arr[count($output_arr)-1] : '';
    $full_output=implode("\n",$output_arr);

    //echo strlen(file_get_contents($errlog_path));
    //echo PHP_EOL . file_get_contents($errlog_path) . PHP_EOL;

    if (file_exists($errlog_path) && strlen(file_get_contents($errlog_path))>0){
	$comments_str.=">  ENCOUNTERED SYNTAX ERROR UPON EXECUTION;\n\n\t[-".$qweight." points off question total.]\n\n";
	//echo file_get_contents($errlog_path).PHP_EOL;
    }
    $comments_str .= "--------------------------------------\n\n".
		     "> Rounding Adjustment:  ". ($rounding_adjustment > 0? " Gained additional  ".(int)($rounding_adjustment+.5) : " Lost additional ".-1*(int)($rounding_adjustment-1)) ." point(s)  due to rounding.\n".
		     "\n--------------------------------------".PHP_EOL.PHP_EOL;

    //if ($output_str)

    //var_dump($output_str);
    //var_dump($comments_str);


    //echo $comments_str.PHP_EOL;    
    

    if ($verbose) {

	echo "tmpfl:\n\n". file_get_contents($local_dir.'/tmpfl').PHP_EOL.PHP_EOL;

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

    //echo $real_result_str.PHP_EOL;
    // Cleanup the mess
    exec('rm -r '.$local_dir);
    $num_failed=0;
    // Return the output as assoc. array of ['results'=>string, 'comments'=>list]
	//echo (string)time()." > Returning preliminary grading info...\n\n ";
    return ['results'=>$real_result_str, 'comments'=>$comments_str, 'rounding_adjustment'=>$rounding_adjustment];
}


function check_constraints($question, $answer, $constraints_weight_percentage, $question_weight_int, $file_path){
    Constraint::reverse_map_enums(Constraint::$enums);
    //print_r(Constraint::$remap);

    $wp=dirname($_SERVER['PATH_TRANSLATED']);
    error_log(var_export($question,true).PHP_EOL,3 ,$wp.'/constraint_log');
    error_log(var_export($answer,true).PHP_EOL,3 ,$wp.'/constraint_log');
    $imp_passed=0;
    $exp_passed=0;
    $constraints = explode('|',$answer['question_constraint']);
    $constraints=array_merge(Constraint::get_implicit_constraints(),$constraints);
    //var_dump($answer);
    //echo $answer['question_constraint'].PHP_EOL;
    //print_r($constraints);
    
    $func_param_start = strpos($answer["question_fxn_name"],'(');
    //echo $func_param_start.PHP_EOL;
    $func_name = substr($answer["question_fxn_name"],0,$func_param_start);
    $func_param_str=substr($answer["question_fxn_name"],$func_param_start);    
    
    $num_constraints=count($constraints);
    $num_implicit=count(Constraint::get_implicit_constraints());
    $num_explicit=$num_constraints-$num_implicit;
    $num_arguments=substr_count($func_param_str,",")+1;
    //echo " Detected ".$num_implicit." implicit constraints".PHP_EOL;
    /*    
    //echo '> Question:'.PHP_EOL;
    var_dump($question);

    echo '> Answer'. PHP_EOL;
    var_dump($answer);
*/
    $constraint_effect=$constraints_weight_percentage/100*$question_weight_int;
    //echo "Max points from constraints earnable on problem: ".$constraint_effect.PHP_EOL;
    $implicit_effect = .75; $explicit_effect = 1-$implicit_effect;
    $points_per_implicit = (1 / $num_implicit) * $implicit_effect * $constraint_effect;
    //echo $points_per_implicit . PHP_EOL;
    $imp_leftover = $points_per_implicit - (int)$points_per_implicit;
// 
    $points_per_explicit = (1 / $num_explicit) * $explicit_effect * $constraint_effect;
    $exp_leftover = $points_per_explicit - (int)$points_per_explicit;
    //$points_per_constraint=(int)$points_per_constraint;
    $answer_text_lines= explode("\n", $question['answer_text']);
    //var_dump($answer_text_lines);

    //var_dump($constraints);
    $comments=['passed'=>[],'failed'=>[]];
    //print_r(Constraint::$implicit_constraints) . PHP_EOL;
    

    foreach($constraints as $constraint){
	$pass_text='';  $fail_text='';  $found=false;
	$c_regex = array_key_exists($constraint, Constraint::$remap) ? Constraint::$remap[$constraint] : Constraint::NO_CONSTRT;

	$c_value = $points_per_explicit;
	if ($c_regex < Constraint::NO_CONSTRT)
	    $c_value = $points_per_implicit;

	switch($c_regex){
	    case Constraint::NO_CONSTRT:
		break;
	    case Constraint::WHILE_LOOP:
		//echo "Checking for While loop...\n";
	    case Constraint::FOR_LOOP:

		if ($c_regex==WHILE_LOOP){
		    $pass_text="   Constraint Met:  Answer must contain while loop.  \t(+". (string)$c_value . " points)" . PHP_EOL;
		    $fail_text=">  FAILED CONSTRAINT:  Answer must contain while loop.  \n\n\t[-". (string)$c_value . " points off question total.]\n" . PHP_EOL;
		}else{
		    //echo "Checking for For loop...\n";
		    $pass_text="   Constraint Met:  Answer must contain for loop.  \t(+". (string)$c_value . " points)" . PHP_EOL;
		    $fail_text=">  FAILED CONSTRAINT:  Answer must contain for loop.  \n\n\t[-". (string)$c_value . " points off question total.]\n" . PHP_EOL;

		}

	    	
		foreach($answer_text_lines as $line){
		    if ( preg_match(Constraint::$lookup[$c_regex], $line) > 0 ){
	        	$imp_passed++;
			$found=true;
			break;
		    }
	        }
		break;

	    case Constraint::RECURSION:
		//echo "Checking for Recursion...\n";
		$pass_text="   Constraint Met:  Answer must contain recursive call to itself.  \t(+". (string)$c_value . " points)" . PHP_EOL;
		$fail_text=">  FAILED CONSTRAINT:  Answer must contain a recursive call to itself.  \n\n\t[-". (string)$c_value . " points off question total.]\n" . PHP_EOL;

		Constraint::mark_funcs_recursive($func_name);

		//echo Constraint::$lookup[$c_regex][$func_name].PHP_EOL;
		//echo "Checking for recursion in following text: \n".$question['answer_text']."...\n";
		if ( preg_match_all(Constraint::$lookup[$c_regex][$func_name], $question['answer_text']) > 1 ){
	            //echo "Recursive call found!";
		    $exp_passed++;
		    $found=true;
		}
		break;		

	    case Constraint::DEF_STMT:
		//echo "Checking for Def statement...\n";
		$pass_text="   Constraint Met:  Function declared using the 'def' keyword.  \t(+". (string)$c_value . " points)" . PHP_EOL;
		$fail_text=">  FAILED CONSTRAINT:  All functions MUST be declared using the 'def' keyword before the function name.  \n\n\t[-". (string)$c_value . " points off question total.]\n" . PHP_EOL;

		$known_functions=preg_split(Constraint::$lookup[$c_regex]['KEYWORD'], $question['answer_text'],-1,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		//echo Constraint::$lookup[$c_regex].PHP_EOL;
		$i=0; $func_declared = False;
		$found=True;
		//print_r($known_functions);

		foreach($known_functions as $known_function){
		    //if (trim($known_function)=="def")
			//continue;
		     
		    if (preg_match(Constraint::$lookup[$c_regex]['KEYWORD'],$known_function)){
		        $func_declared=True;
		        unset($known_functions[$i]);
			$i++;
			continue;
		    }

		    $code_lines=explode("\n",$known_function);
		    $j=0;
		    foreach($code_lines as $code_line){
			//echo "def detected?: ".$func_declared . PHP_EOL;
			//echo "expr: ".$code_line. PHP_EOL;	
			if (preg_match(Constraint::$lookup[$c_regex]['EXPR'], $code_line)){
			    if (!$func_declared){
				//echo '"'.$code_line.'"'. " is an undeclared function.".PHP_EOL;
				$found=False;
			    }
			    $code_lines[$j] = 'def ' . trim($code_line);
			    $func_declared = False;
			    break;
			}
			$func_declared=False;
			$j++;
		    }
		    $known_functions[$i] = implode("\n",$code_lines);
		    $i++;
		}
		if ($found)
		    $imp_passed++;
		$question['answer_text']=implode("\n", $known_functions);
		$answer_text_lines = explode("\n", $question['answer_text']);
		file_put_contents($file_path, $question['answer_text']);

		//echo "Code after DEF exam\n".$question['answer_text'].PHP_EOL;
		break;

	    case Constraint::COLON_STMT:
		//echo "Checking for missing colons...\n";
		$pass_text="   Constraint Met:  No missing colons detected in present code.  \t(+". (string)$c_value . " points)" . PHP_EOL;

		$fail_text=">  FAILED CONSTRAINT:  Lines beginning with keywords 'if','def','for', or 'while' MUST end with a colon (':').  \n\n\t[-". (string)$c_value . " points off question total.]\n" . PHP_EOL;
		$found=true; $linenum=0;
		foreach($answer_text_lines as $line){
		    $linenum++;
		    if ( preg_match(Constraint::$lookup[$c_regex], $line) > 0 ){
	        	//$passed++;
			$found=!true;
			$fail_text.= "\t Missing colon on line ".$linenum. PHP_EOL . "\t\" ".$line." \"".PHP_EOL;
			$answer_text_lines[$linenum-1].=":";
			//break;
		    }
	        }
		$question['answer_text'] = implode("\n",$answer_text_lines);
		echo $question['answer_text']; 
		file_put_contents($file_path, $question['answer_text']);
		//echo PHP_EOL.$question['answer_text'].PHP_EOL;
		if ($found)
		    $imp_passed++;
		break;

	    case Constraint::ARG_MATCH:
		//ARG_MATCH CODE HERE
		$pass_text="   Constraint Met:  Proper number of arguments passed to function.  \t(+". (string)$c_value . " points)" . PHP_EOL;

		$fail_text=">  FAILED CONSTRAINT:  Improper number of arguments passed to the function.  ";
		//echo "Checking for proper argument usage in function signature...\n";
		//echo $question['answer_text'].PHP_EOL;

		Constraint::get_func_args($question['answer_text'], $func_name);
		//echo 'func_name: '.$func_name. PHP_EOL;
		//echo '<';
		//print_r(Constraint::$func_args[$func_name]);
		//echo '>';
		//print_r(explode(',', Constraint::$func_args[$func_name][1]));
		//echo "expected ". $num_arguments . " arguments\n";
		if ( ($arg_count=count(explode(',', Constraint::$func_args[$func_name][1])))==$num_arguments){
		    $imp_passed++;
		    $found=true;
		}
		else{
		    $found=false;
		    //echo "Original: ". $question['answer_text'].PHP_EOL.PHP_EOL;
		    $pattern = '/[[:<:]]'.$func_name.'[[:>:]][[:blank:]]*\(.*\)/';
		    preg_match($pattern,$question['answer_text'],$matches); 
        	    $question['answer_text'] = preg_replace($pattern, $answer['question_fxn_name'], $question['answer_text'],1);

		    $fail_text.="\n\n\t  Function ".$answer["question_fxn_name"]." should expect to receive ".$num_arguments." argument(s) but is accepting ".$arg_count." instead.\n\n\t  ...Correcting ".$matches[0]." to ".$answer['question_fxn_name'].".";


		    $answer_text_lines = explode("\n",$question['answer_text']);
		    //echo "{\n".$question['answer_text']."\n}\n".PHP_EOL;
		}
		//print_r($answer);
		$fail_text.="\n\n\t[-". (string)$c_value . " points off question total.]\n" . PHP_EOL;
		break;

	    case Constraint::FUNC_PARENS:
		//FUNC_PARENS CODE HERE
		//echo "Checking for proper parenthesis usage when using functions...\n";
		$pass_text="   Constraint Met:  No mismatched parenthesis detected in present code.  \t(+". (string)$c_value . " points)" . PHP_EOL;

		$fail_text=">  FAILED CONSTRAINT:  Any  usage of the function name MUST be followed by matching parenthesis, whether calling or defining the function.  \n\n\t[-". (string)$c_value . " points off question total.]\n" . PHP_EOL;
		
		Constraint::check_func_parens($func_name);
		$found=true; $linenum=0;
		foreach($answer_text_lines as $line){
		    $linenum++;
		    //echo $linenum . ": ".$line.PHP_EOL;
		    //var_dump(Constraint::$lookup[$c_regex]);
		    if ( preg_match(Constraint::$lookup[$c_regex][$func_name]['left_paren'], $line) > 0 ){
	        	//$passed++;
			$found=!true;
			$fail_text.= "\t Missing left parenthesis '(' on line ".$linenum. PHP_EOL . "\t\" ".$line." \"".PHP_EOL;
			//echo $fail_text.PHP_EOL;
			#INSERT LEFT PAREN CODE HERE
			$answer_text_lines[$linenum-1]=preg_replace("/".$func_name."/",$func_name."(",$answer_text_lines[$linenum-1]);
			$line=$answer_text_lines[$linenum-1].PHP_EOL;
			#$answer_text_lines[$linenum-1].=":";
			//break;
		    }
		    //echo "$ line=".$line.PHP_EOL;
		    if ( preg_match(Constraint::$lookup[$c_regex][$func_name]['right_paren'], $line) > 0 ){
	        	//$passed++;
			
			$found=!true;
			$fail_text.= "\t Missing right parenthesis ')' on line ".$linenum. PHP_EOL . "\t\" ".$line." \"".PHP_EOL;
			//echo $fail_text.PHP_EOL;
			//INSERT RIGHT PAREN CODE HERE
			$answer_text_lines[$linenum-1]=preg_replace("/:$/","):",$answer_text_lines[$linenum-1]);
			if (!preg_match("/\):$/",$answer_text_lines[$linenum-1]))
			    $answer_text_lines[$linenum-1].=")";

			//echo $answer_text_lines[$linenum-1].PHP_EOL;
			//break;
		    }

	        }
		$question['answer_text'] = implode("\n",$answer_text_lines);
		file_put_contents($file_path, $question['answer_text']);
		//echo PHP_EOL.$question['answer_text'].PHP_EOL;
		if ($found)
		    $imp_passed++;
		break;

	    case Constraint::FUNC_NAME_MATCH:
		//FUNC_NAME_MATCH CODE HERE
		//echo "Checking for specified function name...\n";
		Constraint::identify_functions($question['answer_text']);

		$pass_text="   Constraint Met:  Detected function named '".$func_name."'.  \t(+". (string)$c_value . " points)" . PHP_EOL;

		$fail_text=">  FAILED CONSTRAINT:  Answer must contain a function '".$func_name."'.\n".PHP_EOL;

		
		if (in_array($func_name,Constraint::$function_names)){
		    //$fail_text.="\nIdentified ".$func_name." function.".PHP_EOL;
		    $found=True;
		    $imp_passed++;
		}
		else{
		    $fail_text.="\t    Identified the following function(s): \n\t    {\n\t\t".(Constraint::$function_names? '+ '.implode("\n\t\t+ ",Constraint::$function_names):"\t    <No functions detected.>")."\n\t    }\n";
		    $guess = Constraint::$function_names[count(Constraint::$function_names)-1];
		    $fail_text .= "\n\n\t    Proceeding assuming last known function: '".$guess."()' was intended to be '".$func_name."()'...\n".PHP_EOL.PHP_EOL;


		    $replacements = preg_replace("/[[:<:]]".$guess."[[:>:]]/",$func_name, $question['answer_text'], -1,$num_occurances);
		    
		    $question['answer_text']= $num_occurances ? $replacements : $question['answer_text'];
		    $answer_text_lines = explode('\n',$question['answer_text']);
		    $fail_text .= "  \t[-". (string)$c_value . " points off question total.]\n" . PHP_EOL;

		    //echo $question['answer_text'].PHP_EOL;
		    file_put_contents($file_path, $question['answer_text']);
		    //echo file_get_contents($file_path).PHP_EOL;
		}
		
		break;

	    default:
		die('UNKNOWN CONSTRAINT: '.$constraint.PHP_EOL);
	}

	if ($found){ 	
	    //echo $pass_text.PHP_EOL;	
	    array_push($comments['passed'],$pass_text);
	}else
	    array_push($comments['failed'],$fail_text);

    }
    $percent_earned = ($imp_passed / $num_implicit * $implicit_effect + $exp_passed / $num_explicit * $explicit_effect ) * $constraints_weight_percentage;
    $imp_failed = $num_implicit - $imp_passed;
    $exp_failed = $num_explicit - $exp_passed;
    $corrections = $imp_leftover * ($imp_passed - $imp_failed) + $exp_leftover * ($exp_passed - $exp_failed);

    //echo "estimate off by " . $corrections . PHP_EOL;

    //print_r($comments ['passed']); 
    $comments_str = "--------------------------------------\n\n"
	.	(
			count($comments['passed']) ?
				 //"+   PASSED:\n"
				//"    - - - - - -".PHP_EOL
				implode("\n", $comments['passed']) : ""
		)

	.	PHP_EOL

	.	(
			count($comments['failed']) ?
			(
				(
					count($comments['passed']) ? 
						"    ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~ ~".PHP_EOL
						.PHP_EOL : PHP_EOL
				)
			)

			//."-   FAILED:\n"
			//."    - - - - - -".PHP_EOL
			.implode("\n", $comments['failed']) : ""
		)
	.	    "--------------------------------------"
    ; 

    return ['percent_earned'=>$percent_earned, 'comments'=>$comments_str, 'rounding_adjustment'=>$corrections];
}


function grade($question, $answer, $cweightp=NULL, $qweightp=NULL){
    //echo "Made it to grade...".PHP_EOL;
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
    $qweight = sscanf($answer['question_max_score'], '%D')[0];

    exec('whoami', $output);
    $dirname=uniqid('cjm68-via-'.$output[0].'-CS490-tmp_');
    $tmp_dir=sys_get_temp_dir();
    $local_path = $tmp_dir.'/'.$dirname;
    $filename = 'code.py';
    mkdir($local_path, 0775);

    if (file_put_contents($local_path.'/'.$filename, $question['answer_text'])===False)
        die("Couldn't write to test file.");
    $testcases=explode("||", $answer['question_inputs']);

   // var_dump($answer);
    $constraint_analysis = check_constraints($question, $answer, $cweightp, $qweight, $local_path.'/'.$filename);

    //var_dump($constraint_analysis);

    $cpercent_int = $constraint_analysis['percent_earned'];
    $rounding_adjustment =  $constraint_analysis['rounding_adjustment'];
    $max_points_earnable =  $qweight*$qweightp/100;

    //echo $max_points_earnable.PHP_EOL;

    $output_analysis=runpycode($answer['question_fxn_name'], $local_path, $filename, "python3", $max_points_earnable, $rounding_adjustment, false, $testcases);

    #echo "Each testcase worth: " . (1/substr_count($output_analysis['results'], 'True')*$qweightp) . " points\n";

    #echo ">>Rounding Adjustments: ". $output_analysis['rounding_adjustment'] + $rounding_adjustment . PHP_EOL;

    $qpercent_int = substr_count($output_analysis['results'], 'True') / (substr_count($answer['question_inputs'], '||') + 1) * $qweightp;
    #echo $qpercent_int.PHP_EOL; 
    //echo "Question worth " . (string)$qweight . " points.\n" . PHP_EOL;

    $percent_earned=($qpercent_int + $cpercent_int)/100;
    $grade=(int)($qweight * $percent_earned  + .5);     // <== +.5 & truncate to answer for basic integer rounding
    
    $comments = "\n===========================\nAnalysis of Question " . $question['question_id'] . PHP_EOL . PHP_EOL . $constraint_analysis['comments'] . PHP_EOL . PHP_EOL . $output_analysis['comments'] . "Total Points Earned on Question ".$question['question_id'].":  ".$grade." of ".$qweight." points.".PHP_EOL.PHP_EOL;
    
    //echo $comments. PHP_EOL;
    return  ['grade'=>$grade, 'comments'=>$comments];
}

function update_with_grade($act_args){
//echo "made it to uwg".PHP_EOL;

    $num_questions = count($act_args['questions']);
    foreach(range(0, $num_questions - 1) as $i){
	//echo "getting anskey from be...";
        $answerkey = request_from_backend($GLOBALS['teacher_token'], Request::GET_QUESTION_GRADING_INFO,NULL, $act_args['questions'][$i], ['test_id'=>$act_args['test_id']]);
        //echo "recieved answerkey:".PHP_EOL;
	//var_dump($answerkey);
	//echo " from backend.".PHP_EOL;
	//echo "gottit\n";

	$constraint_worth_percentage_int = NULL;
	$testcases_worth_percentage_int = NULL;

	if ($answerkey === 0){
 	    die('Couldnt obtain evaluation from Backend.');
	}


	if ($answerkey['question_constraint']=='none'){
	    $constraint_worth_percentage_int = 20;
	    $testcases_worth_percentage_int = 100 - $constraint_worth_percentage_int;
	}
	else{
	    $constraint_worth_percentage_int = 80;
	    $testcases_worth_percentage_int = 100 - $constraint_worth_percentage_int;
	}

	//print_r($answerkey);
        //echo "finna grade\n";	
        $evaluation=grade($act_args['questions'][$i], $answerkey, $constraint_worth_percentage_int, $testcases_worth_percentage_int);
	//var_dump($evaluation);


        $act_args['questions'][$i]['grade'] = $evaluation['grade'];
        $act_args['questions'][$i]['comment'] = $evaluation['comments'];
    }
    error_log("*****************************".PHP_EOL.PHP_EOL.print_r($act_args,true).PHP_EOL.PHP_EOL,3, 'results.txt');
    return $act_args;
}

function request_from_backend($requester, $action, $be_url=NULL, ...$act_argv){
    // Set default URL to beta request script
    $be_url = $be_url? $be_url: "https://web.njit.edu/~btc5/cs490/request.php";

//echo "made it to rfbe".PHP_EOL;

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
    //echo "send/receive complete.\n";
    $auth_res = json_decode($result_backend, 1);
    //echo "Received {".$result_backend."} from backend".PHP_EOL;
    error_log('BE returned: '.PHP_EOL.print_r($result_backend, true).PHP_EOL,3,'./runlog');
    
    curl_close($c_backend);
    return $auth_res;
}

function forward_to_frontend($requester, $source=NULL, ...$act_argv){

    //echo "made it to f2fe".PHP_EOL;

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
	    //die('Testing Kill');
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

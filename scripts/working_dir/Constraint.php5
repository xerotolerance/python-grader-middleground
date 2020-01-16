<?php
/*
 * Created by PhpStorm.
 * User: xero
 * Date: 11/30/18
 * Time: 4:43 AM
 */

abstract class Constraint
{

############
# Important:        
#		 ***DO NOT ALTER THIS ORDER;***
# 	
#	This arrangement of constraint enums represents the order 
#	in which the system will check & attempt to correct users'  
#       submitted code during grading.
#
#	- Constraints are marked either Implicit ( < 0) or Explict ( > 0 ). 4
#
#	-Code 0, itself is reserved for the token "No Constraint",
#          a dummy value used to tell the program not to check anything 
#        other than the implicit constraints. 
#.        If an unrecognized constraint is specified, code 0 is called in it's place.
#
#
#	The code assumes that Implicit constraints codes are alway negative
#	 and AUTOMATICALLY ADDS all implicit constraints defined in Constraint::$enums
#	to the list of things to check & grade.
#
#      Priority is assigned from least (checked First) to greatest (checked last); 
#	Thus, the system checks for def keyword (-5) before checking for matching parenthesis (-1)
#	and checks for the existance of a While loop (1) before the usage of a For loop (2).
#
##########


    //Implicit Constraints (Intended to always be checked)
    const DEF_STMT = -5;#handled
    const FUNC_NAME_MATCH = -4; #handled
    const FUNC_PARENS = -3; #handle
    const ARG_MATCH = -2; //<-------  Needs completition
    const COLON_STMT = -1; #handled
    
    //ERROR TOKENS
    const NO_CONSTRT = 0;#handled

    //Explicit Constraints (Checked only when explicitly requested)
    const WHILE_LOOP = 1;#handled
    const FOR_LOOP = 2;#handled
    const RECURSION = 3;#handled

    
    const REGEX = [
	'WHILE_REGEX'=>'^[[:blank:]]*while[[:blank:]]*((\([^:[:space:]]+\))|([^: ][^:]+))[[:blank:]]*:[[:blank:]]*(#*)*',
	'FOR_REGEX'=>'^[[:blank:]]*for[[:blank:]]+[_A-Za-z][A-Za-z0-9_]*[[:blank:]]+in[[:blank:]]*(\([[:blank:]]*([A-Za-z_][A-Za-z0-9_]*(\(.*\))|\'.*\'|\".*\"|\[(.*,?)+\])[[:blank:]]*\)|([A-Za-z_][A-Za-z0-9_]*(\(.*\))?|\'.*\'|\".*\"|\[(.*,?)+\]))[[:blank:]]*:[[:blank:]]*(#.*)?',
	'DEF_REGEX'=>[
	    'STMT'=>'/(?:^[[:blank:]]*)def[[:space:]]+[A-za-z_][\w]*[[:space:]]*(\([^:;\)]*\))[[:space:]]*:[[:space:]]*(?:(?:#|\/\/).*)?$/',
	    'EXPR'=>'/^(?>[[:blank:]]*[[:<:]][A-Za-z_][[:word:]]*[[:>:]][[:space:]]*((\(|[^\n:]*\)):?|:$))/',
	    'KEYWORD'=>'/([[:<:]]def[[:>:]])/m',
	],
	'COLON_REGEX'=>'([[:blank]]*|^)(def|if|for|while)[[:blank:]]([^:]+|)$',
	'FUNC_NAME_REGEX'=>'^[[:blank:]]*def[[:blank:]]+([A-Za-z_][\w]*)'
	
    ];

    public static $enums = array(
	self::WHILE_LOOP => "while",
	self::FOR_LOOP => "for",
	self::RECURSION => "recursion",
	self::DEF_STMT => "def",
	self::COLON_STMT => "colon",
	self::ARG_MATCH => "args",
	self::FUNC_PARENS => "func parens",
	self::FUNC_NAME_MATCH => "func name",
	self::NO_CONSTRT => "none"
    );

    public static $implicit_constraints = array();
    public static function get_implicit_constraints(){
	krsort(self::$enums);
	//print_r(self::$enums);
	self::$implicit_constraints = array_slice(self::$enums, key(self::$enums)+1, NULL, True);
	//print_r(self::$implicit_constraints);
	ksort(self::$implicit_constraints);
	return array_values(self::$implicit_constraints);
    }


    public static $remap;
    public static function reverse_map_enums($enums){
	foreach ($enums as $a => $b)
	    self::$remap[$b] = $a;
	return self::$remap;
    }

    public static $lookup = array( 
	#Arrays
	self::RECURSION => [],
	self::FUNC_PARENS => [],
	self::DEF_STMT => self::REGEX['DEF_REGEX'],  # *note refers to an array of pre-prepared regex strings

	#Strings
	self::WHILE_LOOP => "/".self::REGEX['WHILE_REGEX']."/",
	self::FOR_LOOP =>"/".self::REGEX['FOR_REGEX']."/",
	self::COLON_STMT => "/".self::REGEX['COLON_REGEX']."/",
	self::FUNC_NAME_MATCH => "/".self::REGEX['FUNC_NAME_REGEX']."/m",
    );

   
    private static $recursive_funcs = array();
    public static function mark_funcs_recursive(...$func_names){
	foreach ($func_names as $func_name)
	    self::$recursive_funcs[$func_name]="/([^[:word:]#]|^)".$func_name."([[:space:]]*\(.*\))/";
	self::$lookup[self::RECURSION]=array_merge(self::$lookup[self::RECURSION], self::$recursive_funcs);
	return self::$recursive_funcs;
    }

    private static $functions = array();
    public static function check_func_parens(...$func_names){
	foreach($func_names as $func_name){
	    //echo $func_name.PHP_EOL;
	    self::$functions[$func_name]=['left_paren'=>[],'right_paren'=>[]];
	    //var_dump(self::$functions);
	    self::$functions[$func_name]['left_paren']="/([^\w]|^)".$func_name."([[:blank:]]+|[^[:word:]\(])[^\(]/";
	    self::$functions[$func_name]['right_paren']="/([^\w]|^)".$func_name."[[:blank:]]*\([^\)]+$/";
	    //vARG_MATCHar_dump(self::$functions);
	}
	self::$lookup[self::FUNC_PARENS]=array_merge(self::$lookup[self::FUNC_PARENS], self::$functions);
	return self::$functions;
    }
    
    public static $function_names = [];
    public static function identify_functions($answer_text,$search=NULL){
	//echo "Skimming Function names from following text:";
	//echo "\nUsing pcre pattern ".self::$lookup[self::FUNC_NAME_MATCH]." ...".PHP_EOL;
	//echo $answer_text . PHP_EOL;

	

	preg_match_all(self::$lookup[self::FUNC_NAME_MATCH],$answer_text,$matches); 
	//print_r($matches);
	self::$function_names = $matches[1];
	//print_r(self::$function_names).PHP_EOL;
	return self::$function_names;
    }

    public static $func_args = [];
    public static function get_func_args($answer_text){
	$func_regex = 
'/(?(DEFINE)(?<id>[A-Za-z_]\w*)(?<arg>[[:blank:]]*(\g<id>([[:blank:]]*=[[:blank:]]*((\d)|(\g<id>))[[:blank:]]*)?)|\d[[:blank:]]*)(?<arg_list>[[:blank:]]*((\g<arg>)(?:[[:blank:]]*,[[:blank:]]*(\g<arg>))*)?[[:blank:]]*)(?<parameters>\(\g<arg_list>\)))\g<id>[[:blank:]]*(\g<parameters>)/';
	/*
	echo "Known functions:\n".print_r(self::$function_names,true).PHP_EOL;
	echo "Skimming Arg blocks from following text:";
	echo "\nUsing pcre pattern ".$func_regex." ...".PHP_EOL;
	echo $answer_text . PHP_EOL;
	*/

	preg_match_all($func_regex,$answer_text,$matches,PREG_SET_ORDER);
	
	foreach (range (0,count($matches)-1) as $i){
	    #$removed=array_splice($matches[$i],1,-(substr_count($matches[$i][0],",")+1));
	    while ($key=(array_search("",$matches[$i],True)))
		unset($matches[$i][$key]);
	   	    #print_r($matches[$i]);
	    #echo "Removed items:\n". print_r($removed,true).PHP_EOL;
	    $matches[$i]=array_values($matches[$i]);
	}
        #print_r($matches);
	#print_r(self::$function_names);
	self::identify_functions($answer_text);
	self::$func_args=array_combine(self::$function_names,$matches);
	//print_r(self::$func_args);
	return self::$func_args;
    }
}

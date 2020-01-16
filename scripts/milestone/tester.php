<?php
/**
 * Created by PhpStorm.
 * User: xero
 * Date: 10/17/18
 * Time: 10:44 AM
 */

include_once ("beta.php");

$act_argv = array(["question_id"=>"15"]);
$addi_args = array_values($act_argv);
//$result = request_from_backend($GLOBALS['teacher_token'], Request::GET_QUESTION_GRADING_INFO, NULL, ...$addi_args);

##TEST FOR GET_QUESTION_INFO
//$sample_POST = ["request"=>json_encode(array("sessionid"=>$GLOBALS['teacher_token'],"request"=>"get_question_grading_info","question_id"=>"15"))];
//$result = forward_to_frontend($GLOBALS['teacher_token'], $sample_POST);

##TEST FOR GET_TESTS
//$sample_POST = ["request"=>json_encode(array("sessionid"=>$GLOBALS['teacher_token'],"request"=>"get_tests"))];
//$result = forward_to_frontend($GLOBALS['teacher_token'], $sample_POST);
/*
##TEST submit_graded_test
$request_body = array(
    "sessionid" => $GLOBALS['teacher_token'],
    "request" => "submit_graded_test",
    "test_id" => "92",
    "student_token" => "fd30ed66bdf9de9168a67d5116a063e5403da045",
    "questions" => [

        [
        "question_id"=>"15",
        "answer_text"=>

"def add(a,b):
    return a + b
",
        ],

        [
            "question_id"=>"16",
            "answer_text"=>"blaaa1",
        ],

        [
            "question_id"=>"17",
            "answer_text"=>"blaaa1",
        ],

	]
);

$sample_POST = ["request"=>json_encode($request_body)];
$result = forward_to_frontend($GLOBALS['teacher_token'], $sample_POST);
*/

/*
$request2 = array(
    "request"=>"submit_graded_test",
    "sessionid"=>"5b40714b4459529f8f60f899e71768d873377363",
    "test_id"=>"92",
    "student_token"=>"5b40714b4459529f8f60f899e71768d873377363",
    "questions"=>[
        array(
            "question_id"=>"15",
            "answer_text"=>
"def add(a,b):\n\treturn a+b"
        ),
        array(
            "question_id"=>"16",
            "answer_text"=>
"def fourA():\n\treturn(4*\"a\")"
        ),
        array(
            "question_id"=>"17",
            "answer_text"=>
"def subtract(a,b):\n\treturn b-a"
        )
    ]
);
*/

$request3 = array(
    "request"=>"submit_graded_test",
    "sessionid"=>"5b40714b4459529f8f60f899e71768d873377363",
    "test_id"=>"103",
    "student_token"=>"5b40714b4459529f8f60f899e71768d873377363",
    "questions"=>[
        array(
            "question_id"=>"28",
            "answer_text"=>
                "def operation(op,a,b):\n\tif(op=='+'):\n\t\treturn a+b\n\telif(op=='-'):\n\t\treturn a-b\n\telif(op=='*'):\n\t\treturn a*b \n\telif(op=='/'):\n\t\treturn a/b \n\telse:\n\t\treturn \"error\"\n\t\t"
        )
    ]
);

$request4 = '{"questions":[{"question_id":"41","answer_text":"def whileTest(n):\n\tg=\'\'\n\twhile(n>0):\n\t\tg+=\'a\'\n\t\tn-=1\n\treturn g"},{"question_id":"47","answer_text":"def whileTest2(n):\n\tprint(n*\'a\')"}],"request":"submit_graded_test","test_id":"112","sessionid":"ec4daa568589db9919775145f8cb36a2b6ae144f","student_token":"ec4daa568589db9919775145f8cb36a2b6ae144f"}';




/*
echo json_encode($request3);
die();
*/

$sample_POST = ["request"=>$request4];//["request"=>json_encode($request3)];
$result = forward_to_frontend('ec4daa568589db9919775145f8cb36a2b6ae144f'/*'5b40714b4459529f8f60f899e71768d873377363'*//*$GLOBALS['teacher_token']*/, $sample_POST);

/*
$info = array('sessionid'=>$GLOBALS['teacher_token'], 'request'=>'get_test_questions_answered', 'test_id'=>'92');
$sample_POST = ["request" => json_encode($info)];
$result = forward_to_frontend($GLOBALS['teacher_token'], $sample_POST);
*/

//echo "<html><body>" .PHP_EOL;
echo "MiddleGround says:\n ";
var_dump($result);

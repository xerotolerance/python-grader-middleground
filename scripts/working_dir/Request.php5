<?php
/**
 * Created by PhpStorm.
 * User: xero
 * Date: 10/16/18
 * Time: 4:43 AM
 */

abstract class Request
{
    //no additional args
    const GET_CLASSES = 0;
    const GET_OWNED_QUESTIONS = 1;
    const GET_TESTS = 2;
    //REQUIRE ARGUMENTS
    const GET_TEST_QUESTIONS = 3;
    const INSERT_QUESTION = 4;
    const GET_TEST_QUESTIONS_ANSWERED = 5;
    const CREATE_TEST = 6;
    const MARK_TEST_GRADED = 7;
    const SUBMIT_GRADED_TEST = 8;
    const GET_QUESTION_GRADING_INFO = 9;

    public static $enums = array(
        //no additional args
        self::GET_CLASSES => "get_classes",
        self::GET_OWNED_QUESTIONS => "get_owned_questions",
        self::GET_TESTS => "get_tests",
        //REQUIRE ARGUMENTS
        self::GET_TEST_QUESTIONS => "get_test_questions",
        self::INSERT_QUESTION => "insert_question",
        self::GET_TEST_QUESTIONS_ANSWERED => "get_test_questions_answered",
        self::CREATE_TEST => "create_test",
        self::MARK_TEST_GRADED => "mark_test_graded",
        self::SUBMIT_GRADED_TEST => "submit_graded_test",
        self::GET_QUESTION_GRADING_INFO => "get_question_grading_info"
    );

    public static $remap;
    public static function reverse_map_enum($enums){
        foreach ($enums as $a => $b)
            self::$remap[$b] = $a;
        return self::$remap;
    }





}
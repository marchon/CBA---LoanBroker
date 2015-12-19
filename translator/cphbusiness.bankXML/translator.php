<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('abstract_translator.php');

class Translator extends AbstractTranslator {

    protected function translate_request($request){
        $request = json_decode($request, true);

        $duration_years = intval($request['duration'] / 12);
        $remaining_months = $request['duration'] % 12;

        $request_year = 1970 + $duration_years;
        $request_months = $remaining_months > 0 ? $remaining_months + 1 : 1;
        $request_months = strlen($request_months) == 1 ? '0'.$request_months : $request_months;

        $request_formatted = "{$request_year}-{$request_months}-01 01:00:00.0 CET";

        $domtree = new DOMDocument('1.0', 'UTF-8');

        $root = $domtree->createElement('LoanRequest');
        $root->appendChild($domtree->createElement('ssn', implode('', explode('-', $request['ssn']))));
        $root->appendChild($domtree->createElement('creditScore', $request['credit_score']));
        $root->appendChild($domtree->createElement('loanAmount', floatval($request['amount'])));
        $root->appendChild($domtree->createElement('loanDuration', $request_formatted));

        $domtree->appendChild($root);
        var_dump($domtree->saveXML($domtree->documentElement));

        return $domtree->saveXML($domtree->documentElement);
    }
}

$translator_params = array(
    'host' => 'localhost',
    'port' => '5672',
    'username' => 'test',
    'password' => 'test',
    'routing_key' => '33333333',
);

$bank_params = array(
    'host' => 'datdb.cphbusiness.dk',
    'port' => '5672',
    'username' => 'student',
    'password' => 'cph',
    'exchange' => 'cphbusiness.bankXML',
    'reply_to' => 'vongrad_cphbusiness_xml'
);

$translator = new Translator($translator_params, $bank_params);
$translator->run();

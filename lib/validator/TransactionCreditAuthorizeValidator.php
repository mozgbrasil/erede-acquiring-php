<?php

namespace ERede\Acquiring\Validator;

use \Respect\Validation\Validator as v;
use \ERede\Acquiring\TransactionStatus as s;

class TransactionCreditAuthorizeValidator {

  private $validationResponse;

  public function __construct() {
    $this->validationResponse         = new \sTdClass;
    $this->validationResponse->status = s::SUCCESS;
    $this->validationResponse->errors = array();
  }

  public function validate(array $parameters) {

    if($this->validateRequired($parameters, array("credit_card", "exp_year", "exp_month", "amount"))) {

      $this->validateCreditCard($parameters);
      $this->validateCVV($parameters);
      $this->validateExpDate($parameters);
      $this->validateAmount($parameters);
      $this->validateSoftDescriptor($parameters);

    }

    return $this->validationResponse;

  }

  private function validateCreditCard($parameters) {

    $fieldName = "credit_card";

    if(!v::creditCard()->validate($parameters[$fieldName])) {
      $this->validationResponse->status = s::VALIDATION_ERROR;
      $this->validationResponse->errors[$fieldName] = "is invalid";
      return false;
    }

    return true;

  }

  private function validateExpYear($parameters) {

    $fieldName = "exp_year";

    if(!v::int()->validate($parameters[$fieldName])) {
      $this->validationResponse->status = s::VALIDATION_ERROR;
      $this->validationResponse->errors[$fieldName] = "is not a valid year";
      return false;
    }

    $now  = new \DateTime();
    $year = substr($parameters[$fieldName], -2);

    if(!v::int()->min($now->format("y"), true)->validate($year)) {
      $this->validationResponse->status = s::VALIDATION_ERROR;
      $this->validationResponse->errors[$fieldName] = "is invalid";
      return false;
    }

    return true;

  }

  private function validateExpMonth($parameters) {
    $fieldName = "exp_month";
    return $this->assertIntBetween($fieldName, 1, 12, $parameters[$fieldName]);
  }

  private function validateExpDate($parameters) {

    if($this->validateExpMonth($parameters) && $this->validateExpYear($parameters)) {

      $now = new \DateTime();
      $now = $now->format("y") . $now->format("m");

      $date = substr($parameters["exp_year"], -2) . $parameters["exp_month"];

      if($date >= $now) {
        return true;
      } else {
        $this->validationResponse->status = s::VALIDATION_ERROR;
        $this->validationResponse->errors["exp_date"] = "is invalid";
        return false;
      }

    }

    return false;

  }

  private function validateCVV($parameters) {

    $fieldName = "cvv";

    if(array_key_exists($fieldName, $parameters)) {

      if(!v::numeric()->validate($parameters[$fieldName]) || 
        !v::length(3,3,true)->validate($parameters[$fieldName])) {
          $this->validationResponse->status = s::VALIDATION_ERROR;
          $this->validationResponse->errors[$fieldName] = "is required";
          return false;
      }

    }

    return true;

  }

  private function validateAmount($parameters) {

    $fieldName = "amount";

    if(!v::int()->min(50, true)->validate($parameters[$fieldName])) {
      $this->validationResponse->status = s::VALIDATION_ERROR;
      $this->validationResponse->errors[$fieldName] = "is invalid. Need to be higher than 50 cents";
      return false;
    }

    return true;

  }

  private function validateSoftDescriptor($parameters) {

    $fieldName = "soft_descriptor";

    if(array_key_exists($fieldName, $parameters)) {

      if(!v::length(1,13,true)->validate($parameters[$fieldName]) ||
          !v::alnum()->noWhitespace()->validate($parameters[$fieldName])) {
          $this->validationResponse->status = s::VALIDATION_ERROR;
          $this->validationResponse->errors[$fieldName] = "is invalid";
          return false;
      }

    }

    return true;

  }

  private function validateRequired($parameters, $fieldNames) {

    foreach($fieldNames as $fieldName) {

      if(!v::key($fieldName)->validate($parameters)) {
        $this->validationResponse->status = s::VALIDATION_ERROR;
        $this->validationResponse->errors[$fieldName] = "is required";
        return false;
      }

    }

    return true;

  }

  private function assertIntBetween($fieldName, $start, $end, $parameter) {

    if(!v::int()->between($start, $end)->validate($parameter)) {
      $this->setValidationResponse(s::VALIDATION_ERROR, $fieldName, "is invalid");
      return false;
    }

    return true;

  }

  private function setValidationResponse($status, $fieldName, $message) {
      $this->validationResponse->status = $status;
      $this->validationResponse->errors[$fieldName] = $message;
  }


}

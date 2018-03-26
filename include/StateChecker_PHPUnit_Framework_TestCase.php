<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace SuiteCRM;

use PHPUnit_Framework_TestCase;

/**
 * Description of StateChecker_PHPUnit_Framework_TestCase
 *
 * @author SalesAgility
 */
class StateChecker_PHPUnit_Framework_TestCase extends PHPUnit_Framework_TestCase {
    
    protected $useStateChecker = true;
    
    protected $useAssertationFailureOnError = true;
    
    /**
     *
     * @var StateChecker
     */
    protected $stateChecker;
    
    public function setUp() {
        parent::setUp();
        if($this->useStateChecker) {
            $this->stateChecker = new StateChecker();
        }
    }
    
    public function tearDown() {
        parent::tearDown();
        if($this->useStateChecker && $this->stateChecker) {
            try {
                $this->stateChecker->getStateHash();
            } catch (\SuiteCRM\StateCheckerException $e) {
                $message = 'Incorrect state hash: ' . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString() . "\n";
                if($this->useAssertationFailureOnError) {
                    $this->assertFalse(true, $message);
                } else {
                    echo $message;
                }
            }
        }
    }
    
}